// frontend/src/components/ui/ToastViewport.tsx
import { AnimatePresence, motion } from 'framer-motion';
import { Check, Info, TriangleAlert, X } from 'lucide-react';

import { useToast } from '@/context/ToastContext';

const TONE_STYLE = {
  success: 'bg-canopy text-husk',
  error: 'bg-[#8C3A22] text-husk',
  info: 'bg-soil text-husk',
} as const;

const TONE_ICON = {
  success: Check,
  error: TriangleAlert,
  info: Info,
} as const;

/**
 * Bottom-centre on phones so it sits above the thumb, bottom-right on desktop.
 * Announced politely rather than assertively — a saved listing shouldn't
 * interrupt a screen reader mid-sentence.
 */
export default function ToastViewport() {
  const { toasts, dismiss } = useToast();

  return (
    <div
      aria-live="polite"
      aria-atomic="false"
      className="pointer-events-none fixed inset-x-0 bottom-0 z-[60] flex flex-col items-center gap-2 p-4 sm:inset-x-auto sm:right-0 sm:items-end"
    >
      <AnimatePresence initial={false}>
        {toasts.map((toast) => {
          const Icon = TONE_ICON[toast.tone];
          return (
            <motion.div
              key={toast.id}
              layout
              initial={{ opacity: 0, y: 18, scale: 0.97 }}
              animate={{ opacity: 1, y: 0, scale: 1 }}
              exit={{ opacity: 0, y: 10, scale: 0.97 }}
              transition={{ duration: 0.32, ease: [0.22, 0.61, 0.36, 1] }}
              className={`pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-2xl px-4 py-3 shadow-lift ${TONE_STYLE[toast.tone]}`}
            >
              <Icon size={17} className="mt-0.5 shrink-0" />
              <p className="flex-1 text-sm leading-snug">{toast.message}</p>
              <button
                type="button"
                onClick={() => dismiss(toast.id)}
                aria-label="Dismiss"
                className="shrink-0 rounded-full p-0.5 opacity-70 transition hover:opacity-100"
              >
                <X size={15} />
              </button>
            </motion.div>
          );
        })}
      </AnimatePresence>
    </div>
  );
}
