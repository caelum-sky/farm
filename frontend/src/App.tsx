// frontend/src/App.tsx
import { Suspense, lazy } from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';

import AppShell from '@/components/layout/AppShell';
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
import Account from '@/pages/app/Account';
import AppHome from '@/pages/app/AppHome';
import ManageListings from '@/pages/app/ManageListings';
import Orders from '@/pages/app/Orders';
import Rentals from '@/pages/app/Rentals';

// The admin section pulls in Recharts, which is heavier than the rest of the
// app combined. Splitting it out keeps that weight off every shopper's and
// every farmer's first load — only admins ever download it.
const AdminOverview = lazy(() => import('@/pages/admin/AdminOverview'));
const AdminUsers = lazy(() => import('@/pages/admin/AdminUsers'));
const AdminListings = lazy(() => import('@/pages/admin/AdminListings'));
const AdminReports = lazy(() => import('@/pages/admin/AdminReports'));
const AdminTransactions = lazy(() => import('@/pages/admin/AdminTransactions'));

function LazyPage({ children, label }: { children: React.ReactNode; label: string }) {
  return <Suspense fallback={<Spinner label={label} />}>{children}</Suspense>;
}

export default function App() {
  return (
    <Routes>
      {/* Storefront — public marketing + shopping, top nav, footer, leaf animation */}
      <Route element={<SiteLayout />}>
        <Route path="/" element={<Home />} />
        <Route path="/market" element={<Marketplace category="produce" />} />
        <Route path="/market/:id" element={<ProductDetail />} />
        <Route path="/supplies" element={<Marketplace category="supply" />} />
        <Route path="/equipment" element={<EquipmentPage />} />
        <Route path="/equipment/:id" element={<EquipmentDetail />} />
        <Route path="/cart" element={<Cart />} />
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        <Route path="*" element={<NotFound />} />
      </Route>

      {/* App — everything behind sign-in, sidebar shell, no marketing chrome.
          One index route dispatches to a different home per role; the rest
          of the tree is nav items that only some roles ever see (enforced
          both by the sidebar config and by these route-level role gates). */}
      <Route element={<ProtectedRoute />}>
        <Route path="/app" element={<AppShell />}>
          <Route index element={<AppHome />} />
          <Route path="account" element={<Account />} />
          <Route path="orders" element={<Orders />} />
          <Route path="rentals" element={<Rentals />} />

          <Route element={<ProtectedRoute allow={['farmer', 'cooperative', 'admin']} />}>
            <Route path="listings" element={<ManageListings />} />
          </Route>

          <Route element={<ProtectedRoute allow={['admin']} />}>
            <Route
              path="admin"
              element={
                <LazyPage label="Loading dashboard">
                  <AdminOverview />
                </LazyPage>
              }
            />
            <Route
              path="admin/users"
              element={
                <LazyPage label="Loading users">
                  <AdminUsers />
                </LazyPage>
              }
            />
            <Route
              path="admin/listings"
              element={
                <LazyPage label="Loading listings">
                  <AdminListings />
                </LazyPage>
              }
            />
            <Route
              path="admin/reports"
              element={
                <LazyPage label="Loading reports">
                  <AdminReports />
                </LazyPage>
              }
            />
            <Route
              path="admin/transactions"
              element={
                <LazyPage label="Loading transactions">
                  <AdminTransactions />
                </LazyPage>
              }
            />
          </Route>
        </Route>
      </Route>

      {/* Old paths from before the storefront/app split — keep bookmarks and
          any external links working rather than breaking them silently. */}
      <Route path="/dashboard" element={<Navigate to="/app/listings" replace />} />
      <Route path="/admin" element={<Navigate to="/app/admin" replace />} />
      <Route path="/account" element={<Navigate to="/app/account" replace />} />
      <Route path="/orders" element={<Navigate to="/app/orders" replace />} />
      <Route path="/rentals" element={<Navigate to="/app/rentals" replace />} />
    </Routes>
  );
}
