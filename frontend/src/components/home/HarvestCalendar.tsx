// frontend/src/components/home/HarvestCalendar.tsx
import { useState } from 'react';
import { motion } from 'framer-motion';

import Reveal from '@/components/animations/Reveal';
import { HARVEST_CALENDAR, MONTHS, MONTHS_LONG } from '@/lib/season';

/**
 * Twelve columns, one row per crop, filled where the crop is at peak. Picking a
 * month dims everything that isn't in season, which turns the grid into an
 * answer to the question buyers actually have: what's good right now?
 *
 * The grid needs horizontal room to stay legible, so below `sm` it's replaced
 * by a month strip plus a plain list rather than a table squeezed into 340px.
 */
export default function HarvestCalendar() {
  const [selectedMonth, setSelectedMonth] = useState(new Date().getMonth());
  const inSeason = HARVEST_CALENDAR.filter((crop) => crop.months.includes(selectedMonth));

  return (
    <section id="calendar" className="bg-husk2 section-y">
      <div className="shell">
        <Reveal>
          <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <h2 className="max-w-lg text-section text-canopy">What's ready this month</h2>
            <p className="max-w-sm text-soil/70">
              Peak windows for crops our farms grow. Pick a month to see what to expect in the
              weekly harvest boxes.
            </p>
          </div>
        </Reveal>

        {/* Month strip — the only month control on phones, and a shortcut on
            larger screens where the grid header does the same job. */}
        <Reveal delay={0.08}>
          <div className="mt-8 scroll-row sm:hidden">
            {MONTHS.map((month, index) => (
              <button
                key={month}
                type="button"
                onClick={() => setSelectedMonth(index)}
                aria-pressed={selectedMonth === index}
                className={`pill ${selectedMonth === index ? 'pill-on' : 'pill-off'}`}
              >
                {month}
              </button>
            ))}
          </div>
        </Reveal>

        <Reveal delay={0.1}>
          <div className="mt-10 hidden overflow-x-auto pb-2 sm:block">
            <table className="w-full min-w-[720px] border-separate border-spacing-y-1.5">
              <caption className="sr-only">Peak harvest months by crop</caption>
              <thead>
                <tr>
                  <th scope="col" className="w-44 pb-3 text-left text-sm font-medium text-soil/55">
                    Crop
                  </th>
                  {MONTHS.map((month, index) => (
                    <th key={month} scope="col" className="pb-3">
                      <button
                        type="button"
                        onClick={() => setSelectedMonth(index)}
                        aria-pressed={selectedMonth === index}
                        className={`w-full rounded-full px-1.5 py-1.5 text-xs font-medium transition duration-300 ease-grow motion-safe:hover:scale-110 ${
                          selectedMonth === index
                            ? 'bg-canopy text-husk'
                            : 'text-soil/55 hover:text-soil'
                        }`}
                      >
                        {month}
                      </button>
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {HARVEST_CALENDAR.map((crop) => (
                  <tr key={crop.name}>
                    <th scope="row" className="pr-4 text-left text-sm font-normal text-soil">
                      {crop.name}
                    </th>
                    {MONTHS.map((month, index) => {
                      const peak = crop.months.includes(index);
                      const isSelected = index === selectedMonth;
                      return (
                        <td key={month} className="px-0.5">
                          <div
                            title={peak ? `${crop.name} — peak in ${MONTHS_LONG[index]}` : undefined}
                            className={`h-7 rounded-md transition-all duration-500 ease-grow ${
                              peak ? (isSelected ? 'bg-canopy' : 'bg-leaf/50') : 'bg-white/70'
                            } ${isSelected ? 'ring-1 ring-canopy/30' : ''}`}
                          />
                        </td>
                      );
                    })}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Reveal>

        <Reveal delay={0.14}>
          <motion.div
            key={selectedMonth}
            initial={{ opacity: 0, y: 8 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.4, ease: [0.22, 0.61, 0.36, 1] }}
            className="mt-6 rounded-pod bg-white/70 p-6 sm:mt-8"
          >
            <h3 className="font-display text-xl text-canopy">
              In season in {MONTHS_LONG[selectedMonth]}
            </h3>

            {inSeason.length ? (
              <ul className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                {inSeason.map((crop) => (
                  <li key={crop.name} className="text-sm text-soil/75">
                    <span className="font-medium text-soil">{crop.name}</span> — {crop.note}
                  </li>
                ))}
              </ul>
            ) : (
              <p className="mt-3 text-sm text-soil/65">
                Nothing at peak this month. Stored crops and preserves stay available in the market.
              </p>
            )}
          </motion.div>
        </Reveal>
      </div>
    </section>
  );
}
