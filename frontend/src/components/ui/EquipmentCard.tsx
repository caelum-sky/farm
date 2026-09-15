// frontend/src/components/ui/EquipmentCard.tsx
import { Tractor } from 'lucide-react';
import { Link } from 'react-router-dom';

import { peso } from '@/lib/api';
import type { Equipment } from '@/types';
import Card from './Card';
import { motion, useReducedMotion } from 'framer-motion';

export default function EquipmentCard({ item }: { item: Equipment }) {
  const forRent = item.listingType === 'rent';
  const reduceMotion = useReducedMotion();

  return (
    <motion.div
      initial={false}
      whileHover={{
        scale: 1.05,
        y: -5,
        transition: { type: "spring", stiffness: 300, damping: 20 }
      }}
      whileTap={{ scale: 0.95 }}
      className="group overflow-hidden"
    >
      <Card className="transition duration-500 ease-grow hover:-translate-y-1 hover:shadow-lift">
        <Link to={`/equipment/${item.id}`} className="block">
          <div className="relative aspect-[4/3] overflow-hidden bg-husk2">
            {item.images[0] ? (
              <img
                src={item.images[0]}
                alt={item.name}
                loading="lazy"
                className="h-full w-full object-cover transition duration-700 ease-grow group-hover:scale-105"
              />
            ) : (
              <div className="flex h-full w-full items-center justify-center text-bark/30">
                <Tractor size={48} strokeWidth={1.3} />
              </div>
            )}
            <span
              className={`chip absolute left-3 top-3 ${
                forRent ? 'bg-canopy text-husk' : 'bg-harvest/25 text-bark'
              }`}
            >
              {forRent ? 'For rent' : 'For sale'}
            </span>
          </div>
        </Link>

        <div className="p-5">
          <Link to={`/equipment/${item.id}`}>
            <h3 className="truncate font-display text-xl text-soil">{item.name}</h3>
          </Link>
          <p className="mt-1 text-sm capitalize text-soil/55">{item.category}</p>
          <p className="mt-3 font-display text-lg text-canopy">
            {peso(item.price)}
            {forRent && <span className="text-sm font-sans text-soil/60"> per day</span>}
          </p>
        </div>
      </Card>
    </motion.div>
  );
}
