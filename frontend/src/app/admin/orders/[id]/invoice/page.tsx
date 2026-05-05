'use client';

import { useState, useEffect } from 'react';
import { useParams } from 'next/navigation';
import { useAuth } from '@/contexts/AuthContext';
import { apiFetch } from '@/lib/api';
import { Printer, Download, Mail, ArrowLeft } from 'lucide-react';
import Link from 'next/link';

interface InvoiceItem {
  id: number;
  product_code: string;
  product_name: string;
  unit_price: number | string;
  quantity: number;
  total_price: number | string;
  color_name: string | null;
}

interface InvoiceOrder {
  order_id: string;
  company_name: string;
  contact_name: string;
  email: string;
  phone: string;
  booth_number: string;
  event_slug: string;
  total: number | string;
  created_at: string;
  items: InvoiceItem[];
  notes?: string;
}

export default function InvoicePage() {
  const { id } = useParams();
  const { token } = useAuth();
  const [order, setOrder] = useState<InvoiceOrder | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!token) return;
    setLoading(true);
    // Reusing show endpoint as it has all needed data
    apiFetch<{ order: InvoiceOrder }>(`/orders/${id}`, { token })
      .then(res => setOrder(res.order))
      .finally(() => setLoading(false));
  }, [token, id]);

  const handlePrint = () => {
    window.print();
  };

  if (loading || !order) {
    return (
      <div className="p-10 text-center">
        <div className="w-10 h-10 border-4 border-teal-600 border-t-transparent rounded-full animate-spin mx-auto mb-4" />
        <p className="text-gray-500 font-bold uppercase tracking-widest text-xs">Generating Invoice Preview...</p>
      </div>
    );
  }

  const subtotal = Number(order.total);
  const vat = 0; // Configurable later
  const total = subtotal + vat;

  return (
    <div className="min-h-screen bg-gray-50 pb-20 print:bg-white print:pb-0">
      {/* Action Bar - Hidden on print */}
      <div className="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between sticky top-0 z-50 shadow-sm print:hidden">
        <div className="flex items-center gap-4">
          <Link href={`/admin/orders/${id}`} className="p-2 hover:bg-gray-100 rounded-lg transition-colors text-gray-500">
            <ArrowLeft size={20} />
          </Link>
          <h1 className="font-bold text-gray-900">Invoice Preview - {order.order_id}</h1>
        </div>
        <div className="flex items-center gap-3">
          <button 
            onClick={handlePrint}
            className="flex items-center gap-2 px-4 py-2 bg-gray-900 text-white rounded-lg font-bold text-sm hover:bg-black transition-all shadow-lg"
          >
            <Printer size={18} /> Print Invoice
          </button>
          <button className="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg font-bold text-sm hover:bg-gray-50 transition-all">
            <Download size={18} /> Download PDF
          </button>
          <button className="flex items-center gap-2 px-4 py-2 bg-teal-600 text-white rounded-lg font-bold text-sm hover:bg-teal-700 transition-all">
            <Mail size={18} /> Email to Client
          </button>
        </div>
      </div>

      {/* Invoice Container */}
      <div className="max-w-[800px] mx-auto mt-10 bg-white shadow-2xl rounded-3xl overflow-hidden print:shadow-none print:mt-0 print:rounded-none">
        {/* Header Decor */}
        <div className="h-4 bg-teal-600 print:hidden" />
        
        <div className="p-12 sm:p-16 space-y-12">
          {/* Header */}
          <div className="flex flex-col sm:flex-row justify-between items-start gap-8">
            <div className="space-y-4">
              <div className="w-16 h-16 bg-teal-600 text-white rounded-2xl flex items-center justify-center font-black text-3xl shadow-xl shadow-teal-600/20">
                OS
              </div>
              <div>
                <h2 className="text-2xl font-black text-gray-900 uppercase tracking-tighter">OmniShop Limited</h2>
                <p className="text-gray-500 text-sm mt-1 leading-relaxed">
                  Design & Furniture Solutions<br />
                  123 Business Park, Nairobi, Kenya<br />
                  info@omnishop.co.ke | +254 700 000 000
                </p>
              </div>
            </div>
            <div className="text-right">
              <h1 className="text-6xl font-black text-gray-100 uppercase tracking-tighter mb-4 leading-none select-none">INVOICE</h1>
              <div className="space-y-1">
                <p className="text-[10px] font-black text-gray-400 uppercase tracking-widest">Invoice Number</p>
                <p className="text-xl font-bold text-gray-900">{order.order_id}</p>
              </div>
              <div className="mt-4 space-y-1">
                <p className="text-[10px] font-black text-gray-400 uppercase tracking-widest">Date Issued</p>
                <p className="text-sm font-bold text-gray-900">{new Date(order.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'long', year: 'numeric' })}</p>
              </div>
            </div>
          </div>

          <div className="grid grid-cols-2 gap-12 border-y border-gray-100 py-10">
            <div>
              <p className="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Bill To</p>
              <h3 className="text-xl font-black text-gray-900">{order.company_name}</h3>
              <div className="text-gray-500 text-sm mt-2 space-y-1">
                <p className="font-bold text-gray-700">{order.contact_name}</p>
                <p>{order.email}</p>
                <p>{order.phone}</p>
              </div>
            </div>
            <div>
              <p className="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Event Details</p>
              <h3 className="text-lg font-bold text-gray-900 uppercase tracking-tight">{order.event_slug}</h3>
              <div className="mt-4 flex items-center gap-3">
                <div className="p-3 bg-gray-50 rounded-xl border border-gray-100">
                  <p className="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-0.5 text-center">Booth</p>
                  <p className="text-xl font-black text-teal-600 text-center">{order.booth_number}</p>
                </div>
                <div className="flex-1 p-3 bg-gray-50 rounded-xl border border-gray-100">
                   <p className="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-0.5">Payment Status</p>
                   <p className="text-xs font-bold text-amber-600 uppercase">Payment Pending</p>
                </div>
              </div>
            </div>
          </div>

          {/* Table */}
          <div className="space-y-4">
             <table className="w-full">
               <thead>
                 <tr className="border-b-2 border-gray-900 text-left">
                   <th className="pb-4 text-[10px] font-black text-gray-900 uppercase tracking-widest">Item Description</th>
                   <th className="pb-4 text-center text-[10px] font-black text-gray-900 uppercase tracking-widest w-20">Qty</th>
                   <th className="pb-4 text-right text-[10px] font-black text-gray-900 uppercase tracking-widest w-32">Unit Price</th>
                   <th className="pb-4 text-right text-[10px] font-black text-gray-900 uppercase tracking-widest w-32">Total</th>
                 </tr>
               </thead>
               <tbody className="divide-y divide-gray-100">
                 {order.items.map((item, i) => (
                   <tr key={i}>
                     <td className="py-6">
                       <p className="font-bold text-gray-900">{item.product_name}</p>
                       <div className="flex items-center gap-2 mt-1">
                          <span className="text-[10px] font-mono text-gray-400">{item.product_code}</span>
                          {item.color_name && <span className="text-[9px] font-bold text-teal-600 bg-teal-50 px-1.5 py-0.5 rounded-md uppercase tracking-wider">{item.color_name}</span>}
                       </div>
                     </td>
                     <td className="py-6 text-center font-bold text-gray-900">{item.quantity}</td>
                     <td className="py-6 text-right text-gray-500 font-medium">${Number(item.unit_price).toFixed(2)}</td>
                     <td className="py-6 text-right font-black text-gray-900">${Number(item.total_price).toFixed(2)}</td>
                   </tr>
                 ))}
               </tbody>
             </table>
          </div>

          {/* Footer & Totals */}
          <div className="flex flex-col sm:flex-row justify-between gap-12 pt-8 border-t-2 border-gray-900">
            <div className="flex-1 space-y-4">
               <div className="bg-gray-50 p-6 rounded-2xl border border-gray-100">
                 <h4 className="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Terms & Conditions</h4>
                 <p className="text-[10px] text-gray-500 leading-relaxed italic">
                   1. Please make payments within 7 days of receiving this invoice.<br />
                   2. Mention the Invoice Number as the payment reference.<br />
                   3. Goods once delivered are not returnable.
                 </p>
               </div>
               {order.notes && (
                 <div>
                    <h4 className="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Additional Notes</h4>
                    <p className="text-xs text-gray-600 italic">"{order.notes}"</p>
                 </div>
               )}
            </div>
            <div className="w-full sm:w-72 space-y-4">
               <div className="flex justify-between items-center px-4">
                 <span className="text-[10px] font-black text-gray-400 uppercase tracking-widest">Subtotal</span>
                 <span className="font-bold text-gray-900">${subtotal.toFixed(2)}</span>
               </div>
               <div className="flex justify-between items-center px-4">
                 <span className="text-[10px] font-black text-gray-400 uppercase tracking-widest">VAT (0%)</span>
                 <span className="font-bold text-gray-900">$0.00</span>
               </div>
               <div className="bg-teal-600 p-6 rounded-2xl text-white shadow-xl shadow-teal-600/20 flex justify-between items-center">
                 <span className="text-xs font-black uppercase tracking-widest opacity-80">Total Amount</span>
                 <span className="text-3xl font-black">${total.toFixed(2)}</span>
               </div>
            </div>
          </div>

          <div className="text-center pt-20 border-t border-gray-50">
             <p className="text-[10px] font-black text-gray-300 uppercase tracking-[0.3em]">Thank you for your business</p>
          </div>
        </div>
      </div>
      
      {/* Print Footer Helper */}
      <div className="mt-8 text-center text-xs text-gray-400 print:hidden">
        Powered by OmniShop Limited © 2026
      </div>
    </div>
  );
}
