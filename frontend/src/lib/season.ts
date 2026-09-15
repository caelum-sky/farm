// frontend/src/lib/season.ts
import type { LeafSeason } from '@/components/animations/FallingLeaves';

/**
 * Tints the drifting leaves to the Philippine agricultural calendar. There is no
 * winter here, so the year runs dry → growing → harvest rather than through snow.
 */
export function currentSeason(date = new Date()): LeafSeason {
  const month = date.getMonth(); // 0-indexed
  if (month <= 3) return 'dry'; // Jan–Apr, amihan and the hot dry stretch
  if (month <= 7) return 'growing'; // May–Aug, wet season growth
  return 'harvest'; // Sep–Dec, main harvest
}

export const MONTHS = [
  'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
  'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
];

export const MONTHS_LONG = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December',
];

export interface HarvestCrop {
  name: string;
  /** Month indexes (0-11) when the crop is at peak. */
  months: number[];
  note: string;
}

/** Peak months for produce commonly grown in Mindanao and the Visayas. */
export const HARVEST_CALENDAR: HarvestCrop[] = [
  { name: 'Mango', months: [2, 3, 4, 5], note: 'Carabao mangoes peak through the dry months.' },
  { name: 'Durian', months: [7, 8, 9, 10], note: 'Davao durian season — Puyat and Monthong.' },
  { name: 'Banana (Lakatan)', months: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], note: 'Harvested year-round in rotation.' },
  { name: 'Pineapple', months: [3, 4, 5, 6, 7], note: 'Sweetest right after the first heavy rains.' },
  { name: 'Tomato', months: [0, 1, 10, 11], note: 'Cool-month crop from the highlands.' },
  { name: 'Eggplant', months: [0, 1, 2, 9, 10, 11], note: 'Steady supply outside peak typhoon months.' },
  { name: 'Sweet corn', months: [4, 5, 6, 7, 8], note: 'Wet-season planting, harvested green.' },
  { name: 'Calamansi', months: [6, 7, 8, 9, 10], note: 'Heaviest fruiting in the rainy stretch.' },
  { name: 'Coffee (Robusta)', months: [10, 11, 0, 1], note: 'Cherry picking runs into the new year.' },
  { name: 'Cacao', months: [8, 9, 10, 11], note: 'Main pod flush before the year ends.' },
];
