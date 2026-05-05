'use client';

import { useState, useEffect } from 'react';
import { useAuth } from '@/contexts/AuthContext';
import { apiFetch } from '@/lib/api';
import { useToast } from '@/components/ui/Toast';
import { statusBadge } from '@/lib/constants';
import PageHero from '@/components/admin/PageHero';
import ViewToggle from '@/components/admin/ViewToggle';
import Link from 'next/link';
import { 
  Package, 
  DollarSign, 
  Clock, 
  CheckCircle2, 
  Search,
  Filter,
  MoreVertical,
  Trash2,
  TrendingUp
} from 'lucide-react';
import ContextToolbar from '@/components/admin/ContextToolbar';
import Skeleton, { GridSkeleton, TableSkeleton } from '@/components/ui/Skeleton';
import BulkActionToolbar from '@/components/admin/BulkActionToolbar';

interface Order {
  id: number;
  order_id: string;
  event_slug: string;
  company_name: string;
  booth_number: string;
  contact_name: string;
  email: string;
  total: number;
  status: string;
  created_at: string;
}

interface DashboardData {
  stats: {
    total_orders: number;
    total_revenue: number;
    pending_orders: number;
    paid_orders: number;
  };
  orders: Order[];
  events: { slug: string; short_name: string }[];
  statuses: string[];
}



export default function DashboardPage() {
  const { token } = useAuth();
  const { toast, confirm } = useToast();
  const [data, setData] = useState<DashboardData | null>(null);
  const [view, setView] = useState<'grid' | 'list'>('list');
  const [filterEvent, setFilterEvent] = useState('');
  const [filterStatus, setFilterStatus] = useState('');
  const [search, setSearch] = useState('');
  const [selectedIds, setSelectedIds] = useState<string[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const saved = localStorage.getItem('dashboard_view');
    if (saved === 'grid' || saved === 'list') setView(saved);
  }, []);

  useEffect(() => {
    if (!token) return;
    const params = new URLSearchParams();
    if (filterEvent) params.set('event', filterEvent);
    if (filterStatus) params.set('status', filterStatus);

    setLoading(true);
    apiFetch<DashboardData>(`/dashboard/stats?${params}`, { token })
      .then(setData)
      .catch(console.error)
      .finally(() => setLoading(false));
  }, [token, filterEvent, filterStatus]);

  const filteredOrders = (data?.orders.filter((o: Order) => {
    if (!search) return true;
    const q = search.toLowerCase();
    return (
      o.order_id.toLowerCase().includes(q) ||
      o.company_name.toLowerCase().includes(q) ||
      o.contact_name.toLowerCase().includes(q) ||
      o.email.toLowerCase().includes(q)
    );
  }) ?? []).slice(0, 10);

  const toggleSelect = (id: string) => {
    setSelectedIds(prev => 
      prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]
    );
  };

  const toggleSelectAll = () => {
    if (selectedIds.length === filteredOrders.length) {
      setSelectedIds([]);
    } else {
      setSelectedIds(filteredOrders.map((o: Order) => o.order_id));
    }
  };

  const handleBulkDelete = async () => {
    if (!token || selectedIds.length === 0) return;
    if (!(await confirm(`Are you sure you want to delete ${selectedIds.length} orders?`))) return;

    try {
      await apiFetch('/orders/bulk-delete', {
        method: 'POST',
        token,
        body: JSON.stringify({ ids: selectedIds })
      });
      setSelectedIds([]);
      // Refresh
      apiFetch<DashboardData>(`/dashboard/stats`, { token }).then(setData);
      toast.success(`${selectedIds.length} orders deleted`);
    } catch (err) {
      toast.error('Failed to delete orders');
    }
  };

  const handleBulkStatusUpdate = async (status: string) => {
    if (!token || selectedIds.length === 0) return;
    try {
      await apiFetch('/orders/bulk-status', {
        method: 'POST',
        token,
        body: JSON.stringify({ ids: selectedIds, status })
      });
      setSelectedIds([]);
      // Refresh
      apiFetch<DashboardData>(`/dashboard/stats`, { token }).then(setData);
      toast.success(`Updated ${selectedIds.length} orders to ${status}`);
    } catch (err) {
      toast.error('Failed to update orders');
    }
  };

  if (loading && !data) {
    return (
      <div className="p-6 space-y-6">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {Array.from({ length: 4 }).map((_, i) => <Skeleton key={i} className="h-24" />)}
        </div>
        <TableSkeleton rows={10} />
      </div>
    );
  }

  if (!data) return null;

  return (
    <>
      <PageHero 
        title="Admin Dashboard" 
        subtitle="Quick overview of your store's performance and recent activity."
        breadcrumbs={[{ label: 'Dashboard' }]} 
      />

      <ContextToolbar>
          <div className="flex items-center gap-4 text-white px-4">
            <div>
               <h3 className="text-xs font-black uppercase tracking-widest text-teal-400">Quick Filters</h3>
               <p className="text-[10px] text-white/60 font-bold uppercase tracking-widest">Filter by event or status</p>
            </div>
            <select
                value={filterEvent}
                onChange={(e) => setFilterEvent(e.target.value)}
                className="bg-white/5 border border-white/10 rounded-xl px-3 py-1.5 text-xs font-bold focus:outline-none focus:border-teal-500"
              >
                <option value="" className="bg-[#0d2e2e]">All Events</option>
                {data.events.map((ev) => (
                  <option key={ev.slug} value={ev.slug} className="bg-[#0d2e2e]">{ev.short_name}</option>
                ))}
              </select>
            
            <div className="w-px h-6 bg-white/10" />

            <div className="flex items-center gap-2">
              <select
                value={filterStatus}
                onChange={(e) => setFilterStatus(e.target.value)}
                className="bg-white/5 border border-white/10 rounded-xl px-3 py-1.5 text-xs font-bold focus:outline-none focus:border-teal-500"
              >
                <option value="" className="bg-[#0d2e2e]">All Statuses</option>
                {data.statuses.map((s) => (
                  <option key={s} value={s} className="bg-[#0d2e2e]">{s}</option>
                ))}
              </select>
            </div>

            <div className="w-px h-6 bg-white/10" />
            
            <ViewToggle view={view} onChange={setView} storageKey="dashboard_view" />
          </div>
      </ContextToolbar>

      <div className="p-6 space-y-6">

        <h3 className="text-xs font-black text-teal-600 uppercase tracking-widest mb-1">Store Overview</h3>
        <h2 className="text-2xl font-black text-gray-900 leading-tight">Welcome back!</h2>
        <p className="text-sm text-gray-500 mt-2 font-medium">
          Your store revenue is up 12.5% this week. We've found new orders waiting for review.
        </p>

        {/* Stat Cards */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {[
            { label: 'Total Orders', value: data.stats.total_orders, icon: Package, color: 'text-teal-600', bg: 'bg-teal-50' },
            { label: 'Revenue (USD)', value: `$${data.stats.total_revenue.toLocaleString(undefined, { minimumFractionDigits: 2 })}`, icon: DollarSign, color: 'text-blue-600', bg: 'bg-blue-50' },
            { label: 'Pending', value: data.stats.pending_orders, icon: Clock, color: 'text-amber-600', bg: 'bg-amber-50' },
            { label: 'Paid / Confirmed', value: data.stats.paid_orders, icon: CheckCircle2, color: 'text-green-600', bg: 'bg-green-50' },
          ].map((stat) => (
            <div key={stat.label} className="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-start gap-4">
              <div className={`p-3 rounded-lg ${stat.bg} ${stat.color}`}>
                <stat.icon size={24} />
              </div>
              <div>
                <p className="text-2xl font-extrabold text-[#0d2e2e]">{stat.value}</p>
                <p className="text-xs text-gray-500 uppercase tracking-wider mt-0.5">{stat.label}</p>
              </div>
            </div>
          ))}
        </div>



        <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden max-w-full">
          <div className="px-5 py-3 bg-teal-600 text-white font-semibold text-sm flex items-center justify-between">
            <span>Recent Activity ({filteredOrders.length})</span>
            <Link href="/admin/orders" className="text-xs text-teal-100 hover:text-white transition-colors">
               View All Orders
            </Link>
          </div>

          {filteredOrders.length === 0 ? (
            <div className="p-10 text-center text-gray-400">
              No orders found.
            </div>
          ) : view === 'list' ? (
            /* LIST VIEW */
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="bg-gray-50 text-left text-xs text-gray-500 uppercase tracking-wider">
                    <th className="px-5 py-3 w-10">
                      <input 
                        type="checkbox" 
                        checked={selectedIds.length > 0 && selectedIds.length === filteredOrders.length}
                        onChange={toggleSelectAll}
                        className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                      />
                    </th>
                    <th className="px-5 py-3">Order ID</th>
                    <th className="px-5 py-3">Event</th>
                    <th className="px-5 py-3">Company</th>
                    <th className="px-5 py-3">Contact</th>
                    <th className="px-5 py-3 text-right">Total (USD)</th>
                    <th className="px-5 py-3">Status</th>
                    <th className="px-5 py-3">Date</th>
                    <th className="px-5 py-3"></th>
                  </tr>
                </thead>
                <tbody>
                  {filteredOrders.map((order) => (
                    <tr key={order.order_id} className={`border-b border-gray-50 hover:bg-teal-50/40 transition-colors ${selectedIds.includes(order.order_id) ? 'bg-teal-50/60' : ''}`}>
                      <td className="px-5 py-3">
                        <input 
                          type="checkbox" 
                          checked={selectedIds.includes(order.order_id)}
                          onChange={() => toggleSelect(order.order_id)}
                          className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                        />
                      </td>
                      <td className="px-5 py-3">
                        <Link href={`/admin/orders/${order.order_id}`} className="font-bold text-teal-600 font-mono text-xs hover:underline">
                          {order.order_id}
                        </Link>
                      </td>
                      <td className="px-5 py-3 text-xs text-gray-500">{order.event_slug}</td>
                      <td className="px-5 py-3 font-medium">{order.company_name}</td>
                      <td className="px-5 py-3">
                        <p className="text-xs">{order.contact_name}</p>
                        <p className="text-xs text-gray-400">{order.email}</p>
                      </td>
                      <td className="px-5 py-3 text-right font-bold">${order.total.toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                      <td className="px-5 py-3">
                        <span className={`inline-block px-2.5 py-0.5 rounded-full text-[11px] font-semibold ${statusBadge[order.status] || 'bg-gray-100 text-gray-600'}`}>
                          {order.status}
                        </span>
                      </td>
                      <td className="px-5 py-3 text-xs text-gray-400 whitespace-nowrap">
                        {new Date(order.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}
                      </td>
                      <td className="px-5 py-3">
                        <Link href={`/admin/orders/${order.order_id}`} className="text-xs text-teal-600 border border-teal-200 px-3 py-1 rounded-lg hover:bg-teal-50 transition-colors font-medium">
                          View
                        </Link>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            /* GRID VIEW */
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 p-5">
              {filteredOrders.map((order) => (
                <div
                  key={order.order_id}
                  onClick={() => toggleSelect(order.order_id)}
                  className={`bg-white border rounded-xl p-5 hover:shadow-lg transition-all group cursor-pointer relative ${
                    selectedIds.includes(order.order_id) ? 'border-teal-500 ring-1 ring-teal-500 bg-teal-50/20' : 'border-gray-200 hover:border-teal-200'
                  }`}
                >
                  <div className="absolute top-3 left-3">
                    <div className={`w-4 h-4 rounded-full border flex items-center justify-center transition-colors ${
                      selectedIds.includes(order.order_id) ? 'bg-teal-600 border-teal-600' : 'bg-white border-gray-300'
                    }`}>
                      {selectedIds.includes(order.order_id) && <CheckCircle2 size={10} className="text-white" />}
                    </div>
                  </div>
                  <div className="flex items-center justify-between mb-3 ml-6">
                    <Link href={`/admin/orders/${order.order_id}`} onClick={e => e.stopPropagation()} className="font-mono text-xs font-bold text-teal-600 hover:underline">
                      {order.order_id}
                    </Link>
                    <span className={`px-2.5 py-0.5 rounded-full text-[11px] font-semibold ${statusBadge[order.status] || 'bg-gray-100 text-gray-600'}`}>
                      {order.status}
                    </span>
                  </div>
                  <h3 className="font-semibold text-[#0d2e2e] text-sm group-hover:text-teal-700 transition-colors ml-6">{order.company_name}</h3>
                  <p className="text-xs text-gray-500 mt-1 ml-6">{order.contact_name} — {order.email}</p>
                  <div className="flex items-center justify-between mt-4 pt-3 border-t border-gray-100">
                    <span className="text-lg font-extrabold text-teal-600">
                      ${order.total.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                    </span>
                    <span className="text-[11px] text-gray-400">
                      {new Date(order.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' })}
                    </span>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      <BulkActionToolbar 
        selectedCount={selectedIds.length} 
        onClear={() => setSelectedIds([])}
        actions={[
          { label: 'Mark Approved', icon: CheckCircle2, onClick: () => handleBulkStatusUpdate('Approved') },
          { label: 'Delete', icon: Trash2, onClick: handleBulkDelete, variant: 'danger' }
        ]}
      />
    </>
  );
}
