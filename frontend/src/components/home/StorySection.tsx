// frontend/src/components/home/StorySection.tsx
import { Link } from 'react-router-dom';

import Reveal from '@/components/animations/Reveal';

/**
 * Split layout: an illustrated field panel on one side, the cooperative story on
 * the other. Drawn rather than photographed so the page has no hero-image weight.
 */
export default function StorySection() {
  return (
    <section className="bg-husk section-y">
      <div className="shell grid items-center gap-10 lg:grid-cols-2 lg:gap-14">
        <Reveal>
          <div className="relative aspect-[4/5] max-h-[560px] overflow-hidden rounded-pod bg-[#EADFC4] shadow-crate">
            <svg viewBox="0 0 400 500" className="h-full w-full" aria-hidden="true">
              <rect width="400" height="500" fill="#EADFC4" />
              <circle cx="300" cy="96" r="54" fill="#E0A21C" opacity="0.5" />
              <path d="M0 330 Q 110 280 220 330 T 400 322 V500 H0Z" fill="#6FA042" opacity="0.65" />
              <path d="M0 392 Q 130 350 260 394 T 400 386 V500 H0Z" fill="#2F5D3A" opacity="0.8" />
              {Array.from({ length: 16 }).map((_, i) => (
                <line
                  key={i}
                  x1={12 + i * 25}
                  y1={440 - (i % 3) * 8}
                  x2={12 + i * 25}
                  y2={500}
                  stroke="#1E2A1B"
                  strokeOpacity="0.16"
                  strokeWidth="2"
                />
              ))}
              <g className="animate-sway" style={{ transformOrigin: '196px 300px' }}>
                <path d="M196 300 C 150 262 152 208 196 176 C 240 208 242 262 196 300Z" fill="#6FA042" />
                <path d="M196 300 V 176" stroke="#1E2A1B" strokeOpacity="0.28" strokeWidth="2.5" />
              </g>
            </svg>
          </div>
        </Reveal>

        <Reveal delay={0.12}>
          <div className="max-w-lg">
            <h2 className="text-section text-soil">Rooted in shared work</h2>
            <p className="mt-6 leading-relaxed text-soil/75">
              A tractor sitting idle on one farm is a week of lost planting on the next one
              over. FarmHub started from that gap: cooperatives pool machines and supplies,
              members book what they need by the day, and the produce goes out through the
              same channel instead of through three middlemen.
            </p>
            <p className="mt-4 leading-relaxed text-soil/75">
              Farmers set their own prices. Cooperatives mark member rates on the supplies
              and equipment they hold. Buyers see who grew what, and when it was picked.
            </p>

            <dl className="mt-8 grid gap-6 sm:grid-cols-3">
              {[
                ['Regenerative plots', '120 ha'],
                ['Average days to table', '2.4'],
                ['Member cooperatives', '27'],
              ].map(([label, value]) => (
                <div key={label}>
                  <dt className="text-sm text-soil/55">{label}</dt>
                  <dd className="mt-1 font-display text-2xl text-canopy">{value}</dd>
                </div>
              ))}
            </dl>

            <Link to="/register" className="link-underline mt-9 inline-block font-medium">
              Join as a farm or cooperative
            </Link>
          </div>
        </Reveal>
      </div>
    </section>
  );
}
