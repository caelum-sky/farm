// frontend/src/components/animations/Reveal.tsx
import { motion, useReducedMotion } from 'framer-motion';
import type { ReactNode } from 'react';

type Direction = 'up' | 'left' | 'right' | 'none';

interface RevealProps {
  children: ReactNode;
  delay?: number;
  distance?: number;
  from?: Direction;
  className?: string;
}

const OFFSET: Record<Direction, { x: number; y: number }> = {
  up: { x: 0, y: 1 },
  left: { x: -1, y: 0 },
  right: { x: 1, y: 0 },
  none: { x: 0, y: 0 },
};

/**
 * Fades content into place once, as it scrolls into view. Slow easing on
 * purpose — the pace should read like a walk through a field, not a slide deck.
 * Renders the end state immediately under prefers-reduced-motion.
 */
export default function Reveal({
  children,
  delay = 0,
  distance = 24,
  from = 'up',
  className,
}: RevealProps) {
  const reduceMotion = useReducedMotion();

  if (reduceMotion) {
    return <div className={className}>{children}</div>;
  }

  const offset = OFFSET[from];

  return (
    <motion.div
      className={className}
      initial={{ opacity: 0, x: offset.x * distance, y: offset.y * distance }}
      whileInView={{ opacity: 1, x: 0, y: 0 }}
      viewport={{ once: true, margin: '-70px' }}
      transition={{ duration: 0.8, delay, ease: [0.22, 0.61, 0.36, 1] }}
    >
      {children}
    </motion.div>
  );
}
