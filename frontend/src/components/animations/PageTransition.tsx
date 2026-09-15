import { type ReactNode } from 'react';
import { AnimatePresence, motion } from 'framer-motion';
import { useReducedMotion } from '@/hooks/useReducedMotion';

interface PageTransitionProps {
  children: ReactNode;
  /** Whether to animate on initial mount */
  initial?: boolean;
}

export default function PageTransition({ children, initial }: PageTransitionProps) {
  const reduceMotion = useReducedMotion();

  return (
    <AnimatePresence>
      <motion.div
        initial={reduceMotion ? false : (initial === false ? false : { opacity: 0, y: 10 })}
        animate={{ opacity: 1, y: 0 }}
        exit={{ opacity: 0, y: -10 }}
        transition={{
          duration: 0.3,
          ease: [0.22, 0.61, 0.36, 1],
          ...(reduceMotion && { duration: 0.001 }),
        }}
      >
        {children}
      </motion.div>
    </AnimatePresence>
  );
}