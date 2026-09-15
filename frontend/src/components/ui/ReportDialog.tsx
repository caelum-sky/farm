// frontend/src/components/ui/ReportDialog.tsx
import { useState, type FormEvent } from 'react';

import ErrorNote from '@/components/ui/ErrorNote';
import Modal from '@/components/ui/Modal';
import { useToast } from '@/context/ToastContext';
import { api } from '@/lib/api';

interface ReportDialogProps {
  targetType: 'product' | 'equipment' | 'user';
  targetId: string;
  onClose: () => void;
}

const REASONS = [
  'Misleading description',
  'Wrong or unsafe product',
  'Price gouging',
  'Seller never delivered',
  'Spam or duplicate listing',
  'Something else',
];

export default function ReportDialog({ targetType, targetId, onClose }: ReportDialogProps) {
  const { notify } = useToast();
  const [reason, setReason] = useState(REASONS[0]);
  const [details, setDetails] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setSubmitting(true);
    setError('');
    try {
      await api.post('/reports', { targetType, targetId, reason, details });
      notify('Report sent. An admin reviews the queue daily.');
      onClose();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Could not send the report');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <Modal title="Report this listing" onClose={onClose}>
      <form onSubmit={submit} className="space-y-4">
        <div>
          <label htmlFor="report-reason" className="mb-1.5 block text-sm text-soil/70">
            What's wrong?
          </label>
          <select
            id="report-reason"
            value={reason}
            onChange={(e) => setReason(e.target.value)}
            className="field"
          >
            {REASONS.map((option) => (
              <option key={option} value={option}>
                {option}
              </option>
            ))}
          </select>
        </div>

        <div>
          <label htmlFor="report-details" className="mb-1.5 block text-sm text-soil/70">
            Anything the reviewer should know
          </label>
          <textarea
            id="report-details"
            rows={4}
            value={details}
            onChange={(e) => setDetails(e.target.value)}
            className="field resize-none"
            placeholder="Optional, but it helps."
          />
        </div>

        <ErrorNote message={error} />

        <div className="flex flex-wrap gap-3 pt-1">
          <button type="submit" disabled={submitting} className="btn-primary">
            {submitting ? 'Sending…' : 'Send report'}
          </button>
          <button type="button" onClick={onClose} className="btn-quiet">
            Cancel
          </button>
        </div>
      </form>
    </Modal>
  );
}
