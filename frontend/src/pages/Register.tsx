// frontend/src/pages/Register.tsx
import { useState, type FormEvent } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { ShoppingBasket, Sprout, Users } from 'lucide-react';

import ErrorNote from '@/components/ui/ErrorNote';
import { useAuth } from '@/context/AuthContext';
import { ApiError } from '@/lib/api';
import type { Role } from '@/types';

type SignupRole = Exclude<Role, 'admin'>;

const ROLES: Array<{ value: SignupRole; icon: typeof Sprout; title: string; blurb: string }> = [
  {
    value: 'buyer',
    icon: ShoppingBasket,
    title: 'I buy produce',
    blurb: 'Order fruit, vegetables, and dairy from member farms.',
  },
  {
    value: 'farmer',
    icon: Sprout,
    title: 'I run a farm',
    blurb: 'Sell what you harvest, buy supplies, rent equipment by the day.',
  },
  {
    value: 'cooperative',
    icon: Users,
    title: 'I lead a cooperative',
    blurb: 'Stock supplies, hold shared machines, set member pricing.',
  },
];

export default function Register() {
  const { signUp } = useAuth();
  const navigate = useNavigate();

  const [role, setRole] = useState<SignupRole>('buyer');
  const [displayName, setDisplayName] = useState('');
  const [orgName, setOrgName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setBusy(true);
    setError('');
    try {
      await signUp({
        email,
        password,
        displayName,
        role,
        phone: phone || undefined,
        orgName: role === 'cooperative' ? orgName : undefined,
      });
      navigate('/app', { replace: true });
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else if (err instanceof Error && err.message.includes('email-already-in-use')) {
        setError('An account already uses that email. Sign in instead.');
      } else if (err instanceof Error && err.message.includes('weak-password')) {
        setError('Use at least 6 characters for the password.');
      } else {
        setError('Could not create the account. Check the details and try again.');
      }
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="shell section-y">
      <div className="mx-auto max-w-2xl">
        <h1 className="text-section text-canopy">Join FarmHub</h1>
        <p className="mt-3 text-soil/70">
          Pick what describes you. You can sell and buy from the same account — this just sets
          up the right dashboard.
        </p>

        <div className="mt-8 grid gap-3 sm:mt-10 sm:grid-cols-3">
          {ROLES.map(({ value, icon: Icon, title, blurb }) => (
            <button
              key={value}
              type="button"
              onClick={() => setRole(value)}
              aria-pressed={role === value}
              className={`rounded-pod border p-5 text-left transition duration-400 ease-grow hover:-translate-y-0.5 ${
                role === value
                  ? 'border-canopy bg-canopy text-husk shadow-crate'
                  : 'border-soil/15 bg-white text-soil hover:border-canopy/40'
              }`}
            >
              <Icon size={22} strokeWidth={1.6} className={role === value ? 'text-sprout' : 'text-canopy'} />
              <h2 className="mt-3 font-display text-lg">{title}</h2>
              <p className={`mt-1.5 text-xs leading-relaxed ${role === value ? 'text-husk/75' : 'text-soil/60'}`}>
                {blurb}
              </p>
            </button>
          ))}
        </div>

        <form onSubmit={submit} className="card mt-8 space-y-4 p-7">
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label htmlFor="reg-name" className="mb-1.5 block text-sm text-soil/70">
                Your name
              </label>
              <input
                id="reg-name"
                required
                minLength={2}
                value={displayName}
                onChange={(e) => setDisplayName(e.target.value)}
                className="field"
              />
            </div>

            <div>
              <label htmlFor="reg-phone" className="mb-1.5 block text-sm text-soil/70">
                Mobile number
              </label>
              <input
                id="reg-phone"
                type="tel"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
                placeholder="09xx xxx xxxx"
                className="field"
              />
            </div>
          </div>

          {role === 'cooperative' && (
            <div>
              <label htmlFor="reg-org" className="mb-1.5 block text-sm text-soil/70">
                Cooperative name
              </label>
              <input
                id="reg-org"
                required
                minLength={2}
                value={orgName}
                onChange={(e) => setOrgName(e.target.value)}
                className="field"
              />
            </div>
          )}

          <div>
            <label htmlFor="reg-email" className="mb-1.5 block text-sm text-soil/70">
              Email
            </label>
            <input
              id="reg-email"
              type="email"
              required
              autoComplete="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="field"
            />
          </div>

          <div>
            <label htmlFor="reg-password" className="mb-1.5 block text-sm text-soil/70">
              Password
            </label>
            <input
              id="reg-password"
              type="password"
              required
              minLength={6}
              autoComplete="new-password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="field"
            />
            <p className="mt-1.5 text-xs text-soil/55">At least 6 characters.</p>
          </div>

          <ErrorNote message={error} />

          <button type="submit" disabled={busy} className="btn-primary w-full">
            {busy ? 'Creating account…' : 'Create account'}
          </button>

          <p className="text-center text-sm text-soil/65">
            Already a member?{' '}
            <Link to="/login" className="link-underline">
              Sign in
            </Link>
          </p>
        </form>
      </div>
    </div>
  );
}
