// frontend/src/lib/motionVariants.ts
import type { Variants } from 'framer-motion';

/**
 * Plain variant factories — NOT hooks. React Hooks (useReducedMotion included)
 * can only be called from a component body or from another custom hook (a
 * function whose name starts with `use`). Calling one inside a helper like
 * `fadeIn()` breaks the Rules of Hooks and will misbehave at runtime, not
 * just fail lint — so each caller reads the flag itself with
 * `useReducedMotion()` and passes the result in here.
 *
 *   const reduceMotion = useReducedMotion();
 *   <motion.div variants={fadeIn(reduceMotion)} initial="hidden" animate="shown" />
 */
export function fadeIn(reduceMotion: boolean, distance = 24): Variants {
  if (reduceMotion) {
    return {
      hidden: { opacity: 1 },
      shown: { opacity: 1 },
    };
  }
  return {
    hidden: { opacity: 0, y: distance },
    shown: {
      opacity: 1,
      y: 0,
      transition: { duration: 0.8, ease: [0.22, 0.61, 0.36, 1] },
    },
  };
}

export function staggerContainer(reduceMotion: boolean, stagger = 0.07): Variants {
  if (reduceMotion) {
    return { hidden: {}, shown: {} };
  }
  return {
    hidden: {},
    shown: {
      transition: { staggerChildren: stagger, delayChildren: 0.04 },
    },
  };
}
