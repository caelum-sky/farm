// frontend/src/components/home/RolePanels.tsx
import { Link } from 'react-router-dom';
import { ShoppingBasket, Sprout, Users } from 'lucide-react';

import Reveal from '@/components/animations/Reveal';

const PANELS = [
  {
    icon: ShoppingBasket,
    title: 'Buying for your kitchen',
    body: 'Order fruit, vegetables, eggs, and dairy from farms near you. Delivered or picked up at the coop.',
    to: '/market',
    cta: 'Browse produce',
  },
  {
    icon: Sprout,
    title: 'Running a farm',
    body: 'List what you harvest, buy fertilizer and seed at member rates, and rent a tractor for the days you need it.',
    to: '/register',
    cta: 'Sell and rent',
  },
  {
    icon: Users,
    title: 'Leading a cooperative',
    body: 'Hold shared equipment, publish member pricing on supplies, and give your members one market to sell into.',
    to: '/register',
    cta: 'Register a cooperative',
  },
];

export default function RolePanels() {
  return (
    <section className="bg-canopy section-y text-husk">
      <div className="shell">
        <Reveal>
          <h2 className="max-w-2xl text-section">One platform, three sides of the same trade</h2>
        </Reveal>

        <div className="mt-10 grid gap-5 sm:mt-14 md:grid-cols-3">
          {PANELS.map(({ icon: Icon, title, body, to, cta }, index) => (
            <Reveal key={title} delay={index * 0.08}>
              <div className="flex h-full flex-col rounded-pod border border-husk/15 bg-husk/5 p-7 transition duration-500 ease-grow hover:bg-husk/10 sm:p-8">
                <Icon size={26} strokeWidth={1.6} className="text-sprout" />
                <h3 className="mt-5 font-display text-2xl">{title}</h3>
                <p className="mt-3 flex-1 text-sm leading-relaxed text-husk/80">{body}</p>
                <Link
                  to={to}
                  className="mt-7 inline-flex w-fit items-center rounded-full border border-husk/35 px-5 py-2.5 text-sm font-medium transition duration-300 ease-grow hover:scale-105 hover:bg-husk hover:text-canopy"
                >
                  {cta}
                </Link>
              </div>
            </Reveal>
          ))}
        </div>
      </div>
    </section>
  );
}
