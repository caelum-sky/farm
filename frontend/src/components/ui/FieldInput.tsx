interface FieldInputProps {
  label: string;
  id: string;
  /** The input element (input, textarea, select, etc.) */
  children: React.ReactElement;
  /** Optional helper text or error message */
  helperText?: string;
  /** Whether the field has an error (for aria-invalid and styling) */
  isError?: boolean;
  /** Additional className for the input element */
  inputClassName?: string;
  /** Optional aria-label if no visible label is needed */
  ariaLabel?: string;
  /** Whether to render as a child element (for custom handling) */
  asChild?: boolean;
}

export default function FieldInput({
  label,
  id,
  children,
  helperText,
  isError = false,
  inputClassName = '',
  ariaLabel,
  asChild = false,
}: FieldInputProps) {
  return (
    <>
      {!ariaLabel && label ? (
        <label htmlFor={id} className="mb-1.5 block text-sm text-soil/70 dark:text-husk/70">
          {label}
        </label>
      ) : null}

      {asChild ? (
        children
      ) : (
        React.cloneElement(children, {
          id,
          className: `field ${inputClassName} ${isError ? 'border-canopy/50 bg-canopy/5' : ''}`.trim(),
          'aria-invalid': isError || undefined,
          'aria-label': ariaLabel,
          ...(ariaLabel ? {} : { 'aria-describedby': helperText ? `${id}-helper` : undefined }),
        })
      )}

      {helperText ? (
        <p id={`${id}-helper`} className={`mt-1.5 text-sm ${isError ? 'text-canopy' : 'text-soil/60 dark:text-husk/60'}`}>
          {helperText}
        </p>
      ) : null}
    </>
  );
}