'use client';

import { useState, useEffect, use } from 'react';
import { useRouter } from 'next/navigation';
import { apiFetch } from '@/lib/api';

export default function CatalogLogin({ params }: { params: Promise<{ event_slug: string }> }) {
  const router = useRouter();
  const unwrappedParams = use(params);
  const event_slug = unwrappedParams.event_slug;
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [eventData, setEventData] = useState<any>(null);

  useEffect(() => {
    // Check if we already have a token
    const token = sessionStorage.getItem(`catalog_auth_${event_slug}`);
    if (token) {
      router.replace(`/catalog/${event_slug}`);
    }
  }, [event_slug, router]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      const res = await apiFetch<{ token: string }>(`/catalog/${event_slug}/login`, {
        method: 'POST',
        body: JSON.stringify({ password })
      });
      sessionStorage.setItem(`catalog_auth_${event_slug}`, res.token);
      window.location.href = `/catalog/${event_slug}`; // Force full reload to instantiate context properly
    } catch (err: any) {
      setError(err.body?.message || 'Incorrect password.');
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 flex items-center justify-center p-4">
      <div className="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden">
        <div className="bg-teal-600 p-8 text-center text-white">
          <div className="w-16 h-16 bg-white text-teal-600 font-extrabold text-2xl flex items-center justify-center rounded-2xl mx-auto mb-4 shadow-lg">
            OS
          </div>
          <h2 className="text-2xl font-bold">Exhibitor Portal</h2>
          <p className="text-teal-100 mt-2 text-sm">Enter the event password to access the catalog.</p>
        </div>

        <div className="p-8">
          {error && (
            <div className="mb-6 p-3 bg-red-50 text-red-600 text-sm font-semibold rounded-lg text-center">
              {error}
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-6">
            <div>
              <label className="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                Event Password
              </label>
              <input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500 transition-all text-lg tracking-widest text-center"
                placeholder="••••••••"
                required
              />
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-3 px-4 rounded-xl shadow-md transition-all disabled:opacity-50"
            >
              {loading ? 'Verifying...' : 'Access Catalog'}
            </button>
          </form>

          <div className="mt-8 pt-6 border-t border-gray-100 text-center">
            <a href="/" className="text-xs font-bold text-gray-400 hover:text-teal-600 transition-colors uppercase tracking-widest">
              ← Return to Storefront
            </a>
          </div>
        </div>
      </div>
    </div>
  );
}
