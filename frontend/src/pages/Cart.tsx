// frontend/src/pages/Cart.tsx
import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Minus, Plus, Trash2 } from 'lucide-react';

import EmptyState from '@/components/ui/EmptyState';
import ErrorNote from '@/components/ui/ErrorNote';
import { useAuth } from '@/context/AuthContext';
import { useCart } from '@/context/CartContext';
import { useToast } from '@/context/ToastContext';
import { api, peso } from '@/lib/api';
import type { Order } from '@/types';

export default function Cart() {
  const { lines, subtotal, count, setQty, remove, clear } = useCart();
  const { profile } = useAuth();
  const { notify } = useToast();
  const navigate = useNavigate();
  const [placing, setPlacing] = useState(false);
  const [error, setError] = useState('');

  const checkout = async () => {
    setPlacing(true);
    setError('');
    try {
      const order = await api.post<Order>('/orders', {
        items: lines.map((line) => ({ productId: line.productId, qty: line.qty })),
      });
      clear();
      notify('Order placed');
      // Use the current app route directly so the placement reference survives
      // navigation; the legacy /orders redirect intentionally has no query.
      navigate(`/app/orders?placed=${order.id}`);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Could not place the order');
    } finally {
      setPlacing(false);
    }
  };

  if (lines.length === 0) {
    return (
      <div className="shell section-y">
        <h1 className="text-section text-canopy">Your cart</h1>
        <div className="mt-8">
          <EmptyState
            title="Nothing in the cart yet"
            hint="Browse what member farms harvested this week and add what you need."
            action={
              <Link to="/market" className="btn-primary">
                Shop the harvest
              </Link>
            }
          />
        </div>
      </div>
    );
  }

  return (
    <div className="shell section-y pb-32 lg:pb-24">
      <h1 className="text-section text-canopy">Your cart</h1>
      <p className="mt-2 text-soil/65">
        {count} item{count === 1 ? '' : 's'} from {new Set(lines.map((l) => l.productId)).size}{' '}
        listing{lines.length === 1 ? '' : 's'}
      </p>

      <div className="mt-10 grid gap-8 lg:grid-cols-[1.6fr_1fr] lg:gap-10">
        <ul className="space-y-3">
          {lines.map((line) => (
            <li key={line.productId} className="card p-4 sm:p-5">
              <div className="flex items-start justify-between gap-4">
                <div className="min-w-0">
                  <h2 className="truncate font-display text-lg text-soil sm:text-xl">
                    {line.name}
                  </h2>
                  <p className="mt-1 text-sm text-soil/60">
                    {peso(line.price)} per {line.unit}
                  </p>
                </div>
                <button
                  type="button"
                  onClick={() => remove(line.productId)}
                  aria-label={`Remove ${line.name}`}
                  className="grid h-10 w-10 shrink-0 place-items-center rounded-full text-soil/50 transition hover:bg-soil/5 hover:text-clay"
                >
                  <Trash2 size={17} />
                </button>
              </div>

              <div className="mt-4 flex items-center justify-between gap-4">
                <div className="flex items-center gap-1 rounded-full border border-soil/15 p-1">
                  <button
                    type="button"
                    onClick={() => setQty(line.productId, line.qty - 1)}
                    aria-label={`Less ${line.name}`}
                    className="grid h-9 w-9 place-items-center rounded-full transition hover:bg-soil/5"
                  >
                    <Minus size={15} />
                  </button>
                  <span className="w-9 text-center text-sm font-medium">{line.qty}</span>
                  <button
                    type="button"
                    onClick={() => setQty(line.productId, line.qty + 1)}
                    disabled={line.qty >= line.stock}
                    aria-label={`More ${line.name}`}
                    className="grid h-9 w-9 place-items-center rounded-full transition hover:bg-soil/5 disabled:opacity-35"
                  >
                    <Plus size={15} />
                  </button>
                </div>

                <span className="font-medium text-soil">{peso(line.price * line.qty)}</span>
              </div>

              {line.qty >= line.stock && (
                <p className="mt-2 text-xs text-bark">
                  That's all the seller has listed right now.
                </p>
              )}
            </li>
          ))}
        </ul>

        <aside className="card h-fit p-6 sm:p-7 lg:sticky lg:top-24">
          <h2 className="font-display text-2xl text-soil">Order summary</h2>

          <dl className="mt-6 space-y-3 text-sm">
            <div className="flex justify-between">
              <dt className="text-soil/65">Subtotal</dt>
              <dd className="font-medium">{peso(subtotal)}</dd>
            </div>
            <div className="flex justify-between">
              <dt className="text-soil/65">Delivery</dt>
              <dd className="text-right text-soil/65">Arranged with the seller</dd>
            </div>
            <div className="flex justify-between border-t border-soil/10 pt-3 text-base">
              <dt className="font-medium">Total</dt>
              <dd className="font-display text-xl text-canopy">{peso(subtotal)}</dd>
            </div>
          </dl>

          <div className="mt-6 space-y-3">
            <ErrorNote message={error} />
            {profile ? (
              <button
                type="button"
                onClick={checkout}
                disabled={placing}
                className="btn-primary hidden w-full lg:inline-flex"
              >
                {placing ? 'Placing order…' : 'Place order'}
              </button>
            ) : (
              <Link to="/login" className="btn-primary hidden w-full lg:inline-flex">
                Sign in to check out
              </Link>
            )}
            <button type="button" onClick={clear} className="btn-quiet w-full">
              Clear cart
            </button>
          </div>

          <p className="mt-5 text-xs leading-relaxed text-soil/55">
            Stock is confirmed when the order is placed. If an item sold out in the meantime,
            the whole order is rejected and nothing is reserved.
          </p>
        </aside>
      </div>

      {/* Checkout stays within thumb reach on phones instead of sitting below
          a long list of line items. */}
      <div className="fixed inset-x-0 bottom-0 z-40 border-t border-soil/10 bg-husk/95 px-5 py-3 backdrop-blur-md lg:hidden">
        <div className="flex items-center gap-4">
          <div className="min-w-0">
            <p className="text-xs text-soil/55">Total</p>
            <p className="font-display text-xl text-canopy">{peso(subtotal)}</p>
          </div>
          {profile ? (
            <button
              type="button"
              onClick={checkout}
              disabled={placing}
              className="btn-primary flex-1"
            >
              {placing ? 'Placing…' : 'Place order'}
            </button>
          ) : (
            <Link to="/login" className="btn-primary flex-1">
              Sign in to check out
            </Link>
          )}
        </div>
      </div>
    </div>
  );
}
