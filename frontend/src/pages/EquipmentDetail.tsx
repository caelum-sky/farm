// frontend/src/pages/EquipmentDetail.tsx
import { useCallback, useEffect, useMemo, useState, type FormEvent } from 'react';
import { Link, useParams } from 'react-router-dom';
import { CalendarDays, ChevronLeft, Flag, Tractor } from 'lucide-react';

import ErrorNote from '@/components/ui/ErrorNote';
import ReportDialog from '@/components/ui/ReportDialog';
import ReviewList from '@/components/ui/ReviewList';
import { Skeleton } from '@/components/ui/Skeleton';
import { useAuth } from '@/context/AuthContext';
import { useToast } from '@/context/ToastContext';
import { api, peso } from '@/lib/api';
import type { Equipment } from '@/types';

interface BookedRange {
  startDate: string;
  endDate: string;
}

function daysBetween(start: string, end: string): number {
  if (!start || !end) return 0;
  const ms = new Date(end).getTime() - new Date(start).getTime();
  return ms > 0 ? Math.max(1, Math.ceil(ms / 86_400_000)) : 0;
}

function formatDay(value: string): string {
  return new Date(value).toLocaleDateString('en-PH', { day: 'numeric', month: 'short' });
}

export default function EquipmentDetail() {
  const { id = '' } = useParams();
  const { profile } = useAuth();
  const { notify } = useToast();

  const [item, setItem] = useState<Equipment | null>(null);
  const [booked, setBooked] = useState<BookedRange[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [booking, setBooking] = useState(false);
  const [bookingError, setBookingError] = useState('');
  const [requestSent, setRequestSent] = useState(false);
  const [reporting, setReporting] = useState(false);

  const loadAvailability = useCallback(async () => {
    try {
      const result = await api.get<{ booked: BookedRange[] }>(`/equipment/${id}/availability`);
      setBooked(result.booked);
    } catch {
      // Availability is advisory — the server still rejects a genuine clash.
    }
  }, [id]);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      setLoading(true);
      try {
        const result = await api.get<Equipment>(`/equipment/${id}`);
        if (!cancelled) setItem(result);
        await loadAvailability();
      } catch (err) {
        if (!cancelled) setError(err instanceof Error ? err.message : 'Could not load this listing');
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [id, loadAvailability]);

  const days = useMemo(() => daysBetween(startDate, endDate), [startDate, endDate]);
  const estimate = item ? days * item.price : 0;

  // Warn about a clash before the request goes out, rather than after a 409.
  const clashes = useMemo(() => {
    if (!startDate || !endDate) return false;
    const start = new Date(startDate).getTime();
    const end = new Date(endDate).getTime();
    return booked.some((range) => {
      const rangeStart = new Date(range.startDate).getTime();
      const rangeEnd = new Date(range.endDate).getTime();
      return start < rangeEnd && end > rangeStart;
    });
  }, [startDate, endDate, booked]);

  const book = async (event: FormEvent) => {
    event.preventDefault();
    if (!item || days <= 0 || clashes) return;
    setBooking(true);
    setBookingError('');
    try {
      await api.post('/rentals', {
        equipmentId: item.id,
        startDate: new Date(startDate).toISOString(),
        endDate: new Date(endDate).toISOString(),
      });
      setRequestSent(true);
      notify('Booking requested. The owner will confirm availability.');
      await loadAvailability();
    } catch (err) {
      setBookingError(err instanceof Error ? err.message : 'Could not book those dates');
    } finally {
      setBooking(false);
    }
  };

  if (loading) {
    return (
      <div className="shell section-y">
        <div className="grid gap-10 lg:grid-cols-2">
          <Skeleton className="aspect-[4/3] rounded-pod" />
          <div className="space-y-4">
            <Skeleton className="h-4 w-24" />
            <Skeleton className="h-12 w-3/4" />
            <Skeleton className="h-40 w-full" />
          </div>
        </div>
      </div>
    );
  }

  if (error || !item) {
    return (
      <div className="shell section-y">
        <ErrorNote message={error || 'That machine is no longer listed.'} />
        <Link to="/equipment" className="btn-outline mt-6">
          Back to equipment
        </Link>
      </div>
    );
  }

  const forRent = item.listingType === 'rent';
  const today = new Date().toISOString().slice(0, 10);

  return (
    <div className="shell section-y">
      <Link to="/equipment" className="btn-quiet -ml-4 mb-6">
        <ChevronLeft size={16} />
        Back to equipment
      </Link>

      <div className="grid gap-10 lg:grid-cols-2 lg:gap-14">
        <div className="overflow-hidden rounded-pod bg-husk2 shadow-crate">
          {item.images[0] ? (
            <img src={item.images[0]} alt={item.name} className="aspect-[4/3] w-full object-cover" />
          ) : (
            <div className="flex aspect-[4/3] items-center justify-center text-bark/25">
              <Tractor size={80} strokeWidth={1.2} />
            </div>
          )}
        </div>

        <div>
          <p className="text-sm capitalize text-soil/55">
            {item.category}
            {item.ownerName && <span className="normal-case"> · listed by {item.ownerName}</span>}
          </p>
          <h1 className="mt-2 font-display text-4xl text-soil sm:text-5xl">{item.name}</h1>

          <p className="mt-6 font-display text-3xl text-canopy">
            {peso(item.price)}
            {forRent && <span className="ml-1 font-sans text-base text-soil/60">per day</span>}
          </p>

          <p className="mt-6 leading-relaxed text-soil/75">
            {item.description || 'No description was added for this machine.'}
          </p>

          {forRent && booked.length > 0 && (
            <div className="mt-6 rounded-2xl bg-husk2 p-4">
              <h2 className="flex items-center gap-2 text-sm font-medium text-soil">
                <CalendarDays size={15} />
                Already booked
              </h2>
              <ul className="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-soil/65">
                {booked.slice(0, 6).map((range) => (
                  <li key={`${range.startDate}-${range.endDate}`}>
                    {formatDay(range.startDate)} – {formatDay(range.endDate)}
                  </li>
                ))}
              </ul>
            </div>
          )}

          {forRent ? (
            requestSent ? (
              <div className="card mt-8 p-6">
                <h2 className="font-display text-xl text-canopy">Booking requested</h2>
                <p className="mt-2 text-soil/75">
                  The owner reviews requests and confirms availability. Track it under your bookings.
                </p>
                <Link to="/app/rentals" className="btn-primary mt-5">
                  View my bookings
                </Link>
              </div>
            ) : (
              <form onSubmit={book} className="card mt-8 space-y-4 p-6">
                <h2 className="font-display text-xl text-soil">Book these dates</h2>

                <div className="grid gap-4 sm:grid-cols-2">
                  <div>
                    <label htmlFor="start-date" className="mb-1.5 block text-sm text-soil/70">
                      First day
                    </label>
                    <input
                      id="start-date"
                      type="date"
                      required
                      min={today}
                      value={startDate}
                      onChange={(e) => setStartDate(e.target.value)}
                      className="field"
                    />
                  </div>
                  <div>
                    <label htmlFor="end-date" className="mb-1.5 block text-sm text-soil/70">
                      Return day
                    </label>
                    <input
                      id="end-date"
                      type="date"
                      required
                      min={startDate || today}
                      value={endDate}
                      onChange={(e) => setEndDate(e.target.value)}
                      className="field"
                    />
                  </div>
                </div>

                {days > 0 && !clashes && (
                  <p className="text-sm text-soil/70">
                    {days} day{days === 1 ? '' : 's'} · {peso(estimate)} estimated
                  </p>
                )}

                {clashes && (
                  <ErrorNote message="Those dates overlap an existing booking. Pick another range." />
                )}

                <ErrorNote message={bookingError} />

                {profile ? (
                  <button
                    type="submit"
                    disabled={booking || days <= 0 || clashes}
                    className="btn-primary w-full sm:w-auto"
                  >
                    {booking ? 'Requesting…' : 'Request booking'}
                  </button>
                ) : (
                  <Link to="/login" className="btn-primary w-full sm:w-auto">
                    Sign in to book
                  </Link>
                )}
              </form>
            )
          ) : (
            <div className="card mt-8 p-6">
              <h2 className="font-display text-xl text-soil">Listed for sale</h2>
              <p className="mt-2 text-soil/75">
                Message the cooperative to arrange payment and pickup. Sales aren't processed
                through the cart.
              </p>
            </div>
          )}

          {profile && (
            <button type="button" onClick={() => setReporting(true)} className="btn-quiet mt-5">
              <Flag size={15} />
              Report listing
            </button>
          )}
        </div>
      </div>

      <div className="mt-16 max-w-3xl sm:mt-20">
        <ReviewList targetType="equipment" targetId={item.id} />
      </div>

      {reporting && (
        <ReportDialog targetType="equipment" targetId={item.id} onClose={() => setReporting(false)} />
      )}
    </div>
  );
}
