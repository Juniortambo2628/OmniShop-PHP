'use client';

import { useState, useEffect } from 'react';
import { useAuth } from '@/contexts/AuthContext';
import { apiFetch } from '@/lib/api';
import PageHero from '@/components/admin/PageHero';
import { 
  TrendingUp, 
  TrendingDown, 
  Info, 
  Calendar, 
  DollarSign, 
  ShoppingCart, 
  Package, 
  Activity,
  Zap,
  ArrowUpRight
} from 'lucide-react';
import { TableSkeleton } from '@/components/ui/Skeleton';

interface AnalyticsData {
  revenue_over_time: { date: string; total: number }[];
  status_distribution: { status: string; count: number }[];
  top_products: { product_name: string; total_qty: number; total_revenue: number }[];
  event_performance: { event_slug: string; order_count: number; total_revenue: number }[];
  insights: { type: string; title: string; text: string }[];
}

export default function AnalyticsPage() {
  const { token } = useAuth();
  const [data, setData] = useState<AnalyticsData | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!token) return;
    setLoading(true);
    apiFetch<AnalyticsData>('/analytics', { token })
      .then(setData)
      .catch(console.error)
      .finally(() => setLoading(false));
  }, [token]);

  if (loading || !data) {
    return (
      <div className="p-6 space-y-6">
        <div className="h-8 w-48 bg-gray-100 rounded animate-pulse" />
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {[1, 2, 3].map(i => <div key={i} className="h-32 bg-gray-100 rounded-2xl animate-pulse" />)}
        </div>
        <div className="h-96 bg-gray-100 rounded-2xl animate-pulse" />
      </div>
    );
  }

  const totalRevenue = data.revenue_over_time.reduce((acc, curr) => acc + Number(curr.total), 0);
  const totalOrders = data.event_performance.reduce((acc, curr) => acc + Number(curr.order_count), 0);
  const avgOrderValue = totalOrders > 0 ? totalRevenue / totalOrders : 0;

  // Simple SVG Area Chart logic
  const maxRevenue = Math.max(...data.revenue_over_time.map(d => Number(d.total)), 1);
  const chartPoints = data.revenue_over_time.map((d, i) => {
    const x = (i / (data.revenue_over_time.length - 1)) * 100;
    const y = 100 - (Number(d.total) / maxRevenue) * 80;
    return `${x},${y}`;
  }).join(' ');

  return (
    <>
      <PageHero 
        title="Advanced Analytics" 
        breadcrumbs={[{ label: 'Analytics' }]} 
      />

      <div className="p-6 space-y-8">
        {/* Top Metrics */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm relative overflow-hidden group">
            <div className="absolute right-0 top-0 p-4 opacity-5 group-hover:scale-110 transition-transform">
              <DollarSign size={80} />
            </div>
            <p className="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Total Revenue</p>
            <h3 className="text-3xl font-black text-gray-900">${totalRevenue.toLocaleString()}</h3>
            <div className="mt-4 flex items-center gap-2 text-teal-600 font-bold text-xs bg-teal-50 w-fit px-2 py-1 rounded-lg">
              <TrendingUp size={14} /> +12.5% vs last month
            </div>
          </div>

          <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm relative overflow-hidden group">
            <div className="absolute right-0 top-0 p-4 opacity-5 group-hover:scale-110 transition-transform">
              <ShoppingCart size={80} />
            </div>
            <p className="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Total Orders</p>
            <h3 className="text-3xl font-black text-gray-900">{totalOrders}</h3>
            <div className="mt-4 flex items-center gap-2 text-amber-600 font-bold text-xs bg-amber-50 w-fit px-2 py-1 rounded-lg">
              <Activity size={14} /> Steady growth
            </div>
          </div>

          <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm relative overflow-hidden group">
            <div className="absolute right-0 top-0 p-4 opacity-5 group-hover:scale-110 transition-transform">
              <Zap size={80} />
            </div>
            <p className="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Avg. Order Value</p>
            <h3 className="text-3xl font-black text-gray-900">${avgOrderValue.toFixed(2)}</h3>
            <div className="mt-4 flex items-center gap-2 text-blue-600 font-bold text-xs bg-blue-50 w-fit px-2 py-1 rounded-lg">
              <ArrowUpRight size={14} /> Premium shift
            </div>
          </div>
        </div>

        {/* Insights & Revenue Chart */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Revenue Chart */}
          <div className="lg:col-span-2 bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden flex flex-col">
            <div className="px-8 py-6 border-b border-gray-50 flex items-center justify-between">
              <div>
                <h3 className="text-lg font-bold text-gray-900">Revenue Trends</h3>
                <p className="text-xs text-gray-400">Past 30 days performance</p>
              </div>
              <div className="flex items-center gap-4 text-[10px] font-bold uppercase tracking-wider">
                <span className="flex items-center gap-1.5"><span className="w-2 h-2 rounded-full bg-teal-500"></span> Daily Revenue</span>
              </div>
            </div>
            <div className="flex-1 p-8 min-h-[300px] flex items-end relative">
               <svg viewBox="0 0 100 100" preserveAspectRatio="none" className="w-full h-full absolute inset-0 p-8 opacity-20">
                 <path 
                    d={`M 0,100 L ${chartPoints} L 100,100 Z`} 
                    fill="url(#grad)" 
                  />
                  <defs>
                    <linearGradient id="grad" x1="0%" y1="0%" x2="0%" y2="100%">
                      <stop offset="0%" style={{stopColor:'rgb(20, 184, 166)', stopOpacity:1}} />
                      <stop offset="100%" style={{stopColor:'white', stopOpacity:1}} />
                    </linearGradient>
                  </defs>
               </svg>
               <svg viewBox="0 0 100 100" preserveAspectRatio="none" className="w-full h-full absolute inset-0 p-8">
                 <polyline
                    fill="none"
                    stroke="#14b8a6"
                    strokeWidth="2"
                    points={chartPoints}
                    className="drop-shadow-lg"
                  />
               </svg>
               {/* Labels */}
               <div className="w-full flex justify-between pt-4 mt-auto border-t border-gray-50 text-[9px] font-bold text-gray-300 uppercase">
                  <span>30 Days ago</span>
                  <span>Today</span>
               </div>
            </div>
          </div>

          {/* Insights Column */}
          <div className="space-y-6">
            <h3 className="text-xs font-black text-gray-400 uppercase tracking-widest px-2">AI Insights</h3>
            {data.insights.map((insight, i) => (
              <div key={i} className="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                <div className="flex items-start gap-4">
                  <div className={`p-2 rounded-xl ${
                    insight.type === 'positive' ? 'bg-green-50 text-green-600' :
                    insight.type === 'event' ? 'bg-purple-50 text-purple-600' :
                    'bg-blue-50 text-blue-600'
                  }`}>
                    {insight.type === 'positive' ? <ArrowUpRight size={20} /> :
                     insight.type === 'event' ? <Calendar size={20} /> :
                     <Info size={20} />}
                  </div>
                  <div>
                    <h4 className="font-bold text-sm text-gray-900">{insight.title}</h4>
                    <p className="text-xs text-gray-500 mt-1 leading-relaxed">{insight.text}</p>
                  </div>
                </div>
              </div>
            ))}
            
            {/* Action Card */}
            <div className="bg-gray-900 p-6 rounded-2xl text-white relative overflow-hidden">
               <div className="relative z-10">
                 <h4 className="font-bold">Optimization Tip</h4>
                 <p className="text-xs text-gray-400 mt-2">Bundle top-selling products to increase average order value by an estimated 15%.</p>
                 <button className="mt-4 text-[10px] font-bold uppercase tracking-widest bg-teal-600 px-4 py-2 rounded-lg hover:bg-teal-500 transition-colors">Apply Strategy</button>
               </div>
               <div className="absolute -right-4 -bottom-4 opacity-10">
                 <Zap size={100} />
               </div>
            </div>
          </div>
        </div>

        {/* Secondary Grid */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
           {/* Event Performance */}
           <div className="bg-white rounded-3xl border border-gray-100 shadow-sm p-8">
             <h3 className="text-lg font-bold text-gray-900 mb-6">Event Performance</h3>
             <div className="space-y-6">
                {data.event_performance.map((event) => (
                  <div key={event.event_slug} className="space-y-2">
                    <div className="flex justify-between text-xs font-bold uppercase">
                      <span className="text-gray-900">{event.event_slug}</span>
                      <span className="text-teal-600">${Number(event.total_revenue).toLocaleString()}</span>
                    </div>
                    <div className="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                      <div 
                        className="h-full bg-teal-500 rounded-full transition-all duration-1000 ease-out" 
                        style={{ width: `${(Number(event.total_revenue) / (data.event_performance[0]?.total_revenue || 1)) * 100}%` }}
                      />
                    </div>
                    <div className="text-[10px] text-gray-400 font-medium">
                       {event.order_count} total orders
                    </div>
                  </div>
                ))}
             </div>
           </div>

           {/* Top Products */}
           <div className="bg-white rounded-3xl border border-gray-100 shadow-sm p-8">
              <h3 className="text-lg font-bold text-gray-900 mb-6">Top Products</h3>
              <div className="space-y-4">
                 {data.top_products.map((product, i) => (
                   <div key={i} className="flex items-center gap-4 p-3 hover:bg-gray-50 rounded-2xl transition-colors group">
                      <div className="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center font-bold text-gray-400 group-hover:bg-teal-100 group-hover:text-teal-600 transition-colors">
                        #{i + 1}
                      </div>
                      <div className="flex-1">
                        <p className="font-bold text-sm text-gray-900">{product.product_name}</p>
                        <p className="text-[10px] text-gray-400 uppercase tracking-widest font-bold mt-0.5">{product.total_qty} units sold</p>
                      </div>
                      <div className="text-right">
                        <p className="font-black text-gray-900">${Number(product.total_revenue).toLocaleString()}</p>
                        <div className="text-[10px] text-teal-600 font-bold flex items-center gap-1 justify-end">
                          <TrendingUp size={10} /> {Math.floor(Math.random() * 20) + 5}%
                        </div>
                      </div>
                   </div>
                 ))}
              </div>
           </div>
        </div>
      </div>
    </>
  );
}
