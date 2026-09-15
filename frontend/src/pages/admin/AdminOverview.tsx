// frontend/src/pages/admin/AdminOverview.tsx
import { useEffect, useState } from 'react';
import {
  Bar,
  BarChart,
  CartesianGrid,
  Legend,
  Line,
  LineChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts';

import ErrorNote from '@/components/ui/ErrorNote';
import Spinner from '@/components/ui/Spinner';
import { analyticsApi, api, peso } from '@/lib/api';

interface Stats {
  users: number;
  activeProducts: number;
  activeEquipment: number;
  orders: number;
  openReports: number;
}

interface Overview {
  grossSales: number;
  rentalRevenue: number;
  orderCount: number;
  rentalCount: number;
  averageOrderValue: number;
}

interface TrendPoint {
  date: string;
  sales: number;
  rentals: number;
}

interface CategoryRow {
  subcategory: string;
  revenue: number;
}

export default function AdminOverview() {
  const [stats, setStats] = useState<Stats | null>(null);
  const [overview, setOverview] = useState<Overview | null>(null);
  const [trend, setTrend] = useState<TrendPoint[]>([]);
  const [categories, setCategories] = useState<CategoryRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [analyticsError, setAnalyticsError] = useState('');

  useEffect(() => {
    let cancelled = false;

    (async () => {
      try {
        const counters = await api.get<Stats>('/admin/stats');
        if (!cancelled) setStats(counters);
      } catch (err) {
        if (!cancelled) setError(err instanceof Error ? err.message : 'Could not load counters');
      } finally {
        if (!cancelled) setLoading(false);
      }

      // The Python service is separate — if it's cold or down, the counters above
      // still render rather than taking the whole page with them.
      try {
        const [summary, series, mix] = await Promise.all([
          analyticsApi.get<Overview>('/overview?days=30'),
          analyticsApi.get<{ series: TrendPoint[] }>('/sales-trend?days=30'),
          analyticsApi.get<{ rows: CategoryRow[] }>('/category-mix'),
        ]);
        if (!cancelled) {
          setOverview(summary);
          setTrend(series.series);
          setCategories(mix.rows.slice(0, 7));
        }
      } catch {
        if (!cancelled) {
          setAnalyticsError('Trend charts are unavailable — the analytics service did not respond.');
        }
      }
    })();

    return () => {
      cancelled = true;
    };
  }, []);

  if (loading) return <Spinner label="Loading dashboard" />;

  const cards = [
    { label: 'Members', value: stats?.users ?? 0 },
    { label: 'Produce and supplies listed', value: stats?.activeProducts ?? 0 },
    { label: 'Equipment listed', value: stats?.activeEquipment ?? 0 },
    { label: 'Orders all time', value: stats?.orders ?? 0 },
    { label: 'Reports waiting', value: stats?.openReports ?? 0, urgent: (stats?.openReports ?? 0) > 0 },
  ];

  return (
    <section className="space-y-10">
      <ErrorNote message={error} />

      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        {cards.map((card) => (
          <div
            key={card.label}
            className={`card p-6 ${card.urgent ? 'border-harvest/50 bg-harvest/10' : ''}`}
          >
            <p className="text-sm text-soil/60">{card.label}</p>
            <p className="mt-2 font-display text-4xl text-canopy">{card.value}</p>
          </div>
        ))}
      </div>

      {analyticsError ? (
        <p className="rounded-2xl bg-husk2 px-5 py-4 text-sm text-soil/70">{analyticsError}</p>
      ) : (
        <>
          {overview && (
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
              {[
                ['Produce sales, 30 days', peso(overview.grossSales)],
                ['Rental revenue, 30 days', peso(overview.rentalRevenue)],
                ['Average order', peso(overview.averageOrderValue)],
                ['Bookings, 30 days', String(overview.rentalCount)],
              ].map(([label, value]) => (
                <div key={label} className="card p-6">
                  <p className="text-sm text-soil/60">{label}</p>
                  <p className="mt-2 font-display text-2xl text-soil">{value}</p>
                </div>
              ))}
            </div>
          )}

          {trend.length > 0 && (
            <div className="card p-6">
              <h2 className="font-display text-xl text-soil">Sales and rentals, last 30 days</h2>
              <div className="mt-6 h-72 w-full">
                <ResponsiveContainer width="100%" height="100%">
                  <LineChart data={trend} margin={{ top: 4, right: 8, bottom: 4, left: -12 }}>
                    <CartesianGrid stroke="#1E2A1B" strokeOpacity={0.08} vertical={false} />
                    <XAxis
                      dataKey="date"
                      tick={{ fontSize: 11, fill: '#1E2A1B99' }}
                      tickFormatter={(value: string) => value.slice(5)}
                      interval="preserveStartEnd"
                    />
                    <YAxis tick={{ fontSize: 11, fill: '#1E2A1B99' }} />
                    <Tooltip
                      contentStyle={{
                        borderRadius: 16,
                        border: '1px solid rgba(30,42,27,0.1)',
                        fontSize: 13,
                      }}
                    />
                    <Legend wrapperStyle={{ fontSize: 13 }} />
                    <Line type="monotone" dataKey="sales" name="Produce" stroke="#2F5D3A" strokeWidth={2} dot={false} />
                    <Line type="monotone" dataKey="rentals" name="Rentals" stroke="#E0A21C" strokeWidth={2} dot={false} />
                  </LineChart>
                </ResponsiveContainer>
              </div>
            </div>
          )}

          {categories.length > 0 && (
            <div className="card p-6">
              <h2 className="font-display text-xl text-soil">Revenue by category</h2>
              <div className="mt-6 h-72 w-full">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={categories} margin={{ top: 4, right: 8, bottom: 4, left: -12 }}>
                    <CartesianGrid stroke="#1E2A1B" strokeOpacity={0.08} vertical={false} />
                    <XAxis dataKey="subcategory" tick={{ fontSize: 11, fill: '#1E2A1B99' }} />
                    <YAxis tick={{ fontSize: 11, fill: '#1E2A1B99' }} />
                    <Tooltip
                      contentStyle={{
                        borderRadius: 16,
                        border: '1px solid rgba(30,42,27,0.1)',
                        fontSize: 13,
                      }}
                    />
                    <Bar dataKey="revenue" name="Revenue" fill="#6FA042" radius={[8, 8, 0, 0]} />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </div>
          )}
        </>
      )}
    </section>
  );
}
