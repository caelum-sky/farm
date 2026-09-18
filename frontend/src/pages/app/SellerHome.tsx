// frontend/src/pages/app/SellerHome.tsx
import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { CalendarClock, Package, Plus, ShoppingBag } from 'lucide-react';

import ErrorNote from '@/components/ui/ErrorNote';
import { RowSkeleton } from '@/components/ui/Skeleton';
import { useAuth } from '@/context/AuthContext';
import { api, peso, query } from '@/lib/api';
import type { Equipment, Paginated, Product, Rental } from '@/types';

interface SellerHomeProps {
  audience: 'farmer' | 'cooperative';
}

export default function SellerHome({ audience }: SellerHomeProps) {
  const { profile } = useAuth();
  const isCoop = audience === 'cooperative';

  const [products, setProducts] = useState<Product[]>([]);
  const [equipment, setEquipment] = useState<Equipment[]>([]);
  const [bookings, setBookings] = useState<Rental[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    if (!profile) return;
    let cancelled = false;
    (async () => {
      try {
        const [p, e, b] = await Promise.all([
          api.get<Paginated<Product>>(`/products${query({ ownerId: profile.id, limit: 50 })}`),
          api.get<Paginated<Equipment>>(`/equipment${query({ ownerId: profile.id, limit: 50 })}`),
          api.get<Rental[]>('/rentals/incoming'),
        ]);
        if (!cancelled) {
          setProducts(p.items);
          setEquipment(e.items);
          setBookings(b);
        }
      } catch (err) {
        // A failed fetch and having nothing listed yet both land on zero —
        // without this, a real outage reads as "your listings are gone,"
        // which is a much worse thing for a farmer to silently believe.
        setError(err instanceof Error ? err.message : 'Could not load your dashboard');
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [profile]);

  const activeListings = products.filter((p) => p.status === 'active').length;
  const activeEquipment = equipment.filter((e) => e.status === 'active').length;
  const pendingRequests = bookings.filter((b) => b.status === 'requested').length;

  return (
    <div>
      <p className="text-sm font-medium uppercase tracking-[0.16em] text-canopy/70">
        {isCoop ? 'Cooperative workspace' : 'Farm workspace'}
      </p>
      <h1 className="mt-2 text-section text-canopy">{profile?.orgName || profile?.displayName}</h1>
      <p className="mt-2 max-w-xl text-soil/70">
        {isCoop
          ? 'Manage shared inventory, the equipment pool, and member booking requests.'
          : 'Plan your harvest, manage listings, and respond to buyer requests.'}
      </p>

      <ErrorNote message={error} />

      {loading ? (
        <div className="mt-8">
          <RowSkeleton count={3} />
        </div>
      ) : (
        <div className="mt-8 grid gap-4 sm:grid-cols-3">
          <StatCard icon={Package} label={isCoop ? 'Active inventory' : 'Active harvest listings'} value={activeListings} />
          <StatCard icon={Package} label={isCoop ? 'Pool equipment' : 'Equipment listed'} value={activeEquipment} />
          <StatCard
            icon={CalendarClock}
            label="Booking requests"
            value={pendingRequests}
            urgent={pendingRequests > 0}
          />
        </div>
      )}

      <div className="mt-10 grid gap-4 sm:grid-cols-2">
        <Link
          to="/app/listings"
          className="card flex items-center justify-between p-6 transition duration-300 ease-grow hover:-translate-y-0.5 hover:shadow-lift"
        >
          <div>
            <h2 className="font-display text-xl text-soil">{isCoop ? 'Update inventory' : 'Post a harvest'}</h2>
            <p className="mt-1 text-sm text-soil/60">
              {isCoop ? 'Post supplies or shared equipment' : 'Post produce or equipment to rent out'}
            </p>
          </div>
          <Plus size={22} className="text-canopy" />
        </Link>
        <Link
          to="/app/orders"
          className="card flex items-center justify-between p-6 transition duration-300 ease-grow hover:-translate-y-0.5 hover:shadow-lift"
        >
          <div>
            <h2 className="font-display text-xl text-soil">View sales</h2>
            <p className="mt-1 text-sm text-soil/60">Orders placed against your listings</p>
          </div>
          <ShoppingBag size={22} className="text-bark" />
        </Link>
      </div>

      {pendingRequests > 0 && (
        <section className="mt-10">
          <div className="flex items-center justify-between">
            <h2 className="font-display text-xl text-soil">Waiting on you</h2>
            <Link to="/app/listings" className="link-underline text-sm font-medium">
              Review requests
            </Link>
          </div>
          <ul className="mt-4 space-y-3">
            {bookings
              .filter((b) => b.status === 'requested')
              .slice(0, 3)
              .map((booking) => (
                <li key={booking.id} className="card flex items-center justify-between p-4">
                  <span className="text-sm text-soil/70">{booking.equipmentName}</span>
                  <span className="font-medium text-soil">
                    {booking.days} day{booking.days === 1 ? '' : 's'} · {peso(booking.totalCost)}
                  </span>
                </li>
              ))}
          </ul>
        </section>
      )}
    </div>
  );
}

function StatCard({
  icon: Icon,
  label,
  value,
  urgent,
}: {
  icon: typeof Package;
  label: string;
  value: string | number;
  urgent?: boolean;
}) {
  return (
    <div className={`card p-6 ${urgent ? 'border-harvest/50 bg-harvest/10' : ''}`}>
      <Icon size={20} className="text-canopy" />
      <p className="mt-3 text-sm text-soil/60">{label}</p>
      <p className="mt-1 font-display text-2xl text-soil">{value}</p>
    </div>
  );
}
