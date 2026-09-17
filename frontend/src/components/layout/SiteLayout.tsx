// frontend/src/components/layout/SiteLayout.tsx
import { useEffect } from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import { motion, useReducedMotion } from 'framer-motion';

import ConfigNotice from '@/components/layout/ConfigNotice';
import Footer from '@/components/layout/Footer';
import Navbar from '@/components/layout/Navbar';

/**
 * Wraps every public page. Route changes cross-fade slowly rather than snapping,
 * which is the calm pace the brief asked for.
 */
export default function SiteLayout() {
  const location = useLocation();
  const reduceMotion = useReducedMotion();

  // Without this, navigating from halfway down the market into a product page
  // lands the visitor halfway down that page too.
  useEffect(() => {
    if (location.hash) return; // let anchor links do their own thing
    window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
  }, [location.pathname, location.hash, reduceMotion]);

  return (
    <div className="flex min-h-screen flex-col">
      <ConfigNotice />
      <Navbar />
      <motion.main
        key={location.pathname}
        className="flex-1"
        initial={reduceMotion ? false : { opacity: 0 }}
        animate={{ opacity: 1 }}
        transition={{ duration: 0.5, ease: [0.22, 0.61, 0.36, 1] }}
      >
        <Outlet />
      </motion.main>
      <Footer />
    </div>
  );
}
