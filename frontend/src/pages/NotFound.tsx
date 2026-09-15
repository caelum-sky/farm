// frontend/src/pages/NotFound.tsx
import { Link } from 'react-router-dom';

export default function NotFound() {
  return (
    <div className="shell grid min-h-[60vh] place-items-center section-y text-center">
      <div>
        <h1 className="font-display text-6xl text-canopy">Nothing grows here</h1>
        <p className="mt-4 text-soil/70">
          That page doesn't exist. The market and the equipment pool are both still open.
        </p>
        <div className="mt-8 flex flex-wrap justify-center gap-3">
          <Link to="/" className="btn-primary">Back to the homepage</Link>
          <Link to="/market" className="btn-outline">Shop the harvest</Link>
        </div>
      </div>
    </div>
  );
}
