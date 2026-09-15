// frontend/src/context/CartContext.tsx
import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react';

import type { CartLine, Product } from '@/types';

const STORAGE_KEY = 'farmhub.cart.v1';

interface CartContextValue {
  lines: CartLine[];
  count: number;
  subtotal: number;
  add: (product: Product, qty?: number) => void;
  setQty: (productId: string, qty: number) => void;
  remove: (productId: string) => void;
  clear: () => void;
}

const CartContext = createContext<CartContextValue | undefined>(undefined);

function readStoredCart(): CartLine[] {
  try {
    const raw = window.localStorage.getItem(STORAGE_KEY);
    return raw ? (JSON.parse(raw) as CartLine[]) : [];
  } catch {
    return [];
  }
}

export function CartProvider({ children }: { children: ReactNode }) {
  const [lines, setLines] = useState<CartLine[]>(readStoredCart);

  useEffect(() => {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(lines));
  }, [lines]);

  const add = useCallback((product: Product, qty = 1) => {
    setLines((current) => {
      const existing = current.find((line) => line.productId === product.id);
      if (existing) {
        return current.map((line) =>
          line.productId === product.id
            ? { ...line, qty: Math.min(line.qty + qty, product.stock) }
            : line
        );
      }
      return [
        ...current,
        {
          productId: product.id,
          name: product.name,
          unit: product.unit,
          price: product.price,
          stock: product.stock,
          qty: Math.min(qty, product.stock),
        },
      ];
    });
  }, []);

  const setQty = useCallback((productId: string, qty: number) => {
    setLines((current) =>
      current
        .map((line) =>
          line.productId === productId
            ? { ...line, qty: Math.max(0, Math.min(qty, line.stock)) }
            : line
        )
        .filter((line) => line.qty > 0)
    );
  }, []);

  const remove = useCallback((productId: string) => {
    setLines((current) => current.filter((line) => line.productId !== productId));
  }, []);

  const clear = useCallback(() => setLines([]), []);

  const value = useMemo<CartContextValue>(() => {
    const count = lines.reduce((sum, line) => sum + line.qty, 0);
    const subtotal = lines.reduce((sum, line) => sum + line.qty * line.price, 0);
    return { lines, count, subtotal, add, setQty, remove, clear };
  }, [lines, add, setQty, remove, clear]);

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
}

// eslint-disable-next-line react-refresh/only-export-components
export function useCart(): CartContextValue {
  const ctx = useContext(CartContext);
  if (!ctx) throw new Error('useCart must be used inside <CartProvider>');
  return ctx;
}
