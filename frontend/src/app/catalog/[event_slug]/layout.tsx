'use client';

import { useState, useEffect } from 'react';
import { use } from 'react';
import { useRouter, usePathname } from 'next/navigation';
import { CartProvider } from '@/contexts/CartContext';
import { StoreHeader, StoreFooter } from '@/components/catalog/Layout';
import { apiFetch } from '@/lib/api';

export default function CatalogLayout({ children, params }: { children: React.ReactNode, params: Promise<{ event_slug: string }> }) {
  const router = useRouter();
  const pathname = usePathname();
  const unwrappedParams = use(params);
  const event_slug = unwrappedParams.event_slug;
  const [loading, setLoading] = useState(true);
  const [eventData, setEventData] = useState<any>(null);

  useEffect(() => {
    // Skip auth check for login page
    if (pathname.includes('/login')) {
      setLoading(false);
      return;
    }

    const token = sessionStorage.getItem(`catalog_auth_${event_slug}`);
    if (!token) {
      router.replace(`/catalog/${event_slug}/login`);
      return;
    }

    // In a real app we'd validate the token here. We just load event data instead.
    apiFetch(`/catalog/${event_slug}/data`, { 
      headers: { 'Authorization': `Bearer ${token}` }
    })
      .then(res => setEventData(res.event))
      .catch(() => {
        sessionStorage.removeItem(`catalog_auth_${event_slug}`);
        router.replace(`/catalog/${event_slug}/login`);
      })
      .finally(() => setLoading(false));

  }, [event_slug, pathname, router]);

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="w-10 h-10 border-4 border-teal-600 border-t-transparent rounded-full animate-spin" />
      </div>
    );
  }

  // If login page, don't show header/footer or provide cart context
  if (pathname.includes('/login')) {
    return <>{children}</>;
  }

  if (!eventData) return null;

  return (
    <CartProvider eventSlug={event_slug}>
      <div className="min-h-screen flex flex-col bg-gray-50">
        <StoreHeader event={eventData} onCartClick={() => document.getElementById('cart-drawer')?.classList.toggle('translate-x-full')} />
        <main className="flex-1">
          {children}
        </main>
        <StoreFooter event={eventData} />
      </div>
    </CartProvider>
  );
}
