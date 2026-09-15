// Reusable motion variants for consistent animations across the app

import { useReducedMotion } from '@/hooks/useReducedMotion';

export const fadeIn = (direction: "up" | "down" | "left" | "right" = "up", delay: number = 0) => {
  const reduceMotion = useReducedMotion();

  if (reduceMotion) {
    return {
      initial: false,
      animate: { opacity: 1 },
      exit: { opacity: 0 },
      transition: { duration: 0.001 }
    };
  }

  let initial: any = {};
  switch (direction) {
    case "up":
      initial = { opacity: 0, y: 20 };
      break;
    case "down":
      initial = { opacity: 0, y: -20 };
      break;
    case "left":
      initial = { opacity: 0, x: -20 };
      break;
    case "right":
      initial = { opacity: 0, x: 20 };
      break;
  }

  return {
    initial,
    animate: { opacity: 1, y: 0, x: 0 },
    exit: { opacity: 0, y: direction === "up" ? -20 : direction === "down" ? 20 : 0, x: direction === "left" ? -20 : direction === "right" ? 20 : 0 },
    transition: {
      duration: 0.4,
      ease: [0.22, 0.61, 0.36, 1],
      delay
    }
  };
};

export const staggerContainer = (staggerChildren: number = 0.05, delayChildren: number = 0) => {
  const reduceMotion = useReducedMotion();

  return {
    hidden: {},
    show: {
      transition: {
        staggerChildren: reduceMotion ? 0 : staggerChildren,
        delayChildren: delayChildren || 0
      }
    }
  };
};

export const cardHover = {
  initial: false,
  whileHover: {
    scale: 1.05,
    y: -5,
    transition: { type: "spring", stiffness: 300, damping: 20 }
  },
  whileTap: { scale: 0.95 }
};

export const modalOverlay = {
  initial: { opacity: 0 },
  animate: { opacity: 1 },
  exit: { opacity: 0 },
  transition: { duration: 0.3 }
};

export const modalContent = {
  initial: { opacity: 0, y: 28 },
  animate: { opacity: 1, y: 0 },
  exit: { opacity: 0, y: -20 },
  transition: { duration: 0.32, ease: [0.22, 0.61, 0.36, 1] }
};