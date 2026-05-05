'use client';

import { useState, useEffect } from 'react';
import { useAuth } from '@/contexts/AuthContext';
import { apiFetch } from '@/lib/api';
import { useToast } from '@/components/ui/Toast';
import PageHero from '@/components/admin/PageHero';
import { 
  Receipt, 
  Plus, 
  Trash2, 
  FileText,
  DollarSign,
  Search,
  Filter,
  CreditCard,
  Building2,
  Calendar
} from 'lucide-react';
import ContextToolbar from '@/components/admin/ContextToolbar';
import { TableSkeleton } from '@/components/ui/Skeleton';
import EmptyState from '@/components/ui/EmptyState';
import Modal from '@/components/ui/Modal';
import { statusBadge } from '@/lib/constants';

interface Payment {
  id: number;
  order_id: string;
  amount: number;
  method: string;
  reference: string;
  status: string;
  notes: string;
  paid_at: string;
  created_at: string;
  order?: {
    company_name: string;
    total: number;
  };
}

export default function InvoicesPage() {
  const { token } = useAuth();
  const { toast, confirm } = useToast();
  const [payments, setPayments] = useState<Payment[]>([]);
  const [loading, setLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [search, setSearch] = useState('');

  const [formData, setFormData] = useState({
    order_id: '',
    amount: '',
    method: 'bank_transfer',
    reference: '',
    status: 'confirmed',
    notes: '',
    paid_at: new Date().toISOString().split('T')[0],
  });

  const fetchPayments = async () => {
    if (!token) return;
    try {
      const data = await apiFetch<Payment[]>('/payments', { token });
      setPayments(data);
    } catch (err) {
      toast.error('Failed to load payments');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchPayments();
  }, [token]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    try {
      await apiFetch('/payments', {
        method: 'POST',
        token: token || undefined,
        body: JSON.stringify(formData)
      });
      setIsModalOpen(false);
      setFormData({
        order_id: '',
        amount: '',
        method: 'bank_transfer',
        reference: '',
        status: 'confirmed',
        notes: '',
        paid_at: new Date().toISOString().split('T')[0],
      });
      fetchPayments();
      toast.success('Payment recorded successfully');
    } catch (err: any) {
      toast.error(err.message || 'Failed to record payment');
    } finally {
      setSubmitting(false);
    }
  };

  const handleDelete = async (id: number) => {
    if (!(await confirm('Are you sure you want to delete this payment record?'))) return;
    try {
      await apiFetch(`/payments/${id}`, {
        method: 'DELETE',
        token: token || undefined
      });
      setPayments(prev => prev.filter(p => p.id !== id));
      toast.success('Payment deleted');
    } catch (err) {
      toast.error('Failed to delete payment');
    }
  };

  const filteredPayments = payments.filter(p => 
    p.order_id.toLowerCase().includes(search.toLowerCase()) ||
    p.order?.company_name.toLowerCase().includes(search.toLowerCase())
  );

  const totalCollected = payments
    .filter(p => p.status === 'confirmed')
    .reduce((sum, p) => sum + parseFloat(p.amount as any), 0);

  return (
    <>
      <PageHero 
        title="Invoices & Payments" 
        subtitle="Track incoming payments and order balances."
        breadcrumbs={[{ label: 'Invoices' }]}
      />

      <ContextToolbar>
        <div className="flex items-center gap-4 flex-1 justify-between px-4">
          <div className="flex items-center gap-4 flex-1">
             <div className="relative group max-w-md w-full">
               <Search className="absolute left-4 top-1/2 -translate-y-1/2 text-teal-300" size={18} />
               <input 
                 type="text"
                 placeholder="Search by Order ID or Company..."
                 value={search}
                 onChange={(e) => setSearch(e.target.value)}
                 className="w-full bg-black/20 border border-white/10 rounded-xl py-2.5 pl-12 pr-4 text-sm text-white placeholder:text-teal-100/50 focus:outline-none focus:ring-2 focus:ring-teal-500/50"
               />
             </div>
             
             <div className="h-8 w-px bg-white/10 hidden md:block" />
             
             <div className="hidden md:flex items-center gap-2">
               <span className="text-[10px] font-black uppercase tracking-widest text-teal-400">Total Collected</span>
               <span className="text-sm font-bold text-white">${totalCollected.toLocaleString(undefined, { minimumFractionDigits: 2 })}</span>
             </div>
          </div>
          <button 
            onClick={() => setIsModalOpen(true)}
            className="px-6 py-2.5 bg-teal-500 text-white rounded-xl font-bold text-sm hover:bg-teal-400 transition-colors flex items-center gap-2"
          >
            <Plus size={16} />
            Record Payment
          </button>
        </div>
      </ContextToolbar>

      <div className="p-8 max-w-7xl mx-auto">
        {loading ? (
          <TableSkeleton rows={8} />
        ) : filteredPayments.length === 0 ? (
          <EmptyState 
            icon={Receipt}
            title={search ? "No matches found" : "No payments recorded"}
            subtitle={search ? "Try adjusting your search terms" : "Record a payment to start tracking invoices."}
            action={
              <button 
                onClick={() => setIsModalOpen(true)}
                className="px-6 py-2.5 bg-teal-500 text-white rounded-xl font-bold text-sm hover:bg-teal-400 transition-colors inline-flex items-center gap-2"
              >
                <Plus size={16} />
                Record Payment
              </button>
            }
          />
        ) : (
          <div className="bg-white border border-gray-100 rounded-3xl overflow-hidden shadow-sm">
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr className="bg-gray-50/50 border-b border-gray-100">
                    <th className="p-4 pl-6 text-[10px] font-black uppercase tracking-widest text-gray-400">Order & Company</th>
                    <th className="p-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Amount</th>
                    <th className="p-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Method & Ref</th>
                    <th className="p-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Date</th>
                    <th className="p-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Status</th>
                    <th className="p-4 pr-6 text-right text-[10px] font-black uppercase tracking-widest text-gray-400">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-50">
                  {filteredPayments.map(payment => (
                    <tr key={payment.id} className="hover:bg-gray-50/50 transition-colors group">
                      <td className="p-4 pl-6">
                        <div className="flex flex-col">
                          <span className="text-sm font-bold text-[#0d2e2e]">{payment.order_id}</span>
                          <span className="text-xs text-gray-500 flex items-center gap-1 mt-0.5">
                            <Building2 size={12} />
                            {payment.order?.company_name || 'Unknown'}
                          </span>
                        </div>
                      </td>
                      <td className="p-4">
                        <span className="text-sm font-black text-teal-600 font-mono">
                          ${parseFloat(payment.amount as any).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                        </span>
                      </td>
                      <td className="p-4">
                        <div className="flex flex-col">
                          <span className="text-sm font-medium text-gray-900 capitalize flex items-center gap-1">
                            <CreditCard size={14} className="text-gray-400" />
                            {payment.method.replace('_', ' ')}
                          </span>
                          {payment.reference && (
                            <span className="text-xs text-gray-500 font-mono mt-0.5">{payment.reference}</span>
                          )}
                        </div>
                      </td>
                      <td className="p-4">
                         <span className="text-sm text-gray-600 flex items-center gap-1">
                           <Calendar size={14} className="text-gray-400" />
                           {new Date(payment.paid_at || payment.created_at).toLocaleDateString()}
                         </span>
                      </td>
                      <td className="p-4">
                        <span className={`inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider ${statusBadge[payment.status === 'confirmed' ? 'Approved' : 'Pending'] || 'bg-gray-100 text-gray-600'}`}>
                          {payment.status}
                        </span>
                      </td>
                      <td className="p-4 pr-6 text-right">
                        <div className="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                          <button 
                            className="p-2 text-gray-400 hover:text-teal-600 bg-white hover:bg-teal-50 rounded-lg shadow-sm border border-gray-100 transition-all"
                            title="View Invoice"
                          >
                            <FileText size={16} />
                          </button>
                          <button 
                            onClick={() => handleDelete(payment.id)}
                            className="p-2 text-gray-400 hover:text-red-600 bg-white hover:bg-red-50 rounded-lg shadow-sm border border-gray-100 transition-all"
                            title="Delete Payment"
                          >
                            <Trash2 size={16} />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}
      </div>

      <Modal isOpen={isModalOpen} onClose={() => setIsModalOpen(false)} title="Record Payment">
         <form onSubmit={handleSubmit} className="p-6 space-y-5">
            <div className="space-y-2">
               <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Order ID</label>
               <input 
                 required
                 value={formData.order_id}
                 onChange={(e) => setFormData(prev => ({ ...prev, order_id: e.target.value.toUpperCase() }))}
                 placeholder="e.g. ORD-123456"
                 className="w-full px-5 py-3 bg-gray-50 border border-gray-100 rounded-xl focus:outline-none focus:border-teal-500 font-bold"
               />
            </div>

            <div className="grid grid-cols-2 gap-4">
               <div className="space-y-2">
                  <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Amount ($)</label>
                  <input 
                    required
                    type="number"
                    step="0.01"
                    min="0"
                    value={formData.amount}
                    onChange={(e) => setFormData(prev => ({ ...prev, amount: e.target.value }))}
                    placeholder="0.00"
                    className="w-full px-5 py-3 bg-gray-50 border border-gray-100 rounded-xl focus:outline-none focus:border-teal-500 font-bold font-mono text-teal-700"
                  />
               </div>
               <div className="space-y-2">
                  <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Date Paid</label>
                  <input 
                    type="date"
                    required
                    value={formData.paid_at}
                    onChange={(e) => setFormData(prev => ({ ...prev, paid_at: e.target.value }))}
                    className="w-full px-5 py-3 bg-gray-50 border border-gray-100 rounded-xl focus:outline-none focus:border-teal-500 font-bold text-gray-700"
                  />
               </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
               <div className="space-y-2">
                  <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Method</label>
                  <select 
                    value={formData.method}
                    onChange={(e) => setFormData(prev => ({ ...prev, method: e.target.value }))}
                    className="w-full px-5 py-3 bg-gray-50 border border-gray-100 rounded-xl focus:outline-none focus:border-teal-500 font-bold"
                  >
                     <option value="bank_transfer">Bank Transfer</option>
                     <option value="mpesa">M-PESA / Mobile Money</option>
                     <option value="card">Credit/Debit Card</option>
                     <option value="cash">Cash</option>
                  </select>
               </div>
               <div className="space-y-2">
                  <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Reference No.</label>
                  <input 
                    value={formData.reference}
                    onChange={(e) => setFormData(prev => ({ ...prev, reference: e.target.value }))}
                    placeholder="Txn ID"
                    className="w-full px-5 py-3 bg-gray-50 border border-gray-100 rounded-xl focus:outline-none focus:border-teal-500 font-bold"
                  />
               </div>
            </div>

            <div className="space-y-2">
               <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Notes (Optional)</label>
               <textarea 
                 value={formData.notes}
                 onChange={(e) => setFormData(prev => ({ ...prev, notes: e.target.value }))}
                 placeholder="Any additional details..."
                 className="w-full px-5 py-3 bg-gray-50 border border-gray-100 rounded-xl focus:outline-none focus:border-teal-500 font-medium resize-none"
                 rows={3}
               />
            </div>

            <button 
              type="submit"
              disabled={submitting}
              className="w-full py-4 bg-[#0d2e2e] text-white rounded-xl font-black uppercase tracking-widest text-xs hover:bg-teal-600 transition-all shadow-xl shadow-[#0d2e2e]/10"
            >
               {submitting ? 'Recording...' : 'Record Payment'}
            </button>
         </form>
      </Modal>
    </>
  );
}
