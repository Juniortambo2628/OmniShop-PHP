'use client';

import { useState, useRef, useEffect } from 'react';
import { useAuth } from '@/contexts/AuthContext';
import { apiFetch } from '@/lib/api';
import { useRouter } from 'next/navigation';

interface SearchResult {
  type: string;
  id: string;
  title: string;
  subtitle: string;
  url: string;
}

import { getEchoInstance } from '@/lib/echo';

export default function Header() {
  const { user, token, logout } = useAuth();
  const router = useRouter();
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState<SearchResult[]>([]);
  const [showSearch, setShowSearch] = useState(false);
  const [showProfile, setShowProfile] = useState(false);
  const [showNotifications, setShowNotifications] = useState(false);
  const searchRef = useRef<HTMLDivElement>(null);
  const profileRef = useRef<HTMLDivElement>(null);
  const notifRef = useRef<HTMLDivElement>(null);

  const [notifications, setNotifications] = useState<any[]>([]);
  const [unreadCount, setUnreadCount] = useState(0);

  const fetchNotifications = async () => {
    if (!token) return;
    try {
      const [list, countData] = await Promise.all([
        apiFetch<any[]>('/notifications', { token }),
        apiFetch<{count: number}>('/notifications/unread-count', { token })
      ]);
      setNotifications(list);
      setUnreadCount(countData.count);
    } catch (e) {
      // fail silently
    }
  };

  useEffect(() => {
    fetchNotifications();
    
    if (!token || !user?.id) return;
    
    const echo = getEchoInstance(token);
    
    const channel = echo.private(`App.Models.User.${user.id}`);
    channel.listen('.NewNotification', (e: any) => {
      setNotifications(prev => [e.notification, ...prev]);
      setUnreadCount(prev => prev + 1);
    });

    return () => {
      echo.leave(`App.Models.User.${user.id}`);
    };
  }, [token, user?.id]);

  const handleMarkAsRead = async (id: string, e: React.MouseEvent) => {
    e.stopPropagation();
    if (!token) return;
    try {
      await apiFetch(`/notifications/${id}/mark-read`, { method: 'PUT', token });
      setNotifications(prev => prev.map(n => n.id === id ? { ...n, is_read: true } : n));
      setUnreadCount(prev => Math.max(0, prev - 1));
    } catch {}
  };

  const handleMarkAllRead = async () => {
    if (!token) return;
    try {
      await apiFetch('/notifications/mark-all-read', { method: 'PUT', token });
      setNotifications(prev => prev.map(n => ({ ...n, is_read: true })));
      setUnreadCount(0);
    } catch {}
  };

  const handleLogout = () => {
    logout();
    router.push('/admin/login');
  };

  // Close dropdowns on outside click
  useEffect(() => {
    function handleClick(e: MouseEvent) {
      if (searchRef.current && !searchRef.current.contains(e.target as Node)) setShowSearch(false);
      if (profileRef.current && !profileRef.current.contains(e.target as Node)) setShowProfile(false);
      if (notifRef.current && !notifRef.current.contains(e.target as Node)) setShowNotifications(false);
    }
    document.addEventListener('mousedown', handleClick);
    return () => document.removeEventListener('mousedown', handleClick);
  }, []);

  // Debounced search
  useEffect(() => {
    if (searchQuery.length < 2) {
      setSearchResults([]);
      setShowSearch(false);
      return;
    }

    const timer = setTimeout(async () => {
      try {
        const data = await apiFetch<{ results: SearchResult[] }>(
          `/search?q=${encodeURIComponent(searchQuery)}`,
          { token: token || undefined }
        );
        setSearchResults(data.results);
        setShowSearch(true);
      } catch {
        setSearchResults([]);
      }
    }, 300);

    return () => clearTimeout(timer);
  }, [searchQuery, token]);
  return (
    <header className="sticky top-0 z-50 h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6 shadow-sm">
      {/* Search */}
      <div ref={searchRef} className="relative flex-1 max-w-lg">
        <div className="relative">
          <span className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔍</span>
          <input
            type="text"
            placeholder="Search orders, products…"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="w-full pl-9 pr-4 py-2 bg-gray-100 border border-transparent rounded-lg text-sm focus:outline-none focus:border-teal-500 focus:bg-white focus:ring-2 focus:ring-teal-100 transition-all"
          />
        </div>

        {/* Search Results Dropdown */}
        {showSearch && searchResults.length > 0 && (
          <div className="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden z-50">
            {searchResults.map((result, i) => (
              <button
                key={`${result.type}-${result.id}-${i}`}
                onClick={() => {
                  router.push(result.url);
                  setShowSearch(false);
                  setSearchQuery('');
                }}
                className="w-full text-left px-4 py-3 hover:bg-teal-50 flex items-center gap-3 transition-colors border-b border-gray-50 last:border-0"
              >
                <span className="text-xs font-bold uppercase px-2 py-0.5 rounded bg-teal-100 text-teal-700">
                  {result.type}
                </span>
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-semibold text-gray-800 truncate">{result.title}</p>
                  <p className="text-xs text-gray-500 truncate">{result.subtitle}</p>
                </div>
              </button>
            ))}
          </div>
        )}
      </div>

      {/* Right side: Notifications + Profile */}
      <div className="flex items-center gap-3 ml-6">
        {/* Notifications */}
        <div ref={notifRef} className="relative">
          <button
            onClick={() => setShowNotifications(!showNotifications)}
            className="relative w-9 h-9 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors"
          >
            <span className="text-base">🔔</span>
            {unreadCount > 0 && (
              <span className="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-1 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                {unreadCount > 99 ? '99+' : unreadCount}
              </span>
            )}
          </button>

          {showNotifications && (
            <div className="absolute right-0 top-full mt-2 w-80 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden z-50">
              <div className="px-4 py-3 bg-gray-50 border-b flex items-center justify-between">
                <span className="font-semibold text-sm text-gray-700">Notifications</span>
                {unreadCount > 0 && (
                  <button onClick={handleMarkAllRead} className="text-[10px] text-teal-600 font-bold hover:underline">
                    Mark all read
                  </button>
                )}
              </div>
              <div className="max-h-64 overflow-y-auto">
                {notifications.length === 0 ? (
                  <div className="p-4 text-center text-sm text-gray-500">No notifications yet.</div>
                ) : (
                  notifications.map((n) => (
                    <div 
                      key={n.id} 
                      onClick={() => {
                        if (!n.is_read) handleMarkAsRead(n.id, { stopPropagation: () => {} } as any);
                        if (n.link) router.push(n.link);
                        setShowNotifications(false);
                      }}
                      className={`px-4 py-3 hover:bg-gray-50 flex items-start gap-3 border-b border-gray-50 cursor-pointer transition-colors ${!n.is_read ? 'bg-teal-50/30' : ''}`}
                    >
                      <span className="text-lg mt-0.5">
                        {n.type === 'order' && '📦'}
                        {n.type === 'payment' && '💲'}
                        {n.type === 'stock' && '⚠️'}
                        {n.type === 'system' && '⚙️'}
                      </span>
                      <div className="flex-1">
                        <div className="flex items-start justify-between gap-2">
                          <p className={`text-sm ${!n.is_read ? 'font-bold text-teal-900' : 'text-gray-800'}`}>{n.title}</p>
                          {!n.is_read && (
                            <button 
                              onClick={(e) => handleMarkAsRead(n.id, e)}
                              className="w-2 h-2 rounded-full bg-teal-500 mt-1.5 shrink-0" 
                              title="Mark as read"
                            />
                          )}
                        </div>
                        <p className="text-xs text-gray-600 mt-0.5">{n.message}</p>
                        <p className="text-[10px] text-gray-400 mt-1">
                          {new Date(n.created_at).toLocaleDateString()} at {new Date(n.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                        </p>
                      </div>
                    </div>
                  ))
                )}
              </div>
            </div>
          )}
        </div>

        {/* Profile Dropdown */}
        <div ref={profileRef} className="relative">
          <button
            onClick={() => setShowProfile(!showProfile)}
            className="flex items-center gap-2.5 px-3 py-1.5 rounded-lg hover:bg-gray-100 transition-colors"
          >
            <div className="w-8 h-8 rounded-full bg-teal-600 text-white flex items-center justify-center text-sm font-bold">
              {user?.name?.charAt(0).toUpperCase() || 'A'}
            </div>
            <div className="hidden md:block text-left">
              <p className="text-sm font-semibold text-gray-800 leading-tight">{user?.name || 'Admin'}</p>
              <p className="text-[10px] text-gray-500 leading-tight">Administrator</p>
            </div>
            <span className="text-gray-400 text-xs ml-1">▼</span>
          </button>

          {showProfile && (
            <div className="absolute right-0 top-full mt-2 w-56 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden z-50">
              <div className="px-4 py-3 border-b bg-gray-50">
                <p className="text-sm font-semibold text-gray-800">{user?.name}</p>
                <p className="text-xs text-gray-500">{user?.email}</p>
              </div>
              <div className="py-1">
                <button
                  onClick={handleLogout}
                  className="w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors flex items-center gap-2"
                >
                  <span>🚪</span> Logout
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </header>
  );
}
