'use client';

import { useState, useEffect } from 'react';
import { useAuth } from '@/contexts/AuthContext';
import { apiFetch } from '@/lib/api';
import { useToast } from '@/components/ui/Toast';
import PageHero from '@/components/admin/PageHero';
import { 
  Package, 
  Search, 
  RotateCcw, 
  CheckSquare, 
  TrendingUp,
  AlertCircle,
  ChevronLeft,
  ChevronRight
} from 'lucide-react';
import { TableSkeleton } from '@/components/ui/Skeleton';
import BulkActionToolbar from '@/components/admin/BulkActionToolbar';
import Pagination from '@/components/admin/Pagination';
import ContextToolbar from '@/components/admin/ContextToolbar';

interface StockItem {
  prod_id: string;
  code: string;
  name: string;
  category_id: string;
  stock_limit: number | null;
  stock_used: number;
}

export default function StockPage() {
  const { token } = useAuth();
  const { toast, confirm } = useToast();
  const [items, setItems] = useState<StockItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [updatingId, setUpdatingId] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  const [selectedIds, setSelectedIds] = useState<string[]>([]);

  // Pagination State
  const [currentPage, setCurrentPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const perPage = 20;

  useEffect(() => {
    if (!token) return;
    setLoading(true);
    const params = new URLSearchParams({
      page: currentPage.toString(),
      per_page: perPage.toString(),
      q: search
    });
    apiFetch<any>(`/stock?${params.toString()}`, { token })
      .then(res => {
        setItems(res.data);
        setLastPage(res.last_page);
        setTotal(res.total);
      })
      .finally(() => setLoading(false));
  }, [token, currentPage, search]);

  // Reset to page 1 on search
  useEffect(() => {
    setCurrentPage(1);
  }, [search]);

  const filteredItems = items || [];

  const toggleSelect = (id: string) => {
    setSelectedIds(prev => 
      prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]
    );
  };

  const toggleSelectAll = () => {
    if (selectedIds.length === filteredItems.length) {
      setSelectedIds([]);
    } else {
      setSelectedIds(filteredItems.map(i => i.prod_id));
    }
  };

  const handleBulkReset = async () => {
    if (!token || selectedIds.length === 0) return;
    if (!(await confirm(`Reset used stock to 0 for ${selectedIds.length} items?`))) return;

    try {
      await apiFetch('/stock/bulk-reset', {
        method: 'POST',
        token,
        body: JSON.stringify({ ids: selectedIds })
      });
      setItems(items.map(item => selectedIds.includes(item.prod_id) ? { ...item, stock_used: 0 } : item));
      setSelectedIds([]);
      toast.success(`Reset stock for ${selectedIds.length} items`);
    } catch (err) {
      toast.error('Failed to reset stock');
    }
  };

  const handleUpdateLimit = async (prodId: string, limit: number | null) => {
    if (!token) return;
    setUpdatingId(prodId);
    try {
      await apiFetch(`/stock/${prodId}`, {
        method: 'PUT',
        token,
        body: JSON.stringify({ stock_limit: limit })
      });
      setItems(items.map(item => item.prod_id === prodId ? { ...item, stock_limit: limit } : item));
      toast.success('Stock limit updated');
    } catch (err) {
      toast.error('Failed to update stock limit');
    } finally {
      setUpdatingId(null);
    }
  };

  if (loading) return <div className="p-10 text-center">Loading...</div>;

  return (
    <>
      <PageHero 
        title="Stock Limits" 
        subtitle="Manage product stock and inventory limits."
        breadcrumbs={[{ label: 'Stock Limits' }]}
      >
        <div className="relative group mr-4 hidden md:block">
           <Search className="absolute left-4 top-1/2 -translate-y-1/2 text-teal-300 group-focus-within:text-white transition-colors" size={18} />
           <input 
             type="text"
             placeholder="Search inventory..."
             value={search}
             onChange={(e) => setSearch(e.target.value)}
             className="pl-12 pr-6 py-3 bg-white/10 border border-white/20 rounded-2xl focus:outline-none focus:border-white transition-all font-bold text-sm text-white placeholder-teal-300/50 w-[300px]"
           />
        </div>
      </PageHero>

      <ContextToolbar>
          <div className="flex items-center gap-4 text-white px-4">
            <div>
               <h3 className="text-xs font-black uppercase tracking-widest text-teal-400">Inventory Overview</h3>
               <p className="text-[10px] text-white/60 font-bold uppercase tracking-widest">{total} products tracked</p>
            </div>
            <div className="w-px h-6 bg-white/10" />
            <div className="flex items-center gap-2">
               <AlertCircle size={14} className="text-amber-400" />
               <span className="text-[10px] font-bold uppercase tracking-wider text-amber-100">Stock warnings active</span>
            </div>

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
          <TableSkeleton rows={10} />
        ) : (
          <div className="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div className="px-6 py-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
              <h2 className="font-bold text-gray-800 text-sm uppercase tracking-wider">
                Showing {filteredItems.length} Products
              </h2>
            </div>
            
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="bg-gray-50/50 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                    <th className="px-6 py-4 w-10">
                      <input 
                        type="checkbox" 
                        checked={selectedIds.length > 0 && selectedIds.length === filteredItems.length}
                        onChange={toggleSelectAll}
                        className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                      />
                    </th>
                    <th className="px-6 py-4">Product</th>
                    <th className="px-6 py-4 text-center">Status</th>
                    <th className="px-6 py-4 text-center">Used</th>
                    <th className="px-6 py-4">Limit</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-50">
                  {filteredItems.map((item) => {
                    const isSelected = selectedIds.includes(item.prod_id);
                    const isLowStock = item.stock_limit !== null && (item.stock_limit - item.stock_used) <= 5;
                    
                    return (
                      <tr 
                        key={item.prod_id} 
                        className={`hover:bg-teal-50/30 transition-colors group ${isSelected ? 'bg-teal-50/50' : ''}`}
                      >
                        <td className="px-6 py-4">
                          <input 
                            type="checkbox" 
                            checked={isSelected}
                            onChange={() => toggleSelect(item.prod_id)}
                            className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                          />
                        </td>
                        <td className="px-6 py-4">
                          <div className="flex items-center gap-3">
                            <div className="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400 group-hover:bg-teal-100 group-hover:text-teal-600 transition-colors">
                              <Package size={14} />
                            </div>
                            <div>
                              <p className="font-bold text-gray-900 leading-tight">{item.name}</p>
                              <p className="text-[10px] font-mono text-gray-400">{item.code}</p>
                            </div>
                          </div>
                        </td>
                        <td className="px-6 py-4 text-center">
                          {item.stock_limit === null ? (
                            <span className="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Unlimited</span>
                          ) : isLowStock ? (
                            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-bold">
                              <AlertCircle size={10} /> Low Stock
                            </span>
                          ) : (
                            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-[10px] font-bold">
                              Healthy
                            </span>
                          )}
                        </td>
                        <td className="px-6 py-4 text-center">
                          <span className={`inline-block px-2 py-1 rounded-lg font-mono font-bold text-xs ${item.stock_used > 0 ? 'bg-amber-50 text-amber-600' : 'bg-gray-50 text-gray-400'}`}>
                            {item.stock_used}
                          </span>
                        </td>
                        <td className="px-6 py-4">
                          <div className="flex items-center gap-2">
                            <input 
                              type="number"
                              min="0"
                              placeholder="—"
                              value={item.stock_limit === null ? '' : item.stock_limit}
                              onChange={(e) => handleUpdateLimit(item.prod_id, e.target.value ? parseInt(e.target.value) : null)}
                              disabled={updatingId === item.prod_id}
                              className="w-24 px-3 py-1.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-teal-500 focus:ring-1 focus:ring-teal-500 disabled:opacity-50 transition-all"
                            />
                            {updatingId === item.prod_id && <span className="text-[10px] text-teal-600 font-bold animate-pulse">Saving...</span>}
                          </div>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
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
          { label: 'Reset Used Stock', icon: RotateCcw, onClick: handleBulkReset }
        ]}
      />
    </>
  );
}
