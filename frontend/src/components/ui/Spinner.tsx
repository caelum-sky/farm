// frontend/src/components/ui/Spinner.tsx
export default function Spinner({ label = 'Loading' }: { label?: string }) {
  return (
    <div className="flex items-center justify-center gap-3 py-16 text-soil/60" role="status">
      <span className="h-5 w-5 animate-spin rounded-full border-2 border-canopy/25 border-t-canopy" />
      <span className="text-sm">{label}</span>
    </div>
  );
}
