// frontend/src/pages/app/AppHome.tsx
import { Navigate } from 'react-router-dom';

import { useAuth } from '@/context/AuthContext';
import BuyerHome from '@/pages/app/BuyerHome';
import SellerHome from '@/pages/app/SellerHome';

/**
 * `/app` renders a different screen per role rather than one generic
 * dashboard — a buyer's "what's going on" and a farmer's "what's going on"
 * have nothing in common, so there's no shared view worth building.
 */
export default function AppHome() {
  const { profile } = useAuth();
  if (!profile) return null;

  switch (profile.role) {
    case 'farmer':
    case 'cooperative':
      return <SellerHome />;
    case 'admin':
      return <Navigate to="/app/admin" replace />;
    default:
      return <BuyerHome />;
  }
}
