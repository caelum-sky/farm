// frontend/src/pages/admin/AdminTransactions.tsx
import { useCallback, useEffect, useState } from 'react';

import ErrorNote from '@/components/ui/ErrorNote';
import { RowSkeleton } from '@/components/ui/Skeleton';
import { useToast } from '@/context/ToastContext';
import { api, peso, query } from '@/lib/api';
import type { Order, Rental } from '@/types';

type Tab = 'orders' | 'rentals';

const ORDER_STATUS: Record<Order['status'], string> = {
  placed: 'bg-harvest/20 text-bark',
  confirmed: 'bg-sprout text-canopy',
  fulfilled: 'bg-canopy text-husk',
  cancelled: 'bg-soil/10 text-soil/60',
};

const RENTAL_STATUS: Record<Rental['status'], string> = {
  requested: 'bg-harvest/20 text-bark',
  approved: 'bg-sprout text-canopy',
  active: 'bg-canopy text-husk',
  returned: 'bg-soil/10 text-soil/65',
  cancelled: 'bg-soil/10 text-soil/50',
};

/**
 * Transactions the platform is carrying. Admins mostly come here to unstick a
 * stalled order, so the status controls sit on the row itself.
 */
export default function AdminTransactions() {
  const { notify } = useToast();
  const [tab, setTab] = useState<Tab>('orders');
  const [status, setStatus] = useState('');
  const [orders, setOrders] = useState<Order[]>([]);
  const [rentals, setRentals] = useState<Rental[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      if (tab === 'orders') {
        setOrders(await api.get<Order[]>(`/admin/orders${query({ status, limit: 60 })}`));
      } else {
        setRentals(await api.get<Rental[]>(`/admin/rentals${query({ status, limit: 60 })}`));
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Could not load transactions');
    } finally {
      setLoading(false);
    }
  }, [tab, status]);

  useEffect(() => {
    void load();
  }, [load]);

  const updateOrder = async (order: Order, next: Order['status']) => {
    try {
      await api.patch(`/admin/orders/${order.id}/status`, { status: next });
      notify(`Order marked ${next}`);
      await load();
    } catch (err) {
      notify(err instanceof Error ? err.message : 'Could not update that order', 'error');
    }
  };

  const statusOptions =
    tab === 'orders'
      ? ['', 'placed', 'confirmed', 'fulfilled', 'cancelled']
      : ['', 'requested', 'approved', 'active', 'returned', 'cancelled'];

  return (
    <section>
      <div className="scroll-row">
        {(['orders', 'rentals'] as Tab[]).map((value) => (
          <button
            key={value}
            type="button"
            onClick={() => {
              setTab(value);
              setStatus('');
            }}
            aria-pressed={tab === value}
            className={`pill capitalize ${tab === value ? 'pill-on' : 'pill-off'}`}
          >
            {value}
          </button>
        ))}
      </div>

      <label className="mt-4 flex items-center gap-2 text-sm text-soil/65">
        Status
        <select
          value={status}
          onChange={(e) => setStatus(e.target.value)}
          className="field w-auto py-2 capitalize"
        >
          {statusOptions.map((option) => (
            <option key={option || 'all'} value={option}>
              {option || 'All'}
            </option>
          ))}
        </select>
      </label>

      <ErrorNote message={error} />

      <div className="mt-6">
        {loading ? (
          <RowSkeleton />
        ) : tab === 'orders' ? (
          orders.length === 0 ? (
            <p className="py-12 text-center text-soil/60">No orders with that status.</p>
          ) : (
            <ul className="space-y-3">
              {orders.map((order) => (
                <li key={order.id} className="card p-5">
                  <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="min-w-0">
                      <p className="font-medium text-soil">
                        {order.items.length} item{order.items.length === 1 ? '' : 's'} ·{' '}
                        {peso(order.total)}
                      </p>
                      <p className="mt-0.5 text-sm text-soil/55">
                        buyer {order.buyerId.slice(0, 8)}… · order {order.id.slice(0, 8)}…
                      </p>
                    </div>
                    <span className={`chip ${ORDER_STATUS[order.status]}`}>{order.status}</span>
                  </div>

                  <div className="mt-4 flex flex-wrap gap-2">
                    {(['confirmed', 'fulfilled', 'cancelled'] as Order['status'][])
                      .filter((option) => option !== order.status)
                      .map((option) => (
                        <button
                          key={option}
                          type="button"
                          onClick={() => updateOrder(order, option)}
                          className={option === 'cancelled' ? 'btn-quiet' : 'pill pill-off'}
                        >
                          Mark {option}
                        </button>
                      ))}
                  </div>
                </li>
              ))}
            </ul>
          )
        ) : rentals.length === 0 ? (
          <p className="py-12 text-center text-soil/60">No bookings with that status.</p>
        ) : (
          <ul className="space-y-3">
            {rentals.map((rental) => (
              <li key={rental.id} className="card flex flex-wrap items-center gap-4 p-5">
                <div className="min-w-0 flex-1">
                  <p className="truncate font-medium text-soil">{rental.equipmentName}</p>
                  <p className="mt-0.5 text-sm text-soil/55">
                    {rental.days} day{rental.days === 1 ? '' : 's'} · {peso(rental.totalCost)} ·
                    renter {rental.renterId.slice(0, 8)}…
                  </p>
                </div>
                <span className={`chip ${RENTAL_STATUS[rental.status]}`}>{rental.status}</span>
              </li>
            ))}
          </ul>
        )}
      </div>
    </section>
  );
}
