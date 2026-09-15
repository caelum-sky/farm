// frontend/src/components/ui/EmptyState.tsx
import type { ReactNode } from 'react';

interface EmptyStateProps {
  title: string;
  /** What to do next — an empty screen is an invitation, not a dead end. */
  hint: string;
  action?: ReactNode;
}

export default function EmptyState({ title, hint, action }: EmptyStateProps) {
  return (
    <div className="card flex flex-col items-center gap-3 px-8 py-16 text-center">
      <h3 className="font-display text-2xl text-soil">{title}</h3>
      <p className="max-w-md text-soil/65">{hint}</p>
      {action && <div className="mt-3">{action}</div>}
    </div>
  );
}
