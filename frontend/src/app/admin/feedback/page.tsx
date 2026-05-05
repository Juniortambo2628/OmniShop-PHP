'use client';

import { useState, useEffect } from 'react';
import { useAuth } from '@/contexts/AuthContext';
import { apiFetch } from '@/lib/api';
import PageHero from '@/components/admin/PageHero';
import { 
  MessageSquare, 
  Star, 
  Calendar,
  User,
  ShoppingBag,
  Loader2,
  Filter,
  ChevronLeft,
  ChevronRight,
  Trash2
} from 'lucide-react';
import { motion } from 'framer-motion';
import ContextToolbar from '@/components/admin/ContextToolbar';
import EmptyState from '@/components/ui/EmptyState';
import { useToast } from '@/components/ui/Toast';

export default function FeedbackAdminPage() {
  const { token } = useAuth();
  const { toast, confirm } = useToast();
  const [feedback, setFeedback] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [currentPage, setCurrentPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);

  const avgRating = feedback.length > 0 ? (feedback.reduce((sum, f) => sum + f.rating, 0) / feedback.length) : 0;

  useEffect(() => {
    if (!token) return;
    setLoading(true);
    apiFetch<any>(`/admin/feedback?page=${currentPage}`, { token: token || undefined })
      .then(data => {
        setFeedback(data.data || []);
        setTotal(data.total || 0);
        setLastPage(data.last_page || 1);
      })
      .finally(() => setLoading(false));
  }, [token, currentPage]);

  const handleDelete = async (id: number) => {
    if (!(await confirm('Delete this feedback entry?'))) return;
    try {
      await apiFetch(`/admin/feedback/${id}`, { method: 'DELETE', token: token || undefined });
      setFeedback(prev => prev.filter(f => f.id !== id));
      setTotal(prev => prev - 1);
      toast.success('Feedback deleted');
    } catch {
      toast.error('Failed to delete feedback');
    }
  };

  return (
    <>
      <PageHero 
        title="Customer Feedback" 
        subtitle="Customer reviews and feedback."
        breadcrumbs={[
          { label: 'Analytics', href: '/admin/analytics' },
          { label: 'Feedback' }
        ]} 
      />

      <ContextToolbar>
          <div className="flex items-center gap-4 text-white px-4">
            <div>
               <h3 className="text-xs font-black uppercase tracking-widest text-teal-400">Feedback Stats</h3>
               <p className="text-[10px] text-white/60 font-bold uppercase tracking-widest">{total} submissions</p>
            </div>
            <div className="w-px h-6 bg-white/10" />
            <div className="flex items-center gap-2">
               <Star size={14} className="text-yellow-400 fill-yellow-400" />
               <span className="text-xs font-black">{avgRating.toFixed(1)}</span>
               <span className="text-[10px] font-bold uppercase tracking-wider text-white/40">Avg Rating</span>
            </div>

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

      <div className="p-8 max-w-6xl mx-auto space-y-8">

        {loading ? (
          <div className="py-20 flex flex-col items-center gap-4">
             <Loader2 className="animate-spin text-teal-600" size={32} />
             <p className="text-[10px] font-black uppercase tracking-widest text-gray-400">Loading Feedback...</p>
          </div>
        ) : (
          <div className="grid grid-cols-1 gap-6">
            {feedback.length === 0 && (
              <EmptyState
                icon={MessageSquare}
                title="No feedback received yet"
                subtitle="Customer reviews will appear here once submitted"
              />
            )}
            {feedback.map((f, idx) => (
              <motion.div 
                key={f.id}
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: idx * 0.05 }}
                className="bg-white rounded-[32px] border border-gray-100 p-8 shadow-sm hover:shadow-xl transition-all"
              >
                <div className="flex flex-col md:flex-row gap-8 items-start">
                   <div className="shrink-0 space-y-4">
                      <div className="flex gap-1">
                         {[1, 2, 3, 4, 5].map(s => (
                           <Star 
                             key={s} 
                             size={16} 
                             fill={f.rating >= s ? '#14b8a6' : 'transparent'} 
                             className={f.rating >= s ? 'text-teal-500' : 'text-gray-200'} 
                           />
                         ))}
                      </div>
                      <div className="space-y-2">
                         <div className="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-gray-400">
                            <ShoppingBag size={12} />
                            <span>Order {f.order_id}</span>
                         </div>
                         <div className="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-gray-400">
                            <Calendar size={12} />
                            <span>{new Date(f.created_at).toLocaleDateString()}</span>
                         </div>
                      </div>
                   </div>

                   <div className="flex-1">
                      <div className="flex items-start gap-4">
                         <MessageSquare size={20} className="text-teal-600 shrink-0 mt-1" />
                         <div className="flex-1">
                            <p className="text-gray-700 font-medium leading-relaxed italic">
                               "{f.comment || 'No comment provided.'}"
                            </p>
                         </div>
                         <button
                           onClick={() => handleDelete(f.id)}
                           className="p-2 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all shrink-0"
                           title="Delete feedback"
                         >
                           <Trash2 size={16} />
                         </button>
                      </div>
                   </div>
                </div>
              </motion.div>
            ))}
          </div>
        )}
      </div>
    </>
  );
}
