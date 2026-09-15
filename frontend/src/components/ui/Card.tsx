import { type ReactNode } from 'react';

interface CardProps {
  children: ReactNode;
  /** Additional className for the card */
  className?: string;
  /** Whether to add padding (defaults to true) */
  padded?: boolean;
  /** Padding amount when padded is true */
  p?: number | string; // Tailwind spacing (e.g., 4, 6, "px-6 py-4")
}

export default function Card({
  children,
  className = '',
  padded = true,
  p = 6,
}: CardProps) {
  const baseClass = 'card';
  const paddingClass = padded ? `p-${typeof p === 'number' ? p : p.split(' ')[0].replace('px-', '').replace('py-', '')}` : '';

  return (
    <div className={`${baseClass} ${className} ${paddingClass}`.trim()}>
      {children}
    </div>
  );
}