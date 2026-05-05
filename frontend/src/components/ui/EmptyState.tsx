'use client';

import { LucideIcon } from 'lucide-react';

interface EmptyStateProps {
  icon: LucideIcon;
  title: string;
  subtitle?: string;
  action?: React.ReactNode;
}

export default function EmptyState({ icon: Icon, title, subtitle, action }: EmptyStateProps) {
  return (
    <div className="py-20 text-center bg-white rounded-[40px] border border-gray-100 border-dashed">
      <Icon size={48} className="text-gray-100 mx-auto mb-4" />
      <h3 className="text-lg font-black text-gray-900 mb-1">{title}</h3>
      {subtitle && (
        <p className="text-xs text-gray-400 font-bold uppercase tracking-widest">{subtitle}</p>
      )}
      {action && <div className="mt-6">{action}</div>}
    </div>
  );
}
