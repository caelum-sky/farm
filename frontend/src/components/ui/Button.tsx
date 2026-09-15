import { type ReactNode } from 'react';
import { Link } from 'react-router-dom';

interface ButtonProps {
  variant?: 'primary' | 'outline' | 'quiet' | 'danger';
  size?: 'default' | 'sm' | 'lg';
  children: ReactNode;
  onClick?: (event: React.MouseEvent<HTMLButtonElement>) => void;
  disabled?: boolean;
  ariaLabel?: string;
  className?: string;
  /** If provided, renders as a Link instead of a button */
  to?: string;
}

export default function Button({ variant = 'primary', size = 'default', children, onClick, disabled, ariaLabel, className, to }: ButtonProps) {
  const base = 'inline-flex items-center justify-center gap-2 rounded-full font-medium transition duration-300 ease-grow';
  const variantMap: Record<string, string> = {
    primary: 'bg-canopy px-6 py-3 text-husk shadow-crate hover:bg-leaf hover:shadow-lift dark:bg-leaf dark:hover:bg-leaf dark:hover:shadow-lift',
    outline: 'border border-canopy/45 bg-transparent px-6 py-3 text-canopy hover:border-canopy hover:bg-canopy/10 dark:border-canopy/60 dark:hover:border-canopy/60 dark:hover:bg-canopy/20 dark:text-leaf',
    quiet: 'min-h-[40px] px-4 py-2 text-sm text-soil/70 hover:bg-soil/5 hover:text-soil dark:text-husk/70 dark:hover:bg-husk/5 dark:hover:text-husk',
    danger: 'bg-clay px-6 py-3 text-husk hover:bg-[#763019] dark:hover:bg-[#763019]',
  };
  const sizeMap: Record<string, string> = {
    default: 'h-[44px] px-6',
    sm: 'h-[36px] px-4',
    lg: 'h-[52px] px-8',
  };

  const variantClass = variantMap[variant] ?? variantMap.primary;
  const sizeClass = sizeMap[size] ?? sizeMap.default;

  const CommonProps = {
    className: `${base} ${variantClass} ${sizeClass} ${className ?? ''}`,
    disabled,
    'aria-label': ariaLabel,
  };

  if (to) {
    return (
      <Link to={to} {...CommonProps as Record<string, unknown>}>
        {children}
      </Link>
    );
  }

  return (
    <button
      {...CommonProps as Record<string, unknown>}
      onClick={onClick}
    >
      {children}
    </button>
  );
}