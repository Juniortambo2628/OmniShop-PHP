'use client';

import Link from 'next/link';
import { use, useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { CheckCircle2, Package, Mail, Calendar, ArrowRight, Printer, Share2, Star, MessageSquare, Loader2 } from 'lucide-react';
import { apiFetch } from '@/lib/api';

export default function ConfirmationPage({ params }: { params: Promise<{ event_slug: string, order_id: string }> }) {
  const unwrappedParams = use(params);
  const { event_slug, order_id } = unwrappedParams;

  const [rating, setRating] = useState(0);
  const [hoverRating, setHoverRating] = useState(0);
  const [comment, setComment] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [submitted, setSubmitted] = useState(false);

  const handleFeedbackSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (rating === 0) return;
    setSubmitting(true);
    try {
      await apiFetch('/feedback', {
        method: 'POST',
        body: JSON.stringify({ order_id, rating, comment })
      });
      setSubmitted(true);
    } catch (err) {
      console.error(err);
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50/50 flex flex-col items-center justify-center p-6 py-20">
      <motion.div 
        initial={{ opacity: 0, scale: 0.95, y: 20 }}
        animate={{ opacity: 1, scale: 1, y: 0 }}
        className="max-w-3xl w-full bg-white rounded-[48px] shadow-2xl shadow-gray-900/5 overflow-hidden mb-12"
      >
        {/* Header Section */}
        <div className="bg-[#0d2e2e] p-16 text-center relative overflow-hidden">
           <div className="absolute inset-0 z-0">
              <div className="absolute top-0 right-0 w-full h-full bg-gradient-to-br from-teal-600/20 to-transparent" />
              <motion.div 
                animate={{ scale: [1, 1.2, 1], opacity: [0.1, 0.2, 0.1] }}
                transition={{ duration: 10, repeat: Infinity }}
                className="absolute -top-20 -left-20 w-80 h-80 bg-teal-500 rounded-full blur-[100px]" 
              />
           </div>

           <div className="relative z-10">
              <motion.div
                initial={{ scale: 0 }}
                animate={{ scale: 1 }}
                transition={{ type: 'spring', damping: 12, stiffness: 200, delay: 0.2 }}
                className="w-24 h-24 bg-teal-500 text-white rounded-[32px] flex items-center justify-center mx-auto mb-8 shadow-2xl shadow-teal-500/40"
              >
                <CheckCircle2 size={48} strokeWidth={2.5} />
              </motion.div>
              <h1 className="text-4xl md:text-5xl font-black text-white tracking-tighter mb-4 leading-none">Request Received!</h1>
              <p className="text-gray-400 text-lg font-medium">Your premium furniture selection is being processed.</p>
           </div>
        </div>

        {/* Content Section */}
        <div className="p-12 md:p-20 space-y-12">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-12">
             {/* Left: Order Info */}
             <div className="space-y-8">
                <div>
                   <h3 className="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Order Reference</h3>
                   <div className="text-3xl font-black text-gray-900 tracking-tighter bg-gray-50 px-6 py-4 rounded-3xl border border-gray-100 inline-block">
                      {order_id}
                   </div>
                </div>

                <div className="space-y-4">
                   <div className="flex items-center gap-4 text-gray-600">
                      <div className="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-teal-600">
                        <Mail size={18} />
                      </div>
                      <div>
                        <p className="text-xs font-bold text-gray-900">Confirmation Sent</p>
                        <p className="text-[10px] uppercase tracking-widest text-gray-400 font-black">Email Delivery Pending</p>
                      </div>
                   </div>
                   <div className="flex items-center gap-4 text-gray-600">
                      <div className="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-teal-600">
                        <Package size={18} />
                      </div>
                      <div>
                        <p className="text-xs font-bold text-gray-900">Stock Reservation</p>
                        <p className="text-[10px] uppercase tracking-widest text-gray-400 font-black">Processing in Warehouse</p>
                      </div>
                   </div>
                </div>
             </div>

             {/* Right: Next Steps */}
             <div className="bg-teal-50/50 p-10 rounded-[40px] border border-teal-100/50 relative">
                <div className="absolute top-8 right-8 text-teal-200">
                   <Calendar size={48} strokeWidth={1} />
                </div>
                <h3 className="text-teal-900 font-black text-xl tracking-tight mb-4">What happens next?</h3>
                <p className="text-teal-700 text-sm font-medium leading-relaxed mb-6">
                   Our logistics team will verify stock availability for your booth. Once confirmed, we will email you an official invoice with payment and delivery details.
                </p>
                <div className="flex gap-2">
                   <div className="w-2 h-2 bg-teal-500 rounded-full animate-pulse" />
                   <div className="w-2 h-2 bg-teal-300 rounded-full animate-pulse delay-75" />
                   <div className="w-2 h-2 bg-teal-100 rounded-full animate-pulse delay-150" />
                </div>
             </div>
          </div>

          <div className="pt-12 border-t border-gray-100 flex flex-col md:flex-row items-center justify-between gap-8">
             <div className="flex gap-4">
                <button className="flex items-center gap-2 text-[10px] font-black text-gray-400 uppercase tracking-widest hover:text-gray-900 transition-colors">
                   <Printer size={16} />
                   Print Summary
                </button>
                <button className="flex items-center gap-2 text-[10px] font-black text-gray-400 uppercase tracking-widest hover:text-gray-900 transition-colors">
                   <Share2 size={16} />
                   Share Request
                </button>
             </div>
             <Link 
               href={`/catalog/${event_slug}`} 
               className="group flex items-center gap-3 px-10 py-5 bg-[#0d2e2e] text-white font-black uppercase tracking-widest text-xs rounded-2xl hover:bg-teal-600 transition-all shadow-2xl shadow-[#0d2e2e]/10 active:scale-95"
             >
               Return to Catalog
               <ArrowRight size={16} className="group-hover:translate-x-1 transition-transform" />
             </Link>
          </div>
        </div>
      </motion.div>

      {/* Feedback Section */}
      <motion.div 
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.5 }}
        className="max-w-3xl w-full bg-white rounded-[40px] border border-gray-100 p-12 text-center"
      >
        <AnimatePresence mode="wait">
          {!submitted ? (
            <motion.div 
              key="form"
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
            >
              <h3 className="text-2xl font-black text-[#0d2e2e] tracking-tight mb-2">How was your experience?</h3>
              <p className="text-gray-500 font-medium mb-8">Your feedback helps us improve the furniture catalog for future events.</p>
              
              <form onSubmit={handleFeedbackSubmit} className="space-y-8">
                <div className="flex justify-center gap-2">
                  {[1, 2, 3, 4, 5].map((star) => (
                    <button
                      key={star}
                      type="button"
                      onMouseEnter={() => setHoverRating(star)}
                      onMouseLeave={() => setHoverRating(0)}
                      onClick={() => setRating(star)}
                      className="p-2 transition-transform hover:scale-110 active:scale-90"
                    >
                      <Star 
                        size={40} 
                        fill={(hoverRating || rating) >= star ? '#14b8a6' : 'transparent'} 
                        className={(hoverRating || rating) >= star ? 'text-teal-500' : 'text-gray-200'} 
                        strokeWidth={2.5}
                      />
                    </button>
                  ))}
                </div>

                <div className="relative group">
                  <MessageSquare className="absolute left-6 top-6 text-gray-300 group-focus-within:text-teal-600 transition-colors" size={20} />
                  <textarea
                    value={comment}
                    onChange={(e) => setComment(e.target.value)}
                    placeholder="Anything we could do better? (Optional)"
                    rows={4}
                    className="w-full pl-16 pr-8 py-6 bg-gray-50 border border-gray-100 rounded-[32px] focus:outline-none focus:ring-4 focus:ring-teal-500/5 focus:border-teal-500 transition-all font-medium text-gray-700 leading-relaxed"
                  />
                </div>

                <button
                  type="submit"
                  disabled={rating === 0 || submitting}
                  className="px-12 py-4 bg-[#0d2e2e] text-white rounded-2xl font-black uppercase tracking-widest text-xs hover:bg-teal-600 transition-all disabled:opacity-30 flex items-center justify-center gap-3 mx-auto"
                >
                  {submitting ? <Loader2 size={18} className="animate-spin" /> : 'Submit Feedback'}
                </button>
              </form>
            </motion.div>
          ) : (
            <motion.div 
              key="success"
              initial={{ opacity: 0, scale: 0.9 }}
              animate={{ opacity: 1, scale: 1 }}
              className="py-8"
            >
              <div className="w-20 h-20 bg-teal-50 text-teal-600 rounded-full flex items-center justify-center mx-auto mb-6">
                <CheckCircle2 size={40} />
              </div>
              <h3 className="text-2xl font-black text-[#0d2e2e] tracking-tight mb-2">Thank you for your feedback!</h3>
              <p className="text-gray-500 font-medium">We appreciate your time and input.</p>
            </motion.div>
          )}
        </AnimatePresence>
      </motion.div>
    </div>
  );
}
