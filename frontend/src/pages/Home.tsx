// frontend/src/pages/Home.tsx
import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';

import Reveal from '@/components/animations/Reveal';
import { StaggerGrid, StaggerItem } from '@/components/animations/StaggerGrid';
import HarvestCalendar from '@/components/home/HarvestCalendar';
import Hero from '@/components/home/Hero';
import RolePanels from '@/components/home/RolePanels';
import StorySection from '@/components/home/StorySection';
import EquipmentCard from '@/components/ui/EquipmentCard';
import ProductCard from '@/components/ui/ProductCard';
import { CardGridSkeleton } from '@/components/ui/Skeleton';
import { useCart } from '@/context/CartContext';
import { useToast } from '@/context/ToastContext';
import { api, query } from '@/lib/api';
import type { Equipment, Paginated, Product } from '@/types';

export default function Home() {
  const { add } = useCart();
  const { notify } = useToast();
  const [products, setProducts] = useState<Product[]>([]);
  const [equipment, setEquipment] = useState<Equipment[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;

    (async () => {
      try {
        const [produce, rentals] = await Promise.all([
          api.get<Paginated<Product>>(`/products${query({ category: 'produce', limit: 6 })}`),
          api.get<Paginated<Equipment>>(`/equipment${query({ listingType: 'rent', limit: 3 })}`),
        ]);
        if (!cancelled) {
          setProducts(produce.items);
          setEquipment(rentals.items);
        }
      } catch {
        // The marketing page still stands on its own if the API is cold-starting.
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();

    return () => {
      cancelled = true;
    };
  }, []);

  const addToCart = (product: Product) => {
    add(product);
    notify(`${product.name} added to your cart`);
  };

  return (
    <>
      <Hero />

      <section className="bg-husk section-y">
        <div className="shell">
          <Reveal>
            <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
              <h2 className="max-w-md text-section text-canopy">Picked this week</h2>
              <Link to="/market" className="link-underline font-medium">
                See the whole market
              </Link>
            </div>
          </Reveal>

          <div className="mt-10 sm:mt-12">
            {loading ? (
              <CardGridSkeleton count={3} />
            ) : products.length ? (
              <StaggerGrid className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                {products.map((product) => (
                  <StaggerItem key={product.id}>
                    <ProductCard product={product} onAdd={addToCart} />
                  </StaggerItem>
                ))}
              </StaggerGrid>
            ) : (
              <p className="text-soil/60">
                No produce listed yet. Farms in your area can post their first harvest from the
                seller dashboard.
              </p>
            )}
          </div>
        </div>
      </section>

      <StorySection />
      <HarvestCalendar />

      {equipment.length > 0 && (
        <section className="bg-husk section-y">
          <div className="shell">
            <Reveal>
              <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <h2 className="max-w-lg text-section text-canopy">Machines free this week</h2>
                <Link to="/equipment" className="link-underline font-medium">
                  See all equipment
                </Link>
              </div>
            </Reveal>

            <StaggerGrid className="mt-10 grid gap-5 sm:mt-12 sm:grid-cols-2 lg:grid-cols-3">
              {equipment.map((item) => (
                <StaggerItem key={item.id}>
                  <EquipmentCard item={item} />
                </StaggerItem>
              ))}
            </StaggerGrid>
          </div>
        </section>
      )}

      <RolePanels />
    </>
  );
}
