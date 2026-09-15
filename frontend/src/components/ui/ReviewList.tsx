// frontend/src/components/ui/ReviewList.tsx
import { useCallback, useEffect, useState, type FormEvent } from 'react';
import { Star } from 'lucide-react';

import ErrorNote from '@/components/ui/ErrorNote';
import { useAuth } from '@/context/AuthContext';
import { api, query } from '@/lib/api';

interface Review {
  id: string;
  authorId: string;
  rating: number;
  comment: string;
}

interface ReviewListProps {
  targetType: 'product' | 'equipment' | 'user';
  targetId: string;
}

export default function ReviewList({ targetType, targetId }: ReviewListProps) {
  const { profile } = useAuth();
  const [reviews, setReviews] = useState<Review[]>([]);
  const [rating, setRating] = useState(5);
  const [comment, setComment] = useState('');
  const [error, setError] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const load = useCallback(async () => {
    try {
      setReviews(await api.get<Review[]>(`/reviews${query({ targetType, targetId })}`));
    } catch {
      // A missing review list shouldn't break the page around it.
    }
  }, [targetType, targetId]);

  useEffect(() => {
    void load();
  }, [load]);

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setSubmitting(true);
    setError('');
    try {
      await api.post('/reviews', { targetType, targetId, rating, comment });
      setComment('');
      setRating(5);
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Could not post your review');
    } finally {
      setSubmitting(false);
    }
  };

  const average = reviews.length
    ? reviews.reduce((sum, r) => sum + r.rating, 0) / reviews.length
    : 0;

  return (
    <section>
      <div className="flex items-baseline gap-4">
        <h2 className="font-display text-2xl text-soil">Reviews</h2>
        {reviews.length > 0 && (
          <p className="text-sm text-soil/60">
            {average.toFixed(1)} out of 5 · {reviews.length} review{reviews.length === 1 ? '' : 's'}
          </p>
        )}
      </div>

      {reviews.length === 0 ? (
        <p className="mt-4 text-soil/60">No reviews yet. Be the first to say how it went.</p>
      ) : (
        <ul className="mt-6 space-y-4">
          {reviews.map((review) => (
            <li key={review.id} className="card p-5">
              <div className="flex gap-0.5" aria-label={`${review.rating} out of 5`}>
                {Array.from({ length: 5 }).map((_, i) => (
                  <Star
                    key={i}
                    size={15}
                    className={i < review.rating ? 'fill-harvest text-harvest' : 'text-soil/20'}
                  />
                ))}
              </div>
              {review.comment && <p className="mt-2.5 text-soil/80">{review.comment}</p>}
            </li>
          ))}
        </ul>
      )}

      {profile && (
        <form onSubmit={submit} className="card mt-8 space-y-4 p-6">
          <div>
            <span className="mb-2 block text-sm text-soil/70">Your rating</span>
            <div className="flex gap-1">
              {Array.from({ length: 5 }).map((_, i) => (
                <button
                  key={i}
                  type="button"
                  onClick={() => setRating(i + 1)}
                  aria-label={`${i + 1} star${i === 0 ? '' : 's'}`}
                  className="transition duration-300 ease-grow hover:scale-125"
                >
                  <Star
                    size={24}
                    className={i < rating ? 'fill-harvest text-harvest' : 'text-soil/25'}
                  />
                </button>
              ))}
            </div>
          </div>

          <div>
            <label htmlFor="review-comment" className="mb-1.5 block text-sm text-soil/70">
              How was it?
            </label>
            <textarea
              id="review-comment"
              rows={3}
              value={comment}
              onChange={(e) => setComment(e.target.value)}
              className="field resize-none"
              placeholder="Freshness, packing, how the handover went."
            />
          </div>

          <ErrorNote message={error} />

          <button type="submit" disabled={submitting} className="btn-primary">
            {submitting ? 'Posting…' : 'Post review'}
          </button>
        </form>
      )}
    </section>
  );
}
