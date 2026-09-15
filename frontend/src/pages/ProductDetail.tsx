// frontend/src/pages/ProductDetail.tsx
import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { ChevronLeft, Flag, Minus, Plus, Sprout } from 'lucide-react';

import ErrorNote from '@/components/ui/ErrorNote';
import ReportDialog from '@/components/ui/ReportDialog';
import ReviewList from '@/components/ui/ReviewList';
import { Skeleton } from '@/components/ui/Skeleton';
import { useAuth } from '@/context/AuthContext';
import { useCart } from '@/context/CartContext';
import { useToast } from '@/context/ToastContext';
import { api, humanize, peso } from '@/lib/api';
import type { Product } from '@/types';

const TAG_LABEL: Record<string, string> = {
  organic: 'Organic',
  'just-harvested': 'Just harvested',
  'member-price': 'Member price',
};

export default function ProductDetail() {
  const { id = '' } = useParams();
  const { profile } = useAuth();
  const { add } = useCart();
  const { notify } = useToast();

  const [product, setProduct] = useState<Product | null>(null);
  const [activeImage, setActiveImage] = useState(0);
  const [qty, setQty] = useState(1);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [reporting, setReporting] = useState(false);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      setLoading(true);
      try {
        const result = await api.get<Product>(`/products/${id}`);
        if (!cancelled) {
          setProduct(result);
          setActiveImage(0);
        }
      } catch (err) {
        if (!cancelled) setError(err instanceof Error ? err.message : 'Could not load this listing');
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [id]);

  if (loading) {
    return (
      <div className="shell section-y">
        <div className="grid gap-10 lg:grid-cols-2">
          <Skeleton className="aspect-[4/3] rounded-pod" />
          <div className="space-y-4">
            <Skeleton className="h-4 w-24" />
            <Skeleton className="h-12 w-3/4" />
            <Skeleton className="h-8 w-40" />
            <Skeleton className="h-24 w-full" />
          </div>
        </div>
      </div>
    );
  }

  if (error || !product) {
    return (
      <div className="shell section-y">
        <ErrorNote message={error || 'That listing is no longer available.'} />
        <Link to="/market" className="btn-outline mt-6">
          Back to the market
        </Link>
      </div>
    );
  }

  const soldOut = product.stock <= 0;

  const handleAdd = () => {
    add(product, qty);
    notify(`${qty} × ${product.name} added to your cart`);
  };

  return (
    <div className="shell section-y pb-28 lg:pb-24">
      <Link to="/market" className="btn-quiet -ml-4 mb-6">
        <ChevronLeft size={16} />
        Back to the market
      </Link>

      <div className="grid gap-10 lg:grid-cols-2 lg:gap-14">
        <div>
          <div className="overflow-hidden rounded-pod bg-husk2 shadow-crate">
            {product.images[activeImage] ? (
              <img
                src={product.images[activeImage]}
                alt={product.name}
                className="aspect-[4/3] w-full object-cover"
              />
            ) : (
              <div className="flex aspect-[4/3] items-center justify-center text-canopy/25">
                <Sprout size={72} strokeWidth={1.2} />
              </div>
            )}
          </div>

          {product.images.length > 1 && (
            <ul className="mt-3 flex gap-2 overflow-x-auto pb-1">
              {product.images.map((image, index) => (
                <li key={image}>
                  <button
                    type="button"
                    onClick={() => setActiveImage(index)}
                    aria-label={`View image ${index + 1}`}
                    aria-pressed={activeImage === index}
                    className={`h-16 w-20 shrink-0 overflow-hidden rounded-xl border-2 transition ${
                      activeImage === index ? 'border-canopy' : 'border-transparent opacity-70'
                    }`}
                  >
                    <img src={image} alt="" className="h-full w-full object-cover" />
                  </button>
                </li>
              ))}
            </ul>
          )}
        </div>

        <div>
          <p className="text-sm text-soil/55">{humanize(product.subcategory)}</p>
          <h1 className="mt-2 font-display text-4xl text-soil sm:text-5xl">{product.name}</h1>

          {product.tags.length > 0 && (
            <div className="mt-4 flex flex-wrap gap-2">
              {product.tags.map((tag) => (
                <span key={tag} className="chip bg-sprout text-canopy">
                  {TAG_LABEL[tag]}
                </span>
              ))}
            </div>
          )}

          <p className="mt-6 font-display text-3xl text-canopy">
            {peso(product.price)}
            <span className="ml-1 font-sans text-base text-soil/60">per {product.unit}</span>
          </p>

          <p className="mt-6 leading-relaxed text-soil/75">
            {product.description || 'No description was added for this listing.'}
          </p>

          <p className="mt-6 text-sm text-soil/60">
            {soldOut
              ? 'Sold out for now — check back after the next harvest day.'
              : `${product.stock} ${product.unit} available`}
          </p>

          {/* Desktop actions. On phones these live in the sticky bar below so
              the buy button is always in reach without scrolling back up. */}
          <div className="mt-8 hidden flex-wrap items-center gap-4 lg:flex">
            <QuantityStepper qty={qty} setQty={setQty} max={product.stock} />
            <button type="button" onClick={handleAdd} disabled={soldOut} className="btn-primary">
              Add to cart
            </button>
            {profile && (
              <button type="button" onClick={() => setReporting(true)} className="btn-quiet">
                <Flag size={15} />
                Report listing
              </button>
            )}
          </div>

          {profile && (
            <button
              type="button"
              onClick={() => setReporting(true)}
              className="btn-quiet mt-6 lg:hidden"
            >
              <Flag size={15} />
              Report listing
            </button>
          )}
        </div>
      </div>

      <div className="mt-16 max-w-3xl sm:mt-20">
        <ReviewList targetType="product" targetId={product.id} />
      </div>

      <div className="fixed inset-x-0 bottom-0 z-40 border-t border-soil/10 bg-husk/95 px-5 py-3 backdrop-blur-md lg:hidden">
        <div className="flex items-center gap-3">
          <QuantityStepper qty={qty} setQty={setQty} max={product.stock} />
          <button type="button" onClick={handleAdd} disabled={soldOut} className="btn-primary flex-1">
            {soldOut ? 'Sold out' : `Add · ${peso(product.price * qty)}`}
          </button>
        </div>
      </div>

      {reporting && (
        <ReportDialog targetType="product" targetId={product.id} onClose={() => setReporting(false)} />
      )}
    </div>
  );
}

function QuantityStepper({
  qty,
  setQty,
  max,
}: {
  qty: number;
  setQty: (value: number) => void;
  max: number;
}) {
  return (
    <div className="flex shrink-0 items-center gap-1 rounded-full border border-soil/15 bg-white p-1">
      <button
        type="button"
        onClick={() => setQty(Math.max(1, qty - 1))}
        aria-label="Decrease quantity"
        className="grid h-10 w-10 place-items-center rounded-full transition hover:bg-soil/5"
      >
        <Minus size={16} />
      </button>
      <span className="w-8 text-center font-medium" aria-live="polite">
        {qty}
      </span>
      <button
        type="button"
        onClick={() => setQty(Math.min(max, qty + 1))}
        aria-label="Increase quantity"
        className="grid h-10 w-10 place-items-center rounded-full transition hover:bg-soil/5"
      >
        <Plus size={16} />
      </button>
    </div>
  );
}
