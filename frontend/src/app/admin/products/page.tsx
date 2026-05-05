'use client';

import { useState, useEffect } from 'react';
import { useAuth } from '@/contexts/AuthContext';
import { apiFetch } from '@/lib/api';
import { useToast } from '@/components/ui/Toast';
import PageHero from '@/components/admin/PageHero';
import ViewToggle from '@/components/admin/ViewToggle';
import Link from 'next/link';
import Modal from '@/components/ui/Modal';
import ProductForm from '@/components/admin/ProductForm';
import { Package, MoreVertical, Edit3, Eye, Trash2, LayoutGrid, List, CheckSquare, EyeOff, Search, Plus, ChevronLeft, ChevronRight } from 'lucide-react';
import BulkActionToolbar from '@/components/admin/BulkActionToolbar';
import Pagination from '@/components/admin/Pagination';
import ContextToolbar from '@/components/admin/ContextToolbar';

interface Product {
  source: string;
  db_id: number | null;
  code: string;
  name: string;
  category_id: string;
  price: number;
  price_display: string;
  is_poa: boolean;
  is_active: boolean;
  dimensions: string;
  colors: { id: string; name: string }[];
  catalog_id: string | null;
  image: string | null;
}

interface ProductsData {
  data: Product[];
  categories: Record<string, string>;
  total: number;
  current_page: number;
  last_page: number;
  per_page: number;
}

const sourceStyles: Record<string, { dot: string; label: string; color: string }> = {
  builtin:  { dot: 'bg-green-500', label: 'Standard', color: 'text-green-600' },
  modified: { dot: 'bg-amber-500', label: 'Modified', color: 'text-amber-600' },
  custom:   { dot: 'bg-teal-500',  label: 'Custom',   color: 'text-teal-600' },
};

export default function ProductsPage() {
  const { token } = useAuth();
  const { toast, confirm } = useToast();
  const [data, setData] = useState<ProductsData | null>(null);
  const [view, setView] = useState<'grid' | 'list'>('list');
  const [filterCat, setFilterCat] = useState('');
  const [search, setSearch] = useState('');

  // Pagination State
  const [currentPage, setCurrentPage] = useState(1);
  const perPage = 24;
  
  // Selection state
  const [selectedIds, setSelectedIds] = useState<number[]>([]);

  // Modal state
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [activeProductId, setActiveProductId] = useState<string | undefined>(undefined);
  const [activeCatalogId, setActiveCatalogId] = useState<string | undefined>(undefined);

  const fetchProducts = () => {
    if (!token) return;
    const params = new URLSearchParams({
      page: currentPage.toString(),
      per_page: perPage.toString()
    });
    if (filterCat) params.set('cat', filterCat);
    if (search) params.set('q', search);

    apiFetch<ProductsData>(`/products?${params}`, { token })
      .then(setData)
      .catch(console.error);
  };

  useEffect(() => {
    const saved = localStorage.getItem('products_view');
    if (saved === 'grid' || saved === 'list') setView(saved);
  }, []);

  useEffect(() => {
    fetchProducts();
  }, [token, filterCat, search, currentPage]);

  // Reset to page 1 on filter/search
  useEffect(() => {
    setCurrentPage(1);
  }, [filterCat, search]);

  const openAddModal = (catalogId?: string) => {
    setActiveProductId(undefined);
    setActiveCatalogId(catalogId);
    setIsModalOpen(true);
  };

  const openEditModal = (productId?: number | null) => {
    setActiveProductId(productId?.toString());
    setActiveCatalogId(undefined);
    setIsModalOpen(true);
  };

  const handleModalSuccess = () => {
    setIsModalOpen(false);
    fetchProducts();
  };

  const toggleSelect = (dbId: number | null) => {
    if (!dbId) return; // Can't select builtin products for bulk actions currently (unless they are overrides)
    setSelectedIds(prev => 
      prev.includes(dbId) ? prev.filter(id => id !== dbId) : [...prev, dbId]
    );
  };

  const toggleSelectAll = () => {
    const selectableIds = (data?.data || [])
      .map((p: Product) => p.db_id)
      .filter((id): id is number => id !== null);

    if (selectedIds.length === selectableIds.length && selectableIds.length > 0) {
      setSelectedIds([]);
    } else {
      setSelectedIds(selectableIds);
    }
  };

  const handleBulkDelete = async () => {
    if (!token || selectedIds.length === 0) return;
    if (!(await confirm(`Are you sure you want to delete ${selectedIds.length} products from your overrides?`))) return;

    try {
      await apiFetch('/products/bulk-delete', {
        method: 'POST',
        token,
        body: JSON.stringify({ ids: selectedIds })
      });
      setSelectedIds([]);
      fetchProducts();
      toast.success(`${selectedIds.length} products deleted`);
    } catch (err) {
      console.error(err);
      toast.error('Failed to delete products');
    }
  };

  const handleBulkStatusUpdate = async (isActive: boolean) => {
    if (!token || selectedIds.length === 0) return;

    try {
      await apiFetch('/products/bulk-status', {
        method: 'POST',
        token,
        body: JSON.stringify({ ids: selectedIds, is_active: isActive })
      });
      setSelectedIds([]);
      fetchProducts();
      toast.success(`Updated ${selectedIds.length} products`);
    } catch (err) {
      console.error(err);
      toast.error('Failed to update products');
    }
  };

  if (!data) {
    return (
      <div className="flex-1 flex items-center justify-center">
        <div className="w-8 h-8 border-4 border-teal-600 border-t-transparent rounded-full animate-spin" />
      </div>
    );
  }

  const products = data.data;
  const totalBuiltin = products.filter((p: Product) => p.source === 'builtin').length;
  const totalModified = products.filter((p: Product) => p.source === 'modified').length;
  const totalCustom = products.filter((p: Product) => p.source === 'custom').length;
  const lastPage = data.last_page;
  const categories = Object.values(data.categories || {});

  return (
    <>
      <PageHero 
        title="Manage Products" 
        subtitle="Add, edit, and organize your product catalog."
        breadcrumbs={[{ label: 'Products' }]}
      >
        <div className="relative group mr-4 hidden md:block">
           <Search className="absolute left-4 top-1/2 -translate-y-1/2 text-teal-300 group-focus-within:text-white transition-colors" size={18} />
           <input 
             type="text"
             placeholder="Search products..."
             value={search}
             onChange={(e) => setSearch(e.target.value)}
             className="pl-12 pr-6 py-3 bg-white/10 border border-white/20 rounded-2xl focus:outline-none focus:border-white transition-all font-bold text-sm text-white placeholder-teal-300/50 w-[300px]"
           />
        </div>
        <Link href="/admin/products/add" className="bg-white text-teal-600 px-6 py-3 rounded-xl font-bold text-sm hover:bg-teal-50 transition-all flex items-center gap-2 whitespace-nowrap shadow-lg">
          <Plus size={18} /> Add Product
        </Link>
      </PageHero>

      <ContextToolbar>
          <div className="flex items-center gap-4 text-white px-4">
            <div className="flex items-center gap-2">
              <span className="text-[9px] font-black uppercase tracking-widest text-teal-400">Category</span>
              <select
                value={filterCat}
                onChange={(e) => setFilterCat(e.target.value)}
                className="bg-white/5 border border-white/10 rounded-xl px-3 py-1.5 text-xs font-bold focus:outline-none focus:border-teal-500 min-w-[150px]"
              >
                <option value="" className="bg-[#0d2e2e]">All Categories</option>
                {categories.map((cat) => (
                  <option key={cat} value={cat} className="bg-[#0d2e2e]">{cat}</option>
                ))}
              </select>
            </div>
            
            <div className="w-px h-6 bg-white/10" />
            
            <ViewToggle view={view} onChange={setView} storageKey="products_view" />

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
        {/* Stat Pills */}
        <div className="flex flex-wrap gap-3">
          {[
            { label: 'Standard', count: totalBuiltin, bg: 'bg-teal-600', text: 'text-white' },
            { label: 'Modified', count: totalModified, bg: totalModified ? 'bg-amber-500' : 'bg-gray-100', text: totalModified ? 'text-white' : 'text-gray-500' },
            { label: 'Custom', count: totalCustom, bg: totalCustom ? 'bg-teal-500' : 'bg-gray-100', text: totalCustom ? 'text-white' : 'text-gray-500' },
          ].map((s) => (
            <div key={s.label} className={`${s.bg} ${s.text} rounded-lg px-5 py-3 min-w-[120px]`}>
              <p className="text-2xl font-bold leading-none">{s.count}</p>
              <p className="text-xs opacity-80 mt-1">{s.label}</p>
            </div>
          ))}
        </div>



        {/* Products */}
        <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden mb-6">
          <div className="px-5 py-3 bg-teal-600 text-white font-semibold text-sm flex items-center justify-between">
            <span>
              {filterCat ? data?.categories[filterCat] || filterCat : 'All Products'} — {data?.total || 0} total
            </span>
            <span className="text-xs font-normal opacity-80">
              🟢 Standard &nbsp;|&nbsp; 🟡 Modified &nbsp;|&nbsp; 🔵 Custom
            </span>
          </div>

          {products.length === 0 ? (
            <div className="p-10 text-center text-gray-400">No products found.</div>
          ) : view === 'list' ? (
            /* LIST VIEW */
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="bg-gray-50/50 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                    <th className="px-5 py-4 w-10">
                      <input 
                        type="checkbox" 
                        checked={selectedIds.length > 0 && selectedIds.length === products.filter((p: Product) => p.db_id).length}
                        onChange={toggleSelectAll}
                        className="rounded border-gray-300 text-teal-600 focus:ring-teal-500 mx-auto block"
                      />
                    </th>
                    <th className="px-5 py-4">Product</th>
                    <th className="px-5 py-4">Category</th>
                    <th className="px-5 py-4 text-right">Price</th>
                    <th className="px-5 py-4">Colors</th>
                    <th className="px-5 py-4 text-center">Status</th>
                    <th className="px-5 py-4"></th>
                  </tr>
                </thead>
                <tbody>
                  {products.map((p: Product, i: number) => {
                    const src = sourceStyles[p.source] || sourceStyles.builtin;
                    const isSelected = p.db_id ? selectedIds.includes(p.db_id) : false;

                    return (
                      <tr 
                        key={`${p.code}-${i}`} 
                        className={`border-b border-gray-50 transition-colors group ${isSelected ? 'bg-teal-600 text-white' : 'hover:bg-teal-50/30'}`}
                      >
                        <td className="px-5 py-3">
                          {p.db_id ? (
                            <input 
                              type="checkbox" 
                              checked={isSelected}
                              onChange={() => toggleSelect(p.db_id)}
                              className={`rounded border-gray-300 text-teal-600 focus:ring-teal-500 mx-auto block ${isSelected ? 'accent-white' : ''}`}
                            />
                          ) : (
                             <div className="w-4 h-4 mx-auto border border-gray-100 rounded bg-gray-50 opacity-30" />
                          )}
                        </td>
                        <td className="px-5 py-3">
                          <div className="flex items-center gap-3">
                            <div className="w-10 h-10 bg-white rounded-lg overflow-hidden flex items-center justify-center p-1 border border-gray-100">
                              <img 
                                src={`/static/images/products/${p.image || 'placeholder.jpg'}`} 
                                alt={p.name}
                                className="max-w-full max-h-full object-contain mix-blend-multiply" 
                              />
                            </div>
                            <div>
                              <p className={`font-bold leading-tight ${isSelected ? 'text-white' : 'text-gray-900'}`}>{p.name}</p>
                              {p.source !== 'builtin' && (
                                <span className={`text-[10px] font-semibold ${isSelected ? 'text-teal-200' : src.color}`}>● {src.label}</span>
                              )}
                              {p.dimensions && <p className={`text-[11px] mt-0.5 ${isSelected ? 'text-teal-100' : 'text-gray-400'}`}>{p.dimensions}</p>}
                            </div>
                          </div>
                        </td>
                        <td className={`px-5 py-3 text-xs ${isSelected ? 'text-teal-100' : 'text-gray-500'}`}>{data.categories[p.category_id] || p.category_id}</td>
                        <td className="px-5 py-3 text-right whitespace-nowrap">
                          {p.is_poa ? (
                            <em className={`text-xs ${isSelected ? 'text-teal-200' : 'text-gray-400'}`}>POA</em>
                          ) : (
                            <strong className={isSelected ? 'text-white' : ''}>${Number(p.price).toFixed(2)}</strong>
                          )}
                        </td>
                        <td className={`px-5 py-3 text-xs max-w-[160px] truncate ${isSelected ? 'text-teal-100' : 'text-gray-500'}`}>
                          {p.colors.length > 0 ? p.colors.map((c: any) => c.name).join(', ') : '—'}
                        </td>
                        <td className="px-5 py-3 text-center">
                          <span className={`inline-block px-2 py-0.5 rounded-full text-[11px] font-semibold ${isSelected ? 'bg-white text-teal-700' : (p.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500')}`}>
                            {p.is_active ? 'Visible' : 'Hidden'}
                          </span>
                        </td>
                        <td className="px-5 py-3 text-right">
                          <button 
                            onClick={() => p.source === 'builtin' ? openAddModal(p.catalog_id || undefined) : openEditModal(p.db_id)}
                            className={`text-xs border px-3 py-1 rounded-lg transition-colors font-medium flex items-center gap-1 mx-auto ${isSelected ? 'bg-teal-500 text-white border-teal-400 hover:bg-teal-400' : 'text-teal-600 border-teal-200 hover:bg-teal-50'}`}
                          >
                            <Edit3 size={14} />
                            Edit
                          </button>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          ) : (
                       <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 p-5">
              {products.map((p: Product, i: number) => {
                const src = sourceStyles[p.source] || sourceStyles.builtin;
                const isSelected = p.db_id ? selectedIds.includes(p.db_id) : false;

                return (
                  <div
                    key={`${p.code}-${i}`}
                    onClick={() => p.db_id ? toggleSelect(p.db_id) : (p.source === 'builtin' ? openAddModal(p.catalog_id || undefined) : openEditModal(p.db_id))}
                    className={`border rounded-xl overflow-hidden hover:shadow-xl transition-all group cursor-pointer relative ${
                      isSelected ? 'bg-teal-600 border-teal-500 ring-2 ring-teal-500/10 text-white' : 'bg-white border-gray-200 hover:border-teal-200'
                    } ${!p.is_active && !isSelected ? 'opacity-50' : ''}`}
                  >
                    {p.db_id && (
                      <div className="absolute top-2 left-2 z-10">
                        <input 
                          type="checkbox" 
                          checked={isSelected}
                          onChange={(e) => { e.stopPropagation(); toggleSelect(p.db_id); }}
                          className={`rounded border-gray-300 text-teal-600 focus:ring-teal-500 ${isSelected ? 'accent-white' : ''}`}
                        />
                      </div>
                    )}
                    
                    {/* Image Area */}
                    <div className={`h-40 flex items-center justify-center relative p-4 transition-colors ${isSelected ? 'bg-teal-500/30' : 'bg-gray-100'}`}>
                      <img 
                        src={`/static/images/products/${p.image || 'placeholder.jpg'}`} 
                        alt={p.name}
                        className="max-w-full max-h-full object-contain mix-blend-multiply transition-transform duration-500 group-hover:scale-110"
                      />
                      <span className={`absolute top-2 right-2 w-3 h-3 rounded-full border border-white shadow-sm ${isSelected ? 'bg-white' : src.dot}`} title={src.label} />
                    </div>
                    <div className="p-4">
                      <div className="flex items-start justify-between">
                         <div className="flex-1">
                            <p className={`text-[10px] font-mono tracking-wider ${isSelected ? 'text-teal-100' : 'text-gray-400'}`}>{p.code}</p>
                            <h3 className={`font-bold text-sm leading-tight mt-0.5 transition-colors ${isSelected ? 'text-white' : 'text-gray-900 group-hover:text-teal-700'}`}>
                              {p.name}
                            </h3>
                         </div>
                         <button 
                            onClick={(e) => {
                              e.stopPropagation();
                              p.source === 'builtin' ? openAddModal(p.catalog_id || undefined) : openEditModal(p.db_id);
                            }}
                            className={`p-1.5 rounded-lg transition-all ${isSelected ? 'text-teal-100 hover:bg-teal-500' : 'text-gray-400 hover:text-teal-600 hover:bg-teal-50'}`}
                         >
                            <Edit3 size={14} />
                         </button>
                      </div>
                      {p.dimensions && <p className={`text-[11px] mt-1 ${isSelected ? 'text-teal-100' : 'text-gray-400'}`}>{p.dimensions}</p>}
                      <div className={`flex items-center justify-between mt-3 pt-3 border-t ${isSelected ? 'border-teal-500/50' : 'border-gray-100'}`}>
                        <span className={`text-lg font-extrabold ${isSelected ? 'text-white' : 'text-teal-600'}`}>
                          {p.is_poa ? 'POA' : `$${Number(p.price).toFixed(2)}`}
                        </span>
                        <span className={`px-2 py-0.5 rounded-full text-[10px] font-semibold ${isSelected ? 'bg-white text-teal-700' : (p.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500')}`}>
                          {p.is_active ? 'Visible' : 'Hidden'}
                        </span>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>

        {data && (
          <Pagination 
            currentPage={currentPage}
            lastPage={data.last_page}
            total={data.total}
            onPageChange={setCurrentPage}
            perPage={perPage}
          />
        )}
      </div>

      <Modal 
        isOpen={isModalOpen} 
        onClose={() => setIsModalOpen(false)} 
        title={activeProductId ? 'Edit Product' : 'Add Product'}
        size="2xl"
      >
        <ProductForm 
          productId={activeProductId} 
          fromCatalogId={activeCatalogId}
          onSuccess={handleModalSuccess}
          isModal={true}
        />
      </Modal>

      <BulkActionToolbar 
        selectedCount={selectedIds.length}
        onClear={() => setSelectedIds([])}
        actions={[
          { label: 'Show in Catalog', icon: Eye, onClick: () => handleBulkStatusUpdate(true), variant: 'success' },
          { label: 'Hide from Catalog', icon: EyeOff, onClick: () => handleBulkStatusUpdate(false) },
          { label: 'Delete Overrides', icon: Trash2, onClick: handleBulkDelete, variant: 'danger' }
        ]}
      />
    </>
  );
}
