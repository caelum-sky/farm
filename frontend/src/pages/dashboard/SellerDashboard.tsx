// frontend/src/pages/dashboard/SellerDashboard.tsx
import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { CalendarClock, Package, Pencil, Plus, Tractor, Trash2 } from 'lucide-react';

import ListingFormModal from '@/components/dashboard/ListingFormModal';
import EmptyState from '@/components/ui/EmptyState';
import ErrorNote from '@/components/ui/ErrorNote';
import { RowSkeleton } from '@/components/ui/Skeleton';
import { useAuth } from '@/context/AuthContext';
import { useToast } from '@/context/ToastContext';
import { api, peso, query } from '@/lib/api';
import type { Equipment, Paginated, Product, Rental } from '@/types';

type Tab = 'products' | 'equipment' | 'bookings';

export default function SellerDashboard() {
  const { profile } = useAuth();
  const { notify } = useToast();

  const [tab, setTab] = useState<Tab>('products');
  const [products, setProducts] = useState<Product[]>([]);
  const [equipment, setEquipment] = useState<Equipment[]>([]);
  const [bookings, setBookings] = useState<Rental[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [editor, setEditor] = useState<{
    kind: 'products' | 'equipment';
    listing: Product | Equipment | null;
  } | null>(null);

  const load = useCallback(async () => {
    if (!profile) return;
    setLoading(true);
    setError('');
    try {
      const [ownProducts, ownEquipment, incoming] = await Promise.all([
        api.get<Paginated<Product>>(`/products${query({ ownerId: profile.id, limit: 50 })}`),
        api.get<Paginated<Equipment>>(`/equipment${query({ ownerId: profile.id, limit: 50 })}`),
        api.get<Rental[]>('/rentals/incoming'),
      ]);
      setProducts(ownProducts.items);
      setEquipment(ownEquipment.items);
      setBookings(incoming);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Could not load your listings');
    } finally {
      setLoading(false);
    }
  }, [profile]);

  useEffect(() => {
    void load();
  }, [load]);

  const pendingBookings = useMemo(
    () => bookings.filter((booking) => booking.status === 'requested').length,
    [bookings]
  );

  const removeListing = async (kind: 'products' | 'equipment', item: Product | Equipment) => {
    try {
      await api.delete(`/${kind}/${item.id}`);
      notify(`${item.name} taken down`);
      await load();
    } catch (err) {
      notify(err instanceof Error ? err.message : 'Could not remove that listing', 'error');
    }
  };

  const setBookingStatus = async (booking: Rental, status: Rental['status']) => {
    try {
      await api.patch(`/rentals/${booking.id}/status`, { status });
      notify(`${booking.equipmentName} marked ${status}`);
      await load();
    } catch (err) {
      notify(err instanceof Error ? err.message : 'Could not update that booking', 'error');
    }
  };

  const tabs: Array<[Tab, string]> = [
    ['products', 'Listings'],
    ['equipment', 'Equipment'],
    ['bookings', pendingBookings ? `Requests (${pendingBookings})` : 'Requests'],
  ];

  return (
    <div className="shell section-y">
      <header className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 className="text-section text-canopy">{profile?.orgName || profile?.displayName}</h1>
          <p className="mt-2 max-w-xl text-soil/70">
            {profile?.role === 'cooperative'
              ? 'Manage supplies, the shared equipment pool, and member bookings.'
              : 'Manage what you sell and the machines you rent out.'}
          </p>
        </div>
        <Link to="/orders" className="btn-outline">
          View sales
        </Link>
      </header>

      <div className="mt-8 scroll-row">
        {tabs.map(([value, label]) => (
          <button
            key={value}
            type="button"
            onClick={() => setTab(value)}
            aria-pressed={tab === value}
            className={`pill ${tab === value ? 'pill-on' : 'pill-off'}`}
          >
            {label}
          </button>
        ))}
      </div>

      <ErrorNote message={error} />

      <div className="mt-8">
        {loading ? (
          <RowSkeleton />
        ) : tab === 'bookings' ? (
          bookings.length === 0 ? (
            <EmptyState
              title="No booking requests"
              hint="When a member books one of your machines, the request lands here for approval."
            />
          ) : (
            <ul className="space-y-3">
              {bookings.map((booking) => (
                <li key={booking.id} className="card p-5">
                  <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="min-w-0">
                      <h2 className="flex items-center gap-2 font-medium text-soil">
                        <CalendarClock size={17} className="shrink-0 text-bark" />
                        {booking.equipmentName}
                      </h2>
                      <p className="mt-1 text-sm text-soil/60">
                        {booking.days} day{booking.days === 1 ? '' : 's'} ·{' '}
                        {peso(booking.totalCost)} · {booking.status}
                      </p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                      {booking.status === 'requested' && (
                        <>
                          <button
                            type="button"
                            onClick={() => setBookingStatus(booking, 'approved')}
                            className="rounded-full bg-canopy px-4 py-2 text-sm text-husk transition hover:bg-leaf"
                          >
                            Approve
                          </button>
                          <button
                            type="button"
                            onClick={() => setBookingStatus(booking, 'cancelled')}
                            className="btn-quiet"
                          >
                            Decline
                          </button>
                        </>
                      )}
                      {booking.status === 'approved' && (
                        <button
                          type="button"
                          onClick={() => setBookingStatus(booking, 'returned')}
                          className="btn-quiet"
                        >
                          Mark returned
                        </button>
                      )}
                    </div>
                  </div>
                </li>
              ))}
            </ul>
          )
        ) : (
          <ListingSection
            kind={tab}
            items={tab === 'products' ? products : equipment}
            onAdd={() => setEditor({ kind: tab, listing: null })}
            onEdit={(listing) => setEditor({ kind: tab, listing })}
            onRemove={(listing) => removeListing(tab, listing)}
          />
        )}
      </div>

      {editor && (
        <ListingFormModal
          kind={editor.kind}
          listing={editor.listing}
          role={profile?.role}
          onClose={() => setEditor(null)}
          onSaved={load}
        />
      )}
    </div>
  );
}

interface ListingSectionProps {
  kind: 'products' | 'equipment';
  items: Array<Product | Equipment>;
  onAdd: () => void;
  onEdit: (listing: Product | Equipment) => void;
  onRemove: (listing: Product | Equipment) => void;
}

function ListingSection({ kind, items, onAdd, onEdit, onRemove }: ListingSectionProps) {
  const isProducts = kind === 'products';
  const Icon = isProducts ? Package : Tractor;

  return (
    <section>
      <div className="mb-5 flex justify-end">
        <button type="button" onClick={onAdd} className="btn-primary">
          <Plus size={17} />
          {isProducts ? 'Add a listing' : 'Add equipment'}
        </button>
      </div>

      {items.length === 0 ? (
        <EmptyState
          title={isProducts ? 'No listings yet' : 'No machines listed'}
          hint={
            isProducts
              ? 'Add your first harvest or supply — it shows up in the market straight away.'
              : 'List a tractor or tiller with a daily rate, and members can book it by date.'
          }
          action={
            <button type="button" onClick={onAdd} className="btn-primary">
              {isProducts ? 'Add a listing' : 'Add equipment'}
            </button>
          }
        />
      ) : (
        <ul className="space-y-3">
          {items.map((item) => {
            const product = isProducts ? (item as Product) : null;
            const machine = !isProducts ? (item as Equipment) : null;

            return (
              <li key={item.id} className="card p-4 sm:p-5">
                <div className="flex flex-wrap items-center gap-4">
                  <div className="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-xl bg-husk2">
                    {item.images[0] ? (
                      <img src={item.images[0]} alt="" className="h-full w-full object-cover" />
                    ) : (
                      <Icon size={19} className="text-canopy/60" />
                    )}
                  </div>

                  <div className="min-w-0 flex-1">
                    <h3 className="truncate font-medium text-soil">{item.name}</h3>
                    <p className="text-sm text-soil/60">
                      {peso(item.price)}
                      {product ? ` per ${product.unit} · ${product.stock} in stock` : ''}
                      {machine ? (machine.listingType === 'rent' ? ' per day' : ' sale price') : ''}
                      {' · '}
                      <span className={item.status === 'active' ? 'text-canopy' : 'text-bark'}>
                        {item.status}
                      </span>
                    </p>
                  </div>

                  <div className="flex gap-1">
                    <button
                      type="button"
                      onClick={() => onEdit(item)}
                      aria-label={`Edit ${item.name}`}
                      className="grid h-11 w-11 place-items-center rounded-full text-soil/60 transition hover:bg-soil/5 hover:text-soil"
                    >
                      <Pencil size={17} />
                    </button>
                    <button
                      type="button"
                      onClick={() => onRemove(item)}
                      aria-label={`Remove ${item.name}`}
                      className="grid h-11 w-11 place-items-center rounded-full text-soil/50 transition hover:bg-soil/5 hover:text-clay"
                    >
                      <Trash2 size={17} />
                    </button>
                  </div>
                </div>
              </li>
            );
          })}
        </ul>
      )}
    </section>
  );
}
