// frontend/src/components/home/Hero.tsx
import { motion, useReducedMotion, useScroll, useTransform } from 'framer-motion';
import { Link } from 'react-router-dom';

import FallingLeaves from '@/components/animations/FallingLeaves';
import { currentSeason } from '@/lib/season';

/**
 * The hero opens on the thing the whole platform is about: a field at golden
 * hour. It's drawn as layered SVG rather than a stock photo so the page ships
 * without a 2 MB image and the parallax layers can move independently.
 */
export default function Hero() {
  const reduceMotion = useReducedMotion();
  const { scrollY } = useScroll();

  // Use framer-motion transforms directly - they return MotionValues safe for style props
  const skyY = useTransform(scrollY, [0, 600], [0, 70]);
  const ridgeY = useTransform(scrollY, [0, 600], [0, 140]);
  const fieldY = useTransform(scrollY, [0, 600], [0, 220]);
  const copyY = useTransform(scrollY, [0, 600], [0, 60]);

  return (
    <section className="relative overflow-hidden bg-gradient-to-b from-[#EAE0D5] via-husk/70 to-husk/60">
      <div className="pointer-events-none absolute inset-0">
        <motion.div
          style={reduceMotion ? { y: 0 } : skyY}
          className="absolute inset-0"
        >
          <div className="absolute right-[12%] top-24 h-56 w-56 rounded-full bg-harvest/15 blur-3xl" />
          <div className="absolute left-[6%] top-48 h-40 w-40 rounded-full bg-leaf/10 blur-3xl" />
        </motion.div>

        <motion.svg
          style={reduceMotion ? { y: 0 } : ridgeY}
          className="absolute bottom-0 h-[36%] w-full"
          viewBox="0 0 1200 240"
          preserveAspectRatio="none"
          aria-hidden="true"
        >
          <path d="M0 150 Q 180 70 360 128 T 760 108 T 1200 150 V240 H0Z" fill="#C8DDB0" opacity="0.4" />
        </motion.svg>

        <motion.svg
          style={reduceMotion ? { y: 0 } : fieldY}
          className="absolute bottom-0 h-[24%] w-full"
          viewBox="0 0 1200 200"
          preserveAspectRatio="none"
          aria-hidden="true"
        >
          <path d="M0 96 Q 240 40 520 92 T 1200 84 V200 H0Z" fill="#6FA042" opacity="0.3" />
          <path d="M0 148 Q 300 104 640 146 T 1200 138 V200 H0Z" fill="#2F5D3A" opacity="0.4" />
        </motion.svg>
      </div>

      <FallingLeaves season={currentSeason()} density={25} className="leaf-layer" />

      <motion.div
        style={reduceMotion ? { y: 0 } : copyY}
        className="shell relative z-10 flex min-h-[88svh] flex-col justify-center pb-44 pt-20 sm:min-h-[92svh] sm:pb-56 sm:pt-24"
      >
        <div className="text-center mb-6">
          <p className="mb-3 max-w-lg text-sm text-soil/70 sm:mb-4 sm:text-base">
            A marketplace run with farmers in Davao and cooperatives across Mindanao.
          </p>

          <h1 className="max-w-4xl text-hero font-display text-canopy/95">
            From our soil <br className="hidden sm:block" />
            to your table
          </h1>

          <p className="mt-4 max-w-xl text-base leading-relaxed text-soil/80 sm:mt-5 sm:text-lg">
            Buy produce picked this week, stock up on supplies at cooperative prices, and
            book a tractor for the days you actually need it.
          </p>
        </div>

        <div className="mt-8 flex flex-col gap-3 xs:flex-row xs:flex-wrap sm:mt-10 sm:gap-4">
          <Link to="/market" className="btn-primary btn-primary-enhanced w-full sm:w-auto">
            Shop the harvest
          </Link>
          <Link to="/equipment" className="btn-outline btn-outline-enhanced w-full sm:w-auto mt-4 sm:mt-0">
            Rent equipment
          </Link>
        </div>

        <dl className="mt-12 flex flex-wrap gap-x-8 gap-y-5 sm:mt-14 sm:gap-x-12 sm:gap-y-6 text-center">
          {[
            ['Farms and cooperatives', '340+'],
            ['Machines in the shared pool', '95'],
            ['Average saving on rentals', '38%'],
          ].map(([label, value]) => (
            <div key={label} className="flex flex-col items-center">
              <dt className="text-sm text-soil/50 mb-1">{label}</dt>
              <dd className="font-display text-2xl text-canopy sm:text-3xl">{value}</dd>
            </div>
          ))}
        </dl>
      </motion.div>
    </section>
  );
}
