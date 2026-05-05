'use client';

import { useState, useEffect } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { useAuth } from '@/contexts/AuthContext';
import { apiFetch } from '@/lib/api';
import { statusBadge } from '@/lib/constants';
import { useToast } from '@/components/ui/Toast';
import PageHero from '@/components/admin/PageHero';
import { 
  Building2, 
  User, 
  Mail, 
  Phone, 
  Hash, 
  Calendar, 
  Clock, 
  CheckCircle2, 
  AlertCircle, 
  Printer, 
  Send,
  ArrowRight,
  Package,
  CreditCard,
  ChevronDown
} from 'lucide-react';
import { motion } from 'framer-motion';

interface OrderItem {
  id: number;
  product_code: string;
  product_name: string;
  unit_price: number | string;
  quantity: number;
  total_price: number | string;
  color_name: string | null;
}

interface Order {
  id: number;
  order_id: string;
  event_slug: string;
  company_name: string;
  contact_name: string;
  email: string;
  phone: string;
  booth_number: string;
  status: string;
  notes: string;
  total: number | string;
  created_at: string;
  items: OrderItem[];
}

interface EventData {
  short_name?: string;
  [key: string]: any;
}

interface OrderDetailData {
  order: Order;
  event: EventData;
  statuses: string[];
}



export default function OrderDetailPage() {
  const { id } = useParams();
  const { token } = useAuth();
  const { toast, confirm } = useToast();
  const router = useRouter();
  
  const [data, setData] = useState<OrderDetailData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [updating, setUpdating] = useState(false);
  const [sendingEmail, setSendingEmail] = useState(false);
  const [emailSent, setEmailSent] = useState(false);

  useEffect(() => {
    if (!token) return;
    
    apiFetch<OrderDetailData>(`/orders/${id}`, { token })
      .then(setData)
      .catch(err => setError(err.message || 'Failed to load order'))
      .finally(() => setLoading(false));
  }, [token, id]);

  const handleStatusChange = async (newStatus: string) => {
    if (!data || !token) return;
    setUpdating(true);
    try {
      await apiFetch(`/orders/${id}/status`, {
        method: 'PUT',
        token,
        body: JSON.stringify({ status: newStatus })
      });
      setData({ ...data, order: { ...data.order, status: newStatus } });
      toast.success('Order status updated');
    } catch (err: any) {
      toast.error(err.message || 'Failed to update status');
    } finally {
      setUpdating(false);
    }
  };

  const handlePrint = () => {
    router.push(`/admin/orders/${id}/invoice`);
  };

  const handleSendEmail = async () => {
    if (!token || !data) return;
    if (!(await confirm(`Send confirmation email to ${data.order.email}?`))) return;
    setSendingEmail(true);
    setEmailSent(false);
    try {
      await apiFetch(`/orders/${id}/send-email`, {
        method: 'POST',
        token,
        body: JSON.stringify({ type: 'confirmation' })
      });
      setEmailSent(true);
      toast.success('Email sent to client');
      setTimeout(() => setEmailSent(false), 4000);
    } catch (err: any) {
      toast.error(err.message || 'Failed to send email');
    } finally {
      setSendingEmail(false);
    }
  };

  if (loading) return (
    <div className="p-10 flex flex-col items-center justify-center min-h-[400px]">
       <div className="w-10 h-10 border-4 border-teal-600 border-t-transparent rounded-full animate-spin mb-4" />
       <p className="text-gray-500 font-bold uppercase tracking-widest text-[10px]">Synchronizing Order Data...</p>
    </div>
  );

  if (error) return (
    <div className="p-10 text-center">
       <div className="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
          <AlertCircle size={32} />
       </div>
       <h2 className="text-xl font-black text-gray-900 mb-2">Error Loading Order</h2>
       <p className="text-gray-500 mb-6">{error}</p>
       <button onClick={() => window.location.reload()} className="px-6 py-2 bg-gray-900 text-white rounded-lg text-xs font-black uppercase tracking-widest">Retry</button>
    </div>
  );

  if (!data) return null;

  const { order, event, statuses } = data;

  return (
    <>
      <PageHero 
        title={order.order_id} 
        breadcrumbs={[
          { label: 'Orders', href: '/admin' },
          { label: order.order_id }
        ]} 
      />

      <div className="p-8 max-w-7xl mx-auto space-y-8 w-full">
        
        {/* Top Control Bar */}
        <div className="flex flex-wrap items-center justify-between gap-6 bg-white p-6 rounded-[32px] border border-gray-100 shadow-sm">
          <div className="flex items-center gap-6">
            <div className="flex flex-col">
              <span className="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1">Order Date</span>
              <div className="flex items-center gap-2 text-gray-900">
                <Calendar size={14} className="text-teal-600" />
                <span className="font-bold text-sm">{new Date(order.created_at).toLocaleDateString()}</span>
                <span className="text-gray-300 mx-1">|</span>
                <Clock size={14} className="text-teal-600" />
                <span className="font-bold text-sm">{new Date(order.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
              </div>
            </div>
            <div className="w-[1px] h-10 bg-gray-100" />
            <div className="flex flex-col">
              <span className="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1">Status</span>
              <div className="relative group">
                <select 
                  value={order.status}
                  onChange={(e) => handleStatusChange(e.target.value)}
                  disabled={updating}
                  className={`appearance-none px-4 py-1.5 pr-10 rounded-full text-[11px] font-black uppercase tracking-widest transition-all cursor-pointer border-0 shadow-sm ${statusBadge[order.status] || 'bg-gray-100 text-gray-700'}`}
                >
                  {statuses.map(s => <option key={s} value={s}>{s}</option>)}
                </select>
                <ChevronDown size={14} className="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
              </div>
            </div>
          </div>

          <div className="flex items-center gap-3">
             <button
               onClick={handlePrint}
               className="flex items-center gap-2 px-6 py-3 bg-gray-50 text-gray-900 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-gray-100 transition-all border border-gray-100"
             >
                <Printer size={16} />
                Print Order
             </button>
             <button
               onClick={handleSendEmail}
               disabled={sendingEmail}
               className={`flex items-center gap-2 px-8 py-3 rounded-2xl text-xs font-black uppercase tracking-widest transition-all shadow-xl disabled:opacity-50 ${
                 emailSent
                   ? 'bg-green-500 text-white shadow-green-500/20'
                   : 'bg-teal-600 text-white hover:bg-teal-700 shadow-teal-600/20'
               }`}
             >
                <Send size={16} />
                {sendingEmail ? 'Sending...' : emailSent ? 'Email Sent!' : 'Email to Client'}
             </button>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          
          {/* Items Table */}
          <div className="lg:col-span-2 space-y-8">
            <div className="bg-white rounded-[32px] border border-gray-100 shadow-sm overflow-hidden">
               <div className="px-8 py-6 border-b border-gray-50 flex items-center justify-between bg-gray-50/50">
                  <div className="flex items-center gap-3">
                     <Package size={20} className="text-teal-600" />
                     <h3 className="font-black text-gray-900 uppercase tracking-tight">Reserved Furniture</h3>
                  </div>
                  <span className="text-[10px] text-gray-400 font-black uppercase tracking-widest">{order.items.length} items total</span>
               </div>
               
               <table className="w-full">
                  <thead>
                     <tr className="text-left text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] border-b border-gray-50">
                        <th className="px-8 py-4">Product Details</th>
                        <th className="px-8 py-4 text-center">Qty</th>
                        <th className="px-8 py-4 text-right">Unit Price</th>
                        <th className="px-8 py-4 text-right">Total</th>
                     </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-50">
                     {order.items.map((item) => (
                        <tr key={item.id} className="group hover:bg-teal-50/30 transition-colors">
                           <td className="px-8 py-6">
                              <div className="flex items-center gap-6">
                                 <div className="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center p-2 border border-gray-100 overflow-hidden group-hover:border-teal-200 transition-colors">
                                    <img 
                                       src={`/static/images/products/${(item.product_code || '').toUpperCase()}.jpg`}
                                       alt={item.product_name}
                                       onError={(e) => (e.target as HTMLImageElement).src = '/static/images/products/placeholder.jpg'}
                                       className="max-w-full max-h-full object-contain mix-blend-multiply"
                                    />
                                 </div>
                                 <div>
                                    <p className="font-black text-gray-900 text-sm leading-tight mb-1">{item.product_name}</p>
                                    <div className="flex items-center gap-3">
                                       <span className="text-[10px] font-black text-teal-600 uppercase tracking-widest">{item.product_code}</span>
                                       {item.color_name && (
                                          <div className="flex items-center gap-1.5 px-2 py-0.5 bg-gray-100 rounded-md">
                                             <div className="w-1.5 h-1.5 rounded-full" style={{ backgroundColor: item.color_name.toLowerCase() === 'white' ? '#fff' : item.color_name.toLowerCase() }} />
                                             <span className="text-[10px] font-bold text-gray-500 uppercase">{item.color_name}</span>
                                          </div>
                                       )}
                                    </div>
                                 </div>
                              </div>
                           </td>
                           <td className="px-8 py-6 text-center">
                              <span className="w-8 h-8 inline-flex items-center justify-center bg-gray-900 text-white rounded-lg text-xs font-black">
                                 {item.quantity}
                              </span>
                           </td>
                           <td className="px-8 py-6 text-right">
                              <p className="text-xs font-bold text-gray-500">${Number(item.unit_price).toFixed(2)}</p>
                           </td>
                           <td className="px-8 py-6 text-right">
                              <p className="text-base font-black text-gray-900 tracking-tighter">${Number(item.total_price).toFixed(2)}</p>
                           </td>
                        </tr>
                     ))}
                  </tbody>
               </table>

               <div className="p-10 bg-gray-900 text-white flex items-center justify-between">
                  <div>
                     <h4 className="text-[10px] font-black text-teal-400 uppercase tracking-[0.3em] mb-1">Grand Total</h4>
                     <p className="text-xs text-gray-500 font-medium italic">Pending approval of warehouse stock</p>
                  </div>
                  <div className="text-right">
                     <p className="text-5xl font-black tracking-tighter text-white">${Number(order.total).toFixed(2)}</p>
                  </div>
               </div>
            </div>

            {order.notes && (
              <div className="bg-white rounded-[32px] border border-gray-100 shadow-sm p-8 space-y-4">
                 <div className="flex items-center gap-3 text-amber-500">
                    <AlertCircle size={20} />
                    <h3 className="font-black text-gray-900 uppercase tracking-widest text-xs">Exhibitor Special Instructions</h3>
                 </div>
                 <div className="bg-amber-50 border border-amber-100 text-amber-900 p-6 rounded-2xl text-sm leading-relaxed font-medium">
                    {order.notes}
                 </div>
              </div>
            )}
          </div>

          {/* Sidebar Info Cards */}
          <div className="space-y-8">
             <div className="bg-white rounded-[32px] border border-gray-100 shadow-sm overflow-hidden">
                <div className="px-8 py-6 border-b border-gray-50 bg-gray-50/50">
                   <h3 className="font-black text-gray-900 uppercase tracking-widest text-[10px]">Client Intelligence</h3>
                </div>
                <div className="p-8 space-y-8">
                   <div className="flex items-center gap-4">
                      <div className="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-gray-400">
                         <Building2 size={18} />
                      </div>
                      <div>
                         <p className="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Exhibiting Entity</p>
                         <p className="font-black text-gray-900 tracking-tight">{order.company_name}</p>
                      </div>
                   </div>
                   <div className="flex items-center gap-4">
                      <div className="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-gray-400">
                         <User size={18} />
                      </div>
                      <div>
                         <p className="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Primary Liaison</p>
                         <p className="font-bold text-gray-900">{order.contact_name}</p>
                      </div>
                   </div>
                   <div className="pt-4 space-y-3">
                      <a href={`mailto:${order.email}`} className="flex items-center justify-between p-3 bg-gray-50 rounded-xl hover:bg-teal-50 transition-colors group">
                         <div className="flex items-center gap-3">
                            <Mail size={14} className="text-gray-300 group-hover:text-teal-600" />
                            <span className="text-xs font-bold text-gray-600 truncate max-w-[150px]">{order.email}</span>
                         </div>
                         <ArrowRight size={14} className="text-gray-200 group-hover:text-teal-600" />
                      </a>
                      <a href={`tel:${order.phone}`} className="flex items-center justify-between p-3 bg-gray-50 rounded-xl hover:bg-teal-50 transition-colors group">
                         <div className="flex items-center gap-3">
                            <Phone size={14} className="text-gray-300 group-hover:text-teal-600" />
                            <span className="text-xs font-bold text-gray-600">{order.phone}</span>
                         </div>
                         <ArrowRight size={14} className="text-gray-200 group-hover:text-teal-600" />
                      </a>
                   </div>
                </div>
             </div>

             <div className="bg-white rounded-[32px] border border-gray-100 shadow-sm overflow-hidden">
                <div className="px-8 py-6 border-b border-gray-50 bg-gray-50/50">
                   <h3 className="font-black text-gray-900 uppercase tracking-widest text-[10px]">Logistics Mapping</h3>
                </div>
                <div className="p-8 space-y-8">
                   <div className="flex items-center gap-4">
                      <div className="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-gray-400">
                         <Hash size={18} />
                      </div>
                      <div>
                         <p className="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Assigned Booth</p>
                         <span className="px-3 py-1 bg-gray-900 text-white rounded-lg font-mono font-black text-xs uppercase tracking-widest">
                           {order.booth_number}
                         </span>
                      </div>
                   </div>
                   <div className="flex items-center gap-4">
                      <div className="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-gray-400">
                         <CheckCircle2 size={18} />
                      </div>
                      <div>
                         <p className="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Official Event</p>
                         <p className="font-black text-gray-900 tracking-tight">{event?.short_name || order.event_slug}</p>
                      </div>
                   </div>
                </div>
             </div>

             <div className="bg-gray-50 p-8 rounded-[32px] border border-gray-100 space-y-4">
                <div className="flex items-center gap-2 text-gray-400">
                   <CreditCard size={16} />
                   <h4 className="text-[10px] font-black uppercase tracking-widest">Payment Strategy</h4>
                </div>
                <p className="text-xs text-gray-500 font-medium leading-relaxed">
                   Upon approval, the system will generate a downloadable PDF invoice. You can then trigger an automated email to the client's liaison.
                </p>
             </div>
          </div>
        </div>
      </div>
    </>
  );
}
