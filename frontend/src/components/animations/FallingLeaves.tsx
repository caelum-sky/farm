// frontend/src/components/animations/FallingLeaves.tsx
import { useEffect, useRef } from 'react';

export type LeafSeason = 'growing' | 'harvest' | 'dry';

interface FallingLeavesProps {
  /** Tints the drift to match the time of year. */
  season?: LeafSeason;
  density?: number;
  className?: string;
}

type LeafShape = 'blade' | 'round' | 'frond';

interface Leaf {
  x: number;
  y: number;
  size: number;
  fall: number;
  sway: number;
  swayPhase: number;
  spin: number;
  angle: number;
  /** Simulates the leaf turning edge-on as it tumbles. */
  flutter: number;
  flutterRate: number;
  depth: number;
  color: string;
  shape: LeafShape;
}

/**
 * Leaves coming off the trees, drawn on one canvas.
 *
 * Three things keep it from reading as generic falling confetti:
 * a shared wind field, so every leaf gusts together instead of drifting
 * independently; depth layers, where distant leaves are smaller, paler and
 * slower than near ones; and per-leaf flutter, which squashes the leaf
 * horizontally as it turns edge-on, the way a real one catches the light.
 *
 * Motion is delta-timed, so it falls at the same speed on a 60 Hz laptop and a
 * 120 Hz phone. It parks itself when the tab is hidden and never starts at all
 * when the visitor has asked for reduced motion.
 */
const PALETTES: Record<LeafSeason, string[]> = {
  // wet season: new growth, deep canopy shade
  growing: ['#6FA042', '#2F5D3A', '#8FB85E', '#4C7A33', '#A8C47C'],
  // main harvest: green turning, first yellows
  harvest: ['#6FA042', '#C9A227', '#E0A21C', '#7A5C3E', '#A8C47C'],
  // dry months: sun-bleached golds and russets
  dry: ['#E0A21C', '#C9752C', '#B98B3D', '#7A5C3E', '#C9A227'],
};

const SHAPES: LeafShape[] = ['blade', 'round', 'frond'];

function makeLeaf(width: number, height: number, season: LeafSeason, seeded = false): Leaf {
  const palette = PALETTES[season];
  // depth 0 = far away, 1 = close to the viewer
  const depth = Math.random();

  return {
    x: Math.random() * width,
    y: seeded ? Math.random() * height : -30 - Math.random() * height * 0.35,
    size: 7 + depth * 15,
    fall: 14 + depth * 34,
    sway: 10 + depth * 26,
    swayPhase: Math.random() * Math.PI * 2,
    spin: (Math.random() - 0.5) * (0.5 + depth),
    angle: Math.random() * Math.PI * 2,
    flutter: Math.random() * Math.PI * 2,
    flutterRate: 1.1 + Math.random() * 2.2,
    depth,
    color: palette[Math.floor(Math.random() * palette.length)],
    shape: SHAPES[Math.floor(Math.random() * SHAPES.length)],
  };
}

function drawLeaf(ctx: CanvasRenderingContext2D, leaf: Leaf) {
  const { size } = leaf;
  // As the leaf turns edge-on its width collapses; a floor of 0.15 keeps a
  // sliver visible rather than blinking out entirely.
  const face = Math.max(0.15, Math.abs(Math.cos(leaf.flutter)));

  ctx.save();
  ctx.translate(leaf.x, leaf.y);
  ctx.rotate(leaf.angle);
  ctx.scale(face, 1);
  ctx.fillStyle = leaf.color;
  ctx.globalAlpha = 0.28 + leaf.depth * 0.52;

  switch (leaf.shape) {
    case 'round': {
      // broad leaf — think taro or squash
      ctx.beginPath();
      ctx.moveTo(0, -size * 0.55);
      ctx.bezierCurveTo(size * 0.72, -size * 0.3, size * 0.6, size * 0.5, 0, size * 0.6);
      ctx.bezierCurveTo(-size * 0.6, size * 0.5, -size * 0.72, -size * 0.3, 0, -size * 0.55);
      ctx.fill();
      break;
    }
    case 'frond': {
      // narrow banana-frond strip with a torn edge
      ctx.beginPath();
      ctx.moveTo(0, -size * 0.85);
      ctx.quadraticCurveTo(size * 0.3, -size * 0.2, size * 0.16, size * 0.85);
      ctx.quadraticCurveTo(0, size * 0.55, -size * 0.16, size * 0.85);
      ctx.quadraticCurveTo(-size * 0.3, -size * 0.2, 0, -size * 0.85);
      ctx.fill();
      break;
    }
    default: {
      // classic pointed blade
      ctx.beginPath();
      ctx.moveTo(0, -size * 0.75);
      ctx.quadraticCurveTo(size * 0.52, 0, 0, size * 0.75);
      ctx.quadraticCurveTo(-size * 0.52, 0, 0, -size * 0.75);
      ctx.fill();
    }
  }

  // Midrib, only on leaves near enough for it to read.
  if (leaf.depth > 0.45) {
    ctx.strokeStyle = 'rgba(30, 42, 27, 0.3)';
    ctx.lineWidth = 0.7;
    ctx.beginPath();
    ctx.moveTo(0, -size * 0.6);
    ctx.lineTo(0, size * 0.62);
    ctx.stroke();
  }

  ctx.restore();
}

export default function FallingLeaves({
  season = 'growing',
  density = 34,
  className = '',
}: FallingLeavesProps) {
  const canvasRef = useRef<HTMLCanvasElement | null>(null);
  const frameRef = useRef(0);
  const leavesRef = useRef<Leaf[]>([]);

  useEffect(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;

    const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    if (motionQuery.matches) return;

    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    let width = 0;
    let height = 0;
    let lastTime = performance.now();
    let elapsed = 0;

    const resize = () => {
      const dpr = Math.min(window.devicePixelRatio || 1, 2);
      const rect = canvas.parentElement?.getBoundingClientRect();
      width = rect?.width ?? window.innerWidth;
      height = rect?.height ?? window.innerHeight;
      canvas.width = Math.max(1, width * dpr);
      canvas.height = Math.max(1, height * dpr);
      canvas.style.width = `${width}px`;
      canvas.style.height = `${height}px`;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    };

    resize();

    // Phones get a thinner drift: the effect is decoration, and it must never
    // be the reason a scroll stutters.
    const count = window.innerWidth < 640 ? Math.round(density * 0.4) : density;
    leavesRef.current = Array.from({ length: count }, () => makeLeaf(width, height, season, true));

    const step = (now: number) => {
      const delta = Math.min((now - lastTime) / 1000, 0.05); // clamp after a tab stall
      lastTime = now;
      elapsed += delta;

      // One wind field for the whole canvas: a slow base breeze with an
      // occasional stronger gust layered over it.
      const breeze = Math.sin(elapsed * 0.35) * 0.6;
      const gust = Math.sin(elapsed * 0.11) * Math.sin(elapsed * 0.53) * 1.5;
      const wind = breeze + gust;

      ctx.clearRect(0, 0, width, height);

      for (const leaf of leavesRef.current) {
        leaf.y += leaf.fall * delta;
        leaf.x +=
          (Math.sin(elapsed * 1.1 + leaf.swayPhase) * leaf.sway + wind * leaf.sway) * delta;
        leaf.angle += leaf.spin * delta;
        leaf.flutter += leaf.flutterRate * delta;

        const margin = leaf.size * 2;
        if (leaf.y > height + margin) {
          Object.assign(leaf, makeLeaf(width, height, season));
        } else if (leaf.x < -margin) {
          leaf.x = width + margin;
        } else if (leaf.x > width + margin) {
          leaf.x = -margin;
        }

        drawLeaf(ctx, leaf);
      }

      frameRef.current = window.requestAnimationFrame(step);
    };

    const start = () => {
      if (!frameRef.current) {
        lastTime = performance.now();
        frameRef.current = window.requestAnimationFrame(step);
      }
    };
    const stop = () => {
      window.cancelAnimationFrame(frameRef.current);
      frameRef.current = 0;
    };

    const onVisibility = () => (document.hidden ? stop() : start());
    const onMotionChange = () => (motionQuery.matches ? stop() : start());

    start();

    const observer = new ResizeObserver(resize);
    if (canvas.parentElement) observer.observe(canvas.parentElement);
    document.addEventListener('visibilitychange', onVisibility);
    motionQuery.addEventListener('change', onMotionChange);

    return () => {
      stop();
      observer.disconnect();
      document.removeEventListener('visibilitychange', onVisibility);
      motionQuery.removeEventListener('change', onMotionChange);
    };
  }, [season, density]);

  return (
    <canvas
      ref={canvasRef}
      aria-hidden="true"
      className={`pointer-events-none absolute inset-0 h-full w-full ${className}`}
    />
  );
}
