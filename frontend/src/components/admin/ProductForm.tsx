'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/contexts/AuthContext';
import { apiFetch } from '@/lib/api';
import { useAutosave } from '@/hooks/useAutosave';
import PageHero from '@/components/admin/PageHero';
import Link from 'next/link';

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
    colors: '',
    is_poa: false,
    is_active: true,
    is_override: !!fromCatalogId,
  });

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

  useEffect(() => {
    if (!token) return;

    const loadData = async () => {
      try {
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
            colors: product.colors ? product.colors.map(c => c.name).join(', ') : '',
            is_poa: product.is_poa,
            is_active: product.is_active,
            is_override: product.is_override,
          });
        } else if (fromCatalogId) {
          // Get products to find the catalog one to prefill
          const res = await apiFetch<any>('/products', { token });
          const products = res.data || [];
          const catProduct = products.find((p: any) => p.catalog_id === fromCatalogId && p.source === 'builtin');
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
               colors: catProduct.colors ? catProduct.colors.map((c: any) => c.name).join(', ') : '',
               is_poa: catProduct.is_poa,
               is_override: true,
             }));
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

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setSaving(true);

    try {
      const payload = {
        ...formData,
        colors_json: formData.colors.split(',').map(s => s.trim()).filter(Boolean).map((name, i) => ({
          id: (i + 1).toString().padStart(2, '0'),
          name
        })),
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

  if (loading) return <div className="p-10 text-center">Loading...</div>;

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
          <Link href="/admin/products" className="px-4 py-2 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-semibold transition-colors">
            Cancel
          </Link>
        </PageHero>
      )}

      <div className={`${isModal ? 'p-0' : 'p-6 max-w-6xl mx-auto w-full'}`}>
        <form onSubmit={handleSubmit} className={`${isModal ? '' : 'bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden'}`}>
          {!isModal && (
            <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
              <h2 className="font-semibold text-gray-800">Product Details</h2>
              {formData.is_override && !isEdit && (
                <span className="bg-amber-100 text-amber-700 text-[10px] font-bold px-2 py-1 rounded-full uppercase tracking-wider">
                  Overriding Standard Catalog Item
                </span>
              )}
              {isAutosaving && <span className="text-[10px] text-teal-600 font-bold animate-pulse uppercase tracking-widest ml-4">Autosaving...</span>}
              {lastSaved && !isAutosaving && (
                <span className="text-[10px] text-gray-400 font-medium ml-4">
                  Draft saved at {lastSaved.toLocaleTimeString()}
                </span>
              )}
            </div>
          )}
          
          <div className="p-6">
            {error && <div className="mb-6 p-3 bg-red-50 text-red-600 text-sm rounded-lg border border-red-100">{error}</div>}

            <div className="grid grid-cols-1 lg:grid-cols-12 gap-10">
              
              {/* MAIN FORM COLUMN */}
              <div className="lg:col-span-8 space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Product ID (Internal)</label>
                    <input 
                      type="text" 
                      name="prod_id" 
                      value={formData.prod_id} 
                      onChange={handleChange}
                      readOnly={isEdit || formData.is_override}
                      className={`w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-teal-500 ${(isEdit || formData.is_override) ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : ''}`}
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Product Code (Display)</label>
                    <input 
                      type="text" 
                      name="code" 
                      value={formData.code} 
                      onChange={handleChange}
                      className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-teal-500"
                      required
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Product Name</label>
                  <input 
                    type="text" 
                    name="name" 
                    value={formData.name} 
                    onChange={handleChange}
                    className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-teal-500"
                    required
                  />
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Category</label>
                    <select 
                      name="category_id" 
                      value={formData.category_id} 
                      onChange={handleChange}
                      className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-teal-500 bg-white"
                      required
                    >
                      <option value="">-- Select Category --</option>
                      {Object.entries(categories).map(([id, name]) => (
                        <option key={id} value={id}>{name}</option>
                      ))}
                    </select>
                  </div>
                  <div>
                    <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Dimensions / Specifications</label>
                    <input 
                      type="text" 
                      name="dimensions" 
                      value={formData.dimensions} 
                      onChange={handleChange}
                      className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-teal-500"
                    />
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Price (Numeric)</label>
                    <input 
                      type="number" 
                      step="0.01"
                      name="price" 
                      value={formData.price} 
                      onChange={handleChange}
                      className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-teal-500"
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                      <span>Price Display String</span>
                      <span className="text-[10px] text-gray-400 normal-case font-normal">e.g., "$150" or "POA"</span>
                    </label>
                    <input 
                      type="text" 
                      name="price_display" 
                      value={formData.price_display} 
                      onChange={handleChange}
                      className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-teal-500"
                      required
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                    <span>Colours (Comma separated)</span>
                    <span className="text-[10px] text-gray-400 normal-case font-normal">e.g., White, Black, Red</span>
                  </label>
                  <input 
                    type="text" 
                    name="colors" 
                    value={formData.colors} 
                    onChange={handleChange}
                    className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-teal-500"
                    placeholder="Leave blank if not applicable"
                  />
                </div>

                <div className="flex flex-wrap gap-8 pt-4 border-t border-gray-100">
                  <label className="flex items-center gap-2 cursor-pointer">
                    <input 
                      type="checkbox" 
                      name="is_active" 
                      checked={formData.is_active} 
                      onChange={handleChange}
                      className="w-4 h-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded"
                    />
                    <span className="text-sm font-medium text-gray-700">Active (Visible on Storefront)</span>
                  </label>
                  <label className="flex items-center gap-2 cursor-pointer">
                    <input 
                      type="checkbox" 
                      name="is_poa" 
                      checked={formData.is_poa} 
                      onChange={handleChange}
                      className="w-4 h-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded"
                    />
                    <span className="text-sm font-medium text-gray-700">Price on Application (POA)</span>
                  </label>
                </div>
              </div>

              {/* SIDEBAR COLUMN (IMAGES) */}
              <div className="lg:col-span-4 space-y-6">
                 <div className="bg-gray-50/50 rounded-2xl border border-gray-100 p-6">
                   <label className="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-4">Product Assets</label>
                   <FilePond
                      files={files}
                      onupdatefiles={setFiles}
                      allowMultiple={true}
                      maxFiles={5}
                      server={{
                        process: {
                          url: `${process.env.NEXT_PUBLIC_API_URL || 'http://127.0.0.1:8000/api'}/products/images`,
                          method: 'POST',
                          headers: {
                            'Authorization': `Bearer ${token}`
                          },
                          ondata: (fd) => {
                            fd.append('product_code', formData.code);
                            return fd;
                          }
                        },
                        revert: {
                          url: `${process.env.NEXT_PUBLIC_API_URL || 'http://127.0.0.1:8000/api'}/products/images`,
                          headers: {
                            'Authorization': `Bearer ${token}`
                          }
                        }
                      }}
                      name="image"
                      labelIdle='Drag & Drop images or <span class="filepond--label-action">Browse</span>'
                      acceptedFileTypes={['image/*']}
                      className="filepond-compact"
                   />
                   <div className="mt-4 p-4 bg-white rounded-xl border border-gray-100 shadow-sm">
                     <p className="text-[10px] text-gray-400 leading-relaxed font-medium">
                       <strong className="text-gray-900 block mb-1">Naming Convention:</strong>
                       • Use <strong>CODE.jpg</strong> for primary.<br/>
                       • Use <strong>CODE-COLORID.jpg</strong> for variants.
                     </p>
                   </div>
                 </div>
              </div>

            </div>
          </div>
          
          <div className="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
            <button 
              type="button" 
              onClick={() => onSuccess ? onSuccess() : router.push('/admin/products')}
              className="px-6 py-2.5 text-xs font-black uppercase tracking-widest text-gray-500 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-all"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              disabled={saving}
              className="px-8 py-2.5 text-xs font-black uppercase tracking-widest text-white bg-teal-600 rounded-xl hover:bg-teal-700 transition-all shadow-lg shadow-teal-600/20 disabled:opacity-50"
            >
              {saving ? 'Saving...' : (isEdit ? 'Save Changes' : 'Create Product')}
            </button>
          </div>
        </form>
      </div>
    </>
  );
}
