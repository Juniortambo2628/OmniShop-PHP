'use client';

import { Trash2, CheckCircle, XCircle, ChevronDown } from 'lucide-react';
import { useState } from 'react';

interface BulkAction {
  label: string;
  icon: any;
  onClick: () => void;
  variant?: 'danger' | 'success' | 'default';
}

interface BulkActionToolbarProps {
  selectedCount: number;
  actions: BulkAction[];
  onClear: () => void;
}

export default function BulkActionToolbar({ selectedCount, actions, onClear }: BulkActionToolbarProps) {
  if (selectedCount === 0) return null;

  return (
    <div className="fixed bottom-8 left-1/2 -translate-x-1/2 z-50 animate-in fade-in slide-in-from-bottom-4 duration-300 max-w-[95vw] w-max">
      <div className="bg-gray-900 text-white rounded-2xl shadow-2xl px-4 py-3 flex items-center gap-4 border border-gray-800 overflow-hidden">
        <div className="flex items-center gap-3 border-r border-gray-700 pr-4 flex-shrink-0">
          <div className="w-6 h-6 bg-teal-500 text-white rounded-full flex items-center justify-center text-xs font-bold">
            {selectedCount}
          </div>
          <span className="text-sm font-medium">Items Selected</span>
          <button 
            onClick={onClear}
            className="text-[10px] uppercase tracking-wider font-bold text-gray-400 hover:text-white transition-colors ml-2"
          >
            Clear
          </button>
        </div>

        <div className="flex-1 flex items-center gap-2 flex-nowrap overflow-x-auto no-scrollbar py-1">
          {actions.map((action, i) => {
            const Icon = action.icon;
            return (
              <button
                key={i}
                onClick={action.onClick}
                className={`flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all whitespace-nowrap ${
                  action.variant === 'danger' 
                    ? 'hover:bg-red-500/10 text-red-400' 
                    : action.variant === 'success'
                    ? 'hover:bg-teal-500/10 text-teal-400'
                    : 'hover:bg-white/10 text-gray-300 hover:text-white'
                }`}
              >
                <Icon size={18} />
                {action.label}
              </button>
            );
          })}
        </div>
      </div>
    </div>
  );
}
