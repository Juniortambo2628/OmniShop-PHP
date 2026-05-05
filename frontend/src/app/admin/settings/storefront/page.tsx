'use client';

import { useState, useEffect } from 'react';
import { useAuth } from '@/contexts/AuthContext';
import { apiFetch } from '@/lib/api';
import PageHero from '@/components/admin/PageHero';
import { 
  Palette, 
  Truck, 
  Layout, 
  Save, 
  Plus, 
  Trash2, 
  Info,
  Settings2,
  CheckCircle2,
  AlertCircle,
  MessageSquare
} from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';
import ContextToolbar from '@/components/admin/ContextToolbar';

export default function StorefrontCmsPage() {
  const { token } = useAuth();
  const [settings, setSettings] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  useEffect(() => {
    if (!token) return;
    apiFetch<any[]>('/admin/storefront/settings', { token: token || undefined })
      .then(setSettings)
      .finally(() => setLoading(false));
  }, [token]);

  const updateValue = (key: string, value: string) => {
    setSettings(prev => {
      const exists = prev.some(s => s.key === key);
      if (exists) {
        return prev.map(s => s.key === key ? { ...s, value } : s);
      } else {
        // Fallback for missing settings - determine type based on key or default to string
        let type = 'string';
        if (key.includes('enabled')) type = 'boolean';
        if (key.includes('rates')) type = 'json';
        if (key.includes('color')) type = 'color';
        return [...prev, { key, value, type }];
      }
    });
  };

  const getSetting = (key: string) => {
    const s = settings.find(s => s.key === key);
    if (s) return s;
    // Return a default object if setting is missing to prevent UI crashes
    return { key, value: '', type: 'string' };
  };

  const handleSave = async () => {
    if (!token) return;
    setSaving(true);
    setMessage('');
    setError('');

    try {
      await apiFetch('/admin/storefront/settings', {
        method: 'POST',
        token: token || undefined,
        body: JSON.stringify({ settings })
      });
      setMessage('Storefront settings updated successfully');
      setTimeout(() => setMessage(''), 3000);
    } catch (err: any) {
      setError(err.message || 'Failed to update settings');
    } finally {
      setSaving(false);
    }
  };

  if (loading) return (
    <div className="p-10 flex flex-col items-center justify-center min-h-[400px]">
       <div className="w-10 h-10 border-4 border-teal-600 border-t-transparent rounded-full animate-spin mb-4" />
       <p className="text-gray-500 font-bold uppercase tracking-widest text-[10px]">Loading CMS Engine...</p>
    </div>
  );

  const deliveryRates = JSON.parse(getSetting('delivery_rates')?.value || '{}');

  const updateDeliveryRate = (catId: string, rate: number) => {
    const newRates = { ...deliveryRates, [catId]: rate };
    updateValue('delivery_rates', JSON.stringify(newRates));
  };

  return (
    <>
      <PageHero 
        title="Storefront CMS" 
        subtitle="Control your public presence, branding, and logistical cost structures from one central hub."
        breadcrumbs={[
          { label: 'Settings', href: '/admin/settings' },
          { label: 'Storefront' }
        ]} 
      />

      <div className="p-8 max-w-5xl mx-auto space-y-12">
        
        {/* Status Alerts */}
        <AnimatePresence>
          {message && (
            <motion.div 
              initial={{ opacity: 0, y: -20 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -20 }}
              className="p-6 bg-teal-50 border border-teal-100 rounded-[32px] flex items-center gap-4 text-teal-700 shadow-xl shadow-teal-500/5"
            >
              <CheckCircle2 size={24} />
              <p className="font-black uppercase tracking-widest text-xs">{message}</p>
            </motion.div>
          )}
          {error && (
            <motion.div 
              initial={{ opacity: 0, y: -20 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -20 }}
              className="p-6 bg-red-50 border border-red-100 rounded-[32px] flex items-center gap-4 text-red-700 shadow-xl shadow-red-500/5"
            >
              <AlertCircle size={24} />
              <p className="font-black uppercase tracking-widest text-xs">{error}</p>
            </motion.div>
          )}
        </AnimatePresence>

      <ContextToolbar>
          <div className="flex items-center gap-4 text-white flex-1 justify-between px-4">
            <div>
               <h3 className="text-xs font-black uppercase tracking-widest text-teal-400">Settings Synchronization</h3>
               <p className="text-[10px] text-white/60 font-bold uppercase tracking-widest">Ready to deploy changes</p>
            </div>
            <button 
              onClick={handleSave}
              disabled={saving}
              className="px-8 py-3 bg-teal-500 text-white rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-teal-400 transition-all flex items-center gap-3 shadow-xl disabled:opacity-50"
            >
              {saving ? 'Synchronizing...' : (
                <>
                  <Save size={16} />
                  Save Changes
                </>
              )}
            </button>
          </div>
      </ContextToolbar>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-12">
          
          {/* Section 1: Appearance */}
          <div className="space-y-8">
             <div className="flex items-center gap-4">
                <div className="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-sm border border-gray-100 text-teal-600">
                   <Layout size={18} />
                </div>
                <h3 className="font-black text-[#0d2e2e] uppercase tracking-tight">Hero Content</h3>
             </div>

             <div className="space-y-6 bg-white p-8 rounded-[32px] border border-gray-100 shadow-sm">
                <div className="space-y-2">
                   <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest block ml-1">Hero Heading</label>
                   <input 
                     value={getSetting('hero_title')?.value || ''}
                     onChange={(e) => updateValue('hero_title', e.target.value)}
                     className="w-full px-6 py-4 bg-gray-50 border border-gray-100 rounded-2xl focus:outline-none focus:ring-4 focus:ring-teal-500/5 focus:border-teal-500 transition-all font-bold text-gray-900"
                   />
                </div>
                <div className="space-y-2">
                   <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest block ml-1">Hero Sub-text</label>
                   <textarea 
                     rows={3}
                     value={getSetting('hero_subtitle')?.value || ''}
                     onChange={(e) => updateValue('hero_subtitle', e.target.value)}
                     className="w-full px-6 py-4 bg-gray-50 border border-gray-100 rounded-2xl focus:outline-none focus:ring-4 focus:ring-teal-500/5 focus:border-teal-500 transition-all font-medium text-gray-600"
                   />
                </div>
                <div className="space-y-2">
                   <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest block ml-1">Brand Primary Color</label>
                   <div className="flex items-center gap-4">
                      <input 
                        type="color"
                        value={getSetting('primary_color')?.value || '#0d2e2e'}
                        onChange={(e) => updateValue('primary_color', e.target.value)}
                        className="w-14 h-14 border-0 rounded-2xl cursor-pointer"
                      />
                      <span className="font-mono text-xs font-bold text-gray-400 uppercase tracking-widest">{getSetting('primary_color')?.value}</span>
                   </div>
                </div>
             </div>
          </div>

          {/* Section 2: Delivery Logistics */}
          <div className="space-y-8">
             <div className="flex items-center gap-4">
                <div className="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-sm border border-gray-100 text-teal-600">
                   <Truck size={18} />
                </div>
                <h3 className="font-black text-[#0d2e2e] uppercase tracking-tight">Delivery Logistics</h3>
             </div>

             <div className="space-y-8 bg-white p-8 rounded-[32px] border border-gray-100 shadow-sm">
                <div className="flex items-center justify-between p-4 bg-teal-50/50 rounded-2xl border border-teal-100/50">
                   <div>
                      <h4 className="font-black text-[#0d2e2e] text-xs uppercase tracking-tight">Enable Delivery Costs</h4>
                      <p className="text-[10px] text-teal-600/60 font-bold uppercase tracking-widest">Apply fees based on categories</p>
                   </div>
                   <button 
                     onClick={() => updateValue('delivery_enabled', getSetting('delivery_enabled')?.value === 'true' ? 'false' : 'true')}
                     className={`w-14 h-8 rounded-full transition-all relative ${getSetting('delivery_enabled')?.value === 'true' ? 'bg-teal-500' : 'bg-gray-200'}`}
                   >
                      <div className={`absolute top-1 w-6 h-6 bg-white rounded-full transition-all ${getSetting('delivery_enabled')?.value === 'true' ? 'right-1' : 'left-1 shadow-sm'}`} />
                   </button>
                </div>

                <div className="space-y-4">
                   <h4 className="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Rate Configuration (per item)</h4>
                   <div className="space-y-3 max-h-[300px] overflow-y-auto pr-2 scrollbar-thin">
                      {Object.entries(deliveryRates).map(([catId, rate]: [string, any]) => (
                         <div key={catId} className="flex items-center justify-between p-4 bg-gray-50 rounded-xl group hover:bg-white hover:shadow-lg transition-all border border-transparent hover:border-gray-100">
                            <span className="text-xs font-black text-[#0d2e2e] uppercase tracking-tight">{catId}</span>
                            <div className="flex items-center gap-3">
                               <span className="text-[10px] font-bold text-gray-400">$</span>
                               <input 
                                 type="number"
                                 value={rate}
                                 onChange={(e) => updateDeliveryRate(catId, parseFloat(e.target.value))}
                                 className="w-20 text-right bg-transparent border-0 focus:ring-0 font-black text-[#0d2e2e] text-sm"
                               />
                            </div>
                         </div>
                      ))}
                   </div>
                </div>

                <div className="p-4 bg-amber-50 border border-amber-100 rounded-2xl flex gap-3">
                   <Info size={16} className="text-amber-600 shrink-0 mt-0.5" />
                   <p className="text-[10px] text-amber-900 font-medium leading-relaxed">
                      Delivery is not included by default. When enabled, the checkout will calculate the total cost based on the category of each item and the quantity.
                   </p>
                </div>
             </div>
          </div>
        </div>

        {/* Section 3: Support & Integrations */}
        <div className="space-y-8">
           <div className="flex items-center gap-4">
              <div className="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-sm border border-gray-100 text-teal-600">
                 <MessageSquare size={18} />
              </div>
              <h3 className="font-black text-[#0d2e2e] uppercase tracking-tight">Support & Integrations</h3>
           </div>

           <div className="bg-white p-8 rounded-[40px] border border-gray-100 shadow-sm space-y-6">
              <div className="space-y-2">
                 <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest block ml-1">Support Chat Widget Script (e.g. Crisp, Zendesk)</label>
                 <textarea 
                   rows={6}
                   value={getSetting('chat_widget_script')?.value || ''}
                   onChange={(e) => updateValue('chat_widget_script', e.target.value)}
                   placeholder="<!-- Paste your widget code here -->"
                   className="w-full px-6 py-4 bg-gray-50 border border-gray-100 rounded-3xl focus:outline-none focus:ring-4 focus:ring-teal-500/5 focus:border-teal-500 transition-all font-mono text-xs text-teal-700"
                 />
                 <div className="p-4 bg-teal-50 border border-teal-100 rounded-2xl flex gap-3">
                    <Info size={16} className="text-teal-600 shrink-0 mt-0.5" />
                    <p className="text-[10px] text-teal-900 font-medium leading-relaxed">
                       Inject any third-party script tags into the storefront footer. This is typically used for support chats, analytics trackers, or social proof widgets.
                    </p>
                 </div>
              </div>
           </div>
        </div>
      </div>
    </>
  );
}
