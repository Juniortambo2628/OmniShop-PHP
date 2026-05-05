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
  TrendingUp,
  AlertCircle,
  ChevronLeft,
  ChevronRight,
  Save,
  Layers,
  CheckCircle2,
  Filter
} from 'lucide-react';
import { TableSkeleton } from '@/components/ui/Skeleton';
import ContextToolbar from '@/components/admin/ContextToolbar';
import Pagination from '@/components/admin/Pagination';

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
  const [search, setSearch] = useState('');
  const [selectedIds, setSelectedIds] = useState<string[]>([]);
  const [pendingChanges, setPendingChanges] = useState<Record<string, number | null>>({});
  const [savingAll, setSavingAll] = useState(false);

  // Pagination State
  const [currentPage, setCurrentPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const perPage = 20;

  useEffect(() => {
    if (!token) return;
    fetchData();
  }, [token, currentPage, search]);

  const fetchData = async () => {
    setLoading(true);
    try {
        const params = new URLSearchParams({
            page: currentPage.toString(),
            per_page: perPage.toString(),
            q: search
        });
        const res = await apiFetch<any>(`/stock?${params.toString()}`, { token });
        setItems(res.data);
        setLastPage(res.last_page);
        setTotal(res.total);
    } finally {
        setLoading(false);
    }
  };

  // Reset to page 1 on search
  useEffect(() => {
    setCurrentPage(1);
  }, [search]);

  const toggleSelect = (id: string) => {
    setSelectedIds(prev => 
      prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]
    );
  };

  const toggleSelectAll = () => {
    if (selectedIds.length === items.length) {
      setSelectedIds([]);
    } else {
      setSelectedIds(items.map(i => i.prod_id));
    }
  };

  const handleLocalUpdate = (prodId: string, limit: number | null) => {
    setPendingChanges(prev => ({ ...prev, [prodId]: limit }));
  };

  const handleSaveAll = async () => {
    if (!token || Object.keys(pendingChanges).length === 0) return;
    setSavingAll(true);
    try {
      await Promise.all(
        Object.entries(pendingChanges).map(([id, limit]) => 
          apiFetch(`/stock/${id}`, {
            method: 'PUT',
            token,
            body: JSON.stringify({ stock_limit: limit })
          })
        )
      );
      
      setItems(prev => prev.map(item => 
        pendingChanges[item.prod_id] !== undefined 
          ? { ...item, stock_limit: pendingChanges[item.prod_id] } 
          : item
      ));
      setPendingChanges({});
      toast.success(`Synchronized ${Object.keys(pendingChanges).length} items`);
    } catch (err) {
      toast.error('Failed to save some changes');
    } finally {
      setSavingAll(false);
    }
  };

  const handleBulkSetLimit = async () => {
    const limitStr = prompt(`Set stock limit for ${selectedIds.length} items:`, "100");
    if (limitStr === null) return;
    
    const limit = limitStr === "" ? null : parseInt(limitStr);
    if (limit !== null && isNaN(limit)) return toast.error("Invalid number");

    const newPending = { ...pendingChanges };
    selectedIds.forEach(id => {
      newPending[id] = limit;
    });
    setPendingChanges(newPending);
    toast.info(`Modified ${selectedIds.length} items in local buffer`);
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
      fetchData();
      setSelectedIds([]);
      toast.success(`Reset stock for ${selectedIds.length} items`);
    } catch (err) {
      toast.error('Failed to reset stock');
    }
  };

  const hasChanges = Object.keys(pendingChanges).length > 0;

  return (
    <>
      <PageHero 
        title="Stock Control" 
        subtitle="Manage product limits and track usage across all catalogs."
        breadcrumbs={[{ label: 'Inventory' }, { label: 'Stock Control' }]}
      >
        <div className="relative group mr-4 hidden md:block">
           <Search className="absolute left-4 top-1/2 -translate-y-1/2 text-teal-300 group-focus-within:text-white transition-colors" size={18} />
           <input 
             type="text"
             placeholder="Filter products..."
             value={search}
             onChange={(e) => setSearch(e.target.value)}
             className="pl-12 pr-6 py-3 bg-white/10 border border-white/20 rounded-2xl focus:outline-none focus:border-white transition-all font-bold text-sm text-white placeholder-teal-300/50 w-[300px]"
           />
        </div>
      </PageHero>

      <ContextToolbar>
          <div className="flex items-center justify-between w-full px-4 text-white">
            <div className="flex items-center gap-6">
                {selectedIds.length > 0 ? (
                    <div className="flex items-center gap-4 bg-white/10 px-4 py-2 rounded-xl border border-white/10">
                        <span className="text-[10px] font-black uppercase tracking-tighter text-teal-400">{selectedIds.length} SELECTED</span>
                        <div className="w-px h-4 bg-white/20" />
                        <button onClick={handleBulkSetLimit} className="flex items-center gap-2 text-[10px] font-bold uppercase hover:text-teal-300 transition-colors">
                            <Layers size={14} /> Set Limit
                        </button>
                        <button onClick={handleBulkReset} className="flex items-center gap-2 text-[10px] font-bold uppercase hover:text-amber-300 transition-colors">
                            <RotateCcw size={14} /> Reset Usage
                        </button>
                        <button onClick={() => setSelectedIds([])} className="text-[10px] font-bold text-white/40 hover:text-white transition-colors">
                            CLEAR
                        </button>
                    </div>
                ) : (
                    <div className="flex items-center gap-4">
                        <div>
                            <h3 className="text-xs font-black uppercase tracking-widest text-teal-400 leading-none">Inventory</h3>
                            <p className="text-[10px] text-white/40 font-bold uppercase tracking-widest mt-1">{total} TOTAL PRODUCTS</p>
                        </div>
                        <div className="w-px h-6 bg-white/10" />
                        <div className="flex items-center gap-4">
                             <div className="flex items-center gap-2">
                                <CheckCircle2 size={14} className="text-teal-400" />
                                <span className="text-[10px] font-bold uppercase text-white/80">LIVE DATA</span>
                             </div>
                        </div>
                    </div>
                )}
            </div>

            <div className="flex items-center gap-4">
                {hasChanges && (
                    <button 
                        onClick={handleSaveAll}
                        disabled={savingAll}
                        className="flex items-center gap-2 bg-teal-500 hover:bg-teal-400 text-white px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-teal-500/20 transition-all animate-pulse"
                    >
                        <Save size={14} /> {savingAll ? 'Syncing...' : `Sync ${Object.keys(pendingChanges).length} Changes`}
                    </button>
                )}

                <div className="flex items-center gap-2 bg-black/20 rounded-xl p-1">
                    <button
                        onClick={() => setCurrentPage(prev => Math.max(1, prev - 1))}
                        disabled={currentPage === 1}
                        className="p-1.5 hover:bg-white/10 rounded-lg disabled:opacity-30"
                    >
                        <ChevronLeft size={16} />
                    </button>
                    <span className="text-[10px] font-black uppercase tracking-widest px-2">{currentPage} / {lastPage}</span>
                    <button
                        onClick={() => setCurrentPage(prev => Math.min(lastPage, prev + 1))}
                        disabled={currentPage === lastPage}
                        className="p-1.5 hover:bg-white/10 rounded-lg disabled:opacity-30"
                    >
                        <ChevronRight size={16} />
                    </button>
                </div>
            </div>
          </div>
      </ContextToolbar>

      <div className="p-6">
        {loading && !items.length ? (
          <TableSkeleton rows={10} />
        ) : (
          <div className="bg-white rounded-3xl border border-gray-100 shadow-xl shadow-gray-200/50 overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="bg-gray-50/50 text-left text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] border-b border-gray-100">
                    <th className="px-8 py-5 w-10">
                      <input 
                        type="checkbox" 
                        checked={items.length > 0 && selectedIds.length === items.length}
                        onChange={toggleSelectAll}
                        className="rounded-lg border-gray-300 text-teal-600 focus:ring-teal-500 w-4 h-4 cursor-pointer"
                      />
                    </th>
                    <th className="px-8 py-5">Product Details</th>
                    <th className="px-8 py-5 text-center">Current Status</th>
                    <th className="px-8 py-5 text-center">Usage</th>
                    <th className="px-8 py-5 w-[200px]">Stock Limit</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-50">
                  {items.map((item) => {
                    const isSelected = selectedIds.includes(item.prod_id);
                    const currentLimit = pendingChanges[item.prod_id] !== undefined 
                        ? pendingChanges[item.prod_id] 
                        : item.stock_limit;
                    const isLowStock = currentLimit !== null && (currentLimit - item.stock_used) <= 5;
                    const isChanged = pendingChanges[item.prod_id] !== undefined;
                    
                    return (
                      <tr 
                        key={item.prod_id} 
                        className={`hover:bg-teal-50/30 transition-all group ${isSelected ? 'bg-teal-50/60' : ''}`}
                      >
                        <td className="px-8 py-4">
                          <input 
                            type="checkbox" 
                            checked={isSelected}
                            onChange={() => toggleSelect(item.prod_id)}
                            className="rounded-lg border-gray-200 text-teal-600 focus:ring-teal-500 w-4 h-4 cursor-pointer"
                          />
                        </td>
                        <td className="px-8 py-4">
                          <div className="flex items-center gap-4">
                            <div className="w-10 h-10 bg-gray-50 rounded-2xl flex items-center justify-center text-gray-400 group-hover:bg-white group-hover:shadow-md transition-all border border-transparent group-hover:border-teal-100">
                              <Package size={18} />
                            </div>
                            <div>
                              <p className="font-black text-gray-900 tracking-tight">{item.name}</p>
                              <p className="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{item.code}</p>
                            </div>
                          </div>
                        </td>
                        <td className="px-8 py-4 text-center">
                          {currentLimit === null ? (
                            <span className="text-[10px] font-black text-gray-300 uppercase tracking-widest">Unlimited</span>
                          ) : isLowStock ? (
                            <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-50 text-red-600 text-[10px] font-black uppercase tracking-widest ring-1 ring-red-200">
                              <AlertCircle size={10} /> Critical
                            </span>
                          ) : (
                            <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-teal-50 text-teal-600 text-[10px] font-black uppercase tracking-widest ring-1 ring-teal-200">
                              <TrendingUp size={10} /> Active
                            </span>
                          )}
                        </td>
                        <td className="px-8 py-4 text-center">
                          <span className={`inline-block px-3 py-1.5 rounded-xl font-mono font-black text-xs min-w-[40px] ${item.stock_used > 0 ? 'bg-amber-50 text-amber-600 ring-1 ring-amber-100' : 'bg-gray-50 text-gray-400'}`}>
                            {item.stock_used}
                          </span>
                        </td>
                        <td className="px-8 py-4">
                          <div className="flex items-center gap-3">
                            <div className="relative flex-1">
                                <input 
                                  type="number"
                                  min="0"
                                  placeholder="∞"
                                  value={currentLimit === null ? '' : currentLimit}
                                  onChange={(e) => handleLocalUpdate(item.prod_id, e.target.value ? parseInt(e.target.value) : null)}
                                  className={`w-full px-4 py-2 border-2 rounded-xl text-sm font-bold focus:outline-none transition-all ${isChanged ? 'bg-teal-50 border-teal-200 text-teal-700' : 'bg-gray-50 border-gray-100 hover:border-gray-200 text-gray-900 focus:border-teal-500'}`}
                                />
                                {isChanged && (
                                    <div className="absolute -top-2 -right-2 w-4 h-4 bg-teal-500 rounded-full border-2 border-white flex items-center justify-center">
                                        <CheckCircle2 size={8} className="text-white" />
                                    </div>
                                )}
                            </div>
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

        <div className="mt-8 flex justify-center">
            <Pagination 
              currentPage={currentPage}
              lastPage={lastPage}
              total={total}
              onPageChange={setCurrentPage}
              perPage={perPage}
            />
        </div>
      </div>
    </>
  );
}
