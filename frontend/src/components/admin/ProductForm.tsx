'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/contexts/AuthContext';
import { apiFetch } from '@/lib/api';
import { useAutosave } from '@/hooks/useAutosave';
import PageHero from '@/components/admin/PageHero';
import Link from 'next/link';
import { Plus, Trash2, Upload, Image as ImageIcon, Check, X as CloseIcon, Package } from 'lucide-react';

// FilePond Imports
import { FilePond, registerPlugin } from 'react-filepond';
import 'filepond/dist/filepond.min.css';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css';

// Register FilePond plugins
if (typeof window !== 'undefined') {
  registerPlugin(FilePondPluginImagePreview);
}

interface Product {
  id?: number;
  prod_id: string;
  code: string;
  name: string;
  category_id: string;
  price: number;
  price_display: string;
  dimensions: string;
  is_poa: boolean;
  is_active: boolean;
  is_override: boolean;
  colors: { id: string; name: string }[];
}

interface ProductFormProps {
  productId?: string;
  fromCatalogId?: string;
  onSuccess?: () => void;
  isModal?: boolean;
}

export default function ProductForm({ productId, fromCatalogId, onSuccess, isModal }: ProductFormProps) {
  const { token } = useAuth();
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [categories, setCategories] = useState<Record<string, string>>({});
  const [files, setFiles] = useState<any[]>([]);
  const [existingImages, setExistingImages] = useState<any[]>([]);
  const [selectedColorId, setSelectedColorId] = useState<string | null>(null);
  
  const isEdit = !!productId;
  const title = isEdit ? 'Edit Product' : 'Add Product';

  const [formData, setFormData] = useState({
    prod_id: '',
    code: '',
    name: '',
    category_id: '',
    price: 0,
    price_display: '',
    dimensions: '',
    colors: [] as { id: string; name: string }[],
    is_poa: false,
    is_active: true,
    is_override: !!fromCatalogId,
  });

  const [newColorName, setNewColorName] = useState('');
  const [activeTab, setActiveTab] = useState<'general' | 'variants' | 'media'>('general');

  const { isSaving: isAutosaving, lastSaved, clearDraft } = useAutosave({
    type: 'product',
    id: productId,
    token: token || undefined,
    data: formData,
    onLoad: (draftData) => {
      if (confirm('A draft was found for this product. Would you like to restore it?')) {
        setFormData(draftData);
      }
    }
  });

  const fetchImages = async (code: string) => {
    if (!code || !token) return;
    try {
      const imgs = await apiFetch<any[]>(`/products/code/${code}/images`, { token });
      setExistingImages(imgs);
    } catch (err) {
      console.error('Failed to fetch images:', err);
    }
  };

  useEffect(() => {
    if (!token) return;

    const loadData = async () => {
      try {
        setLoading(true);
        // Load categories for dropdown
        const catData = await apiFetch<Record<string, string>>('/categories', { token });
        setCategories(catData);

        if (isEdit) {
          const product = await apiFetch<Product>(`/products/${productId}`, { token });
          setFormData({
            prod_id: product.prod_id,
            code: product.code,
            name: product.name,
            category_id: product.category_id,
            price: product.price,
            price_display: product.price_display,
            dimensions: product.dimensions || '',
            colors: product.colors || [],
            is_poa: product.is_poa,
            is_active: product.is_active,
            is_override: product.is_override,
          });
          fetchImages(product.code);
        } else if (fromCatalogId) {
          // Optimized fetch for single catalog item
          const catProduct = await apiFetch<any>(`/products/catalog/${fromCatalogId}`, { token });
          if (catProduct) {
             setFormData(prev => ({
               ...prev,
               prod_id: fromCatalogId,
               code: catProduct.code,
               name: catProduct.name,
               category_id: catProduct.category_id,
               price: catProduct.price,
               price_display: catProduct.price_display,
               dimensions: catProduct.dimensions || '',
               colors: catProduct.colors || [],
               is_poa: catProduct.is_poa,
               is_override: true,
             }));
             fetchImages(catProduct.code);
          }
        }
      } catch (err: any) {
        setError(err.message || 'Failed to load data');
      } finally {
        setLoading(false);
      }
    };

    loadData();
  }, [token, productId, fromCatalogId, isEdit]);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const { name, value, type } = e.target;
    if (type === 'checkbox') {
      const checked = (e.target as HTMLInputElement).checked;
      setFormData(prev => ({ ...prev, [name]: checked }));
    } else {
      setFormData(prev => ({ ...prev, [name]: name === 'price' ? parseFloat(value) || 0 : value }));
    }
  };

  const addColor = () => {
    if (!newColorName.trim()) return;
    const nextId = (formData.colors.length + 1).toString().padStart(2, '0');
    setFormData(prev => ({
      ...prev,
      colors: [...prev.colors, { id: nextId, name: newColorName.trim() }]
    }));
    setNewColorName('');
  };

  const removeColor = (id: string) => {
    setFormData(prev => ({
      ...prev,
      colors: prev.colors.filter(c => c.id !== id)
    }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setSaving(true);

    try {
      const payload = {
        ...formData,
        colors_json: formData.colors,
      };

      if (isEdit) {
        await apiFetch(`/products/${productId}`, {
          method: 'PUT',
          token: token || undefined,
          body: JSON.stringify(payload),
        });
      } else {
        await apiFetch('/products', {
          method: 'POST',
          token: token || undefined,
          body: JSON.stringify(payload),
        });
      }
      
      if (onSuccess) {
        await clearDraft();
        onSuccess();
      } else {
        await clearDraft();
        router.push('/admin/products');
      }
    } catch (err: any) {
      setError(err.message || 'Failed to save product');
      setSaving(false);
    }
  };

  const deleteImage = async (fileName: string) => {
    if (!token) return;
    if (!confirm('Are you sure you want to delete this image?')) return;
    try {
      await apiFetch('/products/images', {
        method: 'DELETE',
        token,
        body: JSON.stringify({ file_name: fileName })
      });
      fetchImages(formData.code);
    } catch (err) {
      console.error(err);
    }
  };

  if (loading) return (
    <div className="p-20 flex flex-col items-center justify-center gap-4">
      <div className="w-12 h-12 border-4 border-teal-600 border-t-transparent rounded-full animate-spin" />
      <p className="text-xs font-black uppercase tracking-[0.2em] text-teal-600 animate-pulse">Initializing Catalyst...</p>
    </div>
  );

  return (
    <>
      {!isModal && (
        <PageHero 
          title={title} 
          breadcrumbs={[
            { label: 'Products', href: '/admin/products' },
            { label: title }
          ]} 
        >
          <Link href="/admin/products" className="px-6 py-3 bg-white/10 hover:bg-white/20 border border-white/20 rounded-xl text-xs font-black uppercase tracking-widest text-white transition-all">
            Cancel
          </Link>
        </PageHero>
      )}

      <div className={`${isModal ? 'p-0' : 'p-6 max-w-6xl mx-auto w-full'}`}>
        <form onSubmit={handleSubmit} className={`${isModal ? '' : 'bg-white rounded-3xl shadow-2xl border border-gray-100 overflow-hidden'}`}>
          {!isModal && (
            <div className="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
              <div className="flex items-center gap-3">
                <div className="w-2 h-2 rounded-full bg-teal-500 animate-pulse" />
                <h2 className="font-black text-gray-900 uppercase tracking-widest text-sm">Product Configuration</h2>
              </div>
              {formData.is_override && !isEdit && (
                <span className="bg-amber-500 text-white text-[10px] font-black px-3 py-1.5 rounded-full uppercase tracking-widest shadow-lg shadow-amber-500/20">
                  Override Mode
                </span>
              )}
            </div>
          )}

          {/* Tab Navigation */}
          <div className="flex bg-gray-50 border-b border-gray-100 p-2 gap-2">
            {[
              { id: 'general', label: 'General Info', icon: Package },
              { id: 'variants', label: 'Color Variants', icon: Check },
              { id: 'media', label: 'Media & Assets', icon: ImageIcon },
            ].map((tab) => (
              <button
                key={tab.id}
                type="button"
                onClick={() => setActiveTab(tab.id as any)}
                className={`flex-1 flex items-center justify-center gap-2 py-3 px-4 rounded-2xl text-[11px] font-black uppercase tracking-widest transition-all ${
                  activeTab === tab.id 
                  ? 'bg-white text-teal-600 shadow-sm border border-gray-100' 
                  : 'text-gray-400 hover:text-gray-600 hover:bg-gray-100'
                }`}
              >
                <tab.icon size={14} />
                {tab.label}
              </button>
            ))}
          </div>
          
          <div className="p-8">
            {error && <div className="mb-8 p-4 bg-red-50 text-red-600 text-xs font-bold rounded-2xl border border-red-100 flex items-center gap-3">
              <div className="w-6 h-6 rounded-lg bg-red-100 flex items-center justify-center text-red-600 flex-shrink-0">!</div>
              {error}
            </div>}

            <div className="min-h-[450px]">
              
              {/* Tab Content: General */}
              {activeTab === 'general' && (
                <div className="space-y-8 animate-in fade-in slide-in-from-bottom-2 duration-300">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-12">
                    <div className="space-y-6">
                      <div className="flex items-center gap-2 mb-2">
                        <span className="w-1 h-4 bg-teal-600 rounded-full" />
                        <h3 className="text-[11px] font-black uppercase tracking-[0.2em] text-gray-400">Core Identity</h3>
                      </div>
                      <div className="grid grid-cols-2 gap-6">
                        <div className="group">
                          <label className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 group-focus-within:text-teal-600 transition-colors">Internal ID</label>
                          <input 
                            type="text" 
                            name="prod_id" 
                            value={formData.prod_id} 
                            onChange={handleChange}
                            readOnly={isEdit || formData.is_override}
                            className={`w-full px-4 py-3 bg-gray-50 border-2 border-gray-100 rounded-2xl text-sm font-bold focus:outline-none focus:border-teal-500 focus:bg-white transition-all ${(isEdit || formData.is_override) ? 'opacity-60 cursor-not-allowed' : ''}`}
                            required
                          />
                        </div>
                        <div className="group">
                          <label className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 group-focus-within:text-teal-600 transition-colors">Catalog Code</label>
                          <input 
                            type="text" 
                            name="code" 
                            value={formData.code} 
                            onChange={handleChange}
                            className="w-full px-4 py-3 bg-gray-50 border-2 border-gray-100 rounded-2xl text-sm font-bold focus:outline-none focus:border-teal-500 focus:bg-white transition-all"
                            required
                          />
                        </div>
                      </div>
                      <div className="group">
                        <label className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 group-focus-within:text-teal-600 transition-colors">Product Name</label>
                        <input 
                          type="text" 
                          name="name" 
                          value={formData.name} 
                          onChange={handleChange}
                          className="w-full px-4 py-3 bg-gray-50 border-2 border-gray-100 rounded-2xl text-sm font-bold focus:outline-none focus:border-teal-500 focus:bg-white transition-all"
                          required
                        />
                      </div>
                      <div className="grid grid-cols-2 gap-6">
                        <div className="group">
                          <label className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 group-focus-within:text-teal-600 transition-colors">Category</label>
                          <select 
                            name="category_id" 
                            value={formData.category_id} 
                            onChange={handleChange}
                            className="w-full px-4 py-3 bg-gray-50 border-2 border-gray-100 rounded-2xl text-sm font-bold focus:outline-none focus:border-teal-500 focus:bg-white transition-all"
                            required
                          >
                            <option value="">Select Category</option>
                            {Object.entries(categories).map(([id, name]) => (
                              <option key={id} value={id}>{name}</option>
                            ))}
                          </select>
                        </div>
                        <div className="group">
                          <label className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 group-focus-within:text-teal-600 transition-colors">Specifications</label>
                          <input 
                            type="text" 
                            name="dimensions" 
                            value={formData.dimensions} 
                            onChange={handleChange}
                            placeholder="e.g. 120 x 80 x 75 cm"
                            className="w-full px-4 py-3 bg-gray-50 border-2 border-gray-100 rounded-2xl text-sm font-bold focus:outline-none focus:border-teal-500 focus:bg-white transition-all"
                          />
                        </div>
                      </div>
                    </div>

                    <div className="space-y-6">
                      <div className="flex items-center gap-2 mb-2">
                        <span className="w-1 h-4 bg-teal-600 rounded-full" />
                        <h3 className="text-[11px] font-black uppercase tracking-[0.2em] text-gray-400">Financials & Visibility</h3>
                      </div>
                      <div className="grid grid-cols-2 gap-6">
                        <div className="group">
                          <label className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 group-focus-within:text-teal-600 transition-colors">Numeric Price</label>
                          <div className="relative">
                            <span className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold">$</span>
                            <input 
                              type="number" 
                              step="0.01"
                              name="price" 
                              value={formData.price} 
                              onChange={handleChange}
                              className="w-full pl-8 pr-4 py-3 bg-gray-50 border-2 border-gray-100 rounded-2xl text-sm font-bold focus:outline-none focus:border-teal-500 focus:bg-white transition-all"
                              required
                            />
                          </div>
                        </div>
                        <div className="group">
                          <label className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 group-focus-within:text-teal-600 transition-colors">Display String</label>
                          <input 
                            type="text" 
                            name="price_display" 
                            value={formData.price_display} 
                            onChange={handleChange}
                            placeholder="e.g. $1,250.00"
                            className="w-full px-4 py-3 bg-gray-50 border-2 border-gray-100 rounded-2xl text-sm font-bold focus:outline-none focus:border-teal-500 focus:bg-white transition-all"
                            required
                          />
                        </div>
                      </div>
                      <div className="p-6 bg-gray-50 rounded-3xl border-2 border-white shadow-inner space-y-4">
                        <label className="flex items-center justify-between cursor-pointer group">
                          <span className="text-[10px] font-black text-gray-500 uppercase tracking-widest group-hover:text-teal-600 transition-colors">Visible in Storefront</span>
                          <div className="relative">
                            <input 
                              type="checkbox" 
                              name="is_active" 
                              checked={formData.is_active} 
                              onChange={handleChange}
                              className="sr-only"
                            />
                            <div className={`w-10 h-6 rounded-full transition-colors ${formData.is_active ? 'bg-teal-600' : 'bg-gray-200'}`} />
                            <div className={`absolute top-1 left-1 w-4 h-4 rounded-full bg-white transition-transform ${formData.is_active ? 'translate-x-4' : ''}`} />
                          </div>
                        </label>
                        <label className="flex items-center justify-between cursor-pointer group">
                          <span className="text-[10px] font-black text-gray-500 uppercase tracking-widest group-hover:text-teal-600 transition-colors">Price on Application</span>
                          <div className="relative">
                            <input 
                              type="checkbox" 
                              name="is_poa" 
                              checked={formData.is_poa} 
                              onChange={handleChange}
                              className="sr-only"
                            />
                            <div className={`w-10 h-6 rounded-full transition-colors ${formData.is_poa ? 'bg-amber-500' : 'bg-gray-200'}`} />
                            <div className={`absolute top-1 left-1 w-4 h-4 rounded-full bg-white transition-transform ${formData.is_poa ? 'translate-x-4' : ''}`} />
                          </div>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              )}

              {/* Tab Content: Variants */}
              {activeTab === 'variants' && (
                <div className="space-y-8 animate-in fade-in slide-in-from-bottom-2 duration-300 max-w-2xl mx-auto">
                   <div className="flex items-center justify-between mb-2">
                    <div className="flex items-center gap-2">
                      <span className="w-1 h-4 bg-teal-600 rounded-full" />
                      <h3 className="text-[11px] font-black uppercase tracking-[0.2em] text-gray-400">Color Variants</h3>
                    </div>
                    <span className="text-[9px] font-bold text-gray-400 bg-gray-100 px-2 py-1 rounded tracking-tighter uppercase tracking-widest">Image specific mapping enabled</span>
                  </div>
                  
                  <div className="space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      {formData.colors.map((color) => {
                        const colorImg = existingImages.find(img => img.name.includes(`-${color.id}`));
                        return (
                          <div key={color.id} className="flex items-center justify-between p-4 bg-white border-2 border-gray-100 rounded-2xl group hover:border-teal-200 transition-all shadow-sm">
                            <div className="flex items-center gap-3">
                              <div className="w-12 h-12 rounded-xl bg-gray-50 flex items-center justify-center overflow-hidden border border-gray-100">
                                {colorImg ? (
                                  <img src={colorImg.url} className="w-full h-full object-cover" />
                                ) : (
                                  <div className="w-4 h-4 bg-gray-200 rounded-full" />
                                )}
                              </div>
                              <div>
                                <p className="text-xs font-black text-gray-900 uppercase tracking-widest">{color.name}</p>
                                <p className="text-[9px] font-bold text-gray-400">ID: {color.id}</p>
                              </div>
                            </div>
                            <div className="flex items-center gap-2">
                              <button 
                                type="button"
                                onClick={() => { setSelectedColorId(color.id); setActiveTab('media'); }}
                                className="p-2 bg-teal-50 text-teal-600 hover:bg-teal-100 rounded-lg transition-all"
                                title="Set as upload target"
                              >
                                <Upload size={14} />
                              </button>
                              <button 
                                type="button"
                                onClick={() => removeColor(color.id)}
                                className="p-2 bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 rounded-lg transition-all"
                              >
                                <Trash2 size={14} />
                              </button>
                            </div>
                          </div>
                        );
                      })}
                    </div>
                    <div className="flex gap-2 p-2 bg-gray-50 rounded-[2rem] border-2 border-dashed border-gray-200">
                      <input 
                        type="text"
                        value={newColorName}
                        onChange={(e) => setNewColorName(e.target.value)}
                        placeholder="Add new variant name..."
                        onKeyDown={(e) => e.key === 'Enter' && (e.preventDefault(), addColor())}
                        className="flex-1 px-6 py-3 bg-white border-2 border-transparent rounded-[1.5rem] text-sm font-bold focus:outline-none focus:border-teal-500 transition-all"
                      />
                      <button 
                        type="button"
                        onClick={addColor}
                        className="px-8 py-3 bg-gray-900 text-white rounded-[1.5rem] font-black text-xs uppercase tracking-widest hover:bg-black transition-all"
                      >
                        Add
                      </button>
                    </div>
                  </div>
                </div>
              )}

              {/* Tab Content: Media */}
              {activeTab === 'media' && (
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 animate-in fade-in slide-in-from-bottom-2 duration-300">
                  <div className="lg:col-span-7">
                    <div className="flex items-center justify-between mb-6">
                      <div className="flex items-center gap-2">
                        <span className="w-1 h-4 bg-teal-600 rounded-full" />
                        <h3 className="text-[11px] font-black uppercase tracking-[0.2em] text-gray-400">Asset Library</h3>
                      </div>
                      <span className="text-[10px] font-bold text-teal-600 bg-teal-50 px-2 py-1 rounded">{existingImages.length} Files</span>
                    </div>

                    {existingImages.length === 0 ? (
                      <div className="py-24 border-2 border-dashed border-gray-100 rounded-[2.5rem] flex flex-col items-center justify-center text-gray-300 bg-gray-50/50">
                        <ImageIcon size={48} strokeWidth={1} />
                        <p className="text-[11px] font-black uppercase mt-4 tracking-widest">No assets found</p>
                      </div>
                    ) : (
                      <div className="grid grid-cols-2 sm:grid-cols-3 gap-6">
                        {existingImages.map((img) => (
                          <div key={img.name} className="relative aspect-[4/5] rounded-3xl overflow-hidden border-2 border-white shadow-xl shadow-gray-200/50 group hover:ring-2 hover:ring-teal-500 transition-all">
                             <img src={img.url} className="w-full h-full object-cover" />
                             <button 
                               type="button"
                               onClick={() => deleteImage(img.name)}
                               className="absolute top-4 right-4 p-2 bg-red-500 text-white rounded-xl opacity-0 group-hover:opacity-100 transition-opacity shadow-lg"
                             >
                               <Trash2 size={14} />
                             </button>
                             <div className="absolute bottom-0 inset-x-0 bg-black/60 backdrop-blur-md p-3 text-[9px] font-black text-white uppercase tracking-wider text-center">
                               {img.name.replace(formData.code.toUpperCase(), '').replace('.jpg', '') || 'Main'}
                             </div>
                          </div>
                        ))}
                      </div>
                    )}
                  </div>

                  <div className="lg:col-span-5 space-y-6">
                    <div className="bg-teal-900 rounded-[2.5rem] border border-teal-800 p-8 shadow-2xl shadow-teal-900/40 relative overflow-hidden">
                       <div className="absolute top-0 right-0 p-8 opacity-10">
                         <Upload size={120} className="text-white" />
                       </div>
                       <label className="block text-[10px] font-black text-teal-400 uppercase tracking-[0.2em] mb-4">Transmission Engine</label>
                       <div className="mb-6">
                         {selectedColorId ? (
                           <div className="flex items-center justify-between bg-teal-800/50 rounded-2xl px-5 py-3 border border-teal-700">
                             <div className="flex flex-col">
                               <span className="text-[9px] font-bold text-teal-400 uppercase tracking-widest">Active Target</span>
                               <span className="text-xs font-black text-white uppercase tracking-widest">{formData.colors.find(c => c.id === selectedColorId)?.name}</span>
                             </div>
                             <button onClick={() => setSelectedColorId(null)} className="p-2 bg-teal-700 hover:bg-teal-600 rounded-lg text-teal-400 hover:text-white transition-colors">
                               <CloseIcon size={14}/>
                             </button>
                           </div>
                         ) : (
                           <div className="px-5 py-3 rounded-2xl border border-dashed border-teal-800 text-[10px] font-black text-teal-500 uppercase tracking-widest">Target: Main Product Image</div>
                         )}
                       </div>
                       <FilePond
                          files={files}
                          onupdatefiles={setFiles}
                          allowMultiple={true}
                          maxFiles={5}
                          onprocessfile={() => { fetchImages(formData.code); setFiles([]); setSelectedColorId(null); }}
                          server={{
                            process: {
                              url: `${process.env.NEXT_PUBLIC_API_URL || 'http://127.0.0.1:8000/api'}/products/images`,
                              method: 'POST',
                              headers: { 'Authorization': `Bearer ${token}` },
                              ondata: (fd) => {
                                fd.append('product_code', formData.code);
                                if (selectedColorId) fd.append('color_id', selectedColorId);
                                return fd;
                              }
                            }
                          }}
                          name="image"
                          labelIdle='Drop images or <span class="filepond--label-action">Browse</span>'
                          acceptedFileTypes={['image/*']}
                          className="filepond-dark"
                       />
                    </div>
                    <div className="p-6 bg-gray-50 rounded-3xl border border-gray-100">
                       <p className="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2">Upload Rules</p>
                       <ul className="space-y-1">
                         <li className="text-[9px] font-bold text-gray-500 flex items-center gap-2"><Check size={10}/> Maximum 5 files per transmission</li>
                         <li className="text-[9px] font-bold text-gray-500 flex items-center gap-2"><Check size={10}/> JPG/PNG format only</li>
                       </ul>
                    </div>
                  </div>
                </div>
              )}

            </div>
          </div>
          
          <div className="px-8 py-6 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
            <div className="flex gap-4">
               {isAutosaving && <div className="flex items-center gap-2 text-[10px] font-black text-teal-600 uppercase animate-pulse"><div className="w-1 h-1 bg-teal-600 rounded-full"/> Synced</div>}
               {lastSaved && !isAutosaving && <div className="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Secure Backup: {lastSaved.toLocaleTimeString()}</div>}
            </div>
            <div className="flex gap-4">
              <button 
                type="button" 
                onClick={() => onSuccess ? onSuccess() : router.push('/admin/products')}
                className="px-8 py-3 text-[11px] font-black uppercase tracking-widest text-gray-400 bg-white border-2 border-gray-200 rounded-2xl hover:bg-gray-50 hover:border-gray-300 transition-all"
              >
                Cancel
              </button>
              <button 
                type="submit" 
                disabled={saving}
                className="px-10 py-3 text-[11px] font-black uppercase tracking-widest text-white bg-teal-600 rounded-2xl hover:bg-teal-700 transition-all shadow-xl shadow-teal-600/20 disabled:opacity-50 flex items-center gap-2"
              >
                {saving ? 'Transmitting...' : (isEdit ? 'Update Product' : 'Register Product')}
                <Check size={16} />
              </button>
            </div>
          </div>
        </form>
      </div>
    </>
  );
}
