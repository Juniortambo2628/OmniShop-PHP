'use client';

import { useState, useEffect } from 'react';
import { useAuth } from '@/contexts/AuthContext';
import { apiFetch } from '@/lib/api';
import { useToast } from '@/components/ui/Toast';
import PageHero from '@/components/admin/PageHero';
import { 
  Ticket, 
  Plus, 
  Trash2, 
  CheckCircle2, 
  XCircle, 
  Calendar,
  Save,
  X,
  Loader2,
  Percent,
  DollarSign
} from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';
import Modal from '@/components/ui/Modal';
import ContextToolbar from '@/components/admin/ContextToolbar';

export default function PromoCodesPage() {
  const { token } = useAuth();
  const { toast, confirm } = useToast();
  const [codes, setCodes] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [formData, setFormData] = useState({
    code: '',
    type: 'fixed',
    value: '',
    is_active: true,
    expires_at: '',
    usage_limit: '',
  });

  const fetchCodes = async () => {
    if (!token) return;
    try {
      const res = await apiFetch<any[]>('/admin/promo-codes', { token: token || undefined });
      setCodes(res);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchCodes();
  }, [token]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    try {
      await apiFetch('/admin/promo-codes', {
        method: 'POST',
        token: token || undefined,
        body: JSON.stringify(formData)
      });
      setIsModalOpen(false);
      setFormData({
        code: '',
        type: 'fixed',
        value: '',
        is_active: true,
        expires_at: '',
        usage_limit: '',
      });
      fetchCodes();
      toast.success('Promo code created successfully');
    } catch (err: any) {
      toast.error(err.body?.message || 'Failed to create promo code');
    } finally {
      setSubmitting(false);
    }
  };

  const handleDelete = async (id: number) => {
    if (!(await confirm('Are you sure you want to delete this promo code?'))) return;
    try {
      await apiFetch(`/admin/promo-codes/${id}`, {
        method: 'DELETE',
        token: token || undefined
      });
      fetchCodes();
      toast.success('Promo code deleted');
    } catch (err) {
      toast.error('Failed to delete promo code');
    }
  };

  const handleToggle = async (id: number, currentState: boolean) => {
    try {
      await apiFetch(`/admin/promo-codes/${id}`, {
        method: 'PUT',
        token: token || undefined,
        body: JSON.stringify({ is_active: !currentState })
      });
      setCodes(prev => prev.map(c => c.id === id ? { ...c, is_active: !currentState } : c));
      toast.success(`Promo code ${!currentState ? 'activated' : 'deactivated'}`);
    } catch (err) {
      toast.error('Failed to update promo code');
    }
  };

  return (
    <>
      <PageHero 
        title="Promo Codes" 
        subtitle="Incentivize conversion with custom discount codes, seasonal campaigns, and usage-limited vouchers."
        breadcrumbs={[
          { label: 'Settings', href: '/admin/settings' },
          { label: 'Promo Codes' }
        ]} 
      />

      <ContextToolbar>
          <div className="flex items-center gap-4 text-white flex-1 justify-between px-4">
            <div>
               <h3 className="text-xs font-black uppercase tracking-widest text-teal-400">Marketing Campaigns</h3>
               <p className="text-[10px] text-white/60 font-bold uppercase tracking-widest">{codes.length} active promotions</p>
            </div>
            <button 
              onClick={() => setIsModalOpen(true)}
              className="px-8 py-3 bg-teal-500 text-white rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-teal-400 transition-all flex items-center gap-2 shadow-xl"
            >
              <Plus size={16} />
              New Promo Code
            </button>
          </div>
      </ContextToolbar>

      <div className="p-8 max-w-6xl mx-auto space-y-8">
        <div>
          <h2 className="text-2xl font-black text-[#0d2e2e] tracking-tight">Active Promotions</h2>
          <p className="text-gray-500 font-medium text-sm">Manage discounts and seasonal offers for the storefront.</p>
        </div>

        {loading ? (
          <div className="py-20 flex flex-col items-center gap-4">
             <Loader2 className="animate-spin text-teal-600" size={32} />
             <p className="text-[10px] font-black uppercase tracking-widest text-gray-400">Fetching Campaigns...</p>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {codes.map(code => (
              <motion.div 
                key={code.id}
                layout
                initial={{ opacity: 0, scale: 0.95 }}
                animate={{ opacity: 1, scale: 1 }}
                className="bg-white rounded-[32px] border border-gray-100 p-8 shadow-sm hover:shadow-xl transition-all group"
              >
                <div className="flex items-start justify-between mb-6">
                   <div className={`p-4 rounded-2xl ${code.is_active ? 'bg-teal-50 text-teal-600' : 'bg-gray-50 text-gray-400'}`}>
                      <Ticket size={24} />
                   </div>
                   <div className="flex items-center gap-2">
                      <button 
                        onClick={() => handleToggle(code.id, code.is_active)}
                        className={`px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest cursor-pointer hover:opacity-80 transition-all ${code.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}
                      >
                         {code.is_active ? 'Active' : 'Inactive'}
                      </button>
                      <button 
                        onClick={() => handleDelete(code.id)}
                        className="p-2 text-gray-300 hover:text-red-500 transition-colors"
                      >
                         <Trash2 size={16} />
                      </button>
                   </div>
                </div>

                <h3 className="text-2xl font-black text-[#0d2e2e] tracking-tighter mb-2 font-mono">{code.code}</h3>
                <div className="flex items-center gap-2 mb-6">
                   {code.type === 'percentage' ? <Percent size={14} className="text-teal-600" /> : <DollarSign size={14} className="text-teal-600" />}
                   <span className="text-sm font-bold text-gray-600">
                      {code.type === 'percentage' ? `${code.value}% Discount` : `$${code.value} Flat Discount`}
                   </span>
                </div>

                <div className="space-y-3 pt-6 border-t border-gray-50">
                   <div className="flex items-center justify-between text-[10px] font-black uppercase tracking-widest text-gray-400">
                      <span>Usage</span>
                      <span className="text-[#0d2e2e]">{code.times_used} / {code.usage_limit || '∞'}</span>
                   </div>
                   <div className="w-full h-1.5 bg-gray-50 rounded-full overflow-hidden">
                      <div 
                        className="h-full bg-teal-500 rounded-full" 
                        style={{ width: code.usage_limit ? `${(code.times_used / code.usage_limit) * 100}%` : '10%' }} 
                      />
                   </div>
                   {code.expires_at && (
                     <div className="flex items-center gap-2 pt-2 text-[10px] font-bold text-gray-400">
                        <Calendar size={12} />
                        <span>Expires {new Date(code.expires_at).toLocaleDateString()}</span>
                     </div>
                   )}
                </div>
              </motion.div>
            ))}
          </div>
        )}
      </div>

      <Modal isOpen={isModalOpen} onClose={() => setIsModalOpen(false)} title="Create Promo Code">
         <form onSubmit={handleSubmit} className="p-8 space-y-6">
            <div className="space-y-2">
               <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Promo Code</label>
               <input 
                 required
                 value={formData.code}
                 onChange={(e) => setFormData(prev => ({ ...prev, code: e.target.value.toUpperCase() }))}
                 placeholder="e.g. SUMMER24"
                 className="w-full px-6 py-4 bg-gray-50 border border-gray-100 rounded-2xl focus:outline-none focus:border-teal-500 font-black tracking-widest text-[#0d2e2e]"
               />
            </div>

            <div className="grid grid-cols-2 gap-4">
               <div className="space-y-2">
                  <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Discount Type</label>
                  <select 
                    value={formData.type}
                    onChange={(e) => setFormData(prev => ({ ...prev, type: e.target.value }))}
                    className="w-full px-6 py-4 bg-gray-50 border border-gray-100 rounded-2xl focus:outline-none focus:border-teal-500 font-bold"
                  >
                     <option value="fixed">Fixed Amount ($)</option>
                     <option value="percentage">Percentage (%)</option>
                  </select>
               </div>
               <div className="space-y-2">
                  <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Value</label>
                  <input 
                    required
                    type="number"
                    value={formData.value}
                    onChange={(e) => setFormData(prev => ({ ...prev, value: e.target.value }))}
                    placeholder="0.00"
                    className="w-full px-6 py-4 bg-gray-50 border border-gray-100 rounded-2xl focus:outline-none focus:border-teal-500 font-black"
                  />
               </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
               <div className="space-y-2">
                  <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Usage Limit</label>
                  <input 
                    type="number"
                    value={formData.usage_limit}
                    onChange={(e) => setFormData(prev => ({ ...prev, usage_limit: e.target.value }))}
                    placeholder="∞"
                    className="w-full px-6 py-4 bg-gray-50 border border-gray-100 rounded-2xl focus:outline-none focus:border-teal-500 font-bold"
                  />
               </div>
               <div className="space-y-2">
                  <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Expiry Date</label>
                  <input 
                    type="date"
                    value={formData.expires_at}
                    onChange={(e) => setFormData(prev => ({ ...prev, expires_at: e.target.value }))}
                    className="w-full px-6 py-4 bg-gray-50 border border-gray-100 rounded-2xl focus:outline-none focus:border-teal-500 font-bold"
                  />
               </div>
            </div>

            <div className="flex items-center gap-3 p-4 bg-gray-50 rounded-2xl">
               <input 
                 type="checkbox"
                 checked={formData.is_active}
                 onChange={(e) => setFormData(prev => ({ ...prev, is_active: e.target.checked }))}
                 className="w-5 h-5 rounded border-gray-300 text-teal-600 focus:ring-teal-500"
               />
               <span className="text-xs font-bold text-[#0d2e2e]">Code is active immediately</span>
            </div>

            <button 
              type="submit"
              disabled={submitting}
              className="w-full py-5 bg-[#0d2e2e] text-white rounded-2xl font-black uppercase tracking-widest text-xs hover:bg-teal-600 transition-all shadow-xl shadow-[#0d2e2e]/10 flex items-center justify-center gap-2"
            >
               {submitting ? <Loader2 size={18} className="animate-spin" /> : (
                 <>
                   <Save size={18} />
                   Create Promo Code
                 </>
               )}
            </button>
         </form>
      </Modal>
    </>
  );
}
