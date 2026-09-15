// frontend/src/components/layout/Footer.tsx
import { useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';

import FallingLeaves from '@/components/animations/FallingLeaves';
import { currentSeason } from '@/lib/season';

export default function Footer() {
  const [email, setEmail] = useState('');
  const [signedUp, setSignedUp] = useState(false);

  const handleSubscribe = (event: FormEvent) => {
    event.preventDefault();
    if (!email.includes('@')) return;
    // Wire this to your mailing provider (or a /api/newsletter route) when ready.
    setSignedUp(true);
    setEmail('');
  };

  return (
    <footer className="relative overflow-hidden bg-soil text-husk">
      <FallingLeaves season={currentSeason()} density={26} className="opacity-35" />

      <div className="shell relative z-10 section-y">
        <div className="grid gap-10 sm:grid-cols-2 lg:grid-cols-[1.2fr_1fr_1fr] lg:gap-12">
          <div className="max-w-sm sm:col-span-2 lg:col-span-1">
            <h2 className="font-display text-3xl">Weekly harvest box</h2>
            <p className="mt-3 text-sm leading-relaxed text-husk/70">
              One note each Thursday: what's being picked, which coop rates changed, and
              which machines are free next week.
            </p>

            <form onSubmit={handleSubscribe} className="mt-6 flex flex-wrap gap-3">
              <label className="sr-only" htmlFor="newsletter-email">
                Email address
              </label>
              <input
                id="newsletter-email"
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="you@example.com"
                className="min-w-[220px] flex-1 rounded-full border border-husk/25 bg-husk/10 px-5 py-3 text-husk placeholder:text-husk/45 focus:border-sprout focus:outline-none"
              />
              <button
                type="submit"
                className="rounded-full bg-sprout px-6 py-3 font-medium text-soil transition duration-300 ease-grow hover:scale-105 hover:bg-leaf hover:text-husk"
              >
                Sign up
              </button>
            </form>
            {signedUp && (
              <p className="mt-3 text-sm text-sprout" role="status">
                You're on the list. First note arrives Thursday.
              </p>
            )}
          </div>

          <div>
            <h3 className="font-display text-lg">Shop</h3>
            <ul className="mt-4 space-y-2.5 text-sm text-husk/70">
              <li><Link to="/market" className="hover:text-husk">Fresh produce</Link></li>
              <li><Link to="/supplies" className="hover:text-husk">Fertilizer and pesticides</Link></li>
              <li><Link to="/equipment" className="hover:text-husk">Equipment for rent</Link></li>
              <li><Link to="/equipment?listingType=sale" className="hover:text-husk">Equipment for sale</Link></li>
            </ul>
          </div>

          <div>
            <h3 className="font-display text-lg">Pickup and delivery</h3>
            <ul className="mt-4 space-y-2.5 text-sm text-husk/70">
              <li>Coop pickup: Tue and Fri, 6am–11am</li>
              <li>Davao City delivery: Wed and Sat</li>
              <li>Bulk orders: message the seller directly</li>
            </ul>
            <div className="mt-6 flex gap-4 text-sm text-husk/70">
              <a href="https://facebook.com" className="hover:text-husk">Facebook</a>
              <a href="https://instagram.com" className="hover:text-husk">Instagram</a>
              <a href="https://github.com" className="hover:text-husk">GitHub</a>
            </div>
          </div>
        </div>

        <div className="mt-14 flex flex-col gap-3 border-t border-husk/15 pt-7 text-sm text-husk/55 sm:flex-row sm:justify-between">
          <p>© {new Date().getFullYear()} FarmHub Cooperative Marketplace</p>
          <p>Built in Davao, Philippines</p>
        </div>
      </div>
    </footer>
  );
}
