'use client';

import { useState, useEffect, use } from 'react';
import { apiFetch } from '@/lib/api';
import { useCart } from '@/contexts/CartContext';
import CartDrawer from '@/components/catalog/CartDrawer';
import ProductDetailsModal from '@/components/catalog/ProductDetailsModal';
import Hero from '@/components/catalog/Hero';
import { motion, AnimatePresence } from 'framer-motion';
import { ShoppingBag, ChevronRight, Filter, Search, ChevronLeft } from 'lucide-react';

export default function CatalogIndex({ params }: { params: Promise<{ event_slug: string }> }) {
  const unwrappedParams = use(params);
  const event_slug = unwrappedParams.event_slug;
  const [data, setData] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [activeCategory, setActiveCategory] = useState<string>('');
  const [searchQuery, setSearchQuery] = useState('');
  const [cmsSettings, setCmsSettings] = useState<any[]>([]);
  const { addToCart } = useCart();
  
  // Track selected colors per product ID
  const [selectedColors, setSelectedColors] = useState<Record<string, string>>({});
  const [addingToCart, setAddingToCart] = useState<string | null>(null);
  const [currentPage, setCurrentPage] = useState(1);
  const ITEMS_PER_PAGE = 12;

  // Modal State
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [activeProduct, setActiveProduct] = useState<any>(null);

  useEffect(() => {
    const token = sessionStorage.getItem(`catalog_auth_${event_slug}`);
    apiFetch(`/catalog/${event_slug}/data`, { 
      headers: { 'Authorization': `Bearer ${token}` }
    })
      .then(res => {
        setData(res);
        // Also fetch CMS settings
        apiFetch<any[]>('/storefront/settings')
          .then(setCmsSettings)
          .catch(() => {}); // Fallback to defaults in code if fails
        // Auto-select first category
        const firstCat = Object.keys(res.grouped)[0];
        if (firstCat) setActiveCategory(firstCat);
        
        // Initialize default colors
        const defaultColors: Record<string, string> = {};
        Object.values(res.grouped).flat().forEach((p: any) => {
          const prodKey = p.prod_id || p.code;
          if (p.colors && p.colors.length > 0) {
            defaultColors[prodKey] = p.colors[0].id;
          }
        });
        setSelectedColors(defaultColors);
      })
      .catch(err => {
        console.error(err);
        // If 401/403, redirect to login
        if (err.status === 401 || err.status === 403) {
           window.location.href = `/catalog/${event_slug}/login`;
        }
      })
      .finally(() => setLoading(false));
  }, [event_slug]);

  const openProductModal = (product: any) => {
    setActiveProduct(product);
    setIsModalOpen(true);
  };

  const handleAddToCart = (product: any) => {
    setAddingToCart(product.prod_id || product.code);
    
    let colorName = null;
    let selectedColorId: string | null = null;
    if (product.colors && product.colors.length > 0) {
      selectedColorId = selectedColors[product.prod_id || product.code];
      const colorObj = product.colors.find((c: any) => c.id === selectedColorId);
      colorName = colorObj ? colorObj.name : product.colors[0].name;
    }

    // Image logic
    let imgSrc = '/static/images/products/placeholder.jpg';
    const imgData = data.images[product.code.toUpperCase()];
    if (imgData) {
      if (product.colors?.length > 0 && selectedColorId && imgData[selectedColorId]) {
        imgSrc = `/static/images/products/${imgData[selectedColorId]}`;
      } else if (imgData['default']) {
        imgSrc = `/static/images/products/${imgData['default']}`;
      } else {
        imgSrc = `/static/images/products/${Object.values(imgData)[0]}`;
      }
    }

    addToCart({
      product_id: product.prod_id || product.code,
      name: product.name,
      price: product.price,
      quantity: 1,
      color: colorName,
      image: imgSrc,
      category: activeCategory,
    });

    setTimeout(() => setAddingToCart(null), 600);
    document.getElementById('cart-drawer')?.classList.remove('translate-x-full');
  };

  if (loading || !data) {
    return (
      <div className="py-20 flex justify-center">
        <div className="w-8 h-8 border-4 border-teal-600 border-t-transparent rounded-full animate-spin" />
      </div>
    );
  }

  const { categories, grouped, images } = data;
  
  // Filtering logic
  const allProducts = Object.values(grouped).flat();
  const filteredProducts = searchQuery 
    ? allProducts.filter((p: any) => 
        p.name.toLowerCase().includes(searchQuery.toLowerCase()) || 
        p.code.toLowerCase().includes(searchQuery.toLowerCase())
      )
    : (activeCategory === 'all' ? allProducts : grouped[activeCategory] || []);

  const totalPages = Math.ceil(filteredProducts.length / ITEMS_PER_PAGE);
  const currentProducts = filteredProducts.slice((currentPage - 1) * ITEMS_PER_PAGE, currentPage * ITEMS_PER_PAGE);

  const getCmsValue = (key: string, def: string) => cmsSettings.find(s => s.key === key)?.value || def;

  return (
    <>
      <CartDrawer eventSlug={event_slug} />
      <Hero 
        event={data.event} 
        customTitle={getCmsValue('hero_title', 'FURNITURE PARTNER')}
        customSubtitle={getCmsValue('hero_subtitle', 'Elevating your brand presence with premium furniture solutions.')}
      />

      {/* Categories Nav */}
      <div className="bg-white/80 backdrop-blur-md border-b border-gray-100 sticky top-20 z-40 shadow-sm">
        <div className="max-w-7xl mx-auto px-4">
          <div className="flex items-center justify-between gap-6 py-4">
            <div className="flex items-center gap-6 overflow-x-auto hide-scrollbar flex-1">
                <div className="flex items-center gap-2 pr-4 border-r border-gray-100 shrink-0">
                <Filter size={14} className="text-gray-400" />
                <span className="text-[10px] font-black text-gray-400 uppercase tracking-widest">Categories</span>
                </div>
                <div className="flex gap-2">
                    <button
                        onClick={() => {
                            setActiveCategory('all');
                            setSearchQuery('');
                            setCurrentPage(1);
                        }}
                        className={`whitespace-nowrap px-6 py-2 rounded-xl text-xs font-black transition-all duration-300 ${
                        activeCategory === 'all' && !searchQuery
                            ? 'bg-[#0d2e2e] text-white shadow-lg shadow-[#0d2e2e]/20' 
                            : 'bg-transparent text-gray-500 hover:text-[#0d2e2e]'
                        }`}
                    >
                        All Items
                    </button>
                    {Object.keys(grouped).map(catId => (
                    <button
                        key={catId}
                        onClick={() => {
                            setActiveCategory(catId);
                            setSearchQuery(''); // Clear search when changing category
                            setCurrentPage(1);
                        }}
                        className={`whitespace-nowrap px-6 py-2 rounded-xl text-xs font-black transition-all duration-300 ${
                        activeCategory === catId && !searchQuery
                            ? 'bg-[#0d2e2e] text-white shadow-lg shadow-[#0d2e2e]/20' 
                            : 'bg-transparent text-gray-500 hover:text-[#0d2e2e]'
                        }`}
                    >
                        {categories[catId] || catId}
                    </button>
                    ))}
                </div>
            </div>

            {/* Search Input */}
            <div className="relative w-full max-w-[300px] hidden md:block">
               <input 
                 type="text"
                 placeholder="Search furniture or code..."
                 value={searchQuery}
                 onChange={(e) => {
                     setSearchQuery(e.target.value);
                     setCurrentPage(1);
                 }}
                 className="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs font-bold focus:outline-none focus:ring-4 focus:ring-teal-500/5 focus:border-teal-500 transition-all"
               />
               <Search size={14} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" />
            </div>
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 py-12">
        <div className="flex items-center justify-between mb-12">
           <div>
             <h2 className="text-4xl font-black text-gray-900 tracking-tighter leading-none">
               {searchQuery ? `Search Results for "${searchQuery}"` : (activeCategory === 'all' ? 'All Collection' : categories[activeCategory] || activeCategory)}
             </h2>
             <p className="text-gray-500 text-sm mt-2 font-medium">
               {searchQuery ? `Found ${filteredProducts.length} matching items` : `Curated selection of ${filteredProducts.length} items`}
             </p>
           </div>
        </div>

        <motion.div 
          layout
          className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8"
        >
          <AnimatePresence mode="popLayout">
          {currentProducts.map((p: any, idx: number) => {
            const prodKey = p.prod_id || p.code;
            const hasColors = p.colors && p.colors.length > 0;
            const selectedColorId = selectedColors[prodKey];
            
            // Image logic port from legacy
            let imgSrc = '/static/images/products/placeholder.jpg';
            const imgData = images[p.code.toUpperCase()];
            if (imgData) {
              if (hasColors && selectedColorId && imgData[selectedColorId]) {
                imgSrc = `/static/images/products/${imgData[selectedColorId]}`;
              } else if (imgData['default']) {
                imgSrc = `/static/images/products/${imgData['default']}`;
              } else {
                 imgSrc = `/static/images/products/${Object.values(imgData)[0]}`;
              }
            }

            return (
              <motion.div 
                key={prodKey} 
                layout
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, scale: 0.95 }}
                transition={{ duration: 0.5, delay: idx * 0.05 }}
                onClick={() => openProductModal(p)}
                className="bg-white rounded-[32px] border border-gray-100 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 overflow-hidden flex flex-col group cursor-pointer"
              >
                {/* Image Area */}
                <div className="aspect-square bg-gray-50 relative overflow-hidden flex items-center justify-center group">
                  <div className="absolute inset-0 bg-gradient-to-br from-teal-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-700 z-10" />
                  
                  {imgSrc.includes('placeholder') ? (
                    <span className="text-7xl opacity-10 transform group-hover:scale-110 group-hover:rotate-6 transition-transform duration-700">🛋️</span>
                  ) : (
                    <img src={imgSrc} alt={p.name} className="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700" />
                  )}
                  
                  {p.is_poa && (
                     <div className="absolute top-6 right-6 bg-gray-900 text-white text-[10px] font-black px-3 py-1.5 rounded-full uppercase tracking-widest shadow-xl">
                       POA
                     </div>
                  )}

                  {/* Hover Quick View Overlay */}
                  <div className="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">
                     <div className="bg-white/90 backdrop-blur-md px-6 py-3 rounded-2xl shadow-xl transform translate-y-4 group-hover:translate-y-0 transition-transform duration-500 flex items-center gap-2">
                        <span className="text-xs font-black text-gray-900 uppercase tracking-widest">Details</span>
                        <ChevronRight size={14} className="text-teal-600" />
                     </div>
                  </div>
                </div>

                <div className="p-7 flex-1 flex flex-col">
                  <div className="flex justify-between items-start mb-3">
                    <p className="text-[10px] font-black text-teal-600 uppercase tracking-widest">{p.code}</p>
                    {hasColors && (
                        <div className="flex items-center gap-1">
                            {p.colors.slice(0, 3).map((c: any) => (
                                <div key={c.id} className="w-2 h-2 rounded-full shadow-inner" style={{ backgroundColor: c.name.toLowerCase() === 'white' ? '#f3f4f6' : c.name.toLowerCase() }} />
                            ))}
                            {p.colors.length > 3 && <span className="text-[8px] font-bold text-gray-400">+{p.colors.length - 3}</span>}
                        </div>
                    )}
                  </div>
                  <h3 className="font-bold text-[#0d2e2e] text-lg leading-tight mb-2 group-hover:text-teal-600 transition-colors">{p.name}</h3>
                  {p.dimensions && (
                    <div className="mt-2 mb-6">
                      <span className="text-[9px] font-black text-teal-600 uppercase tracking-widest block mb-1">Dimensions</span>
                      <p className="text-xs text-[#0d2e2e] font-black leading-tight bg-teal-50/50 p-2 rounded-lg border border-teal-100/50">{p.dimensions}</p>
                    </div>
                  )}

                  <div className="mt-auto pt-6 border-t border-gray-50 flex items-center justify-between">
                    <div className="flex flex-col">
                      <span className="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1">Price</span>
                      <span className="text-xl font-black text-[#0d2e2e]">
                        {p.is_poa ? 'POA' : `$${Number(p.price).toFixed(2)}`}
                      </span>
                    </div>

                    <button
                      onClick={(e) => {
                        e.stopPropagation();
                        handleAddToCart(p);
                      }}
                      disabled={addingToCart === prodKey}
                      className={`w-14 h-14 rounded-2xl transition-all duration-500 flex items-center justify-center shadow-lg ${
                        addingToCart === prodKey
                          ? 'bg-green-500 text-white shadow-green-500/20'
                          : 'bg-[#0d2e2e] text-white hover:bg-teal-600 shadow-[#0d2e2e]/10 hover:shadow-teal-600/20'
                      }`}
                    >
                      {addingToCart === prodKey ? (
                        <motion.div initial={{ scale: 0 }} animate={{ scale: 1 }}><ShoppingBag size={20} /></motion.div>
                      ) : (
                        <ShoppingBag size={20} />
                      )}
                    </button>
                  </div>
                </div>
              </motion.div>
            );
          })}
          {currentProducts.length === 0 && (
            <div className="col-span-full py-20 text-center">
              <p className="text-gray-400 font-bold uppercase tracking-widest text-xs">No products found.</p>
            </div>
          )}
          </AnimatePresence>
        </motion.div>

        {/* Pagination UI */}
        {totalPages > 1 && (
          <div className="mt-20 flex items-center justify-center gap-2">
            <button 
              disabled={currentPage === 1}
              onClick={() => {
                  setCurrentPage(prev => Math.max(1, prev - 1));
                  window.scrollTo({ top: 400, behavior: 'smooth' });
              }}
              className="w-12 h-12 flex items-center justify-center rounded-2xl border border-gray-100 bg-white text-gray-400 hover:text-[#0d2e2e] hover:border-teal-200 transition-all disabled:opacity-30 disabled:hover:border-gray-100 disabled:hover:text-gray-400"
            >
              <ChevronLeft size={20} />
            </button>
            
            <div className="flex items-center gap-2 px-6 py-3 bg-white rounded-3xl border border-gray-100 shadow-sm">
               {Array.from({ length: totalPages }).map((_, i) => (
                 <button
                   key={i + 1}
                   onClick={() => {
                       setCurrentPage(i + 1);
                       window.scrollTo({ top: 400, behavior: 'smooth' });
                   }}
                   className={`w-10 h-10 rounded-xl text-xs font-black transition-all ${
                     currentPage === i + 1 
                       ? 'bg-[#0d2e2e] text-white shadow-lg shadow-[#0d2e2e]/20' 
                       : 'text-gray-400 hover:text-[#0d2e2e]'
                   }`}
                 >
                   {i + 1}
                 </button>
               ))}
            </div>

            <button 
              disabled={currentPage === totalPages}
              onClick={() => {
                  setCurrentPage(prev => Math.min(totalPages, prev + 1));
                  window.scrollTo({ top: 400, behavior: 'smooth' });
              }}
              className="w-12 h-12 flex items-center justify-center rounded-2xl border border-gray-100 bg-white text-gray-400 hover:text-[#0d2e2e] hover:border-teal-200 transition-all disabled:opacity-30 disabled:hover:border-gray-100 disabled:hover:text-gray-400"
            >
              <ChevronRight size={20} />
            </button>
          </div>
        )}
      </div>

      {activeProduct && (
        <ProductDetailsModal
          product={activeProduct}
          isOpen={isModalOpen}
          onClose={() => setIsModalOpen(false)}
          onAddToCart={handleAddToCart}
          selectedColorId={selectedColors[activeProduct.prod_id || activeProduct.code]}
          onColorSelect={(colorId) => setSelectedColors(prev => ({ ...prev, [activeProduct.prod_id || activeProduct.code]: colorId }))}
          imgSrc={(() => {
            const p = activeProduct;
            const prodKey = p.prod_id || p.code;
            const imgData = images[p.code.toUpperCase()];
            const selectedColorId = selectedColors[prodKey];
            if (imgData) {
              if (p.colors?.length > 0 && selectedColorId && imgData[selectedColorId]) {
                return `/static/images/products/${imgData[selectedColorId]}`;
              } else if (imgData['default']) {
                return `/static/images/products/${imgData['default']}`;
              } else {
                 return `/static/images/products/${Object.values(imgData)[0]}`;
              }
            }
            return '/static/images/products/placeholder.jpg';
          })()}
        />
      )}
      
      <style dangerouslySetInnerHTML={{__html: `
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
      `}} />
    </>
  );
}
