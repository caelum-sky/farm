// frontend/src/pages/Marketplace.tsx
import { useCallback, useEffect, useState } from 'react';
import { Search, SlidersHorizontal, X } from 'lucide-react';

import { StaggerGrid, StaggerItem } from '@/components/animations/StaggerGrid';
import EmptyState from '@/components/ui/EmptyState';
import ErrorNote from '@/components/ui/ErrorNote';
import ProductCard from '@/components/ui/ProductCard';
import { CardGridSkeleton } from '@/components/ui/Skeleton';
import { useCart } from '@/context/CartContext';
import { useToast } from '@/context/ToastContext';
import { useDebounced } from '@/hooks/useDebounced';
import { api, humanize, query } from '@/lib/api';
import type { Paginated, Product } from '@/types';

const SUBCATEGORIES: Record<'produce' | 'supply', string[]> = {
  produce: ['fruits', 'vegetables', 'rice-and-grain', 'dairy-and-eggs', 'preserves'],
  supply: ['fertilizer', 'pesticide', 'seed', 'feed', 'tools'],
};

const SORTS = [
  { value: 'newest', label: 'Newest' },
  { value: 'price-low', label: 'Price: low to high' },
  { value: 'price-high', label: 'Price: high to low' },
];

const COPY = {
  produce: {
    title: 'The market',
    intro: 'Everything listed here was harvested by a member farm. Prices are set by the grower.',
  },
  supply: {
    title: 'Farm supplies',
    intro:
      'Fertilizer, seed, and crop protection stocked by cooperatives. Member pricing shows on listings your coop holds.',
  },
};

export default function Marketplace({ category }: { category: 'produce' | 'supply' }) {
  const { add } = useCart();
  const { notify } = useToast();

  const [products, setProducts] = useState<Product[]>([]);
  const [cursor, setCursor] = useState<string | null>(null);
  const [subcategory, setSubcategory] = useState('');
  const [sort, setSort] = useState('newest');
  const [maxPrice, setMaxPrice] = useState('');
  const [searchInput, setSearchInput] = useState('');
  const [showFilters, setShowFilters] = useState(false);
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
        const result = await api.get<Paginated<Product>>(
          `/products${query({
            category,
            subcategory,
            search: search || undefined,
            maxPrice: maxPrice || undefined,
            sort,
            limit: 12,
            cursor: nextCursor,
          })}`
        );
        setProducts((current) => (nextCursor ? [...current, ...result.items] : result.items));
        setCursor(result.nextCursor);
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Could not load listings');
      } finally {
        setLoading(false);
        setLoadingMore(false);
      }
    },
    [category, subcategory, search, maxPrice, sort]
  );

  useEffect(() => {
    void load();
  }, [load]);

  const addToCart = (product: Product) => {
    add(product);
    notify(`${product.name} added to your cart`);
  };

  const clearFilters = () => {
    setSubcategory('');
    setMaxPrice('');
    setSort('newest');
    setSearchInput('');
  };

  const filtersActive = Boolean(subcategory || maxPrice || searchInput || sort !== 'newest');
  const copy = COPY[category];

  return (
    <div className="shell section-y">
      <header className="max-w-2xl">
        <h1 className="text-section text-canopy">{copy.title}</h1>
        <p className="mt-3 text-soil/70 sm:mt-4">{copy.intro}</p>
      </header>

      <div className="mt-8 flex flex-wrap items-center gap-3">
        <div className="relative min-w-0 flex-1 sm:max-w-sm">
          <Search size={17} className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-soil/40" />
          <input
            type="search"
            value={searchInput}
            onChange={(e) => setSearchInput(e.target.value)}
            placeholder={category === 'produce' ? 'Search mangoes, corn…' : 'Search fertilizer, seed…'}
            aria-label="Search listings"
            className="field pl-11"
          />
        </div>

        <button
          type="button"
          onClick={() => setShowFilters((v) => !v)}
          aria-expanded={showFilters}
          className="pill pill-off gap-2 sm:hidden"
        >
          <SlidersHorizontal size={15} />
          Filters
        </button>

        <label className="hidden items-center gap-2 text-sm text-soil/65 sm:flex">
          Sort
          <select
            value={sort}
            onChange={(e) => setSort(e.target.value)}
            aria-label="Sort listings"
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

      {/* Category pills scroll sideways on phones rather than wrapping into
          four stacked rows that push the grid off-screen. */}
      <div className="mt-5 scroll-row">
        <button
          type="button"
          onClick={() => setSubcategory('')}
          aria-pressed={subcategory === ''}
          className={`pill ${subcategory === '' ? 'pill-on' : 'pill-off'}`}
        >
          Everything
        </button>
        {SUBCATEGORIES[category].map((option) => (
          <button
            key={option}
            type="button"
            onClick={() => setSubcategory(option)}
            aria-pressed={subcategory === option}
            className={`pill ${subcategory === option ? 'pill-on' : 'pill-off'}`}
          >
            {humanize(option)}
          </button>
        ))}
      </div>

      {showFilters && (
        <div className="mt-4 grid gap-4 rounded-pod border border-soil/10 bg-white p-5 sm:hidden">
          <label className="text-sm text-soil/70">
            Sort
            <select
              value={sort}
              onChange={(e) => setSort(e.target.value)}
              className="field mt-1.5"
            >
              {SORTS.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
          </label>

          <label className="text-sm text-soil/70">
            Highest price
            <input
              type="number"
              min="0"
              inputMode="numeric"
              value={maxPrice}
              onChange={(e) => setMaxPrice(e.target.value)}
              placeholder="Any"
              className="field mt-1.5"
            />
          </label>
        </div>
      )}

      <div className="mt-4 hidden items-center gap-3 sm:flex">
        <label className="flex items-center gap-2 text-sm text-soil/65">
          Highest price
          <input
            type="number"
            min="0"
            value={maxPrice}
            onChange={(e) => setMaxPrice(e.target.value)}
            placeholder="Any"
            className="field w-32 py-2"
          />
        </label>

        {filtersActive && (
          <button type="button" onClick={clearFilters} className="btn-quiet">
            <X size={14} />
            Clear filters
          </button>
        )}
      </div>

      {error && (
        <div className="mt-8">
          <ErrorNote message={error} />
        </div>
      )}

      <div className="mt-10">
        {loading ? (
          <CardGridSkeleton />
        ) : products.length === 0 ? (
          <EmptyState
            title={filtersActive ? 'Nothing matches those filters' : 'Nothing listed here yet'}
            hint={
              filtersActive
                ? 'Widen the price or clear the filters to see the rest of the market.'
                : category === 'produce'
                  ? 'Check back after the next harvest day, or try another category.'
                  : 'Cooperatives post supplies as stock arrives. Try another category.'
            }
            action={
              filtersActive ? (
                <button type="button" onClick={clearFilters} className="btn-primary">
                  Clear filters
                </button>
              ) : undefined
            }
          />
        ) : (
          <>
            <StaggerGrid className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
              {products.map((product) => (
                <StaggerItem key={product.id}>
                  <ProductCard product={product} onAdd={addToCart} />
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
