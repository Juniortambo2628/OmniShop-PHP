'use client';

import { X, ShoppingBag, Check, Info, Box, Ruler, Palette } from 'lucide-react';
import Modal from '@/components/ui/Modal';
import { motion, AnimatePresence } from 'framer-motion';

interface ProductDetailsModalProps {
  product: any;
  isOpen: boolean;
  onClose: () => void;
  onAddToCart?: (product: any) => void;
  selectedColorId: string | null;
  onColorSelect: (colorId: string) => void;
  imgSrc: string;
}

export default function ProductDetailsModal({
  product,
  isOpen,
  onClose,
  onAddToCart,
  selectedColorId,
  onColorSelect,
  imgSrc
}: ProductDetailsModalProps) {
  if (!product) return null;

  const selectedColorName = product.colors?.find((c: any) => c.id === selectedColorId)?.name;

  return (
    <Modal isOpen={isOpen} onClose={onClose} title="" size="4xl">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-0 -m-6">
        {/* Left: Image Section */}
        <div className="bg-gray-50 relative min-h-[400px] flex items-center justify-center p-12 overflow-hidden border-r border-gray-100">
          <div className="absolute inset-0 bg-gradient-to-br from-teal-500/5 to-transparent" />
          
          <AnimatePresence mode="wait">
            <motion.div
              key={imgSrc}
              initial={{ opacity: 0, scale: 0.9, rotate: -2 }}
              animate={{ opacity: 1, scale: 1, rotate: 0 }}
              exit={{ opacity: 0, scale: 1.1, rotate: 2 }}
              transition={{ duration: 0.5 }}
              className="relative z-10 w-full h-full flex items-center justify-center"
            >
              {imgSrc.includes('placeholder') ? (
                <span className="text-[120px] opacity-10">🛋️</span>
              ) : (
                <img 
                  src={imgSrc} 
                  alt={product.name} 
                  className="w-full h-full object-contain mix-blend-multiply drop-shadow-2xl" 
                />
              )}
            </motion.div>
          </AnimatePresence>

          {product.is_poa && (
            <div className="absolute top-8 right-8 bg-[#0d2e2e] text-white text-[10px] font-black px-4 py-2 rounded-full uppercase tracking-widest shadow-2xl z-20">
              Price on Application
            </div>
          )}
        </div>

        {/* Right: Content Section */}
        <div className="p-10 flex flex-col bg-white">
          <div className="mb-10">
            <div className="flex items-center gap-3 mb-4">
              <span className="px-3 py-1 bg-teal-50 text-teal-700 text-[10px] font-black uppercase tracking-widest rounded-lg">
                Rental Collection
              </span>
              <span className="text-[10px] font-black text-gray-300 uppercase tracking-widest">
                ID: {product.code}
              </span>
            </div>
            <h2 className="text-4xl font-black text-[#0d2e2e] leading-[0.95] tracking-tighter mb-4">{product.name}</h2>
            <div className="flex items-center gap-2 text-emerald-500">
               <div className="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse" />
               <span className="text-xs font-black uppercase tracking-widest">Ready for immediate rental</span>
            </div>
          </div>

          <div className="space-y-10 flex-1">
            {product.dimensions && (
              <div className="group">
                <div className="flex items-center gap-2 mb-3">
                   <Ruler size={14} className="text-teal-600" />
                   <h4 className="text-[10px] font-black text-teal-600 uppercase tracking-[0.2em]">Key Dimensions</h4>
                </div>
                <div className="p-6 bg-teal-50/50 rounded-3xl border border-teal-100/50 group-hover:border-teal-300 transition-colors shadow-inner">
                  <p className="text-lg text-[#0d2e2e] font-black tracking-tight leading-none">
                    {product.dimensions}
                  </p>
                  <p className="text-[10px] text-teal-600/50 font-bold uppercase tracking-widest mt-2">Standard booth scaling applicable</p>
                </div>
              </div>
            )}

            {product.colors && product.colors.length > 0 && (
              <div>
                <div className="flex items-center justify-between mb-4">
                   <div className="flex items-center gap-2">
                      <Palette size={14} className="text-gray-400" />
                      <h4 className="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Selection</h4>
                   </div>
                   <span className="text-[10px] font-black text-teal-600 uppercase tracking-widest">
                      {selectedColorName || 'Choose Color'}
                   </span>
                </div>
                <div className="flex flex-wrap gap-4">
                  {product.colors.map((c: any) => (
                    <button
                      key={c.id}
                      onClick={() => onColorSelect(c.id)}
                      className="group relative flex flex-col items-center"
                    >
                      <div 
                        className={`w-12 h-12 rounded-2xl border-2 transition-all duration-300 flex items-center justify-center ${
                          selectedColorId === c.id 
                            ? 'border-teal-600 ring-8 ring-teal-500/5 scale-110 shadow-lg' 
                            : 'border-gray-100 hover:border-gray-300'
                        }`}
                        style={{ backgroundColor: c.name.toLowerCase() === 'white' ? '#f9fafb' : c.name.toLowerCase() }}
                      >
                        {selectedColorId === c.id && (
                           <motion.div initial={{ scale: 0 }} animate={{ scale: 1 }}>
                              <Check size={20} className={c.name.toLowerCase() === 'white' ? 'text-teal-600' : 'text-white'} />
                           </motion.div>
                        )}
                      </div>
                    </button>
                  ))}
                </div>
              </div>
            )}
          </div>

          <div className="mt-12 pt-8 border-t border-gray-100 flex items-center gap-6">
            <div className="flex flex-col">
              <span className="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1 text-left">Total Rate</span>
              <span className="text-4xl font-black text-[#0d2e2e] tracking-tighter">
                {product.is_poa ? 'POA' : `$${product.price.toFixed(2)}`}
              </span>
            </div>
            {onAddToCart && (
              <button
                onClick={() => onAddToCart(product)}
                className="flex-1 bg-[#0d2e2e] hover:bg-teal-600 text-white py-5 rounded-3xl font-black text-sm uppercase tracking-widest transition-all shadow-2xl shadow-[#0d2e2e]/20 hover:shadow-teal-600/30 flex items-center justify-center gap-3 active:scale-95"
              >
                <ShoppingBag size={20} />
                Add to Collection
              </button>
            )}
          </div>
        </div>
      </div>
    </Modal>
  );
}
