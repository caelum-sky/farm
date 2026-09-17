// frontend/src/components/layout/AppShell.tsx
import { useEffect, useState } from 'react';
import { Link, NavLink, Outlet, useNavigate } from 'react-router-dom';
import { AnimatePresence, motion } from 'framer-motion';
import { ArrowLeft, LogOut, Menu, X } from 'lucide-react';

import { useAuth } from '@/context/AuthContext';
import { navForRole, ROLE_LABEL } from '@/lib/appNav';

/**
 * Layout for everything behind sign-in: role-specific sidebar, no marketing
 * hero/footer/leaf animation. SaaS products keep this split deliberately —
 * the storefront sells the idea, the app shell is where people do the work,
 * and the two don't share chrome. See SiteLayout for the storefront half.
 */
export default function AppShell() {
  const { profile, logOut } = useAuth();
  const navigate = useNavigate();
  const [drawerOpen, setDrawerOpen] = useState(false);

  useEffect(() => {
    document.body.style.overflow = drawerOpen ? 'hidden' : '';
    return () => {
      document.body.style.overflow = '';
    };
  }, [drawerOpen]);

  if (!profile) return null; // ProtectedRoute handles the redirect; this avoids a flash

  const items = navForRole(profile.role);

  const handleSignOut = async () => {
    await logOut();
    navigate('/');
  };

  const linkClass = ({ isActive }: { isActive: boolean }) =>
    `flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition ${
      isActive ? 'bg-canopy text-husk' : 'text-husk/75 hover:bg-husk/10 hover:text-husk'
    }`;

  const sidebarContent = (
    <>
      <div className="flex items-center gap-2.5 px-2">
        <svg viewBox="0 0 32 32" className="h-7 w-7 shrink-0" aria-hidden="true">
          <path d="M26 4C13 4 6 11 6 21c0 2 .4 4 1.2 5.7l2.2-2.2C9.1 23.3 9 22.2 9 21c0-8 5.6-13 17-13Z" fill="#6FA042" />
          <path d="M26 4c0 13-7 20-17 20 2 2.5 5 4 8 4 7 0 11-6 11-14 0-4-1-8-2-10Z" fill="#C8DDB0" />
        </svg>
        <span className="font-display text-lg text-husk">FarmHub</span>
      </div>

      <nav aria-label="Dashboard" className="mt-8 flex-1 space-y-1 px-2">
        {items.map((item) => (
          <NavLink key={item.to} to={item.to} end={item.end} className={linkClass}>
            <item.icon size={18} />
            {item.label}
          </NavLink>
        ))}
      </nav>

      <div className="space-y-1 border-t border-husk/10 px-2 pt-4">
        <Link
          to="/"
          className="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm text-husk/65 transition hover:bg-husk/10 hover:text-husk"
        >
          <ArrowLeft size={18} />
          Back to the market
        </Link>
        <button
          type="button"
          onClick={handleSignOut}
          className="flex w-full items-center gap-3 rounded-xl px-3.5 py-2.5 text-left text-sm text-husk/65 transition hover:bg-husk/10 hover:text-husk"
        >
          <LogOut size={18} />
          Sign out
        </button>
      </div>
    </>
  );

  return (
    <div className="min-h-screen bg-husk2 lg:flex">
      {/* Desktop sidebar — persistent, never scrolls with content */}
      <aside className="hidden w-64 shrink-0 flex-col bg-soil px-3 py-6 lg:fixed lg:inset-y-0 lg:flex">
        {sidebarContent}
      </aside>

      {/* Mobile drawer */}
      <AnimatePresence>
        {drawerOpen && (
          <>
            <motion.button
              type="button"
              aria-hidden="true"
              tabIndex={-1}
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setDrawerOpen(false)}
              className="fixed inset-0 z-40 bg-soil/50 backdrop-blur-sm lg:hidden"
            />
            <motion.aside
              initial={{ x: '-100%' }}
              animate={{ x: 0 }}
              exit={{ x: '-100%' }}
              transition={{ duration: 0.32, ease: [0.22, 0.61, 0.36, 1] }}
              className="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-soil px-3 py-6 lg:hidden"
            >
              <button
                type="button"
                onClick={() => setDrawerOpen(false)}
                aria-label="Close menu"
                className="absolute right-3 top-4 grid h-9 w-9 place-items-center rounded-full text-husk/70 hover:bg-husk/10"
              >
                <X size={18} />
              </button>
              {sidebarContent}
            </motion.aside>
          </>
        )}
      </AnimatePresence>

      <div className="flex min-h-screen flex-1 flex-col lg:pl-64">
        <header className="sticky top-0 z-30 flex h-16 items-center justify-between gap-4 border-b border-soil/10 bg-husk2/90 px-5 backdrop-blur-md sm:px-8">
          <button
            type="button"
            onClick={() => setDrawerOpen(true)}
            aria-label="Open menu"
            className="grid h-10 w-10 place-items-center rounded-full text-soil transition hover:bg-soil/5 lg:hidden"
          >
            <Menu size={20} />
          </button>

          <p className="hidden text-sm text-soil/55 lg:block">
            {ROLE_LABEL[profile.role]} dashboard
          </p>

          <div className="ml-auto flex items-center gap-3">
            <span className="hidden text-sm text-soil/70 sm:inline">{profile.displayName}</span>
            <div className="grid h-9 w-9 place-items-center rounded-full bg-canopy text-sm font-medium text-husk">
              {profile.displayName.charAt(0).toUpperCase()}
            </div>
          </div>
        </header>

        <main className="flex-1 px-5 py-8 sm:px-8 sm:py-10">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
