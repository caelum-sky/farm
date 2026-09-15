// frontend/src/components/animations/StaggerGrid.tsx
import { motion, useReducedMotion } from 'framer-motion';
import type { ReactNode } from 'react';

interface StaggerGridProps {
  children: ReactNode;
  className?: string;
}

/**
 * Reveals grid children in sequence from one parent trigger. Cheaper than
 * wrapping every card in its own scroll observer, and the cascade stays in
 * order regardless of how fast the person scrolls past.
 */
export function StaggerGrid({ children, className }: StaggerGridProps) {
  const reduceMotion = useReducedMotion();

  if (reduceMotion) return <div className={className}>{children}</div>;

  return (
    <motion.div
      className={className}
      initial="hidden"
      whileInView="shown"
      viewport={{ once: true, margin: '-60px' }}
      variants={{
        hidden: {},
        shown: { transition: { staggerChildren: 0.07, delayChildren: 0.04 } },
      }}
    >
      {children}
    </motion.div>
  );
}

export function StaggerItem({ children, className }: StaggerGridProps) {
  const reduceMotion = useReducedMotion();

  if (reduceMotion) return <div className={className}>{children}</div>;

  return (
    <motion.div
      className={className}
      variants={{
        hidden: { opacity: 0, y: 22 },
        shown: { opacity: 1, y: 0, transition: { duration: 0.7, ease: [0.22, 0.61, 0.36, 1] } },
      }}
    >
      {children}
    </motion.div>
  );
}
