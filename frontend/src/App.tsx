// frontend/src/App.tsx
import { Suspense, lazy } from 'react';
import { Route, Routes } from 'react-router-dom';
import PageTransition from '@/components/animations/PageTransition';

import ProtectedRoute from '@/components/layout/ProtectedRoute';
import SiteLayout from '@/components/layout/SiteLayout';
import Spinner from '@/components/ui/Spinner';
import Cart from '@/pages/Cart';
import EquipmentDetail from '@/pages/EquipmentDetail';
import EquipmentPage from '@/pages/EquipmentPage';
import Home from '@/pages/Home';
import Login from '@/pages/Login';
import Marketplace from '@/pages/Marketplace';
import NotFound from '@/pages/NotFound';
import ProductDetail from '@/pages/ProductDetail';
import Register from '@/pages/Register';
import Account from '@/pages/dashboard/Account';
import Orders from '@/pages/dashboard/Orders';
import Rentals from '@/pages/dashboard/Rentals';
import SellerDashboard from '@/pages/dashboard/SellerDashboard';

// The admin panel pulls in Recharts, which is heavier than the rest of the app
// combined. Splitting it out keeps that weight off every shopper's first load —
// only the handful of admins ever download it.
const AdminLayout = lazy(() => import('@/components/admin/AdminLayout'));
const AdminOverview = lazy(() => import('@/pages/admin/AdminOverview'));
const AdminUsers = lazy(() => import('@/pages/admin/AdminUsers'));
const AdminListings = lazy(() => import('@/pages/admin/AdminListings'));
const AdminReports = lazy(() => import('@/pages/admin/AdminReports'));
const AdminTransactions = lazy(() => import('@/pages/admin/AdminTransactions'));

export default function App() {
  return (
    <PageTransition>
      <Routes>
        <Route element={<SiteLayout />}>
          {/* Public */}
          <Route path="/" element={<Home />} />
          <Route path="/market" element={<Marketplace category="produce" />} />
          <Route path="/market/:id" element={<ProductDetail />} />
          <Route path="/supplies" element={<Marketplace category="supply" />} />
          <Route path="/equipment" element={<EquipmentPage />} />
          <Route path="/equipment/:id" element={<EquipmentDetail />} />
          <Route path="/cart" element={<Cart />} />
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />

        {/* Any signed-in member */}
        <Route element={<ProtectedRoute />}>
          <Route path="/account" element={<Account />} />
          <Route path="/orders" element={<Orders />} />
          <Route path="/rentals" element={<Rentals />} />
        </Route>

        {/* Sellers only */}
        <Route element={<ProtectedRoute allow={['farmer', 'cooperative', 'admin']} />}>
          <Route path="/dashboard" element={<SellerDashboard />} />
        </Route>

        {/* Admins only */}
        <Route element={<ProtectedRoute allow={['admin']} />}>
          <Route
            path="/admin"
            element={
              <Suspense fallback={<Spinner label="Opening the admin panel" />}>
                <AdminLayout />
              </Suspense>
            }
          >
            <Route
              index
              element={
                <Suspense fallback={<Spinner label="Loading dashboard" />}>
                  <AdminOverview />
                </Suspense>
              }
            />
            <Route
              path="users"
              element={
                <Suspense fallback={<Spinner label="Loading users" />}>
                  <AdminUsers />
                </Suspense>
              }
            />
            <Route
              path="listings"
              element={
                <Suspense fallback={<Spinner label="Loading listings" />}>
                  <AdminListings />
                </Suspense>
              }
            />
            <Route
              path="reports"
              element={
                <Suspense fallback={<Spinner label="Loading reports" />}>
                  <AdminReports />
                </Suspense>
              }
            />
            <Route
              path="transactions"
              element={
                <Suspense fallback={<Spinner label="Loading transactions" />}>
                  <AdminTransactions />
                </Suspense>
              }
            />
          </Route>
        </Route>

        <Route path="*" element={<NotFound />} />
      </Route>
    </Routes>
    </PageTransition>
  );
}
