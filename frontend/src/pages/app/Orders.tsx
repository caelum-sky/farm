// frontend/src/pages/app/Orders.tsx
import { useCallback, useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';

import EmptyState from '@/components/ui/EmptyState';
import ErrorNote from '@/components/ui/ErrorNote';
import { RowSkeleton } from '@/components/ui/Skeleton';
import { useAuth } from '@/context/AuthContext';
import { useToast } from '@/context/ToastContext';
import { api, peso } from '@/lib/api';
import type { Order } from '@/types';

const STATUS_STYLE: Record<Order['status'], string> = {
  placed: 'bg-harvest/20 text-bark',
  confirmed: 'bg-sprout text-canopy',
  fulfilled: 'bg-canopy text-husk',
  cancelled: 'bg-soil/10 text-soil/60',
};

export default function Orders() {
  const { isSeller } = useAuth();
  const { notify } = useToast();
  const [params] = useSearchParams();
  const justPlaced = params.get('placed');

  const [bought, setBought] = useState<Order[]>([]);
  const [sold, setSold] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    try {
      const requests: Array<Promise<Order[]>> = [api.get<Order[]>('/orders/mine')];
      if (isSeller) requests.push(api.get<Order[]>('/orders/sales'));
      const [mine, sales] = await Promise.all(requests);
      setBought(mine);
      setSold(sales || []);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Could not load orders');
    } finally {
      setLoading(false);
    }
  }, [isSeller]);

  useEffect(() => {
    void load();
  }, [load]);

  const setStatus = async (order: Order, status: Order['status']) => {
    try {
      await api.patch(`/orders/${order.id}/status`, { status });
      notify(`Order marked ${status}`);
      await load();
    } catch (err) {
      notify(err instanceof Error ? err.message : 'Could not update that order', 'error');
    }
  };

  if (loading) {
    return (
      <div className="mx-auto max-w-6xl">
        <h1 className="text-section text-canopy">Orders</h1>
        <div className="mt-10">
          <RowSkeleton />
        </div>
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-6xl">
      <h1 className="text-section text-canopy">Orders</h1>

      {justPlaced && (
        <p className="mt-6 rounded-2xl bg-sprout px-5 py-4 text-canopy" role="status">
          Order placed. The seller confirms stock and arranges handover from here.
        </p>
      )}

      <ErrorNote message={error} />

      <section className="mt-10 sm:mt-12">
        <h2 className="font-display text-2xl text-soil">What you ordered</h2>

        {bought.length === 0 ? (
          <div className="mt-5">
            <EmptyState
              title="No orders yet"
              hint="Anything you buy from the market shows up here with its status."
              action={
                <Link to="/market" className="btn-primary">
                  Shop the harvest
                </Link>
              }
            />
          </div>
        ) : (
          <ul className="mt-5 space-y-3">
            {bought.map((order) => (
              <li key={order.id} className="card p-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                  <span className={`chip ${STATUS_STYLE[order.status]}`}>{order.status}</span>
                  <span className="font-display text-xl text-canopy">{peso(order.total)}</span>
                </div>

                <ul className="mt-3 space-y-1 text-sm text-soil/70">
                  {order.items.map((item) => (
                    <li key={item.productId}>
                      {item.qty} {item.unit} · {item.name} · {peso(item.lineTotal)}
                    </li>
                  ))}
                </ul>

                {order.status === 'placed' && (
                  <button
                    type="button"
                    onClick={() => setStatus(order, 'cancelled')}
                    className="btn-quiet mt-3 -ml-4"
                  >
                    Cancel order
                  </button>
                )}
              </li>
            ))}
          </ul>
        )}
      </section>

      {isSeller && (
        <section className="mt-14 sm:mt-16">
          <h2 className="font-display text-2xl text-soil">Sold from your listings</h2>

          {sold.length === 0 ? (
            <p className="mt-4 text-soil/60">No sales yet.</p>
          ) : (
            <ul className="mt-5 space-y-3">
              {sold.map((order) => (
                <li key={order.id} className="card p-5">
                  <div className="flex flex-wrap items-center justify-between gap-3">
                    <span className={`chip ${STATUS_STYLE[order.status]}`}>{order.status}</span>
                    <span className="font-display text-xl text-canopy">{peso(order.total)}</span>
                  </div>

                  <ul className="mt-3 space-y-1 text-sm text-soil/70">
                    {order.items.map((item) => (
                      <li key={item.productId}>
                        {item.qty} {item.unit} · {item.name}
                      </li>
                    ))}
                  </ul>

                  <div className="mt-4 flex flex-wrap gap-2">
                    {order.status === 'placed' && (
                      <button
                        type="button"
                        onClick={() => setStatus(order, 'confirmed')}
                        className="rounded-full bg-canopy px-4 py-2 text-sm text-husk transition hover:bg-leaf"
                      >
                        Confirm stock
                      </button>
                    )}
                    {order.status === 'confirmed' && (
                      <button
                        type="button"
                        onClick={() => setStatus(order, 'fulfilled')}
                        className="rounded-full bg-canopy px-4 py-2 text-sm text-husk transition hover:bg-leaf"
                      >
                        Mark handed over
                      </button>
                    )}
                    {order.status !== 'cancelled' && order.status !== 'fulfilled' && (
                      <button
                        type="button"
                        onClick={() => setStatus(order, 'cancelled')}
                        className="btn-quiet"
                      >
                        Cancel and restock
                      </button>
                    )}
                  </div>
                </li>
              ))}
            </ul>
          )}
        </section>
      )}
    </div>
  );
}
