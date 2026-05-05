'use client';

import { useState, useEffect } from 'react';
import { useAuth } from '@/contexts/AuthContext';
import { apiFetch } from '@/lib/api';
import { useToast } from '@/components/ui/Toast';
import PageHero from '@/components/admin/PageHero';
import Link from 'next/link';
import { 
  Settings, 
  Globe, 
  Mail, 
  FileText, 
  Save, 
  Loader2, 
  Lock, 
  Layout, 
  Palette,
  CheckCircle2,
  Calendar,
  Layers
} from 'lucide-react';
import { TableSkeleton } from '@/components/ui/Skeleton';
import ContextToolbar from '@/components/admin/ContextToolbar';

interface SettingsData {
  settings: Record<string, string>;
  events: Record<string, any>;
  categories: any[];
}

export default function SettingsPage() {
  const { token } = useAuth();
  const { toast } = useToast();
  const [data, setData] = useState<SettingsData | null>(null);
  const [formData, setFormData] = useState<Record<string, string>>({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [activeTab, setActiveTab] = useState<'general' | 'events' | 'appearance' | 'emails'>('general');
  const [success, setSuccess] = useState(false);

  useEffect(() => {
    if (!token) return;
    apiFetch<SettingsData>('/settings', { token })
      .then((res) => {
        setData(res);
        setFormData(res.settings);
      })
      .finally(() => setLoading(false));
  }, [token]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!token) return;
    setSaving(true);
    setSuccess(false);
    try {
      await apiFetch('/settings', {
        method: 'PUT',
        token,
        body: JSON.stringify({ settings: formData })
      });
      setSuccess(true);
      toast.success('Settings saved successfully');
      setTimeout(() => setSuccess(false), 3000);
    } catch (err) {
      toast.error('Failed to save settings');
    } finally {
      setSaving(false);
    }
  };

  const handleChange = (key: string, value: string) => {
    setFormData(prev => ({ ...prev, [key]: value }));
  };

  if (loading) return <div className="p-6"><TableSkeleton rows={8} /></div>;

  return (
    <>
      <PageHero 
        title="System Settings" 
        subtitle="Manage store rules, storefront content, and email templates."
        breadcrumbs={[{ label: 'Settings' }]} 
      />

      <ContextToolbar>
          <div className="flex items-center gap-4 text-white flex-1 justify-between px-4">
            <div>
               <h3 className="text-xs font-black uppercase tracking-widest text-teal-400">Settings Menu</h3>
               <p className="text-[10px] text-white/60 font-bold uppercase tracking-widest">Currently Editing: {activeTab.toUpperCase()}</p>
            </div>
            <button 
              onClick={handleSubmit}
              disabled={saving}
              className="px-8 py-3 bg-teal-500 text-white rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-teal-400 transition-all flex items-center gap-3 shadow-xl disabled:opacity-50"
            >
              {saving ? 'Saving...' : (
                <>
                  <Save size={16} />
                  Save Changes
                </>
              )}
            </button>
          </div>
      </ContextToolbar>

      <div className="p-6 max-w-6xl mx-auto w-full">
        <div className="flex flex-col lg:flex-row gap-8">
          
          {/* Sidebar Tabs */}
          <div className="lg:w-64 flex-shrink-0">
            <nav className="space-y-1">
              {[
                { id: 'general', label: 'General', icon: Settings },
                { id: 'events', label: 'Storefront Access', icon: Lock },
                { id: 'appearance', label: 'Appearance', icon: Palette },
                { id: 'emails', label: 'Email Templates', icon: Mail },
              ].map((tab) => (
                <button
                  key={tab.id}
                  onClick={() => setActiveTab(tab.id as any)}
                  className={`w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all ${
                    activeTab === tab.id 
                      ? 'bg-teal-600 text-white shadow-lg shadow-teal-600/20' 
                      : 'text-gray-500 hover:bg-gray-100'
                  }`}
                >
                  <tab.icon size={18} />
                  {tab.label}
                </button>
              ))}
            </nav>

            <div className="mt-8 p-4 bg-teal-50 rounded-2xl border border-teal-100">
              <h4 className="text-[10px] font-bold text-teal-700 uppercase tracking-widest mb-2 flex items-center gap-1.5">
                <CheckCircle2 size={12} /> Auto-Sync
              </h4>
              <p className="text-[11px] text-teal-600 leading-relaxed">
                Changes saved here are applied immediately to all active storefronts.
              </p>
            </div>
          </div>

          {/* Main Form */}
          <div className="flex-1">
            <form onSubmit={handleSubmit} className="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
              <div className="p-8">
                
                {activeTab === 'general' && (
                  <div className="space-y-8 animate-in fade-in slide-in-from-bottom-2 duration-300">
                    <div className="flex items-center gap-4 mb-2">
                       <div className="w-12 h-12 bg-teal-50 rounded-2xl flex items-center justify-center text-teal-600">
                          <Settings size={24} />
                       </div>
                       <div>
                          <h2 className="text-xl font-bold text-gray-900">General Settings</h2>
                          <p className="text-sm text-gray-500">Global configuration for the OmniShop engine.</p>
                       </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <div className="space-y-2">
                        <label className="text-[10px] font-bold text-gray-400 uppercase tracking-widest flex items-center gap-1.5">
                          <Globe size={12} /> Default Active Event
                        </label>
                        <select 
                          value={formData['default_event'] || ''}
                          onChange={(e) => handleChange('default_event', e.target.value)}
                          className="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-2xl text-sm focus:outline-none focus:border-teal-500 focus:ring-4 focus:ring-teal-500/5 transition-all"
                        >
                          <option value="">-- Select Event --</option>
                          {data?.events && Object.entries(data.events).map(([slug, ev]: [string, any]) => (
                            <option key={slug} value={slug}>{ev.short_name || slug}</option>
                          ))}
                        </select>
                      </div>

                      <div className="space-y-2">
                        <label className="text-[10px] font-bold text-gray-400 uppercase tracking-widest flex items-center gap-1.5">
                          <Mail size={12} /> Notification Email
                        </label>
                        <input 
                          type="email" 
                          value={formData['contact_email'] || ''}
                          onChange={(e) => handleChange('contact_email', e.target.value)}
                          className="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-2xl text-sm focus:outline-none focus:border-teal-500 focus:ring-4 focus:ring-teal-500/5 transition-all"
                          placeholder="sales@omnispace3d.com"
                        />
                      </div>

                      <div className="md:col-span-2 space-y-2">
                        <label className="text-[10px] font-bold text-gray-400 uppercase tracking-widest flex items-center gap-1.5">
                          <FileText size={12} /> Invoice Footer Text
                        </label>
                        <textarea 
                          value={formData['invoice_footer'] || ''}
                          onChange={(e) => handleChange('invoice_footer', e.target.value)}
                          rows={4}
                          className="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-2xl text-sm focus:outline-none focus:border-teal-500 focus:ring-4 focus:ring-teal-500/5 transition-all resize-none"
                          placeholder="Thank you for your business! Please contact us for any assistance."
                        />
                      </div>
                    </div>
                  </div>
                )}

                {activeTab === 'events' && (
                  <div className="space-y-8 animate-in fade-in slide-in-from-bottom-2 duration-300">
                    <div className="flex items-center gap-4 mb-2">
                       <div className="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-600">
                          <Lock size={24} />
                       </div>
                       <div>
                          <h2 className="text-xl font-bold text-gray-900">Event Security</h2>
                          <p className="text-sm text-gray-500">Manage login credentials for individual show storefronts.</p>
                       </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4">
                      {data?.events && Object.entries(data.events).map(([slug, ev]: [string, any]) => (
                        <div key={slug} className="flex items-center justify-between p-5 bg-gray-50 rounded-2xl border border-gray-100 hover:border-teal-200 transition-all group">
                          <div className="flex items-center gap-4">
                            <div className="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-gray-400 group-hover:text-teal-600 transition-colors">
                              <Calendar size={20} />
                            </div>
                            <div>
                              <h4 className="font-bold text-gray-900">{ev.short_name || slug}</h4>
                              <p className="text-[10px] text-gray-400 uppercase font-bold tracking-widest">Storefront URL: /catalog/{slug}</p>
                            </div>
                          </div>
                          <div className="w-48">
                            <input 
                              type="text"
                              placeholder="Access Password"
                              value={formData[`catalog_password_${slug}`] || ''}
                              onChange={(e) => handleChange(`catalog_password_${slug}`, e.target.value)}
                              className="w-full px-3 py-2 bg-white border border-gray-200 rounded-xl text-sm font-mono focus:border-teal-500 focus:outline-none"
                            />
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {activeTab === 'appearance' && (
                  <div className="space-y-8 animate-in fade-in slide-in-from-bottom-2 duration-300">
                    <div className="flex items-center gap-4 mb-2">
                       <div className="w-12 h-12 bg-purple-50 rounded-2xl flex items-center justify-center text-purple-600">
                          <Palette size={24} />
                       </div>
                       <div>
                          <h2 className="text-xl font-bold text-gray-900">Branding & UI</h2>
                          <p className="text-sm text-gray-500">Customize the look and feel of your storefronts.</p>
                       </div>
                    </div>

                    <div className="space-y-6">
                       <div className="p-6 bg-gray-50 rounded-3xl border border-dashed border-gray-200 flex flex-col items-center justify-center text-center">
                          <div className="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center text-gray-300 mb-4">
                             <Layout size={32} />
                          </div>
                          <h4 className="font-bold text-gray-900 mb-1">Company Logo</h4>
                          <p className="text-xs text-gray-500 mb-4">Upload a high-resolution logo for invoices and headers.</p>
                          <button type="button" className="px-4 py-2 bg-white border border-gray-200 rounded-xl text-xs font-bold hover:border-teal-500 transition-all shadow-sm">
                             Select File
                          </button>
                       </div>

                       <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                          <div className="space-y-2">
                            <label className="text-[10px] font-bold text-gray-400 uppercase tracking-widest flex items-center gap-1.5">
                              <Palette size={12} /> Primary Theme Color
                            </label>
                            <div className="flex items-center gap-3">
                               <input 
                                  type="color" 
                                  value={formData['theme_primary'] || '#0d9488'}
                                  onChange={(e) => handleChange('theme_primary', e.target.value)}
                                  className="w-12 h-12 rounded-xl cursor-pointer border-0 p-0 overflow-hidden"
                               />
                               <input 
                                  type="text" 
                                  value={formData['theme_primary'] || '#0d9488'}
                                  onChange={(e) => handleChange('theme_primary', e.target.value)}
                                  className="flex-1 px-4 py-3 bg-gray-50 border border-gray-100 rounded-2xl text-sm font-mono focus:border-teal-500 focus:outline-none"
                               />
                            </div>
                          </div>
                          
                          <div className="space-y-2">
                             <label className="text-[10px] font-bold text-gray-400 uppercase tracking-widest flex items-center gap-1.5">
                              <Layers size={12} /> Sidebar Style
                            </label>
                            <select 
                              value={formData['sidebar_style'] || 'dark'}
                              onChange={(e) => handleChange('sidebar_style', e.target.value)}
                              className="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-2xl text-sm focus:border-teal-500 focus:outline-none"
                            >
                               <option value="dark">Glassmorphism Dark</option>
                               <option value="light">Clean Light</option>
                               <option value="teal">Omni Teal</option>
                            </select>
                          </div>
                       </div>
                    </div>
                  </div>
                )}

                {activeTab === 'emails' && (
                  <div className="space-y-8 animate-in fade-in slide-in-from-bottom-2 duration-300">
                    <div className="flex items-center gap-4 mb-2">
                       <div className="w-12 h-12 bg-teal-50 rounded-2xl flex items-center justify-center text-teal-600">
                          <Mail size={24} />
                       </div>
                       <div>
                          <h2 className="text-xl font-bold text-gray-900">Email System</h2>
                          <p className="text-sm text-gray-500">Configure how and when emails are sent to your customers.</p>
                       </div>
                    </div>

                    <div className="bg-teal-600 p-8 rounded-3xl text-white shadow-xl shadow-teal-600/20 flex items-center justify-between gap-8">
                       <div className="space-y-4 max-w-md">
                          <h3 className="text-2xl font-black uppercase tracking-tighter">Email Designer</h3>
                          <p className="text-teal-50 opacity-90 leading-relaxed">
                            Craft beautiful, professional email templates with dynamic placeholders for order data.
                          </p>
                          <Link href="/admin/settings/emails" className="inline-flex items-center gap-2 px-6 py-3 bg-white text-teal-700 rounded-xl font-bold text-sm hover:bg-teal-50 transition-all shadow-lg">
                            <Mail size={18} /> OPEN EMAIL EDITOR
                          </Link>
                       </div>
                       <div className="hidden md:block w-32 h-32 bg-white/10 rounded-full flex items-center justify-center border border-white/20">
                          <Mail size={64} className="opacity-50" />
                       </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                       <div className="p-6 bg-gray-50 rounded-2xl border border-gray-100">
                          <h4 className="font-bold text-gray-900 mb-2">Order Notifications</h4>
                          <p className="text-xs text-gray-500 mb-4">Send a confirmation email automatically to customers when they checkout.</p>
                          <div className="flex items-center gap-2">
                             <input type="checkbox" checked={formData['enable_order_emails'] === '1'} onChange={(e) => handleChange('enable_order_emails', e.target.checked ? '1' : '0')} className="rounded border-gray-300 text-teal-600 focus:ring-teal-500" />
                             <span className="text-xs font-bold text-gray-700">Enable Automated Emails</span>
                          </div>
                       </div>
                       <div className="p-6 bg-gray-50 rounded-2xl border border-gray-100">
                          <h4 className="font-bold text-gray-900 mb-2">Invoice PDFs</h4>
                          <p className="text-xs text-gray-500 mb-4">Attach the invoice PDF automatically to the confirmation email.</p>
                          <div className="flex items-center gap-2 text-gray-400">
                             <CheckCircle2 size={16} />
                             <span className="text-xs font-bold italic">Requires PDF Service</span>
                          </div>
                       </div>
                    </div>
                  </div>
                )}

              </div>
              
              <div className="px-8 py-6 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                <div className="flex items-center gap-2">
                   {success && (
                     <div className="flex items-center gap-2 text-teal-600 font-bold text-sm animate-in fade-in zoom-in-95">
                        <CheckCircle2 size={18} />
                        Changes Saved!
                     </div>
                   )}
                </div>
                <button 
                  type="submit" 
                  disabled={saving}
                  className="px-8 py-4 text-sm font-bold text-white bg-gray-900 rounded-2xl hover:bg-teal-600 transition-all shadow-xl shadow-gray-900/10 hover:shadow-teal-600/20 flex items-center gap-3 disabled:opacity-50"
                >
                  {saving ? <Loader2 size={20} className="animate-spin" /> : <Save size={20} />}
                  {saving ? 'Syncing...' : 'Apply Changes'}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </>
  );
}
