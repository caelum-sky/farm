// frontend/src/pages/admin/AdminListings.tsx
import { useCallback, useEffect, useState } from 'react';

import ErrorNote from '@/components/ui/ErrorNote';
import { RowSkeleton } from '@/components/ui/Skeleton';
import { api, peso, query } from '@/lib/api';
import type { Equipment, ListingStatus, Paginated, Product } from '@/types';

type Kind = 'products' | 'equipment';
type Row = (Product | Equipment) & { price: number; status: ListingStatus };

const STATUSES: ListingStatus[] = ['active', 'pending', 'removed'];

export default function AdminListings() {
  const [kind, setKind] = useState<Kind>('products');
  const [status, setStatus] = useState<ListingStatus>('active');
  const [rows, setRows] = useState<Row[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const result = await api.get<Paginated<Row>>(`/admin/${kind}${query({ status, limit: 50 })}`);
      setRows(result.items);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Could not load listings');
    } finally {
      setLoading(false);
    }
  }, [kind, status]);

  useEffect(() => {
    void load();
  }, [load]);

  const moderate = async (id: string, next: ListingStatus) => {
    setError('');
    try {
      await api.patch(`/admin/${kind}/${id}/status`, { status: next });
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Could not update that listing');
    }
  };

  return (
    <section>
      <div className="scroll-row">
        {(['products', 'equipment'] as Kind[]).map((value) => (
          <button
            key={value}
            type="button"
            onClick={() => setKind(value)}
            aria-pressed={kind === value}
            className={`pill capitalize ${kind === value ? 'pill-on' : 'pill-off'}`}
          >
            {value}
          </button>
        ))}
        <span className="mx-2 w-px bg-soil/15" />
        {STATUSES.map((value) => (
          <button
            key={value}
            type="button"
            onClick={() => setStatus(value)}
            aria-pressed={status === value}
            className={`pill capitalize ${status === value ? 'pill-on' : 'pill-off'}`}
          >
            {value}
          </button>
        ))}
      </div>

      <ErrorNote message={error} />

      {loading ? (
        <RowSkeleton />
      ) : rows.length === 0 ? (
        <p className="py-12 text-center text-soil/60">Nothing with that status right now.</p>
      ) : (
        <ul className="mt-6 space-y-3">
          {rows.map((row) => (
            <li key={row.id} className="card flex flex-wrap items-center gap-4 p-5">
              <div className="min-w-0 flex-1">
                <h2 className="truncate font-medium text-soil">{row.name}</h2>
                <p className="text-sm text-soil/60">
                  {peso(row.price)} · owner {row.ownerId.slice(0, 8)}… · {row.status}
                </p>
              </div>

              <div className="flex flex-wrap gap-2">
                {row.status !== 'active' && (
                  <button
                    type="button"
                    onClick={() => moderate(row.id, 'active')}
                    className="rounded-full bg-canopy px-4 py-2 text-sm text-husk transition hover:bg-leaf"
                  >
                    Publish
                  </button>
                )}
                {row.status !== 'pending' && (
                  <button type="button" onClick={() => moderate(row.id, 'pending')} className="btn-quiet">
                    Hold for review
                  </button>
                )}
                {row.status !== 'removed' && (
                  <button
                    type="button"
                    onClick={() => moderate(row.id, 'removed')}
                    className="btn-quiet hover:text-clay"
                  >
                    Take down
                  </button>
                )}
              </div>
            </li>
          ))}
        </ul>
      )}
    </section>
  );
}
