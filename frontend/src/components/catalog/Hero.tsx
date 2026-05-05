'use client';

import { motion } from 'framer-motion';

interface HeroProps {
  event: {
    slug: string;
    short_name: string;
    start_date?: string;
    location?: string;
  };
  customTitle?: string;
  customSubtitle?: string;
}

export default function Hero({ event, customTitle, customSubtitle }: HeroProps) {
  return (
    <div className="relative h-[60vh] min-h-[400px] bg-[#0d2e2e] overflow-hidden flex items-center">
      {/* Background Decor */}
      <div className="absolute inset-0 z-0">
        <div className="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-teal-600/20 to-transparent" />
        <div className="absolute bottom-0 left-0 w-full h-1/2 bg-gradient-to-t from-[#0d2e2e] to-transparent" />
        {/* Animated Orbs */}
        <motion.div 
          animate={{ 
            scale: [1, 1.2, 1],
            opacity: [0.3, 0.5, 0.3],
            x: [0, 100, 0],
            y: [0, -50, 0]
          }}
          transition={{ duration: 15, repeat: Infinity, ease: "linear" }}
          className="absolute -top-20 -right-20 w-96 h-96 bg-teal-500/20 rounded-full blur-[100px]" 
        />
        <motion.div 
          animate={{ 
            scale: [1, 1.5, 1],
            opacity: [0.1, 0.2, 0.1],
            x: [0, -50, 0],
            y: [0, 100, 0]
          }}
          transition={{ duration: 20, repeat: Infinity, ease: "linear" }}
          className="absolute -bottom-40 -left-20 w-[500px] h-[500px] bg-teal-600/10 rounded-full blur-[120px]" 
        />
      </div>

      <div className="max-w-7xl mx-auto px-4 w-full relative z-10">
        <div className="max-w-2xl">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8 }}
          >
            <span className="inline-block px-4 py-1.5 bg-teal-600/20 border border-teal-500/30 text-teal-400 rounded-full text-[10px] font-black uppercase tracking-[0.2em] mb-6">
              {customTitle || 'Official Furniture Catalog'}
            </span>
            <h1 className="text-5xl md:text-7xl font-black text-white leading-[0.9] tracking-tighter mb-6">
              Elevate Your <br />
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-teal-400 to-emerald-400">
                Booth Experience
              </span>
            </h1>
            <p className="text-xl text-gray-400 font-medium leading-relaxed mb-10 max-w-lg">
              {customSubtitle || `Premium rental furniture for ${event.short_name || event.slug}. Designed for impact, crafted for comfort.`}
            </p>

            <div className="flex flex-wrap gap-4">
              <button 
                onClick={() => window.scrollTo({ top: 500, behavior: 'smooth' })}
                className="px-10 py-4 bg-white text-gray-900 rounded-2xl font-black text-sm hover:bg-teal-400 hover:text-gray-900 transition-all shadow-xl shadow-white/5"
              >
                BROWSE COLLECTION
              </button>
              <div className="flex flex-col justify-center border-l border-white/10 pl-6">
                <p className="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-1">Location</p>
                <p className="text-sm text-white font-bold">{event.location || 'Exhibition Hall'}</p>
              </div>
            </div>
          </motion.div>
        </div>
      </div>

      {/* Scroll Indicator */}
      <motion.div 
        animate={{ y: [0, 10, 0] }}
        transition={{ duration: 2, repeat: Infinity }}
        className="absolute bottom-10 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2"
      >
        <span className="text-[10px] text-gray-500 font-black uppercase tracking-[0.3em]">Scroll</span>
        <div className="w-[1px] h-12 bg-gradient-to-b from-teal-500 to-transparent" />
      </motion.div>
    </div>
  );
}
