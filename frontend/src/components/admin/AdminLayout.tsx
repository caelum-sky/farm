// frontend/src/components/admin/AdminLayout.tsx
import { NavLink, Outlet } from 'react-router-dom';
import { BarChart3, Flag, LayoutList, Receipt, Users } from 'lucide-react';

const NAV = [
  { to: '/admin', label: 'Overview', icon: BarChart3, end: true },
  { to: '/admin/users', label: 'Users', icon: Users, end: false },
  { to: '/admin/listings', label: 'Listings', icon: LayoutList, end: false },
  { to: '/admin/reports', label: 'Reports', icon: Flag, end: false },
  { to: '/admin/transactions', label: 'Transactions', icon: Receipt, end: false },
];

export default function AdminLayout() {
  return (
    <div className="shell section-y">
      <header>
        <h1 className="text-section text-canopy">Moderation</h1>
        <p className="mt-2 text-soil/70">
          Everything here is logged against your account. Bans take effect immediately and end
          the member's active sessions.
        </p>
      </header>

      <div className="mt-10 grid gap-8 lg:grid-cols-[220px_1fr]">
        <nav aria-label="Admin sections">
          <ul className="scroll-row lg:flex-col lg:gap-1">
            {NAV.map(({ to, label, icon: Icon, end }) => (
              <li key={to}>
                <NavLink
                  to={to}
                  end={end}
                  className={({ isActive }) =>
                    `flex items-center gap-2.5 whitespace-nowrap rounded-full px-4 py-2.5 text-sm transition duration-300 ease-grow ${
                      isActive
                        ? 'bg-canopy text-husk'
                        : 'text-soil/70 hover:bg-soil/5 hover:text-soil'
                    }`
                  }
                >
                  <Icon size={17} />
                  {label}
                </NavLink>
              </li>
            ))}
          </ul>
        </nav>

        <div className="min-w-0">
          <Outlet />
        </div>
      </div>
    </div>
  );
}
