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
  Search, 
  ChevronRight, 
  ChevronLeft,
  Calendar, 
  Clock, 
  User as UserIcon, 
  CheckCircle2, 
  AlertCircle, 
  MoreVertical,
  Edit,
  TrendingUp,
  LayoutGrid,
  List,
  ShoppingCart,
  Building2,
  FileText,
  Trash2,
  Filter
} from 'lucide-react';
import ContextToolbar from '@/components/admin/ContextToolbar';
import { TableSkeleton, GridSkeleton } from '@/components/ui/Skeleton';
import BulkActionToolbar from '@/components/admin/BulkActionToolbar';
import Pagination from '@/components/admin/Pagination';

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



export default function OrdersPage() {
  const { token } = useAuth();
  const { toast, confirm } = useToast();
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [view, setView] = useState<'grid' | 'list'>('list');
  const [search, setSearch] = useState('');
  const [filterEvent, setFilterEvent] = useState('');
  const [filterStatus, setFilterStatus] = useState('');
  const [selectedIds, setSelectedIds] = useState<string[]>([]);
  const [events, setEvents] = useState<any[]>([]);
  const statuses = ['Pending', 'Approved', 'Invoiced', 'Fulfilled', 'Cancelled'];
  
  // Pagination State
  const [currentPage, setCurrentPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const perPage = 20;

  useEffect(() => {
    const saved = localStorage.getItem('orders_view');
    if (saved === 'grid' || saved === 'list') setView(saved);
    apiFetch<any[]>('/events', { token: token || undefined }).then(setEvents);
  }, []);

  useEffect(() => {
    if (!token) return;
    setLoading(true);
    const params = new URLSearchParams({
      page: currentPage.toString(),
      per_page: perPage.toString(),
      q: search,
      event: filterEvent,
      status: filterStatus
    });
    apiFetch<any>(`/orders?${params.toString()}`, { token: token || undefined })
      .then(res => {
        setOrders(res.data);
        setLastPage(res.last_page);
        setTotal(res.total);
      })
      .catch(console.error)
      .finally(() => setLoading(false));
  }, [token, currentPage, search, filterEvent, filterStatus]);

  // Reset to page 1 on search
  useEffect(() => {
    setCurrentPage(1);
  }, [search, filterEvent, filterStatus]);

  const filteredOrders = orders || [];

  const toggleSelect = (id: string) => {
    setSelectedIds(prev => 
      prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]
    );
  };

  const toggleSelectAll = () => {
    if (selectedIds.length === filteredOrders.length && filteredOrders.length > 0) {
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
      // Refresh list
      apiFetch<{ data: Order[] }>(`/orders?page=${currentPage}`, { token }).then(res => setOrders(res.data));
      toast.success(`${selectedIds.length} orders deleted`);
    } catch (err) {
      console.error(err);
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
      // Refresh list
      apiFetch<{ data: Order[] }>(`/orders?page=${currentPage}`, { token }).then(res => setOrders(res.data));
      toast.success(`Updated ${selectedIds.length} orders to ${status}`);
    } catch (err) {
      console.error(err);
      toast.error('Failed to update orders');
    }
  };

  return (
    <>
      <PageHero 
        title="Manage Orders" 
        subtitle="View and process customer orders across all events."
        breadcrumbs={[{ label: 'Orders' }]}
      >
        <div className="relative group mr-4 hidden md:block">
           <Search className="absolute left-4 top-1/2 -translate-y-1/2 text-teal-300 group-focus-within:text-white transition-colors" size={18} />
           <input 
             type="text"
             placeholder="Search orders..."
             value={search}
             onChange={(e) => setSearch(e.target.value)}
             className="pl-12 pr-6 py-3 bg-white/10 border border-white/20 rounded-2xl focus:outline-none focus:border-white transition-all font-bold text-sm text-white placeholder-teal-300/50 w-[300px]"
           />
        </div>
      </PageHero>

      <ContextToolbar>
          <div className="flex items-center gap-4 text-white px-4">
            <div className="flex items-center gap-2">
              <span className="text-[9px] font-black uppercase tracking-widest text-teal-400">Event</span>
              <select
                value={filterEvent}
                onChange={(e) => setFilterEvent(e.target.value)}
                className="bg-white/5 border border-white/10 rounded-xl px-3 py-1.5 text-xs font-bold focus:outline-none focus:border-teal-500"
              >
                <option value="" className="bg-[#0d2e2e]">All Events</option>
                {events.map((ev) => (
                  <option key={ev.slug} value={ev.slug} className="bg-[#0d2e2e]">{ev.short_name}</option>
                ))}
              </select>
            </div>
            
            <div className="w-px h-6 bg-white/10" />

            <div className="flex items-center gap-2">
              <span className="text-[9px] font-black uppercase tracking-widest text-teal-400">Status</span>
              <select
                value={filterStatus}
                onChange={(e) => setFilterStatus(e.target.value)}
                className="bg-white/5 border border-white/10 rounded-xl px-3 py-1.5 text-xs font-bold focus:outline-none focus:border-teal-500"
              >
                <option value="" className="bg-[#0d2e2e]">All Statuses</option>
                {statuses.map((s) => (
                  <option key={s} value={s} className="bg-[#0d2e2e]">{s}</option>
                ))}
              </select>
            </div>

            <div className="w-px h-6 bg-white/10" />
            
            <ViewToggle view={view} onChange={setView} storageKey="orders_view" />

            <div className="w-px h-6 bg-white/10" />

            <div className="flex items-center gap-2">
              <button
                onClick={() => setCurrentPage(prev => Math.max(1, prev - 1))}
                disabled={currentPage === 1}
                className="p-2 hover:bg-white/10 rounded-lg disabled:opacity-30"
              >
                <ChevronLeft size={18} />
              </button>
              <span className="text-[10px] font-black uppercase tracking-widest">Page {currentPage} / {lastPage}</span>
              <button
                onClick={() => setCurrentPage(prev => Math.min(lastPage, prev + 1))}
                disabled={currentPage === lastPage}
                className="p-2 hover:bg-white/10 rounded-lg disabled:opacity-30"
              >
                <ChevronRight size={18} />
              </button>
            </div>
          </div>
      </ContextToolbar>

      <div className="p-6 space-y-6">
        {loading ? (
          view === 'list' ? <TableSkeleton rows={10} /> : <GridSkeleton items={8} />
        ) : (
          <div className="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div className="px-6 py-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
              <h2 className="font-bold text-gray-800 text-sm uppercase tracking-wider">
                Showing {filteredOrders.length} Orders
              </h2>
            </div>

            {filteredOrders.length === 0 ? (
              <div className="p-20 text-center">
                <div className="w-16 h-16 bg-gray-50 text-gray-300 rounded-full flex items-center justify-center mx-auto mb-4">
                  <ShoppingCart size={32} />
                </div>
                <h3 className="text-gray-900 font-bold">No orders found</h3>
                <p className="text-gray-500 text-sm mt-1">Try adjusting your filters or search query.</p>
              </div>
            ) : view === 'list' ? (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="bg-gray-50/50 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                      <th className="px-6 py-4 w-10">
                        <input 
                          type="checkbox" 
                          checked={selectedIds.length > 0 && selectedIds.length === filteredOrders.length}
                          onChange={toggleSelectAll}
                          className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                        />
                      </th>
                      <th className="px-6 py-4">Reference</th>
                      <th className="px-6 py-4">Customer</th>
                      <th className="px-6 py-4">Event</th>
                      <th className="px-6 py-4 text-right">Total</th>
                      <th className="px-6 py-4 text-center">Status</th>
                      <th className="px-6 py-4 text-right">Date</th>
                      <th className="px-6 py-4"></th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredOrders.map((order) => (
                      <tr 
                        key={order.order_id} 
                        className={`border-b border-gray-50 transition-colors group ${selectedIds.includes(order.order_id) ? 'bg-teal-600 text-white' : 'hover:bg-teal-50/30'}`}
                      >
                        <td className="px-6 py-4">
                          <input 
                            type="checkbox" 
                            checked={selectedIds.includes(order.order_id)}
                            onChange={() => toggleSelect(order.order_id)}
                            className={`rounded border-gray-300 text-teal-600 focus:ring-teal-500 ${selectedIds.includes(order.order_id) ? 'accent-white' : ''}`}
                          />
                        </td>
                        <td className="px-6 py-4">
                          <Link href={`/admin/orders/${order.order_id}`} className={`font-mono text-xs font-bold hover:underline ${selectedIds.includes(order.order_id) ? 'text-teal-50' : 'text-teal-600'}`}>
                            {order.order_id}
                          </Link>
                        </td>
                        <td className="px-6 py-4">
                          <div className="flex items-center gap-3">
                            <div className={`w-8 h-8 rounded-lg flex items-center justify-center transition-colors ${selectedIds.includes(order.order_id) ? 'bg-teal-500 text-white' : 'bg-gray-100 text-gray-400 group-hover:bg-teal-100 group-hover:text-teal-600'}`}>
                              <Building2 size={14} />
                            </div>
                            <div>
                              <p className={`font-bold leading-tight ${selectedIds.includes(order.order_id) ? 'text-white' : 'text-gray-900'}`}>{order.company_name}</p>
                              <p className={`text-[10px] ${selectedIds.includes(order.order_id) ? 'text-teal-100' : 'text-gray-400'}`}>{order.contact_name}</p>
                            </div>
                          </div>
                        </td>
                        <td className="px-6 py-4">
                          <span className={`text-xs font-medium px-2 py-0.5 rounded uppercase tracking-tighter ${selectedIds.includes(order.order_id) ? 'bg-teal-500 text-white border border-teal-400' : 'bg-gray-100 text-gray-600'}`}>
                            {order.event_slug}
                          </span>
                        </td>
                        <td className={`px-6 py-4 text-right font-extrabold ${selectedIds.includes(order.order_id) ? 'text-white' : 'text-gray-900'}`}>
                          ${order.total.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                        </td>
                        <td className="px-6 py-4 text-center">
                          <span className={`inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider ${selectedIds.includes(order.order_id) ? 'bg-white text-teal-700' : (statusBadge[order.status] || 'bg-gray-100 text-gray-600')}`}>
                            {order.status}
                          </span>
                        </td>
                        <td className={`px-6 py-4 text-right text-xs font-medium ${selectedIds.includes(order.order_id) ? 'text-teal-100' : 'text-gray-400'}`}>
                          {new Date(order.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}
                        </td>
                        <td className="px-6 py-4 text-right">
                          <Link href={`/admin/orders/${order.order_id}`} className={`inline-flex items-center justify-center w-8 h-8 rounded-lg transition-all ${selectedIds.includes(order.order_id) ? 'hover:bg-teal-500 text-white' : 'hover:bg-gray-100 text-gray-400 hover:text-teal-600'}`}>
                            <ChevronRight size={18} />
                          </Link>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
                {filteredOrders.map((order) => (
                  <div
                    key={order.order_id}
                    onClick={() => toggleSelect(order.order_id)}
                    className={`bg-white border rounded-2xl p-6 hover:shadow-xl transition-all cursor-pointer relative group ${
                      selectedIds.includes(order.order_id) ? 'border-teal-500 ring-2 ring-teal-500/10 bg-teal-50/20' : 'border-gray-100 hover:border-teal-200'
                    }`}
                  >
                    <div className="flex items-center justify-between mb-4">
                      <span className="font-mono text-[10px] font-bold text-teal-600 px-2 py-1 bg-teal-50 rounded">{order.order_id}</span>
                      <span className={`px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider ${statusBadge[order.status] || 'bg-gray-100 text-gray-600'}`}>
                        {order.status}
                      </span>
                    </div>
                    
                    <div className="flex items-start gap-3 mb-6">
                      <div className="p-2 bg-gray-50 rounded-xl group-hover:bg-teal-50 group-hover:text-teal-600 transition-colors">
                        <Building2 size={20} />
                      </div>
                      <div>
                        <h3 className="font-bold text-gray-900 group-hover:text-teal-700 transition-colors">{order.company_name}</h3>
                        <p className="text-xs text-gray-500 flex items-center gap-1.5 mt-0.5">
                          <UserIcon size={12} /> {order.contact_name}
                        </p>
                      </div>
                    </div>

                    <div className="flex items-center justify-between pt-4 border-t border-gray-100">
                      <div>
                        <p className="text-[10px] text-gray-400 uppercase font-bold tracking-widest mb-0.5">Total Amount</p>
                        <p className="text-xl font-black text-teal-600">
                          ${order.total.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                        </p>
                      </div>
                      <div className="text-right">
                        <p className="text-[10px] text-gray-400 uppercase font-bold tracking-widest mb-0.5">Date</p>
                        <p className="text-xs font-bold text-gray-900">
                          {new Date(order.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' })}
                        </p>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        )}

        <Pagination 
          currentPage={currentPage}
          lastPage={lastPage}
          total={total}
          onPageChange={setCurrentPage}
          perPage={perPage}
        />
      </div>

      <BulkActionToolbar 
        selectedCount={selectedIds.length}
        onClear={() => setSelectedIds([])}
        actions={[
          { label: 'Mark as Approved', icon: CheckCircle2, onClick: () => handleBulkStatusUpdate('Approved') },
          { label: 'Mark as Invoiced', icon: FileText, onClick: () => handleBulkStatusUpdate('Invoiced'), variant: 'success' },
          { label: 'Mark as Fulfilled', icon: CheckCircle2, onClick: () => handleBulkStatusUpdate('Fulfilled') },
          { label: 'Export PDF', icon: FileText, onClick: () => toast.info('Bulk PDF export coming soon!') },
          { label: 'Delete', icon: Trash2, onClick: handleBulkDelete, variant: 'danger' }
        ]}
      />
    </>
  );
}
