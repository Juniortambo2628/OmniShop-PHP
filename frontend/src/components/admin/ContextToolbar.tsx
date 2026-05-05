'use client';

import { motion, AnimatePresence } from 'framer-motion';
import { Settings2 } from 'lucide-react';

interface ContextToolbarProps {
  children: React.ReactNode;
  visible?: boolean;
}

export default function ContextToolbar({ children, visible = true }: ContextToolbarProps) {
  return (
    <AnimatePresence>
      {visible && (
        <div className="fixed bottom-8 left-60 right-0 z-50 flex justify-center pointer-events-none">
          <motion.div
            initial={{ y: 100, opacity: 0 }}
            animate={{ y: 0, opacity: 1 }}
            exit={{ y: 100, opacity: 0 }}
            className="pointer-events-auto min-w-[300px] max-w-[90%]"
          >
            <div className="bg-[#0d2e2e]/90 backdrop-blur-xl border border-white/10 rounded-[24px] p-2 shadow-2xl flex items-center gap-2">
              <div className="w-10 h-10 bg-teal-500 rounded-2xl flex items-center justify-center shrink-0 shadow-lg shadow-teal-500/20">
                 <Settings2 size={20} className="text-white" />
              </div>
              
              <div className="flex items-center gap-1 flex-1 px-2">
                {children}
              </div>
            </div>
          </motion.div>
        </div>
      )}
    </AnimatePresence>
  );
}
