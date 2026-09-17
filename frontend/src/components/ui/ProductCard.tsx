// frontend/src/components/ui/ProductCard.tsx
import { Plus, Sprout } from 'lucide-react';
import { Link } from 'react-router-dom';

import { peso } from '@/lib/api';
import type { Product } from '@/types';

const TAG_LABEL: Record<string, string> = {
  organic: 'Organic',
  'just-harvested': 'Just harvested',
  'member-price': 'Member price',
};

const TAG_STYLE: Record<string, string> = {
  organic: 'bg-sprout text-canopy',
  'just-harvested': 'bg-harvest/20 text-bark',
  'member-price': 'bg-canopy/10 text-canopy',
};

interface ProductCardProps {
  product: Product;
  onAdd?: (product: Product) => void;
}

export default function ProductCard({ product, onAdd }: ProductCardProps) {
  const soldOut = product.stock <= 0;

  return (
    <article className="group card overflow-hidden transition duration-500 ease-grow hover:-translate-y-1 hover:shadow-lift">
      <Link to={`/market/${product.id}`} className="block">
        <div className="relative aspect-[4/3] overflow-hidden bg-husk2">
          {product.images[0] ? (
            <img
              src={product.images[0]}
              alt={product.name}
              loading="lazy"
              className="h-full w-full object-cover transition duration-700 ease-grow group-hover:scale-105"
            />
          ) : (
            <div className="flex h-full w-full items-center justify-center text-canopy/30">
              <Sprout size={44} strokeWidth={1.4} />
            </div>
          )}

          {product.tags.length > 0 && (
            <div className="absolute left-3 top-3 flex flex-wrap gap-1.5">
              {product.tags.map((tag) => (
                <span key={tag} className={`chip ${TAG_STYLE[tag]}`}>
                  {TAG_LABEL[tag]}
                </span>
              ))}
            </div>
          )}
        </div>
      </Link>

      <div className="flex items-end justify-between gap-4 p-5">
        <div className="min-w-0">
          <Link to={`/market/${product.id}`}>
            <h3 className="truncate font-display text-xl text-soil">{product.name}</h3>
          </Link>
          {product.ownerName && (
            <p className="mt-0.5 truncate text-xs text-soil/50">{product.ownerName}</p>
          )}
          <p className="mt-1 text-sm text-soil/60">
            {peso(product.price)} per {product.unit}
            {soldOut ? ' · sold out' : ` · ${product.stock} left`}
          </p>
        </div>

        {onAdd && (
          <button
            type="button"
            onClick={() => onAdd(product)}
            disabled={soldOut}
            aria-label={`Add ${product.name} to cart`}
            className="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-canopy text-husk
                       transition duration-300 ease-grow hover:scale-110 hover:bg-leaf
                       focus-visible:scale-110 disabled:opacity-40 disabled:hover:scale-100"
          >
            <Plus size={19} />
          </button>
        )}
      </div>
    </article>
  );
}
