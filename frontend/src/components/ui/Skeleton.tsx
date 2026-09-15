// frontend/src/components/ui/Skeleton.tsx

/**
 * Placeholder blocks shaped like the content that's coming. Better than a
 * spinner here because the grid doesn't jump when the real cards land.
 */
export function Skeleton({ className = '' }: { className?: string }) {
  return <div className={`animate-pulse rounded-xl bg-soil/10 ${className}`} />;
}

export function ProductCardSkeleton() {
  return (
    <div className="card overflow-hidden">
      <Skeleton className="aspect-[4/3] rounded-none" />
      <div className="space-y-2.5 p-5">
        <Skeleton className="h-5 w-2/3" />
        <Skeleton className="h-4 w-1/2" />
      </div>
    </div>
  );
}

export function CardGridSkeleton({ count = 6 }: { count?: number }) {
  return (
    <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3" aria-hidden="true">
      {Array.from({ length: count }).map((_, i) => (
        <ProductCardSkeleton key={i} />
      ))}
    </div>
  );
}

export function RowSkeleton({ count = 4 }: { count?: number }) {
  return (
    <div className="space-y-3" aria-hidden="true">
      {Array.from({ length: count }).map((_, i) => (
        <Skeleton key={i} className="h-20 rounded-pod" />
      ))}
    </div>
  );
}
