'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';

import { 
  LayoutDashboard, 
  ShoppingCart, 
  Package, 
  BarChart3, 
  Settings, 
  User,
  History,
  TrendingUp,
  PackageCheck,
  Monitor,
  Ticket,
  MessageSquare,
  Receipt
} from 'lucide-react';

const navItems = [
  { href: '/admin', label: 'Dashboard', icon: LayoutDashboard },
  { href: '/admin/analytics', label: 'Analytics', icon: TrendingUp },
  { href: '/admin/orders', label: 'Orders', icon: ShoppingCart },
  { href: '/admin/invoices', label: 'Invoices', icon: Receipt },
  { href: '/admin/products', label: 'Products', icon: Package },
  { href: '/admin/stock', label: 'Stock Limits', icon: PackageCheck },
  { href: '/admin/settings/storefront', label: 'Storefront CMS', icon: Monitor },
  { href: '/admin/settings/promo-codes', label: 'Promo Codes', icon: Ticket },
  { href: '/admin/feedback', label: 'Feedback', icon: MessageSquare },
  { href: '/admin/settings', label: 'Settings', icon: Settings },
  { href: '/admin/profile', label: 'My Profile', icon: User },
];

export default function Sidebar() {
  const pathname = usePathname();

  return (
    <aside className="fixed left-0 top-0 h-screen w-60 bg-[#0d2e2e] flex flex-col z-40">
      {/* Logo */}
      <div className="px-5 py-6 border-b border-white/5 text-center">
        <div className="inline-flex items-center justify-center w-10 h-10 bg-white text-teal-600 font-extrabold text-xl rounded-lg mx-auto">
          OS
        </div>
        <p className="text-[10px] text-teal-200 tracking-widest mt-1.5 uppercase">Admin Panel</p>
      </div>

      {/* Navigation */}
      <nav className="flex-1 py-4 overflow-y-auto">
        <div className="px-5 pb-2 text-[10px] font-bold text-gray-500 uppercase tracking-wider">
          Navigation
        </div>
        {navItems.map((item) => {
          // Exact match for dashboard and settings root; startsWith for everything else
          const exactMatchRoutes = ['/admin', '/admin/settings'];
          const isActive = exactMatchRoutes.includes(item.href)
            ? pathname === item.href
            : pathname.startsWith(item.href);

          return (
            <Link
              key={item.href}
              href={item.href}
              className={`flex items-center gap-3 px-5 py-3 text-sm transition-all duration-300 ${
                isActive
                  ? 'bg-teal-600 text-white font-black shadow-2xl shadow-teal-500/20'
                  : 'text-teal-400/40 hover:bg-white/5 hover:text-white'
              }`}
            >
              <item.icon size={18} className={isActive ? 'text-white' : 'text-gray-500'} />
              {item.label}
            </Link>
          );
        })}
      </nav>

      {/* Footer */}
      <div className="px-5 py-4 border-t border-gray-700 text-xs text-gray-500">
        OmniShop v1.0
      </div>
    </aside>
  );
}
