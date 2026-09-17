// frontend/src/pages/app/BuyerHome.tsx
import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ShoppingBag, Sprout, Tractor } from 'lucide-react';

import ErrorNote from '@/components/ui/ErrorNote';
import { RowSkeleton } from '@/components/ui/Skeleton';
import { useAuth } from '@/context/AuthContext';
import { api, peso } from '@/lib/api';
import type { Order, Rental } from '@/types';

export default function BuyerHome() {
  const { profile } = useAuth();
  const [orders, setOrders] = useState<Order[]>([]);
  const [rentals, setRentals] = useState<Rental[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    let cancelled = false;
    (async () => {
      try {
        const [o, r] = await Promise.all([
          api.get<Order[]>('/orders/mine'),
          api.get<Rental[]>('/rentals/mine'),
        ]);
        if (!cancelled) {
          setOrders(o);
          setRentals(r);
        }
      } catch (err) {
        // A failed fetch and a genuinely new account both start at zero —
        // without this, they're indistinguishable and a real outage reads
        // as "you have nothing," which is a worse failure than an error banner.
        setError(err instanceof Error ? err.message : 'Could not load your account summary');
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, []);

  const openOrders = orders.filter((o) => o.status === 'placed' || o.status === 'confirmed').length;
  const activeBookings = rentals.filter((r) =>
    ['requested', 'approved', 'active'].includes(r.status)
  ).length;
  const totalSpent = orders
    .filter((o) => o.status !== 'cancelled')
    .reduce((sum, o) => sum + o.total, 0);

  return (
    <div>
      <h1 className="text-section text-canopy">Welcome back{profile ? `, ${profile.displayName.split(' ')[0]}` : ''}</h1>
      <p className="mt-2 max-w-xl text-soil/70">
        Here's where things stand with what you've ordered and booked.
      </p>

      <ErrorNote message={error} />

      {loading ? (
        <div className="mt-8">
          <RowSkeleton count={3} />
        </div>
      ) : (
        <div className="mt-8 grid gap-4 sm:grid-cols-3">
          <StatCard icon={ShoppingBag} label="Orders in progress" value={openOrders} />
          <StatCard icon={Tractor} label="Active bookings" value={activeBookings} />
          <StatCard icon={Sprout} label="Spent this account" value={peso(totalSpent)} />
        </div>
      )}

      <div className="mt-10 grid gap-4 sm:grid-cols-2">
        <Link
          to="/market"
          className="card flex items-center justify-between p-6 transition duration-300 ease-grow hover:-translate-y-0.5 hover:shadow-lift"
        >
          <div>
            <h2 className="font-display text-xl text-soil">Shop the harvest</h2>
            <p className="mt-1 text-sm text-soil/60">Fresh produce from member farms</p>
          </div>
          <ShoppingBag size={22} className="text-canopy" />
        </Link>
        <Link
          to="/equipment"
          className="card flex items-center justify-between p-6 transition duration-300 ease-grow hover:-translate-y-0.5 hover:shadow-lift"
        >
          <div>
            <h2 className="font-display text-xl text-soil">Book equipment</h2>
            <p className="mt-1 text-sm text-soil/60">Rent a tractor for the days you need it</p>
          </div>
          <Tractor size={22} className="text-bark" />
        </Link>
      </div>

      {orders.length > 0 && (
        <section className="mt-10">
          <div className="flex items-center justify-between">
            <h2 className="font-display text-xl text-soil">Recent orders</h2>
            <Link to="/app/orders" className="link-underline text-sm font-medium">
              See all
            </Link>
          </div>
          <ul className="mt-4 space-y-3">
            {orders.slice(0, 3).map((order) => (
              <li key={order.id} className="card flex items-center justify-between p-4">
                <span className="text-sm text-soil/70">
                  {order.items.length} item{order.items.length === 1 ? '' : 's'} · {order.status}
                </span>
                <span className="font-medium text-soil">{peso(order.total)}</span>
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
}: {
  icon: typeof ShoppingBag;
  label: string;
  value: string | number;
}) {
  return (
    <div className="card p-6">
      <Icon size={20} className="text-canopy" />
      <p className="mt-3 text-sm text-soil/60">{label}</p>
      <p className="mt-1 font-display text-2xl text-soil">{value}</p>
    </div>
  );
}
