// frontend/src/lib/api.ts
import { auth } from '@/firebase/config';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8080/api';
const ANALYTICS_URL =
  import.meta.env.VITE_ANALYTICS_URL || 'http://localhost:8090/api/analytics';

export class ApiError extends Error {
  status: number;
  details?: Record<string, string[]>;

  constructor(status: number, message: string, details?: Record<string, string[]>) {
    super(message);
    this.status = status;
    this.details = details;
  }
}

async function authHeader(): Promise<Record<string, string>> {
  const user = auth?.currentUser;
  if (!user) return {};
  const token = await user.getIdToken();
  return { Authorization: `Bearer ${token}` };
}

async function request<T>(base: string, path: string, init: RequestInit = {}): Promise<T> {
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    ...(await authHeader()),
    ...((init.headers as Record<string, string>) || {}),
  };

  let response: Response;
  try {
    response = await fetch(`${base}${path}`, { ...init, headers });
  } catch {
    // fetch only rejects on a network-level failure — offline, DNS, CORS, or a
    // server that never answered. "Failed to fetch" means nothing to a farmer,
    // so it never reaches the screen.
    throw new ApiError(0, "Can't reach FarmHub right now. Check your connection and try again.");
  }
  const isJson = response.headers.get('content-type')?.includes('application/json');
  const payload = isJson ? await response.json() : null;

  if (!response.ok) {
    throw new ApiError(
      response.status,
      payload?.error || `Request failed (${response.status})`,
      payload?.details
    );
  }

  return payload as T;
}

export const api = {
  get: <T>(path: string) => request<T>(API_URL, path),
  post: <T>(path: string, body?: unknown) =>
    request<T>(API_URL, path, { method: 'POST', body: JSON.stringify(body ?? {}) }),
  patch: <T>(path: string, body?: unknown) =>
    request<T>(API_URL, path, { method: 'PATCH', body: JSON.stringify(body ?? {}) }),
  delete: <T>(path: string) => request<T>(API_URL, path, { method: 'DELETE' }),
};

/** Separate base URL — analytics runs as its own Python service on Render. */
export const analyticsApi = {
  get: <T>(path: string) => request<T>(ANALYTICS_URL, path),
};

export function query(params: Record<string, string | number | undefined>): string {
  const entries = Object.entries(params).filter(([, v]) => v !== undefined && v !== '');
  if (!entries.length) return '';
  return `?${entries.map(([k, v]) => `${k}=${encodeURIComponent(String(v))}`).join('&')}`;
}

/**
 * Sentence case for slugs like "rice-and-grain" → "Rice and grain". Tailwind's
 * `capitalize` would render "Rice And Grain", which reads as a proper noun.
 */
export function humanize(slug: string): string {
  const words = slug.replace(/[-_]/g, ' ').trim();
  return words.charAt(0).toUpperCase() + words.slice(1);
}

export function peso(value: number): string {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    maximumFractionDigits: 2,
  }).format(value);
}
