// frontend/src/pages/dashboard/Rentals.tsx
import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';

import EmptyState from '@/components/ui/EmptyState';
import ErrorNote from '@/components/ui/ErrorNote';
import { RowSkeleton } from '@/components/ui/Skeleton';
import { useToast } from '@/context/ToastContext';
import { api, peso } from '@/lib/api';
import type { Rental } from '@/types';

const STATUS_STYLE: Record<Rental['status'], string> = {
  requested: 'bg-harvest/20 text-bark',
  approved: 'bg-sprout text-canopy',
  active: 'bg-canopy text-husk',
  returned: 'bg-soil/10 text-soil/65',
  cancelled: 'bg-soil/10 text-soil/50',
};

function formatDate(value: unknown): string {
  if (!value) return '';
  const raw = typeof value === 'object' && value !== null && '_seconds' in value
    ? new Date((value as { _seconds: number })._seconds * 1000)
    : new Date(value as string);
  return Number.isNaN(raw.getTime())
    ? ''
    : raw.toLocaleDateString('en-PH', { day: 'numeric', month: 'short', year: 'numeric' });
}

export default function Rentals() {
  const { notify } = useToast();
  const [rentals, setRentals] = useState<Rental[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    try {
      setRentals(await api.get<Rental[]>('/rentals/mine'));
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Could not load your bookings');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  const cancel = async (id: string) => {
    try {
      await api.patch(`/rentals/${id}/status`, { status: 'cancelled' });
      notify('Booking cancelled');
      await load();
    } catch (err) {
      notify(err instanceof Error ? err.message : 'Could not cancel that booking', 'error');
    }
  };

  if (loading) {
    return (
      <div className="shell section-y">
        <h1 className="text-section text-canopy">Your bookings</h1>
        <div className="mt-10">
          <RowSkeleton />
        </div>
      </div>
    );
  }

  return (
    <div className="shell section-y">
      <h1 className="text-section text-canopy">Your bookings</h1>
      <ErrorNote message={error} />

      {rentals.length === 0 ? (
        <div className="mt-10">
          <EmptyState
            title="No equipment booked"
            hint="Book a tractor or tiller for the days you need it, instead of buying one outright."
            action={<Link to="/equipment" className="btn-primary">Browse equipment</Link>}
          />
        </div>
      ) : (
        <ul className="mt-10 space-y-3">
          {rentals.map((rental) => (
            <li key={rental.id} className="card flex flex-wrap items-center gap-4 p-5">
              <div className="min-w-0 flex-1">
                <h2 className="font-display text-xl text-soil">{rental.equipmentName}</h2>
                <p className="mt-1 text-sm text-soil/60">
                  {formatDate(rental.startDate)} – {formatDate(rental.endDate)} · {rental.days} day
                  {rental.days === 1 ? '' : 's'} · {peso(rental.totalCost)}
                </p>
              </div>
              <span className={`chip ${STATUS_STYLE[rental.status]}`}>{rental.status}</span>
              {(rental.status === 'requested' || rental.status === 'approved') && (
                <button type="button" onClick={() => cancel(rental.id)} className="btn-quiet">
                  Cancel
                </button>
              )}
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
