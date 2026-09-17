// frontend/src/pages/admin/AdminUsers.tsx
import { useCallback, useEffect, useMemo, useState } from 'react';
import { Ban, Search, ShieldCheck, Trash2 } from 'lucide-react';

import ErrorNote from '@/components/ui/ErrorNote';
import Modal from '@/components/ui/Modal';
import { RowSkeleton } from '@/components/ui/Skeleton';
import { useAuth } from '@/context/AuthContext';
import { useToast } from '@/context/ToastContext';
import { useDebounced } from '@/hooks/useDebounced';
import { api, query } from '@/lib/api';
import type { Role, UserProfile } from '@/types';

interface UserListResponse {
  users: UserProfile[];
  nextCursor: string | null;
}

const ROLES: Role[] = ['buyer', 'farmer', 'cooperative', 'admin'];

export default function AdminUsers() {
  const { profile: me } = useAuth();
  const { notify } = useToast();

  const [users, setUsers] = useState<UserProfile[]>([]);
  const [roleFilter, setRoleFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [searchInput, setSearchInput] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [pending, setPending] = useState<string | null>(null);
  const [confirmDelete, setConfirmDelete] = useState<UserProfile | null>(null);

  const search = useDebounced(searchInput, 200);

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const result = await api.get<UserListResponse>(
        `/admin/users${query({ role: roleFilter, status: statusFilter, limit: 100 })}`
      );
      setUsers(result.users);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Could not load users');
    } finally {
      setLoading(false);
    }
  }, [roleFilter, statusFilter]);

  useEffect(() => {
    void load();
  }, [load]);

  const act = async (uid: string, fn: () => Promise<unknown>, success: string) => {
    setPending(uid);
    try {
      await fn();
      notify(success);
      await load();
    } catch (err) {
      notify(err instanceof Error ? err.message : 'That action did not go through', 'error');
    } finally {
      setPending(null);
    }
  };

  const setRole = (user: UserProfile, role: Role) =>
    act(
      user.id,
      () => api.patch(`/admin/users/${user.id}`, { role }),
      `${user.displayName} is now a ${role}`
    );

  const toggleBan = (user: UserProfile) =>
    act(
      user.id,
      () => api.post(`/admin/users/${user.id}/ban`, { banned: user.status !== 'banned' }),
      user.status === 'banned' ? `${user.displayName} unbanned` : `${user.displayName} banned`
    );

  const remove = (user: UserProfile) =>
    act(
      user.id,
      async () => {
        await api.delete(`/admin/users/${user.id}`);
        setConfirmDelete(null);
      },
      `${user.displayName} deleted`
    );

  const visible = useMemo(() => {
    if (!search) return users;
    const needle = search.toLowerCase();
    return users.filter(
      (user) =>
        user.displayName?.toLowerCase().includes(needle) ||
        user.email?.toLowerCase().includes(needle) ||
        user.orgName?.toLowerCase().includes(needle)
    );
  }, [users, search]);

  return (
    <div className="mx-auto max-w-6xl">
      <header>
        <h1 className="text-section text-canopy">Users</h1>
        <p className="mt-2 text-soil/70">
          Change roles, ban, or delete accounts. Bans take effect immediately and end active
          sessions.
        </p>
      </header>

      <div className="mt-8 flex flex-wrap gap-3">
        <div className="relative min-w-0 flex-1 sm:max-w-xs">
          <Search size={17} className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-soil/40" />
          <input
            type="search"
            value={searchInput}
            onChange={(e) => setSearchInput(e.target.value)}
            placeholder="Search name, email, coop"
            aria-label="Search users"
            className="field pl-11"
          />
        </div>

        <select
          value={roleFilter}
          onChange={(e) => setRoleFilter(e.target.value)}
          aria-label="Filter by role"
          className="field w-auto capitalize"
        >
          <option value="">All roles</option>
          {ROLES.map((role) => (
            <option key={role} value={role}>
              {role}
            </option>
          ))}
        </select>

        <select
          value={statusFilter}
          onChange={(e) => setStatusFilter(e.target.value)}
          aria-label="Filter by status"
          className="field w-auto"
        >
          <option value="">All statuses</option>
          <option value="active">Active</option>
          <option value="banned">Banned</option>
        </select>
      </div>

      <ErrorNote message={error} />

      <div className="mt-6">
        {loading ? (
          <RowSkeleton count={5} />
        ) : visible.length === 0 ? (
          <p className="py-12 text-center text-soil/60">No members match those filters.</p>
        ) : (
          /* Cards rather than a table: a four-column table on a phone either
             scrolls sideways or crushes the action buttons into unusable
             targets, and this is the screen admins use most on mobile. */
          <ul className="space-y-3">
            {visible.map((user) => {
              const isMe = user.id === me?.id;
              const busy = pending === user.id;

              return (
                <li key={user.id} className="card p-4 sm:p-5">
                  <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="min-w-0">
                      <p className="truncate font-medium text-soil">
                        {user.displayName}
                        {isMe && <span className="ml-2 text-xs text-soil/50">you</span>}
                      </p>
                      <p className="truncate text-sm text-soil/55">{user.email}</p>
                      {user.orgName && (
                        <p className="truncate text-sm text-soil/55">{user.orgName}</p>
                      )}
                    </div>

                    <span
                      className={`chip shrink-0 ${
                        user.status === 'banned' ? 'bg-clay/15 text-clay' : 'bg-sprout text-canopy'
                      }`}
                    >
                      {user.status}
                    </span>
                  </div>

                  <div className="mt-4 flex flex-wrap items-center gap-2">
                    <label className="sr-only" htmlFor={`role-${user.id}`}>
                      Role for {user.displayName}
                    </label>
                    <select
                      id={`role-${user.id}`}
                      value={user.role}
                      disabled={isMe || busy}
                      onChange={(e) => setRole(user, e.target.value as Role)}
                      className="rounded-xl border border-soil/15 bg-husk px-3 py-2 text-sm capitalize disabled:opacity-50"
                    >
                      {ROLES.map((role) => (
                        <option key={role} value={role}>
                          {role}
                        </option>
                      ))}
                    </select>

                    <button
                      type="button"
                      disabled={isMe || busy}
                      onClick={() => toggleBan(user)}
                      className="btn-quiet disabled:opacity-40"
                    >
                      {user.status === 'banned' ? (
                        <>
                          <ShieldCheck size={15} />
                          Unban
                        </>
                      ) : (
                        <>
                          <Ban size={15} />
                          Ban
                        </>
                      )}
                    </button>

                    <button
                      type="button"
                      disabled={isMe || busy}
                      onClick={() => setConfirmDelete(user)}
                      aria-label={`Delete ${user.displayName}`}
                      className="ml-auto grid h-11 w-11 place-items-center rounded-full text-soil/50 transition hover:bg-soil/5 hover:text-clay disabled:opacity-40"
                    >
                      <Trash2 size={17} />
                    </button>
                  </div>
                </li>
              );
            })}
          </ul>
        )}
      </div>

      {confirmDelete && (
        <Modal title="Delete this account?" onClose={() => setConfirmDelete(null)}>
          <p className="text-soil/75">
            {confirmDelete.displayName}'s sign-in is removed and their listings are taken down.
            Orders and bookings stay on record. This can't be undone — ban instead if you may
            want them back.
          </p>
          <div className="mt-6 flex flex-wrap gap-3">
            <button type="button" onClick={() => remove(confirmDelete)} className="btn-danger">
              Delete permanently
            </button>
            <button type="button" onClick={() => setConfirmDelete(null)} className="btn-quiet">
              Keep account
            </button>
          </div>
        </Modal>
      )}
    </div>
  );
}
