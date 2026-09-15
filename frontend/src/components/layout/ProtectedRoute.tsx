// frontend/src/components/layout/ProtectedRoute.tsx
import { Navigate, Outlet, useLocation } from 'react-router-dom';

import Spinner from '@/components/ui/Spinner';
import { useAuth } from '@/context/AuthContext';
import type { Role } from '@/types';

interface ProtectedRouteProps {
  /** When set, the signed-in user's role must be in this list. */
  allow?: Role[];
}

/**
 * Client-side gate. It keeps people out of screens they can't use, but it is not
 * the security boundary — the API and the Firestore rules both re-check the role
 * on every request.
 */
export default function ProtectedRoute({ allow }: ProtectedRouteProps) {
  const { profile, loading } = useAuth();
  const location = useLocation();

  if (loading) return <Spinner label="Checking your account" />;

  if (!profile) {
    return <Navigate to="/login" replace state={{ from: location.pathname }} />;
  }

  if (allow && !allow.includes(profile.role)) {
    return <Navigate to="/" replace />;
  }

  return <Outlet />;
}
