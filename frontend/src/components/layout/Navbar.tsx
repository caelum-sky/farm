// frontend/src/components/layout/Navbar.tsx
import { useEffect, useState } from 'react';
import { Link, NavLink, useLocation, useNavigate } from 'react-router-dom';
import { AnimatePresence, motion } from 'framer-motion';
import { Menu, ShoppingBasket, X } from 'lucide-react';

import { useAuth } from '@/context/AuthContext';
import { useCart } from '@/context/CartContext';

const PUBLIC_LINKS = [
  { to: '/market', label: 'Produce' },
  { to: '/supplies', label: 'Supplies' },
  { to: '/equipment', label: 'Equipment' },
  { to: '/#calendar', label: 'Harvest calendar' },
];

export default function Navbar() {
  const { profile, isAdmin, isSeller, logOut } = useAuth();
  const { count } = useCart();
  const navigate = useNavigate();
  const location = useLocation();
  const [open, setOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 12);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  // Navigating should always close the drawer, including on a hash link.
  useEffect(() => setOpen(false), [location.pathname, location.hash]);

  // An open drawer shouldn't leave the page scrolling underneath it.
  useEffect(() => {
    document.body.style.overflow = open ? 'hidden' : '';
    return () => {
      document.body.style.overflow = '';
    };
  }, [open]);

  useEffect(() => {
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') setOpen(false);
    };
    window.addEventListener('keydown', onKeyDown);
    return () => window.removeEventListener('keydown', onKeyDown);
  }, []);

  const handleSignOut = async () => {
    await logOut();
    setOpen(false);
    navigate('/');
  };

  const linkClass = ({ isActive }: { isActive: boolean }) =>
    `text-sm transition-colors ${isActive ? 'text-canopy' : 'text-soil/70 hover:text-soil'}`;

  const accountLinks = profile
    ? [
        ...(isAdmin ? [{ to: '/admin', label: 'Admin panel' }] : []),
        ...(isSeller ? [{ to: '/dashboard', label: 'Seller dashboard' }] : []),
        { to: '/orders', label: 'Orders' },
        { to: '/rentals', label: 'Bookings' },
        { to: '/account', label: 'My account' },
      ]
    : [];

  return (
    <header
      className={`sticky top-0 z-50 transition-all duration-500 ease-grow ${
        scrolled ? 'bg-husk/92 shadow-crate backdrop-blur-md' : 'bg-transparent'
      }`}
    >
      {/* z-10 keeps the bar itself painted above the drawer's backdrop, which
          covers the whole viewport so a tap anywhere outside closes the menu. */}
      <nav className="shell relative z-10 flex h-[68px] items-center justify-between gap-4 sm:h-[72px] sm:gap-6">
        <Link to="/" className="flex shrink-0 items-center gap-2.5">
          <svg viewBox="0 0 32 32" className="h-7 w-7" aria-hidden="true">
            <path d="M26 4C13 4 6 11 6 21c0 2 .4 4 1.2 5.7l2.2-2.2C9.1 23.3 9 22.2 9 21c0-8 5.6-13 17-13Z" fill="#2F5D3A" />
            <path d="M26 4c0 13-7 20-17 20 2 2.5 5 4 8 4 7 0 11-6 11-14 0-4-1-8-2-10Z" fill="#6FA042" />
          </svg>
          <span className="font-display text-xl text-canopy">FarmHub</span>
        </Link>

        <div className="hidden items-center gap-7 lg:flex">
          {PUBLIC_LINKS.map((link) =>
            link.to.includes('#') ? (
              <a key={link.to} href={link.to} className="text-sm text-soil/70 transition-colors hover:text-soil">
                {link.label}
              </a>
            ) : (
              <NavLink key={link.to} to={link.to} className={linkClass}>
                {link.label}
              </NavLink>
            )
          )}
        </div>

        <div className="flex items-center gap-1 sm:gap-2">
          <Link
            to="/cart"
            aria-label={`Cart, ${count} item${count === 1 ? '' : 's'}`}
            className="relative grid h-11 w-11 place-items-center rounded-full transition hover:bg-soil/5"
          >
            <ShoppingBasket size={20} className="text-soil/80" />
            {count > 0 && (
              <span className="absolute right-0.5 top-1 grid h-5 min-w-5 place-items-center rounded-full bg-harvest px-1 text-[11px] font-semibold text-soil">
                {count}
              </span>
            )}
          </Link>

          <div className="hidden items-center gap-2 lg:flex">
            {profile ? (
              <>
                {isAdmin && (
                  <Link to="/admin" className="btn-quiet">
                    Admin
                  </Link>
                )}
                {isSeller && (
                  <Link to="/dashboard" className="btn-quiet">
                    Dashboard
                  </Link>
                )}
                <Link to="/account" className="btn-quiet">
                  {profile.displayName.split(' ')[0]}
                </Link>
                <button type="button" onClick={handleSignOut} className="btn-quiet">
                  Sign out
                </button>
              </>
            ) : (
              <>
                <Link to="/login" className="btn-quiet">
                  Sign in
                </Link>
                <Link
                  to="/register"
                  className="rounded-full bg-canopy px-5 py-2.5 text-sm font-medium text-husk transition duration-300 ease-grow hover:bg-leaf motion-safe:hover:scale-105"
                >
                  Join FarmHub
                </Link>
              </>
            )}
          </div>

          <button
            type="button"
            onClick={() => setOpen((v) => !v)}
            aria-label={open ? 'Close menu' : 'Open menu'}
            aria-expanded={open}
            className="grid h-11 w-11 place-items-center rounded-full transition hover:bg-soil/5 lg:hidden"
          >
            {open ? <X size={20} /> : <Menu size={20} />}
          </button>
        </div>
      </nav>

      <AnimatePresence>
        {open && (
          <>
            <motion.button
              type="button"
              tabIndex={-1}
              aria-hidden="true"
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              transition={{ duration: 0.3 }}
              onClick={() => setOpen(false)}
              className="fixed inset-0 z-0 cursor-default bg-soil/40 backdrop-blur-sm lg:hidden"
            />

            <motion.div
              id="mobile-menu"
              initial={{ opacity: 0, y: -12 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -12 }}
              transition={{ duration: 0.34, ease: [0.22, 0.61, 0.36, 1] }}
              /* Anchored to the header rather than a fixed offset, so a banner
                 above the nav can't push the bar underneath the drawer. */
              className="absolute inset-x-0 top-full z-10 max-h-[72dvh] overflow-y-auto border-t border-soil/10 bg-husk px-5 pb-8 pt-4 shadow-crate lg:hidden"
            >
              <div className="flex flex-col gap-1">
                {PUBLIC_LINKS.map((link) => (
                  <a
                    key={link.to}
                    href={link.to}
                    onClick={() => setOpen(false)}
                    className="rounded-xl px-3 py-3 text-soil/85 transition hover:bg-soil/5"
                  >
                    {link.label}
                  </a>
                ))}

                <div className="my-3 h-px bg-soil/10" />

                {profile ? (
                  <>
                    {accountLinks.map((link) => (
                      <Link
                        key={link.to}
                        to={link.to}
                        onClick={() => setOpen(false)}
                        className="rounded-xl px-3 py-3 text-soil/85 transition hover:bg-soil/5"
                      >
                        {link.label}
                      </Link>
                    ))}
                    <button
                      type="button"
                      onClick={handleSignOut}
                      className="rounded-xl px-3 py-3 text-left text-soil/85 transition hover:bg-soil/5"
                    >
                      Sign out
                    </button>
                  </>
                ) : (
                  <>
                    <Link
                      to="/login"
                      onClick={() => setOpen(false)}
                      className="rounded-xl px-3 py-3 text-soil/85 transition hover:bg-soil/5"
                    >
                      Sign in
                    </Link>
                    <Link to="/register" onClick={() => setOpen(false)} className="btn-primary mt-3">
                      Join FarmHub
                    </Link>
                  </>
                )}
              </div>
            </motion.div>
          </>
        )}
      </AnimatePresence>
    </header>
  );
}
