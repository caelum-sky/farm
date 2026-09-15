// frontend/src/components/layout/ErrorBoundary.tsx
import { Component, type ErrorInfo, type ReactNode } from 'react';

interface Props {
  children: ReactNode;
}

interface State {
  error: Error | null;
}

/**
 * Last line of defence. A render crash anywhere below this becomes a page that
 * explains itself and offers a way out, instead of a white screen.
 */
export default class ErrorBoundary extends Component<Props, State> {
  state: State = { error: null };

  static getDerivedStateFromError(error: Error): State {
    return { error };
  }

  componentDidCatch(error: Error, info: ErrorInfo) {
    // Swap for your error reporter when you add one.
    console.error('Unhandled render error', error, info.componentStack);
  }

  render() {
    if (!this.state.error) return this.props.children;

    return (
      <div className="grid min-h-screen place-items-center bg-husk px-6 text-center">
        <div className="max-w-md">
          <h1 className="font-display text-4xl text-canopy">Something broke on this page</h1>
          <p className="mt-4 text-soil/70">
            The rest of the site is fine. Reloading usually clears it — if it keeps happening,
            the details are in the browser console.
          </p>
          <div className="mt-8 flex flex-wrap justify-center gap-3">
            <button type="button" onClick={() => window.location.reload()} className="btn-primary">
              Reload the page
            </button>
            <a href="/" className="btn-outline">
              Back to the homepage
            </a>
          </div>
        </div>
      </div>
    );
  }
}
