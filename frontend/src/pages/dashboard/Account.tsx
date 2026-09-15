// frontend/src/pages/dashboard/Account.tsx
import { useEffect, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';

import ErrorNote from '@/components/ui/ErrorNote';
import { useAuth } from '@/context/AuthContext';
import { api } from '@/lib/api';

const ROLE_LABEL: Record<string, string> = {
  buyer: 'Buyer',
  farmer: 'Farmer',
  cooperative: 'Cooperative',
  admin: 'Administrator',
};

export default function Account() {
  const { profile, refreshProfile } = useAuth();
  const [displayName, setDisplayName] = useState('');
  const [phone, setPhone] = useState('');
  const [orgName, setOrgName] = useState('');
  const [saved, setSaved] = useState(false);
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (profile) {
      setDisplayName(profile.displayName);
      setPhone(profile.phone || '');
      setOrgName(profile.orgName || '');
    }
  }, [profile]);

  const save = async (event: FormEvent) => {
    event.preventDefault();
    setBusy(true);
    setError('');
    setSaved(false);
    try {
      await api.patch('/auth/me', {
        displayName,
        phone: phone || undefined,
        orgName: orgName || undefined,
      });
      await refreshProfile();
      setSaved(true);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Could not save your profile');
    } finally {
      setBusy(false);
    }
  };

  if (!profile) return null;

  return (
    <div className="shell section-y">
      <h1 className="text-section text-canopy">Your account</h1>

      <div className="mt-10 grid gap-8 lg:grid-cols-[1fr_320px]">
        <form onSubmit={save} className="card space-y-4 p-7">
          <div>
            <label htmlFor="acc-name" className="mb-1.5 block text-sm text-soil/70">Name</label>
            <input id="acc-name" required value={displayName} onChange={(e) => setDisplayName(e.target.value)} className="field" />
          </div>

          <div>
            <label htmlFor="acc-phone" className="mb-1.5 block text-sm text-soil/70">Mobile number</label>
            <input id="acc-phone" type="tel" value={phone} onChange={(e) => setPhone(e.target.value)} className="field" />
          </div>

          {profile.role === 'cooperative' && (
            <div>
              <label htmlFor="acc-org" className="mb-1.5 block text-sm text-soil/70">Cooperative name</label>
              <input id="acc-org" value={orgName} onChange={(e) => setOrgName(e.target.value)} className="field" />
            </div>
          )}

          <div>
            <span className="mb-1.5 block text-sm text-soil/70">Email</span>
            <p className="rounded-2xl bg-husk2 px-4 py-3 text-soil/70">{profile.email}</p>
          </div>

          <ErrorNote message={error} />
          {saved && <p className="text-sm text-canopy" role="status">Profile saved.</p>}

          <button type="submit" disabled={busy} className="btn-primary">
            {busy ? 'Saving…' : 'Save changes'}
          </button>
        </form>

        <aside className="card h-fit p-7">
          <h2 className="font-display text-xl text-soil">Account type</h2>
          <p className="mt-2 text-2xl font-display text-canopy">{ROLE_LABEL[profile.role]}</p>
          <p className="mt-3 text-sm leading-relaxed text-soil/65">
            Roles are set by an administrator. If you started as a buyer and now run a farm,
            message support to have it changed.
          </p>

          <div className="mt-6 space-y-2 text-sm">
            <Link to="/orders" className="block link-underline">Your orders</Link>
            <Link to="/rentals" className="block link-underline">Your bookings</Link>
            {(profile.role === 'farmer' || profile.role === 'cooperative') && (
              <Link to="/dashboard" className="block link-underline">Seller dashboard</Link>
            )}
          </div>
        </aside>
      </div>
    </div>
  );
}
