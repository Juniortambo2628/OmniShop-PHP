'use client';

import { useState, use, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useCart } from '@/contexts/CartContext';
import { apiFetch } from '@/lib/api';
import Link from 'next/link';
import { motion, AnimatePresence } from 'framer-motion';
import { 
  Building2, 
  User, 
  Mail, 
  Phone, 
  Hash, 
  MessageSquare, 
  ArrowRight, 
  ChevronLeft, 
  ShoppingBag, 
  CheckCircle2,
  AlertCircle,
  ShieldCheck,
  Map,
  Maximize,
  X,
  Truck,
  Ticket,
  Loader2
} from 'lucide-react';
import Modal from '@/components/ui/Modal';

export default function CheckoutPage({ params }: { params: Promise<{ event_slug: string }> }) {
  const unwrappedParams = use(params);
  const event_slug = unwrappedParams.event_slug;
  const router = useRouter();
  const { items, totalItems, subtotal, clearCart, updateQuantity } = useCart();
  
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [formData, setFormData] = useState({
    company_name: '',
    contact_name: '',
    email: '',
    phone: '',
    booth_number: '',
    notes: '',
  });

  const [cmsSettings, setCmsSettings] = useState<any[]>([]);
  const [isMapOpen, setIsMapOpen] = useState(false);

  // Promo Code State
  const [promoCode, setPromoCode] = useState('');
  const [appliedPromo, setAppliedPromo] = useState<any>(null);
  const [promoError, setPromoError] = useState('');
  const [isValidatingPromo, setIsValidatingPromo] = useState(false);

  useEffect(() => {
    apiFetch<any[]>('/storefront/settings')
      .then(setCmsSettings)
      .catch(() => {});
  }, []);

  const getCmsValue = (key: string, def: string) => cmsSettings.find(s => s.key === key)?.value || def;
  const deliveryEnabled = getCmsValue('delivery_enabled', 'false') === 'true';
  const deliveryRates = JSON.parse(getCmsValue('delivery_rates', '{}'));

  const deliveryCost = deliveryEnabled ? items.reduce((sum, item) => {
    const rate = deliveryRates[item.category] || deliveryRates.default || 0;
    return sum + (rate * item.quantity);
  }, 0) : 0;

  const discountAmount = appliedPromo ? appliedPromo.discount_amount : 0;
  const finalTotal = Math.max(0, subtotal + deliveryCost - discountAmount);

  const handleValidatePromo = async () => {
    if (!promoCode) return;
    setIsValidatingPromo(true);
    setPromoError('');
    try {
      const res = await apiFetch<any>('/promo-codes/validate', {
        method: 'POST',
        body: JSON.stringify({ code: promoCode, subtotal })
      });
      setAppliedPromo(res);
      setPromoCode('');
    } catch (err: any) {
      setPromoError(err.body?.message || 'Invalid promo code');
    } finally {
      setIsValidatingPromo(false);
    }
  };

  const removePromo = () => {
    setAppliedPromo(null);
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (items.length === 0) {
      setError('Your cart is empty.');
      return;
    }

    setLoading(true);
    setError('');

    try {
      const token = sessionStorage.getItem(`catalog_auth_${event_slug}`);
      const payload = {
        ...formData,
        delivery_cost: deliveryCost,
        promo_code: appliedPromo?.code || null,
        discount_amount: discountAmount,
        total_amount: finalTotal,
        items: items.map(i => ({
          product_id: i.product_id,
          name: i.name,
          price: i.price,
          quantity: i.quantity,
          color: i.color,
          category: i.category
        }))
      };

      const res = await apiFetch<{ order_id: string }>(`/catalog/${event_slug}/checkout`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${token}` },
        body: JSON.stringify(payload)
      });

      clearCart();
      router.push(`/catalog/${event_slug}/confirmation/${res.order_id}`);
    } catch (err: any) {
      setError(err.body?.message || 'Failed to submit order. Please try again.');
      setLoading(false);
    }
  };

  if (items.length === 0) {
    return (
      <div className="min-h-[80vh] flex items-center justify-center px-4">
        <motion.div 
          initial={{ opacity: 0, scale: 0.9 }}
          animate={{ opacity: 1, scale: 1 }}
          className="max-w-md w-full text-center"
        >
          <div className="w-24 h-24 bg-gray-50 rounded-[32px] flex items-center justify-center mx-auto mb-8 shadow-inner">
             <ShoppingBag size={40} className="text-gray-200" />
          </div>
          <h2 className="text-3xl font-black text-[#0d2e2e] tracking-tighter mb-4">Your collection is empty</h2>
          <p className="text-gray-500 font-medium mb-10 leading-relaxed">
            Browse our catalog to select premium furniture for your event booth.
          </p>
          <Link 
            href={`/catalog/${event_slug}`} 
            className="inline-flex items-center justify-center gap-2 px-10 py-4 bg-[#0d2e2e] text-white font-black uppercase tracking-widest text-xs rounded-2xl hover:bg-teal-600 transition-all shadow-xl shadow-[#0d2e2e]/10"
          >
            START BROWSING
            <ArrowRight size={16} />
          </Link>
        </motion.div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50/50">
      {/* Checkout Header */}
      <div className="bg-white border-b border-gray-100">
         <div className="max-w-7xl mx-auto px-4 h-24 flex items-center justify-between">
            <Link 
              href={`/catalog/${event_slug}`}
              className="flex items-center gap-2 text-gray-400 hover:text-[#0d2e2e] transition-colors group"
            >
              <ChevronLeft size={20} className="group-hover:-translate-x-1 transition-transform" />
              <span className="text-xs font-black uppercase tracking-widest">Back to Catalog</span>
            </Link>
            <div className="flex items-center gap-3">
               <div className="hidden sm:flex items-center gap-2 text-teal-600">
                  <ShieldCheck size={18} />
                  <span className="text-[10px] font-black uppercase tracking-[0.2em]">Secure Checkout</span>
               </div>
            </div>
         </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 py-16">
        <div className="mb-16">
          <h1 className="text-5xl font-black text-[#0d2e2e] tracking-tighter leading-none mb-4">Complete Request</h1>
          <p className="text-gray-500 font-medium text-lg">Finalize your selection and provide exhibitor details.</p>
        </div>

        <div className="flex flex-col lg:flex-row gap-16 items-start">
          
          {/* Main Content: Form Sections */}
          <div className="flex-1 space-y-12">
            <form id="checkout-form" onSubmit={handleSubmit} className="space-y-12">
              
              {error && (
                <motion.div 
                  initial={{ opacity: 0, y: -10 }}
                  animate={{ opacity: 1, y: 0 }}
                  className="p-6 bg-red-50 border border-red-100 rounded-3xl flex items-center gap-4 text-red-700"
                >
                  <AlertCircle size={24} />
                  <p className="font-bold text-sm">{error}</p>
                </motion.div>
              )}

              {/* Section 1: Contact Information */}
              <div className="space-y-8">
                <div className="flex items-center gap-4">
                  <div className="w-12 h-12 bg-white rounded-2xl flex items-center justify-center shadow-sm border border-gray-100">
                    <User size={20} className="text-teal-600" />
                  </div>
                  <h2 className="text-2xl font-black text-[#0d2e2e] tracking-tight">Exhibitor Contact</h2>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div className="space-y-2">
                    <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest block ml-1">Contact Name</label>
                    <div className="relative group">
                      <User className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-teal-600 transition-colors" size={18} />
                      <input 
                        required 
                        name="contact_name"
                        value={formData.contact_name}
                        onChange={handleChange}
                        placeholder="e.g. John Doe"
                        className="w-full pl-12 pr-6 py-4 bg-white border border-gray-100 rounded-2xl focus:outline-none focus:ring-4 focus:ring-teal-500/5 focus:border-teal-500 transition-all font-bold text-[#0d2e2e]"
                      />
                    </div>
                  </div>
                  <div className="space-y-2">
                    <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest block ml-1">Email Address</label>
                    <div className="relative group">
                      <Mail className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-teal-600 transition-colors" size={18} />
                      <input 
                        required 
                        type="email"
                        name="email"
                        value={formData.email}
                        onChange={handleChange}
                        placeholder="john@company.com"
                        className="w-full pl-12 pr-6 py-4 bg-white border border-gray-100 rounded-2xl focus:outline-none focus:ring-4 focus:ring-teal-500/5 focus:border-teal-500 transition-all font-bold text-[#0d2e2e]"
                      />
                    </div>
                  </div>
                  <div className="space-y-2 md:col-span-2">
                    <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest block ml-1">Phone Number</label>
                    <div className="relative group">
                      <Phone className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-teal-600 transition-colors" size={18} />
                      <input 
                        required 
                        name="phone"
                        value={formData.phone}
                        onChange={handleChange}
                        placeholder="+1 (555) 000-0000"
                        className="w-full pl-12 pr-6 py-4 bg-white border border-gray-100 rounded-2xl focus:outline-none focus:ring-4 focus:ring-teal-500/5 focus:border-teal-500 transition-all font-bold text-[#0d2e2e]"
                      />
                    </div>
                  </div>
                </div>
              </div>

              {/* Section 2: Booth Details */}
              <div className="space-y-8">
                <div className="flex items-center justify-between">
                   <div className="flex items-center gap-4">
                     <div className="w-12 h-12 bg-white rounded-2xl flex items-center justify-center shadow-sm border border-gray-100">
                        <Building2 size={20} className="text-teal-600" />
                     </div>
                     <h2 className="text-2xl font-black text-[#0d2e2e] tracking-tight">Stand & Booth Info</h2>
                   </div>
                   <button 
                     type="button"
                     onClick={() => setIsMapOpen(true)}
                     className="flex items-center gap-2 px-4 py-2 bg-teal-50 text-teal-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-teal-100 transition-all"
                   >
                      <Map size={14} />
                      Visual Floor Plan
                   </button>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <div className="md:col-span-2 space-y-2">
                    <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest block ml-1">Exhibiting Company</label>
                    <div className="relative group">
                      <Building2 className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-teal-600 transition-colors" size={18} />
                      <input 
                        name="company_name"
                        value={formData.company_name}
                        onChange={handleChange}
                        placeholder="Your Company LTD (Optional)"
                        className="w-full pl-12 pr-6 py-4 bg-white border border-gray-100 rounded-2xl focus:outline-none focus:ring-4 focus:ring-teal-500/5 focus:border-teal-500 transition-all font-bold text-[#0d2e2e]"
                      />
                    </div>
                  </div>
                  <div className="space-y-2">
                    <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest block ml-1">Booth / Stand No.</label>
                    <div className="relative group">
                      <Hash className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-teal-600 transition-colors" size={18} />
                      <input 
                        name="booth_number"
                        value={formData.booth_number}
                        onChange={handleChange}
                        placeholder="e.g. A12 (Optional)"
                        className="w-full pl-12 pr-6 py-4 bg-white border border-gray-100 rounded-2xl focus:outline-none focus:ring-4 focus:ring-teal-500/5 focus:border-teal-500 transition-all font-bold text-[#0d2e2e]"
                      />
                    </div>
                  </div>
                </div>
              </div>

              {/* Section 3: Additional Notes */}
              <div className="space-y-8">
                <div className="flex items-center gap-4">
                  <div className="w-12 h-12 bg-white rounded-2xl flex items-center justify-center shadow-sm border border-gray-100">
                    <MessageSquare size={20} className="text-teal-600" />
                  </div>
                  <h2 className="text-2xl font-black text-[#0d2e2e] tracking-tight">Special Instructions</h2>
                </div>

                <div className="space-y-2">
                   <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest block ml-1">Notes for our delivery team</label>
                   <textarea 
                     name="notes"
                     value={formData.notes}
                     onChange={handleChange}
                     rows={4}
                     placeholder="e.g. Please deliver after 2PM on set-up day..."
                     className="w-full px-6 py-4 bg-white border border-gray-100 rounded-3xl focus:outline-none focus:ring-4 focus:ring-teal-500/5 focus:border-teal-500 transition-all font-medium text-gray-700 leading-relaxed"
                   />
                </div>
              </div>
            </form>
          </div>

          {/* Sidebar: Order Summary Card */}
          <div className="w-full lg:w-[450px] shrink-0 sticky top-12">
            <div className="bg-[#0d2e2e] text-white rounded-[40px] shadow-2xl overflow-hidden">
               <div className="p-10 border-b border-white/5">
                  <div className="flex items-center justify-between mb-2">
                     <h3 className="text-2xl font-black tracking-tighter">Order Summary</h3>
                     <ShoppingBag size={24} className="text-teal-400" />
                  </div>
                  <p className="text-gray-400 text-xs font-medium uppercase tracking-widest">{totalItems} Premium Items Selected</p>
               </div>

               <div className="px-10 py-8 max-h-[400px] overflow-y-auto space-y-8 scrollbar-thin scrollbar-thumb-white/10">
                  <AnimatePresence>
                  {items.map(item => (
                    <motion.div 
                      key={item.cart_id} 
                      layout
                      initial={{ opacity: 0, x: 20 }}
                      animate={{ opacity: 1, x: 0 }}
                      className="flex gap-6 group"
                    >
                      <div className="w-20 h-20 bg-white/5 rounded-2xl border border-white/10 flex items-center justify-center p-2 relative shrink-0 overflow-hidden">
                        {item.image ? (
                          <img src={item.image} alt={item.name} className="w-full h-full object-contain" />
                        ) : (
                          <span className="text-2xl opacity-20">🛋️</span>
                        )}
                      </div>
                      <div className="flex-1 min-w-0">
                        <h4 className="font-bold text-sm leading-tight truncate mb-1">{item.name}</h4>
                        <div className="flex items-center gap-3 mb-4">
                           <p className="text-[10px] font-black text-teal-400 uppercase tracking-widest">{item.product_id}</p>
                           {item.color && (
                              <span className="text-[9px] font-bold text-gray-400 uppercase px-2 py-0.5 bg-white/5 rounded-md border border-white/5">{item.color}</span>
                           )}
                        </div>
                        <div className="flex items-center justify-between">
                           <div className="flex items-center gap-4 bg-white/5 p-1 rounded-xl">
                              <button onClick={() => updateQuantity(item.cart_id, item.quantity - 1)} className="w-6 h-6 flex items-center justify-center hover:bg-white/10 rounded-lg transition-colors text-gray-400">−</button>
                              <span className="text-xs font-black">{item.quantity}</span>
                              <button onClick={() => updateQuantity(item.cart_id, item.quantity + 1)} className="w-6 h-6 flex items-center justify-center hover:bg-white/10 rounded-lg transition-colors text-gray-400">+</button>
                           </div>
                           <p className="font-black text-base tracking-tight">${(Number(item.price) * item.quantity).toFixed(2)}</p>
                        </div>
                      </div>
                    </motion.div>
                  ))}
                  </AnimatePresence>
               </div>

               {/* Promo Code Section */}
               <div className="px-10 py-6 border-y border-white/5 bg-white/[0.02]">
                  {!appliedPromo ? (
                    <div className="space-y-3">
                       <label className="text-[10px] font-black text-gray-500 uppercase tracking-widest ml-1">Have a promo code?</label>
                       <div className="flex gap-2">
                          <div className="relative flex-1">
                             <Ticket className="absolute left-3 top-1/2 -translate-y-1/2 text-white/20" size={16} />
                             <input 
                               value={promoCode}
                               onChange={(e) => setPromoCode(e.target.value.toUpperCase())}
                               placeholder="ENTER CODE"
                               className="w-full pl-10 pr-4 py-3 bg-white/5 border border-white/10 rounded-xl focus:outline-none focus:border-teal-500 transition-all font-black text-xs tracking-widest text-white uppercase"
                             />
                          </div>
                          <button 
                            type="button"
                            onClick={handleValidatePromo}
                            disabled={isValidatingPromo || !promoCode}
                            className="px-6 bg-teal-600 hover:bg-teal-500 disabled:opacity-50 disabled:hover:bg-teal-600 rounded-xl transition-all flex items-center justify-center min-w-[80px]"
                          >
                             {isValidatingPromo ? <Loader2 size={18} className="animate-spin" /> : <span className="text-[10px] font-black uppercase">Apply</span>}
                          </button>
                       </div>
                       {promoError && <p className="text-[10px] font-bold text-red-400 ml-1">{promoError}</p>}
                    </div>
                  ) : (
                    <div className="flex items-center justify-between bg-teal-500/10 border border-teal-500/30 p-4 rounded-2xl">
                       <div className="flex items-center gap-3">
                          <div className="w-8 h-8 bg-teal-500 rounded-lg flex items-center justify-center shadow-lg shadow-teal-500/20">
                             <Ticket size={16} className="text-white" />
                          </div>
                          <div>
                             <p className="text-[10px] font-black uppercase tracking-widest leading-none mb-1">Code Applied</p>
                             <p className="text-xs font-black text-teal-400">{appliedPromo.code}</p>
                          </div>
                       </div>
                       <div className="flex items-center gap-4">
                          <p className="text-sm font-black text-teal-400">-${Number(appliedPromo.discount_amount).toFixed(2)}</p>
                          <button onClick={removePromo} className="text-white/20 hover:text-white transition-colors">
                             <X size={16} />
                          </button>
                       </div>
                    </div>
                  )}
               </div>

               <div className="p-10 bg-white/5 space-y-8">
                  <div className="space-y-4">
                     <div className="flex items-center justify-between text-gray-400">
                        <span className="text-xs font-black uppercase tracking-widest">Subtotal</span>
                        <span className="font-bold">${Number(subtotal).toFixed(2)}</span>
                     </div>
                     {appliedPromo && (
                        <div className="flex items-center justify-between text-teal-400">
                           <span className="text-xs font-black uppercase tracking-widest">Discount</span>
                           <span className="font-bold">-${Number(appliedPromo.discount_amount).toFixed(2)}</span>
                        </div>
                     )}
                     <div className="flex items-center justify-between text-gray-400">
                        <span className="text-xs font-black uppercase tracking-widest">Delivery & Set-up</span>
                        {deliveryEnabled ? (
                          <span className="font-bold text-teal-400">${deliveryCost.toFixed(2)}</span>
                        ) : (
                          <span className="text-[10px] font-black uppercase tracking-widest text-teal-400 bg-teal-400/10 px-2 py-1 rounded-md">Not Included</span>
                        )}
                     </div>
                     <div className="pt-4 border-t border-white/10 flex items-center justify-between">
                        <span className="text-lg font-black tracking-tight">Total Due</span>
                        <span className="text-3xl font-black text-teal-400 tracking-tighter">${finalTotal.toFixed(2)}</span>
                     </div>
                  </div>

                  <button 
                    type="submit" 
                    form="checkout-form"
                    disabled={loading}
                    className="group w-full h-16 bg-white text-[#0d2e2e] rounded-[20px] font-black uppercase tracking-widest text-xs flex items-center justify-center gap-3 transition-all duration-300 hover:bg-teal-400 hover:shadow-xl hover:shadow-teal-400/20 active:scale-95 disabled:opacity-50"
                  >
                    {loading ? 'Processing...' : (
                      <>
                        Confirm Request
                        <CheckCircle2 size={18} className="group-hover:scale-110 transition-transform" />
                      </>
                    )}
                  </button>

                  <div className="flex items-center justify-center gap-2 pt-4 opacity-30">
                     <ShieldCheck size={14} />
                     <p className="text-[9px] font-black uppercase tracking-[0.2em]">End-to-end Encrypted Request</p>
                  </div>
               </div>
            </div>

            <div className="mt-8 p-8 bg-teal-50 border border-teal-100 rounded-[32px]">
               <div className="flex items-center gap-3 mb-3">
                  <Truck size={20} className="text-teal-600" />
                  <h4 className="text-teal-900 font-black uppercase tracking-widest text-[10px]">Logistics Note</h4>
               </div>
               <p className="text-teal-700 text-xs font-medium leading-relaxed">
                  {deliveryEnabled 
                    ? 'Delivery fees are calculated based on item categories and quantities to ensure specialized handling.'
                    : 'Standard delivery is not included. Our team will contact you to discuss logistical arrangements and specialized handling fees.'}
               </p>
            </div>
          </div>

        </div>
      </div>

      {/* Visual Floor Plan Modal */}
      <Modal 
        isOpen={isMapOpen} 
        onClose={() => setIsMapOpen(false)} 
        title="Event Floor Plan"
        size="4xl"
      >
        <div className="p-4 flex flex-col items-center">
           <div className="bg-gray-100 rounded-3xl border border-gray-200 overflow-hidden relative w-full aspect-video flex items-center justify-center">
              <div className="absolute inset-0 z-0 opacity-20">
                 <div className="w-full h-full" style={{ backgroundImage: 'radial-gradient(#0d2e2e 1px, transparent 1px)', backgroundSize: '20px 20px' }} />
              </div>
              <div className="relative z-10 text-center p-20">
                 <Map size={64} className="text-[#0d2e2e] opacity-10 mx-auto mb-6" />
                 <h3 className="text-2xl font-black text-[#0d2e2e] mb-2 tracking-tighter">Interactive Map Integration</h3>
                 <p className="text-gray-500 font-medium max-w-sm mx-auto mb-8">
                    Select your booth location visually to help our delivery team navigate the exhibition hall more efficiently.
                 </p>
                 <div className="grid grid-cols-4 gap-4 max-w-lg mx-auto">
                    {[1,2,3,4,5,6,7,8].map(i => (
                       <button 
                         key={i}
                         onClick={() => {
                           setFormData(prev => ({ ...prev, booth_number: `Booth ${i}` }));
                           setIsMapOpen(false);
                         }}
                         className="h-16 bg-white border border-gray-100 rounded-xl font-black text-xs text-[#0d2e2e] hover:border-teal-500 hover:text-teal-600 transition-all shadow-sm hover:shadow-lg"
                       >
                          B{i}
                       </button>
                    ))}
                 </div>
              </div>
           </div>
           <button 
             onClick={() => setIsMapOpen(false)}
             className="mt-8 px-10 py-4 bg-[#0d2e2e] text-white rounded-2xl font-black uppercase tracking-widest text-xs hover:bg-teal-600 transition-all"
           >
              Close Floor Plan
           </button>
        </div>
      </Modal>
    </div>
  );
}
