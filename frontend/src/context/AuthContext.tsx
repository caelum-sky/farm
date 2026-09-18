// frontend/src/context/AuthContext.tsx
import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react';
import {
  GoogleAuthProvider,
  createUserWithEmailAndPassword,
  onAuthStateChanged,
  sendPasswordResetEmail,
  signInWithEmailAndPassword,
  signInWithPopup,
  signOut,
  updateProfile as updateFirebaseProfile,
  type User,
} from 'firebase/auth';

import { auth, firebaseReady, requireAuth } from '@/firebase/config';
import { api, ApiError } from '@/lib/api';
import type { Role, UserProfile } from '@/types';

interface SignUpInput {
  email: string;
  password: string;
  displayName: string;
  role: Exclude<Role, 'admin'>;
  phone?: string;
  orgName?: string;
}

interface AuthContextValue {
  firebaseUser: User | null;
  profile: UserProfile | null;
  loading: boolean;
  /** False when the deployment has no Firebase config — sign-in is disabled. */
  authAvailable: boolean;
  isAdmin: boolean;
  isSeller: boolean;
  signIn: (email: string, password: string) => Promise<void>;
  signUp: (input: SignUpInput) => Promise<void>;
  signInWithGoogle: (role?: Exclude<Role, 'admin'>) => Promise<void>;
  resetPassword: (email: string) => Promise<void>;
  logOut: () => Promise<void>;
  refreshProfile: () => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [firebaseUser, setFirebaseUser] = useState<User | null>(null);
  const [profile, setProfile] = useState<UserProfile | null>(null);
  const [loading, setLoading] = useState(true);

  const loadProfile = useCallback(async (user: User | null = auth?.currentUser ?? null) => {
    if (!user) {
      setProfile(null);
      return;
    }

    try {
      // Use the user supplied by Firebase's auth-state callback rather than
      // reading auth.currentUser again. During session restoration the latter
      // can briefly be null, which sent /auth/me without a bearer token.
      const token = await user.getIdToken(true);
      setProfile(
        await api.get<UserProfile>('/auth/me', {
          headers: { Authorization: `Bearer ${token}` },
        })
      );
    } catch (err) {
      // A banned or deleted account can still hold a valid token briefly.
      if (err instanceof ApiError && (err.status === 401 || err.status === 403)) {
        if (auth) await signOut(auth);
        setProfile(null);
      }
    }
  }, []);

  useEffect(() => {
    if (!auth) {
      // No Firebase config: the storefront still renders, signed out.
      setLoading(false);
      return;
    }

    const unsubscribe = onAuthStateChanged(auth, async (user) => {
      setFirebaseUser(user);
      if (user) {
        await loadProfile(user);
      } else {
        setProfile(null);
      }
      setLoading(false);
    });
    return unsubscribe;
  }, [loadProfile]);

  const signIn = useCallback(async (email: string, password: string) => {
    await signInWithEmailAndPassword(requireAuth(), email, password);
  }, []);

  const signUp = useCallback(
    async ({ email, password, displayName, role, phone, orgName }: SignUpInput) => {
      const credential = await createUserWithEmailAndPassword(requireAuth(), email, password);
      await updateFirebaseProfile(credential.user, { displayName });
      // The role is assigned server-side so nobody can sign up as an admin.
      await api.post<UserProfile>('/auth/profile', { displayName, role, phone, orgName });
      await loadProfile();
    },
    [loadProfile]
  );

  const signInWithGoogle = useCallback(
    async (role: Exclude<Role, 'admin'> = 'buyer') => {
      const credential = await signInWithPopup(requireAuth(), new GoogleAuthProvider());
      // Idempotent on the server: returning users keep their existing role.
      await api.post<UserProfile>('/auth/profile', {
        displayName: credential.user.displayName || 'FarmHub member',
        role,
      });
      await loadProfile();
    },
    [loadProfile]
  );

  const resetPassword = useCallback(async (email: string) => {
    await sendPasswordResetEmail(requireAuth(), email);
  }, []);

  const logOut = useCallback(async () => {
    if (auth) await signOut(auth);
    setProfile(null);
  }, []);

  const value = useMemo<AuthContextValue>(
    () => ({
      firebaseUser,
      profile,
      loading,
      authAvailable: firebaseReady,
      isAdmin: profile?.role === 'admin',
      isSeller: profile?.role === 'farmer' || profile?.role === 'cooperative',
      signIn,
      signUp,
      signInWithGoogle,
      resetPassword,
      logOut,
      refreshProfile: loadProfile,
    }),
    [firebaseUser, profile, loading, signIn, signUp, signInWithGoogle, resetPassword, logOut, loadProfile]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

// eslint-disable-next-line react-refresh/only-export-components
export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used inside <AuthProvider>');
  return ctx;
}
