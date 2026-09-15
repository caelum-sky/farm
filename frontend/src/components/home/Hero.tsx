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

  const skyY = useTransform(scrollY, [0, 600], [0, 70]);
  const ridgeY = useTransform(scrollY, [0, 600], [0, 140]);
  const fieldY = useTransform(scrollY, [0, 600], [0, 220]);
  const copyY = useTransform(scrollY, [0, 600], [0, 60]);

  const still = { y: 0 };

  return (
    <section className="relative overflow-hidden bg-gradient-to-b from-[#FDF6E3] via-husk to-husk">
      <div className="pointer-events-none absolute inset-0">
        <motion.div style={reduceMotion ? still : { y: skyY }} className="absolute inset-0">
          <div className="absolute right-[12%] top-24 h-56 w-56 rounded-full bg-harvest/25 blur-3xl" />
          <div className="absolute left-[6%] top-48 h-40 w-40 rounded-full bg-leaf/20 blur-3xl" />
        </motion.div>

        <motion.svg
          style={reduceMotion ? still : { y: ridgeY }}
          className="absolute bottom-0 h-[36%] w-full"
          viewBox="0 0 1200 240"
          preserveAspectRatio="none"
          aria-hidden="true"
        >
          <path d="M0 150 Q 180 70 360 128 T 760 108 T 1200 150 V240 H0Z" fill="#C8DDB0" opacity="0.7" />
        </motion.svg>

        <motion.svg
          style={reduceMotion ? still : { y: fieldY }}
          className="absolute bottom-0 h-[24%] w-full"
          viewBox="0 0 1200 200"
          preserveAspectRatio="none"
          aria-hidden="true"
        >
          <path d="M0 96 Q 240 40 520 92 T 1200 84 V200 H0Z" fill="#6FA042" opacity="0.55" />
          <path d="M0 148 Q 300 104 640 146 T 1200 138 V200 H0Z" fill="#2F5D3A" opacity="0.65" />
        </motion.svg>
      </div>

      <FallingLeaves season={currentSeason()} density={42} />

      <motion.div
        style={reduceMotion ? still : { y: copyY }}
        className="shell relative z-10 flex min-h-[88svh] flex-col justify-center pb-44 pt-20 sm:min-h-[92svh] sm:pb-56 sm:pt-24"
      >
        <p className="mb-5 max-w-lg text-sm text-soil/70 sm:mb-6 sm:text-base">
          A marketplace run with farmers in Davao and cooperatives across Mindanao.
        </p>

        <h1 className="max-w-4xl text-hero font-display text-canopy">
          From our soil <br className="hidden sm:block" />
          to your table
        </h1>

        <p className="mt-6 max-w-xl text-base leading-relaxed text-soil/80 sm:mt-7 sm:text-lg">
          Buy produce picked this week, stock up on supplies at cooperative prices, and
          book a tractor for the days you actually need it.
        </p>

        <div className="mt-8 flex flex-col gap-3 xs:flex-row xs:flex-wrap sm:mt-10 sm:gap-4">
          <Link to="/market" className="btn-primary">
            Shop the harvest
          </Link>
          <Link to="/equipment" className="btn-outline">
            Rent equipment
          </Link>
        </div>

        <dl className="mt-12 flex flex-wrap gap-x-8 gap-y-5 sm:mt-14 sm:gap-x-12 sm:gap-y-6">
          {[
            ['Farms and cooperatives', '340+'],
            ['Machines in the shared pool', '95'],
            ['Average saving on rentals', '38%'],
          ].map(([label, value]) => (
            <div key={label}>
              <dt className="text-sm text-soil/60">{label}</dt>
              <dd className="font-display text-2xl text-canopy sm:text-3xl">{value}</dd>
            </div>
          ))}
        </dl>
      </motion.div>
    </section>
  );
}
