'use client';

import Link from 'next/link';
import { useCart } from '@/contexts/CartContext';
import { usePathname } from 'next/navigation';

export function StoreHeader({ event, onCartClick }: { event: any, onCartClick?: () => void }) {
  const { totalItems } = useCart();
  const pathname = usePathname();
  const isCheckout = pathname.includes('/checkout');

  return (
    <header className="sticky top-0 z-50 bg-white/80 backdrop-blur-xl border-b border-gray-100 shadow-sm">
      <div className="max-w-7xl mx-auto px-4 h-20 flex items-center justify-between">
        
        {/* Logo & Event Info */}
        <div className="flex items-center gap-4">
          <Link href={`/catalog/${event.slug}`} className="flex items-center gap-3 group">
            <div className="w-11 h-11 bg-gray-900 text-white font-black flex items-center justify-center rounded-2xl text-xl shadow-lg shadow-gray-900/10 group-hover:bg-teal-600 transition-all duration-500">
              OS
            </div>
            <div className="hidden sm:block">
              <p className="text-[10px] font-black text-teal-600 uppercase tracking-[0.2em] mb-0.5">Furniture Partner</p>
              <h1 className="text-xl font-black text-gray-900 leading-none tracking-tighter">{event.short_name || event.slug}</h1>
            </div>
          </Link>
        </div>

        {/* Right Actions */}
        <div className="flex items-center gap-6">
          {!isCheckout && (
            <button 
              onClick={onCartClick}
              className="relative p-2.5 bg-gray-50 text-gray-900 hover:bg-teal-600 hover:text-white rounded-xl transition-all duration-300 group"
            >
              <svg width="22" height="22" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
              </svg>
              {totalItems > 0 && (
                <span className="absolute -top-2 -right-2 w-6 h-6 bg-teal-600 text-white text-[11px] font-black flex items-center justify-center rounded-full border-4 border-white shadow-lg">
                  {totalItems}
                </span>
              )}
            </button>
          )}
        </div>

      </div>
    </header>
  );
}

export function StoreFooter({ event }: { event: any }) {
  return (
    <footer className="bg-gray-900 text-gray-400 py-10 mt-auto">
      <div className="max-w-7xl mx-auto px-4 flex flex-col md:flex-row justify-between items-center gap-4">
        <div className="text-sm">
          <p className="font-semibold text-white mb-1">OmniSpace 3D Events Ltd.</p>
          <p>Official furniture provider for {event.short_name || event.slug}</p>
        </div>
        <div className="text-xs text-center md:text-right">
          <p>© {new Date().getFullYear()} OmniSpace 3D Events. All rights reserved.</p>
          <p className="mt-1">For support, contact <a href="mailto:sales@omnispace3d.com" className="text-teal-400 hover:underline">sales@omnispace3d.com</a></p>
        </div>
      </div>
    </footer>
  );
}
