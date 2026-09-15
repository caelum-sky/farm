// frontend/src/components/layout/ConfigNotice.tsx
import { useAuth } from '@/context/AuthContext';

/**
 * Shown only when the build went out without Firebase credentials. Browsing still
 * works, so instead of a blank screen the site says exactly what's missing and
 * who can fix it.
 */
export default function ConfigNotice() {
  const { authAvailable } = useAuth();
  if (authAvailable) return null;

  return (
    <div className="bg-harvest/20 px-5 py-2.5 text-center text-sm text-bark">
      Sign-in is switched off because this deployment has no Firebase keys. Add the
      VITE_FIREBASE_* values to the environment and rebuild to turn accounts back on.
    </div>
  );
}
