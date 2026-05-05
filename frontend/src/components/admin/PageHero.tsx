import Link from 'next/link';
import { motion } from 'framer-motion';
import { TrendingUp, ChevronRight } from 'lucide-react';

interface Breadcrumb {
  label: string;
  href?: string;
}

interface PageHeroProps {
  title: string;
  subtitle?: string;
  breadcrumbs?: Breadcrumb[];
  children?: React.ReactNode;
  icon?: React.ReactNode;
}

export default function PageHero({ 
  title, 
  subtitle, 
  breadcrumbs = [], 
  children,
  icon = <TrendingUp size={240} />
}: PageHeroProps) {
  const defaultBreadcrumbs: Breadcrumb[] = [
    { label: 'Admin', href: '/admin' },
    ...breadcrumbs,
  ];

  return (
    <div className="sticky top-0 z-40 bg-gray-50/80 backdrop-blur-md px-6 py-4">
      <div className="bg-teal-600 rounded-[32px] p-8 text-white flex flex-col md:flex-row items-center justify-between gap-6 shadow-2xl shadow-teal-900/20 border border-teal-500 overflow-hidden relative group">
        <div className="relative z-10 flex-1">
          {/* Breadcrumbs inside the card for a more integrated feel */}
          <nav className="inline-flex items-center gap-1.5 px-4 py-1.5 bg-white/5 backdrop-blur-sm rounded-full border border-white/10 text-[10px] font-black uppercase tracking-[0.2em] mb-4">
            {defaultBreadcrumbs.map((crumb, i) => (
              <span key={i} className="flex items-center gap-1.5">
                {i > 0 && <ChevronRight size={10} className="opacity-40" />}
                {crumb.href ? (
                  <Link href={crumb.href} className="text-white/60 hover:text-white transition-colors">
                    {crumb.label}
                  </Link>
                ) : (
                  <span className="text-white">{crumb.label}</span>
                )}
              </span>
            ))}
          </nav>

          <div className="flex items-center gap-2 mb-2">
            <div className="w-2 h-2 bg-white rounded-full animate-pulse" />
            <span className="text-[10px] font-black uppercase tracking-[0.3em] opacity-80">Admin Dashboard</span>
          </div>
          <h1 className="text-4xl font-black leading-none tracking-tighter mb-2">{title}</h1>
          {subtitle && (
            <p className="text-sm text-teal-100 max-w-xl opacity-90 font-medium">{subtitle}</p>
          )}
        </div>

        {children && (
          <div className="relative z-10 flex items-center gap-3">
            {children}
          </div>
        )}

        <div className="absolute -right-10 -bottom-10 opacity-10 group-hover:scale-110 transition-transform duration-700 pointer-events-none">
          {icon}
        </div>
      </div>
    </div>
  );
}
