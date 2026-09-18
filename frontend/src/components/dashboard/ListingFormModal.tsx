// frontend/src/components/dashboard/ListingFormModal.tsx
import { useState, type FormEvent } from 'react';

import ErrorNote from '@/components/ui/ErrorNote';
import ImageUploader from '@/components/ui/ImageUploader';
import Modal from '@/components/ui/Modal';
import { useToast } from '@/context/ToastContext';
import { api, humanize } from '@/lib/api';
import type { Equipment, Product, Role } from '@/types';

type Kind = 'products' | 'equipment';

interface ListingFormModalProps {
  kind: Kind;
  /** Omit to create; pass a listing to edit it in place. */
  listing?: Product | Equipment | null;
  role?: Role;
  onClose: () => void;
  onSaved: () => Promise<void> | void;
}

const PRODUCE_SUBS = ['fruits', 'vegetables', 'rice-and-grain', 'dairy-and-eggs', 'preserves'];
const SUPPLY_SUBS = ['fertilizer', 'pesticide', 'seed', 'feed', 'tools'];
const EQUIPMENT_CATEGORIES = [
  'tractor',
  'tiller',
  'sprayer',
  'thresher',
  'harvester',
  'water pump',
  'other',
];

/**
 * One form for both creating and editing. Editing PATCHes only what changed,
 * which keeps an edit from silently resetting fields this form doesn't show.
 */
export default function ListingFormModal({
  kind,
  listing,
  role,
  onClose,
  onSaved,
}: ListingFormModalProps) {
  const { notify } = useToast();
  const isEdit = Boolean(listing);
  const product = kind === 'products' ? (listing as Product | undefined) : undefined;
  const equipment = kind === 'equipment' ? (listing as Equipment | undefined) : undefined;

  const [name, setName] = useState(listing?.name ?? '');
  const [description, setDescription] = useState(listing?.description ?? '');
  const [images, setImages] = useState<string[]>(listing?.images ?? []);
  const [price, setPrice] = useState(listing ? String(listing.price) : '');

  // product-only fields
  const [category, setCategory] = useState<'produce' | 'supply'>(
    product?.category ?? (role === 'cooperative' ? 'supply' : 'produce')
  );
  const [subcategory, setSubcategory] = useState(
    product?.subcategory ?? (role === 'cooperative' ? 'fertilizer' : 'vegetables')
  );
  const [unit, setUnit] = useState(product?.unit ?? 'kg');
  const [stock, setStock] = useState(product ? String(product.stock) : '');
  const [organic, setOrganic] = useState(product?.tags?.includes('organic') ?? false);
  const [justHarvested, setJustHarvested] = useState(
    product?.tags?.includes('just-harvested') ?? false
  );

  // equipment-only fields
  const [equipCategory, setEquipCategory] = useState(equipment?.category ?? 'tractor');
  const [listingType, setListingType] = useState<'rent' | 'sale'>(
    equipment?.listingType ?? 'rent'
  );

  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');

  const subs = category === 'produce' ? PRODUCE_SUBS : SUPPLY_SUBS;

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setBusy(true);
    setError('');

    if (!Number.isFinite(Number(price)) || Number(price) < 0) {
      setError('Enter a valid price of zero or more.');
      setBusy(false);
      return;
    }
    if (kind === 'products' && (!Number.isFinite(Number(stock)) || Number(stock) < 0)) {
      setError('Enter a valid stock quantity of zero or more.');
      setBusy(false);
      return;
    }

    try {
      const payload =
        kind === 'products'
          ? {
              name,
              description,
              category,
              subcategory,
              price: Number(price),
              unit,
              stock: Number(stock),
              images,
              tags: [
                ...(organic ? ['organic'] : []),
                ...(justHarvested ? ['just-harvested'] : []),
              ],
            }
          : {
              name,
              description,
              category: equipCategory,
              listingType,
              price: Number(price),
              images,
            };

      if (isEdit && listing) {
        await api.patch(`/${kind}/${listing.id}`, payload);
        notify(`${name} updated`);
      } else {
        await api.post(`/${kind}`, payload);
        notify(`${name} is live on the market`);
      }

      await onSaved();
      onClose();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Could not save the listing');
    } finally {
      setBusy(false);
    }
  };

  const title = isEdit
    ? `Edit ${listing?.name}`
    : kind === 'products'
      ? 'Add a listing'
      : 'Add equipment';

  return (
    <Modal title={title} onClose={onClose} size="lg">
      <form onSubmit={submit} className="space-y-5">
        {kind === 'products' ? (
          <div className="flex gap-2">
            {(['produce', 'supply'] as const).map((value) => (
              <button
                key={value}
                type="button"
                onClick={() => {
                  setCategory(value);
                  setSubcategory(value === 'produce' ? 'vegetables' : 'fertilizer');
                }}
                aria-pressed={category === value}
                className={`pill flex-1 justify-center capitalize ${
                  category === value ? 'pill-on' : 'pill-off'
                }`}
              >
                {value}
              </button>
            ))}
          </div>
        ) : (
          <div className="flex gap-2">
            {(['rent', 'sale'] as const).map((value) => (
              <button
                key={value}
                type="button"
                onClick={() => setListingType(value)}
                aria-pressed={listingType === value}
                className={`pill flex-1 justify-center ${
                  listingType === value ? 'pill-on' : 'pill-off'
                }`}
              >
                {value === 'rent' ? 'For rent' : 'For sale'}
              </button>
            ))}
          </div>
        )}

        <div>
          <label htmlFor="listing-name" className="mb-1.5 block text-sm text-soil/70">
            Name
          </label>
          <input
            id="listing-name"
            required
            minLength={2}
            value={name}
            onChange={(e) => setName(e.target.value)}
            className="field"
          />
        </div>

        {kind === 'products' ? (
          <>
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <label htmlFor="listing-sub" className="mb-1.5 block text-sm text-soil/70">
                  Category
                </label>
                <select
                  id="listing-sub"
                  value={subcategory}
                  onChange={(e) => setSubcategory(e.target.value)}
                  className="field"
                >
                  {subs.map((option) => (
                    <option key={option} value={option}>
                      {humanize(option)}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label htmlFor="listing-unit" className="mb-1.5 block text-sm text-soil/70">
                  Sold by
                </label>
                <input
                  id="listing-unit"
                  required
                  value={unit}
                  onChange={(e) => setUnit(e.target.value)}
                  placeholder="kg, sack, tray"
                  className="field"
                />
              </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <label htmlFor="listing-price" className="mb-1.5 block text-sm text-soil/70">
                  Price per {unit || 'unit'}
                </label>
                <input
                  id="listing-price"
                  type="number"
                  min="0"
                  step="0.01"
                  inputMode="decimal"
                  required
                  value={price}
                  onChange={(e) => setPrice(e.target.value)}
                  className="field"
                />
              </div>

              <div>
                <label htmlFor="listing-stock" className="mb-1.5 block text-sm text-soil/70">
                  Stock on hand
                </label>
                <input
                  id="listing-stock"
                  type="number"
                  min="0"
                  inputMode="numeric"
                  required
                  value={stock}
                  onChange={(e) => setStock(e.target.value)}
                  className="field"
                />
              </div>
            </div>
          </>
        ) : (
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label htmlFor="equip-category" className="mb-1.5 block text-sm text-soil/70">
                Type
              </label>
              <select
                id="equip-category"
                value={equipCategory}
                onChange={(e) => setEquipCategory(e.target.value)}
                className="field"
              >
                {EQUIPMENT_CATEGORIES.map((option) => (
                  <option key={option} value={option}>
                    {humanize(option)}
                  </option>
                ))}
              </select>
            </div>

            <div>
              <label htmlFor="equip-price" className="mb-1.5 block text-sm text-soil/70">
                {listingType === 'rent' ? 'Daily rate' : 'Sale price'}
              </label>
              <input
                id="equip-price"
                type="number"
                min="0"
                step="0.01"
                inputMode="decimal"
                required
                value={price}
                onChange={(e) => setPrice(e.target.value)}
                className="field"
              />
            </div>
          </div>
        )}

        <div>
          <label htmlFor="listing-desc" className="mb-1.5 block text-sm text-soil/70">
            Description
          </label>
          <textarea
            id="listing-desc"
            rows={3}
            value={description}
            onChange={(e) => setDescription(e.target.value)}
            placeholder={
              kind === 'products'
                ? 'Variety, when it was picked, how it travels.'
                : 'Condition, horsepower, whether an operator comes with it.'
            }
            className="field resize-none"
          />
        </div>

        <div>
          <span className="mb-1.5 block text-sm text-soil/70">Photos</span>
          <ImageUploader
            kind={kind}
            listingId={listing?.id}
            value={images}
            onChange={setImages}
          />
        </div>

        {kind === 'products' && category === 'produce' && (
          <div className="flex flex-wrap gap-5 text-sm text-soil/75">
            <label className="flex items-center gap-2">
              <input
                type="checkbox"
                checked={organic}
                onChange={(e) => setOrganic(e.target.checked)}
                className="h-4 w-4"
              />
              Organic
            </label>
            <label className="flex items-center gap-2">
              <input
                type="checkbox"
                checked={justHarvested}
                onChange={(e) => setJustHarvested(e.target.checked)}
                className="h-4 w-4"
              />
              Just harvested
            </label>
          </div>
        )}

        <ErrorNote message={error} />

        <div className="sticky bottom-0 -mx-5 mt-2 flex flex-wrap gap-3 border-t border-soil/10 bg-husk px-5 pb-1 pt-4 sm:-mx-7 sm:px-7">
          <button type="submit" disabled={busy} className="btn-primary">
            {busy ? 'Saving…' : isEdit ? 'Save changes' : 'Publish listing'}
          </button>
          <button type="button" onClick={onClose} className="btn-quiet">
            Cancel
          </button>
        </div>
      </form>
    </Modal>
  );
}
