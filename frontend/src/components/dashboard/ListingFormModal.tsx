// frontend/src/components/dashboard/ListingFormModal.tsx
import { useState, type FormEvent } from 'react';

import ErrorNote from '@/components/ui/ErrorNote';
import { FieldInput, ToggleGroup } from '@/components/ui';
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
          <ToggleGroup
            label="Category"
            options={[
              { value: 'produce', label: 'Produce' },
              { value: 'supply', label: 'Supply' },
            ]}
            value={category}
            onChange={(value) => {
              setCategory(value);
              setSubcategory(value === 'produce' ? 'vegetables' : 'fertilizer');
            }}
          />
        ) : (
          <ToggleGroup
            label="Listing Type"
            options={[
              { value: 'rent', label: 'For rent' },
              { value: 'sale', label: 'For sale' },
            ]}
            value={listingType}
            onChange={setListingType}
          />
        )}

        <FieldInput
          label="Name"
          id="listing-name"
          required
          minLength={2}
          value={name}
          onChange={(e) => setName(e.target.value)}
        />

        {kind === 'products' ? (
          <>
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
              <FieldInput
                label="Category"
                id="listing-sub"
                asChild
                value={subcategory}
                onChange={(e) => setSubcategory(e.target.value)}
              >
                <select>
                  {subs.map((option) => (
                    <option key={option} value={option}>
                      {humanize(option)}
                    </option>
                  ))}
                </select>
              </FieldInput>
            </div>

              <FieldInput
                  label="Sold by"
                  id="listing-unit"
                  required
                  value={unit}
                  onChange={(e) => setUnit(e.target.value)}
                  placeholder="kg, sack, tray"
                />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <FieldInput
                  label={`Price per ${unit || 'unit'}`}
                  id="listing-price"
                  type="number"
                  min="0"
                  step="0.01"
                  inputMode="decimal"
                  required
                  value={price}
                  onChange={(e) => setPrice(e.target.value)}
                />

              <FieldInput
                  label="Stock on hand"
                  id="listing-stock"
                  type="number"
                  min="0"
                  inputMode="numeric"
                  required
                  value={stock}
                  onChange={(e) => setStock(e.target.value)}
                />
            </div>
          </>
        ) : (
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <FieldInput
                label="Type"
                id="equip-category"
                asChild
                value={equipCategory}
                onChange={(e) => setEquipCategory(e.target.value)}
              >
                <select>
                  {EQUIPMENT_CATEGORIES.map((option) => (
                    <option key={option} value={option}>
                      {humanize(option)}
                    </option>
                  ))}
                </select>
              </FieldInput>
            </div>

            <div>
              <FieldInput
                label={listingType === 'rent' ? 'Daily rate' : 'Sale price'}
                id="equip-price"
                type="number"
                min="0"
                step="0.01"
                inputMode="decimal"
                required
                value={price}
                onChange={(e) => setPrice(e.target.value)}
              />
            </div>
          </div>
        )}

        <FieldInput
          label="Description"
          id="listing-desc"
          asChild
          rows={3}
          value={description}
          onChange={(e) => setDescription(e.target.value)}
          placeholder={
            kind === 'products'
              ? 'Variety, when it was picked, how it travels.'
              : 'Condition, horsepower, whether an operator comes with it.'
          }
        >
          <textarea className="resize-none" />
        </FieldInput>

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

        <div className="flex flex-wrap gap-3 pt-1">
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
