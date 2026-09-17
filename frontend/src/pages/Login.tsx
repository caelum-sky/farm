// frontend/src/pages/Login.tsx
import { useState, type FormEvent } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';

import ErrorNote from '@/components/ui/ErrorNote';
import { useAuth } from '@/context/AuthContext';

export default function Login() {
  const { signIn, signInWithGoogle, resetPassword } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const from = (location.state as { from?: string })?.from || '/app';

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [notice, setNotice] = useState('');
  const [busy, setBusy] = useState(false);

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setBusy(true);
    setError('');
    try {
      await signIn(email, password);
      navigate(from, { replace: true });
    } catch {
      setError('That email and password combination did not work. Check both and try again.');
    } finally {
      setBusy(false);
    }
  };

  const google = async () => {
    setBusy(true);
    setError('');
    try {
      await signInWithGoogle();
      navigate(from, { replace: true });
    } catch {
      setError('Google sign-in did not complete.');
    } finally {
      setBusy(false);
    }
  };

  const forgot = async () => {
    if (!email.includes('@')) {
      setError('Enter your email address first, then tap the reset link.');
      return;
    }
    try {
      await resetPassword(email);
      setNotice('Reset link sent. Check your inbox.');
      setError('');
    } catch {
      setError('Could not send a reset link to that address.');
    }
  };

  return (
    <div className="shell grid place-items-center section-y">
      <div className="card w-full max-w-md p-8">
        <h1 className="font-display text-3xl text-canopy">Welcome back</h1>
        <p className="mt-2 text-sm text-soil/65">Sign in to order, sell, or manage bookings.</p>

        <form onSubmit={submit} className="mt-8 space-y-4">
          <div>
            <label htmlFor="login-email" className="mb-1.5 block text-sm text-soil/70">
              Email
            </label>
            <input
              id="login-email"
              type="email"
              required
              autoComplete="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="field"
            />
          </div>

          <div>
            <label htmlFor="login-password" className="mb-1.5 block text-sm text-soil/70">
              Password
            </label>
            <input
              id="login-password"
              type="password"
              required
              autoComplete="current-password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="field"
            />
          </div>

          <ErrorNote message={error} />
          {notice && <p className="text-sm text-canopy" role="status">{notice}</p>}

          <button type="submit" disabled={busy} className="btn-primary w-full">
            {busy ? 'Signing in…' : 'Sign in'}
          </button>
        </form>

        <button type="button" onClick={google} disabled={busy} className="btn-outline mt-3 w-full">
          Continue with Google
        </button>

        <div className="mt-6 flex items-center justify-between text-sm">
          <button type="button" onClick={forgot} className="link-underline">
            Forgot password
          </button>
          <Link to="/register" className="link-underline">
            Create an account
          </Link>
        </div>
      </div>
    </div>
  );
}
