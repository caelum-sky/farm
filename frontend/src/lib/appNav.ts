// frontend/src/lib/appNav.ts
import type { LucideIcon } from 'lucide-react';
import {
  BarChart3,
  Flag,
  LayoutDashboard,
  LayoutList,
  Package,
  Receipt,
  ShoppingBag,
  Tractor,
  UserCircle,
  Users,
} from 'lucide-react';

import type { Role } from '@/types';

export interface NavItem {
  to: string;
  label: string;
  icon: LucideIcon;
  end?: boolean;
}

/**
 * What shows up in the app sidebar, keyed by role. Each role only sees the
 * sections relevant to what they actually do on FarmHub — a buyer never
 * needs "My Listings", an admin's job here is moderation, not shopping.
 */
export function navForRole(role: Role): NavItem[] {
  const account: NavItem = { to: '/app/account', label: 'Account', icon: UserCircle };

  switch (role) {
    case 'farmer':
    case 'cooperative':
      return [
        { to: '/app', label: 'Overview', icon: LayoutDashboard, end: true },
        { to: '/app/listings', label: 'My Listings', icon: Package },
        { to: '/app/orders', label: 'Orders', icon: ShoppingBag },
        { to: '/app/rentals', label: 'Bookings', icon: Tractor },
        account,
      ];
    case 'admin':
      return [
        { to: '/app/admin', label: 'Overview', icon: BarChart3, end: true },
        { to: '/app/admin/users', label: 'Users', icon: Users },
        { to: '/app/admin/listings', label: 'Listings', icon: LayoutList },
        { to: '/app/admin/reports', label: 'Reports', icon: Flag },
        { to: '/app/admin/transactions', label: 'Transactions', icon: Receipt },
        account,
      ];
    default: // buyer
      return [
        { to: '/app', label: 'Overview', icon: LayoutDashboard, end: true },
        { to: '/app/orders', label: 'My Orders', icon: ShoppingBag },
        { to: '/app/rentals', label: 'My Bookings', icon: Tractor },
        account,
      ];
  }
}

export const ROLE_LABEL: Record<Role, string> = {
  buyer: 'Buyer',
  farmer: 'Farmer',
  cooperative: 'Cooperative',
  admin: 'Admin',
};
