// frontend/src/pages/admin/AdminReports.tsx
import { useCallback, useEffect, useState } from 'react';

import ErrorNote from '@/components/ui/ErrorNote';
import { RowSkeleton } from '@/components/ui/Skeleton';
import { api, query } from '@/lib/api';
import type { Report } from '@/types';

const FILTERS: Array<[string, string]> = [
  ['open', 'Open'],
  ['reviewed', 'Actioned'],
  ['dismissed', 'Dismissed'],
  ['all', 'Everything'],
];

export default function AdminReports() {
  const [status, setStatus] = useState('open');
  const [reports, setReports] = useState<Report[]>([]);
  const [notes, setNotes] = useState<Record<string, string>>({});
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      setReports(await api.get<Report[]>(`/admin/reports${query({ status, limit: 100 })}`));
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Could not load reports');
    } finally {
      setLoading(false);
    }
  }, [status]);

  useEffect(() => {
    void load();
  }, [load]);

  const resolve = async (report: Report, next: 'reviewed' | 'dismissed') => {
    setError('');
    try {
      await api.patch(`/admin/reports/${report.id}`, {
        status: next,
        resolutionNote: notes[report.id] || '',
      });
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Could not resolve that report');
    }
  };

  const takeDown = async (report: Report) => {
    setError('');
    try {
      if (report.targetType === 'user') {
        await api.post(`/admin/users/${report.targetId}/ban`, { banned: true });
      } else {
        const collection = report.targetType === 'product' ? 'products' : 'equipment';
        await api.patch(`/admin/${collection}/${report.targetId}/status`, { status: 'removed' });
      }
      await resolve(report, 'reviewed');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Could not action that report');
    }
  };

  return (
    <div className="mx-auto max-w-6xl">
      <header>
        <h1 className="text-section text-canopy">Reports</h1>
        <p className="mt-2 text-soil/70">Flags from members, waiting on a decision.</p>
      </header>

      <div className="mt-8 scroll-row">
        {FILTERS.map(([value, label]) => (
          <button
            key={value}
            type="button"
            onClick={() => setStatus(value)}
            aria-pressed={status === value}
            className={`pill ${status === value ? 'pill-on' : 'pill-off'}`}
          >
            {label}
          </button>
        ))}
      </div>

      <ErrorNote message={error} />

      {loading ? (
        <RowSkeleton />
      ) : reports.length === 0 ? (
        <p className="py-12 text-center text-soil/60">
          Queue is clear. New reports from members land here.
        </p>
      ) : (
        <ul className="mt-6 space-y-4">
          {reports.map((report) => (
            <li key={report.id} className="card p-6">
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <h2 className="font-display text-xl text-soil">{report.reason}</h2>
                  <p className="mt-1 text-sm text-soil/55">
                    {report.targetType} · {report.targetId.slice(0, 10)}… · reported by{' '}
                    {report.reporterId.slice(0, 8)}…
                  </p>
                </div>
                <span
                  className={`chip ${
                    report.status === 'open' ? 'bg-harvest/20 text-bark' : 'bg-soil/10 text-soil/60'
                  }`}
                >
                  {report.status}
                </span>
              </div>

              {report.details && <p className="mt-4 text-soil/75">{report.details}</p>}

              {report.status === 'open' ? (
                <div className="mt-5 space-y-3">
                  <label className="sr-only" htmlFor={`note-${report.id}`}>
                    Resolution note
                  </label>
                  <input
                    id={`note-${report.id}`}
                    value={notes[report.id] || ''}
                    onChange={(e) => setNotes({ ...notes, [report.id]: e.target.value })}
                    placeholder="What you found and what you did"
                    className="field"
                  />
                  <div className="flex flex-wrap gap-2">
                    <button
                      type="button"
                      onClick={() => takeDown(report)}
                      className="rounded-full bg-clay px-5 py-2.5 text-sm font-medium text-husk transition hover:bg-[#763019]"
                    >
                      {report.targetType === 'user' ? 'Ban the member' : 'Take the listing down'}
                    </button>
                    <button
                      type="button"
                      onClick={() => resolve(report, 'reviewed')}
                      className="rounded-full bg-canopy px-5 py-2.5 text-sm text-husk transition hover:bg-leaf"
                    >
                      Mark handled
                    </button>
                    <button type="button" onClick={() => resolve(report, 'dismissed')} className="btn-quiet">
                      Dismiss
                    </button>
                  </div>
                </div>
              ) : (
                report.resolutionNote && (
                  <p className="mt-4 rounded-2xl bg-husk2 px-4 py-3 text-sm text-soil/70">
                    {report.resolutionNote}
                  </p>
                )
              )}
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
