// frontend/src/pages/EquipmentPage.tsx
import { useCallback, useEffect, useState } from 'react';
import { Search } from 'lucide-react';

import { StaggerGrid, StaggerItem } from '@/components/animations/StaggerGrid';
import EmptyState from '@/components/ui/EmptyState';
import EquipmentCard from '@/components/ui/EquipmentCard';
import ErrorNote from '@/components/ui/ErrorNote';
import { CardGridSkeleton } from '@/components/ui/Skeleton';
import { useDebounced } from '@/hooks/useDebounced';
import { api, query } from '@/lib/api';
import type { Equipment, Paginated } from '@/types';

type Mode = '' | 'rent' | 'sale';

const SORTS = [
  { value: 'newest', label: 'Newest' },
  { value: 'price-low', label: 'Cheapest first' },
  { value: 'price-high', label: 'Dearest first' },
];

export default function EquipmentPage() {
  const [items, setItems] = useState<Equipment[]>([]);
  const [mode, setMode] = useState<Mode>('rent');
  const [sort, setSort] = useState('newest');
  const [searchInput, setSearchInput] = useState('');
  const [cursor, setCursor] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState('');

  const search = useDebounced(searchInput);

  const load = useCallback(
    async (nextCursor?: string) => {
      if (nextCursor) setLoadingMore(true);
      else setLoading(true);
      setError('');

      try {
        const result = await api.get<Paginated<Equipment>>(
          `/equipment${query({
            listingType: mode || undefined,
            search: search || undefined,
            sort,
            limit: 12,
            cursor: nextCursor,
          })}`
        );
        setItems((current) => (nextCursor ? [...current, ...result.items] : result.items));
        setCursor(result.nextCursor);
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Could not load equipment');
      } finally {
        setLoading(false);
        setLoadingMore(false);
      }
    },
    [mode, search, sort]
  );

  useEffect(() => {
    void load();
  }, [load]);

  return (
    <div className="shell section-y">
      <header className="max-w-2xl">
        <h1 className="text-section text-canopy">Equipment</h1>
        <p className="mt-3 text-soil/70 sm:mt-4">
          Tractors, tillers, sprayers, and threshers held by member cooperatives. Book by the
          day instead of buying a machine you'll use three weeks a year.
        </p>
      </header>

      <div className="mt-8 flex flex-wrap items-center gap-3">
        <div className="relative min-w-0 flex-1 sm:max-w-sm">
          <Search size={17} className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-soil/40" />
          <input
            type="search"
            value={searchInput}
            onChange={(e) => setSearchInput(e.target.value)}
            placeholder="Search tractor, sprayer…"
            aria-label="Search equipment"
            className="field pl-11"
          />
        </div>

        <label className="flex items-center gap-2 text-sm text-soil/65">
          <span className="hidden sm:inline">Sort</span>
          <select
            value={sort}
            onChange={(e) => setSort(e.target.value)}
            aria-label="Sort equipment"
            className="field w-auto py-2"
          >
            {SORTS.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>
        </label>
      </div>

      <div className="mt-5 scroll-row">
        {([
          ['rent', 'For rent'],
          ['sale', 'For sale'],
          ['', 'Everything'],
        ] as Array<[Mode, string]>).map(([value, label]) => (
          <button
            key={label}
            type="button"
            onClick={() => setMode(value)}
            aria-pressed={mode === value}
            className={`pill ${mode === value ? 'pill-on' : 'pill-off'}`}
          >
            {label}
          </button>
        ))}
      </div>

      {error && (
        <div className="mt-8">
          <ErrorNote message={error} />
        </div>
      )}

      <div className="mt-10">
        {loading ? (
          <CardGridSkeleton />
        ) : items.length === 0 ? (
          <EmptyState
            title="No machines listed here yet"
            hint="Cooperatives add equipment to the shared pool as it becomes available. Try the other tab."
          />
        ) : (
          <>
            <StaggerGrid className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
              {items.map((item) => (
                <StaggerItem key={item.id}>
                  <EquipmentCard item={item} />
                </StaggerItem>
              ))}
            </StaggerGrid>

            {cursor && (
              <div className="mt-12 flex justify-center">
                <button
                  type="button"
                  onClick={() => load(cursor)}
                  disabled={loadingMore}
                  className="btn-outline"
                >
                  {loadingMore ? 'Loading…' : 'Load more'}
                </button>
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
}
