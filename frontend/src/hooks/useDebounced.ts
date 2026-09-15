// frontend/src/hooks/useDebounced.ts
import { useEffect, useState } from 'react';

/**
 * Delays a fast-changing value so a search box fires one request after typing
 * stops, instead of one per keystroke.
 */
export function useDebounced<T>(value: T, delay = 350): T {
  const [settled, setSettled] = useState(value);

  useEffect(() => {
    const timer = window.setTimeout(() => setSettled(value), delay);
    return () => window.clearTimeout(timer);
  }, [value, delay]);

  return settled;
}
