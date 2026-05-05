'use client';

import { useCart, CartItem } from '@/contexts/CartContext';
import Link from 'next/link';
import { X, ShoppingBag, Trash2, Plus, Minus, ArrowRight } from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';

export default function CartDrawer({ eventSlug }: { eventSlug: string }) {
  const { items, totalItems, subtotal, updateQuantity, removeFromCart } = useCart();

  const closeDrawer = () => {
    document.getElementById('cart-drawer')?.classList.add('translate-x-full');
  };

  return (
    <div 
      id="cart-drawer" 
      className="fixed inset-y-0 right-0 w-full md:w-[450px] bg-white shadow-[0_0_100px_rgba(0,0,0,0.2)] z-[60] transform translate-x-full transition-transform duration-500 flex flex-col"
    >
      <div className="px-8 py-8 border-b border-gray-100 flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 bg-gray-900 text-white rounded-xl flex items-center justify-center">
            <ShoppingBag size={20} />
          </div>
          <div>
             <h2 className="font-black text-xl text-gray-900 tracking-tighter">My Selection</h2>
             <p className="text-[10px] text-gray-400 font-black uppercase tracking-widest">{totalItems} items reserved</p>
          </div>
        </div>
        <button 
          onClick={closeDrawer}
          className="w-10 h-10 flex items-center justify-center hover:bg-gray-100 rounded-full transition-colors text-gray-400 hover:text-gray-900"
        >
          <X size={20} />
        </button>
      </div>

      <div className="flex-1 overflow-y-auto px-8 py-6">
        <AnimatePresence mode="popLayout">
          {items.length === 0 ? (
            <motion.div 
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              className="text-center py-20"
            >
              <div className="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                 <ShoppingBag size={32} className="text-gray-300" />
              </div>
              <p className="text-gray-900 font-black uppercase tracking-widest text-xs mb-2">Your cart is empty</p>
              <p className="text-sm text-gray-400">Start adding furniture to your booth collection.</p>
            </motion.div>
          ) : (
            <div className="space-y-8">
              {items.map(item => (
                <motion.div 
                  key={item.cart_id} 
                  layout
                  initial={{ opacity: 0, x: 20 }}
                  animate={{ opacity: 1, x: 0 }}
                  exit={{ opacity: 0, x: 50 }}
                  className="flex gap-6 group"
                >
                  <div className="w-24 h-24 bg-gray-50 rounded-[24px] border border-gray-100 flex items-center justify-center p-2 relative shrink-0 group-hover:border-teal-200 transition-colors overflow-hidden">
                    {item.image ? (
                      <img src={item.image} alt={item.name} className="w-full h-full object-contain mix-blend-multiply" />
                    ) : (
                      <span className="text-3xl opacity-20">🛋️</span>
                    )}
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex justify-between items-start gap-2 mb-1">
                      <h3 className="font-bold text-gray-900 text-base leading-tight truncate">{item.name}</h3>
                      <button 
                        onClick={() => removeFromCart(item.cart_id)}
                        className="p-2 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all"
                      >
                        <Trash2 size={16} />
                      </button>
                    </div>
                    <div className="flex items-center gap-3 mb-4">
                       <p className="text-[10px] font-black text-teal-600 uppercase tracking-widest">{item.product_id}</p>
                       {item.color && (
                          <div className="flex items-center gap-1.5 px-2 py-0.5 bg-gray-100 rounded-md">
                             <div className="w-2 h-2 rounded-full" style={{ backgroundColor: item.color.toLowerCase() === 'white' ? '#fff' : item.color.toLowerCase() }} />
                             <span className="text-[10px] font-bold text-gray-500 uppercase">{item.color}</span>
                          </div>
                       )}
                    </div>
                    
                    <div className="flex items-center justify-between">
                      <div className="flex items-center bg-gray-50 p-1 rounded-xl border border-gray-100">
                        <button 
                          onClick={() => updateQuantity(item.cart_id, item.quantity - 1)}
                          className="w-8 h-8 flex items-center justify-center hover:bg-white hover:shadow-sm rounded-lg transition-all text-gray-500"
                        ><Minus size={14} /></button>
                        <span className="w-10 text-center text-sm font-black text-gray-900">
                          {item.quantity}
                        </span>
                        <button 
                          onClick={() => updateQuantity(item.cart_id, item.quantity + 1)}
                          className="w-8 h-8 flex items-center justify-center hover:bg-white hover:shadow-sm rounded-lg transition-all text-gray-500"
                        ><Plus size={14} /></button>
                      </div>
                      <div className="text-right">
                        <p className="text-lg font-black text-gray-900">${(item.price * item.quantity).toFixed(2)}</p>
                      </div>
                    </div>
                  </div>
                </motion.div>
              ))}
            </div>
          )}
        </AnimatePresence>
      </div>

      {items.length > 0 && (
        <div className="p-8 border-t border-gray-100 bg-gray-50/50 backdrop-blur-md">
          <div className="flex items-center justify-between mb-8">
            <div>
               <p className="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-1">Estimated Total</p>
               <p className="text-3xl font-black text-[#0d2e2e] tracking-tighter">${subtotal.toFixed(2)}</p>
            </div>
            <div className="text-right">
               <p className="text-[10px] font-black text-teal-600 uppercase tracking-widest">Inclusive of taxes</p>
            </div>
          </div>
          <Link 
            href={`/catalog/${eventSlug}/checkout`}
            onClick={closeDrawer}
            className="group w-full h-16 bg-[#0d2e2e] hover:bg-teal-600 text-white font-black uppercase tracking-widest text-sm rounded-3xl flex items-center justify-center gap-3 transition-all duration-500 shadow-2xl shadow-[#0d2e2e]/20 hover:shadow-teal-600/30"
          >
            Review & Checkout
            <ArrowRight size={18} className="group-hover:translate-x-1 transition-transform" />
          </Link>
          <p className="mt-6 text-center text-[10px] text-gray-400 font-bold uppercase tracking-widest">
             Secure Checkout powered by OmniShop
          </p>
        </div>
      )}
    </div>
  );
}
