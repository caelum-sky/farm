// frontend/src/main.tsx
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';

import App from '@/App';
import ErrorBoundary from '@/components/layout/ErrorBoundary';
import ToastViewport from '@/components/ui/ToastViewport';
import { AuthProvider } from '@/context/AuthContext';
import { CartProvider } from '@/context/CartContext';
import { ToastProvider } from '@/context/ToastContext';
import '@/index.css';

const container = document.getElementById('root');
if (!container) throw new Error('Root element is missing from index.html');

createRoot(container).render(
  <StrictMode>
    <ErrorBoundary>
      <BrowserRouter>
        <ToastProvider>
          <AuthProvider>
            <CartProvider>
              <App />
              <ToastViewport />
            </CartProvider>
          </AuthProvider>
        </ToastProvider>
      </BrowserRouter>
    </ErrorBoundary>
  </StrictMode>
);
