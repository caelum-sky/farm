import { useState, type ReactNode } from 'react';

interface ToggleGroupProps {
  /** Label for the toggle group */
  label?: string;
  /** Options for the toggle buttons */
  options: Array<{
    value: string | number;
    label: string;
    /** Optional icon to display */
    icon?: ReactNode;
  }>;
  /** The currently selected value */
  value: string | number;
  /** Callback when selection changes */
  onChange: (value: string | number) => void;
  /** Additional className for the container */
  className?: string;
  /** Whether to display as a grid (true) or flex (false) */
  asGrid?: boolean;
}

export default function ToggleGroup({
  label,
  options,
  value,
  onChange,
  className = '',
  asGrid = true,
}: ToggleGroupProps) {
  const [localValue, setLocalValue] = useState(value);

  // Sync with external value changes
  useEffect(() => {
    setLocalValue(value);
  }, [value]);

  const handleChange = (newValue: string | number) => {
    setLocalValue(newValue);
    onChange(newValue);
  };

  return (
    <div className={`${className} mb-4`.trim()}>
      {label && (
        <p className="mb-1.5 block text-sm text-soil/70 dark:text-husk/70">
          {label}
        </p>
      )}

      <div
        className={`
          inline-flex gap-2 ${asGrid ? 'grid grid-cols-2' : 'flex-wrap'}
          ${options.length > 2 && !asGrid ? 'flex-wrap' : ''}
        `.trim()}
      >
        {options.map((option) => (
          <button
            key={option.value}
            type="button"
            onClick={() => handleChange(option.value)}
            aria-pressed={localValue === option.value}
            className={`
              pill flex-1 justify-center items-center
              ${localValue === option.value ? 'pill-on' : 'pill-off'}
              transition-colors
              ${option.icon ? 'flex items-center gap-2' : ''}
            `}
          >
            {option.icon}
            {option.label}
          </button>
        ))}
      </div>
    </div>
  );
}