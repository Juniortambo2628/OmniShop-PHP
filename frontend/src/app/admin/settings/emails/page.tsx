'use client';

import { useState, useEffect } from 'react';
import { useAuth } from '@/contexts/AuthContext';
import { apiFetch } from '@/lib/api';
import { useAutosave } from '@/hooks/useAutosave';
import { useToast } from '@/components/ui/Toast';
import PageHero from '@/components/admin/PageHero';
import Modal from '@/components/ui/Modal';
import { Mail, Save, AlertCircle, Eye, Variable, Info, Monitor, Send } from 'lucide-react';

interface SettingsData {
  settings: Record<string, string>;
}

export default function EmailTemplatesPage() {
  const { token } = useAuth();
  const { toast, confirm } = useToast();
  const [settings, setSettings] = useState<Record<string, string>>({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [activeTab, setActiveTab] = useState<'confirmation' | 'invoice'>('confirmation');
  const [success, setSuccess] = useState(false);
  const [isPreviewOpen, setIsPreviewOpen] = useState(false);
  const [sendingTest, setSendingTest] = useState(false);
  const [testSent, setTestSent] = useState(false);

  useEffect(() => {
    if (!token) return;
    apiFetch<SettingsData>('/settings', { token })
      .then(res => setSettings(res.settings))
      .finally(() => setLoading(false));
  }, [token]);

  const { isSaving: isAutosaving, lastSaved } = useAutosave({
    type: 'email_templates',
    token: token || undefined,
    data: settings,
    onLoad: async (draftData) => {
      if (await confirm('A draft was found for email templates. Restore it?')) {
        setSettings(draftData);
        toast.info('Draft restored');
      }
    }
  });

  const handleSave = async () => {
    if (!token) return;
    setSaving(true);
    setSuccess(false);
    try {
      await apiFetch('/settings', {
        method: 'PUT',
        token,
        body: JSON.stringify({ settings })
      });
      setSuccess(true);
      toast.success('Templates saved successfully');
      setTimeout(() => setSuccess(false), 3000);
    } catch (err) {
      toast.error('Failed to save settings');
    } finally {
      setSaving(false);
    }
  };

  const updateSetting = (key: string, value: string) => {
    setSettings(prev => ({ ...prev, [key]: value }));
  };

  const handleSendTest = async () => {
    if (!token) return;
    setSendingTest(true);
    setTestSent(false);
    try {
      // Save current templates first
      await apiFetch('/settings', {
        method: 'PUT',
        token,
        body: JSON.stringify({ settings })
      });
      // Then send test
      const res = await apiFetch<{ message: string }>('/admin/send-test-email', {
        method: 'POST',
        token,
        body: JSON.stringify({ type: activeTab })
      });
      setTestSent(true);
      toast.success('Test email sent successfully');
      setTimeout(() => setTestSent(false), 4000);
    } catch (err: any) {
      toast.error(err.message || 'Failed to send test email. Check your SMTP config in .env');
    } finally {
      setSendingTest(false);
    }
  };

  if (loading) return <div className="p-10 text-center">Loading...</div>;

  const templates = {
    confirmation: {
      title: 'Order Confirmation',
      desc: 'Sent to customers immediately after they place an order.',
      subjectKey: 'email_template_order_confirmation_subject',
      bodyKey: 'email_template_order_confirmation_body',
      placeholders: ['order_id', 'company_name', 'contact_name', 'total_amount', 'booth_number']
    },
    invoice: {
      title: 'Invoice Delivery',
      desc: 'Sent when you manually trigger an invoice email to the client.',
      subjectKey: 'email_template_invoice_subject',
      bodyKey: 'email_template_invoice_body',
      placeholders: ['order_id', 'company_name', 'contact_name', 'total_amount', 'invoice_link']
    }
  };

  const current = templates[activeTab];

  return (
    <>
      <PageHero title="Email Templates" breadcrumbs={[{ label: 'Settings', href: '/admin/settings' }, { label: 'Email Templates' }]} />

      <div className="p-6 max-w-5xl mx-auto space-y-8">
        {/* Tab Navigation */}
        <div className="flex gap-2 bg-gray-100 p-1.5 rounded-2xl w-max">
           <button 
             onClick={() => setActiveTab('confirmation')}
             className={`px-6 py-2.5 rounded-xl text-sm font-bold transition-all ${activeTab === 'confirmation' ? 'bg-white text-teal-600 shadow-sm' : 'text-gray-500 hover:text-gray-900'}`}
           >
             Order Confirmation
           </button>
           <button 
             onClick={() => setActiveTab('invoice')}
             className={`px-6 py-2.5 rounded-xl text-sm font-bold transition-all ${activeTab === 'invoice' ? 'bg-white text-teal-600 shadow-sm' : 'text-gray-500 hover:text-gray-900'}`}
           >
             Invoice Delivery
           </button>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
           {/* Editor Section */}
           <div className="lg:col-span-2 space-y-6">
              <div className="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                 <div className="p-8 border-b border-gray-50 bg-gray-50/50 flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-black text-gray-900">{current.title} Template</h2>
                        <p className="text-sm text-gray-500 mt-1">{current.desc}</p>
                    </div>
                    <div className="text-right">
                        {isAutosaving && <span className="text-[10px] text-teal-600 font-bold animate-pulse uppercase tracking-widest">Autosaving...</span>}
                        {lastSaved && !isAutosaving && (
                            <span className="text-[10px] text-gray-400 font-medium block">
                                Draft saved at {lastSaved.toLocaleTimeString()}
                            </span>
                        )}
                    </div>
                 </div>
                 
                 <div className="p-8 space-y-6">
                    <div>
                       <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Email Subject</label>
                       <input 
                         type="text"
                         value={settings[current.subjectKey] || ''}
                         onChange={(e) => updateSetting(current.subjectKey, e.target.value)}
                         placeholder="e.g. Order Confirmation - {{order_id}}"
                         className="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-2xl font-bold text-gray-900 focus:outline-none focus:border-teal-500 transition-all placeholder:text-gray-300"
                       />
                    </div>

                    <div>
                       <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Message Body (HTML supported)</label>
                       <textarea 
                         rows={12}
                         value={settings[current.bodyKey] || ''}
                         onChange={(e) => updateSetting(current.bodyKey, e.target.value)}
                         placeholder="Write your email body here..."
                         className="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-2xl font-medium text-gray-700 focus:outline-none focus:border-teal-500 transition-all placeholder:text-gray-300 font-mono text-sm leading-relaxed"
                       />
                    </div>
                 </div>
              </div>

              <div className="flex items-center justify-between bg-teal-50 p-6 rounded-3xl border border-teal-100">
                  <div className="flex items-center gap-4">
                     <div className="w-10 h-10 bg-teal-600 text-white rounded-xl flex items-center justify-center shadow-lg shadow-teal-600/20">
                        <Mail size={20} />
                     </div>
                     <div>
                        <p className="font-bold text-teal-900">Ready to update?</p>
                        <p className="text-xs text-teal-600">Changes will take effect immediately for all new emails.</p>
                     </div>
                  </div>
                  <div className="flex gap-3">
                    <button 
                      onClick={() => setIsPreviewOpen(true)}
                      className="flex items-center gap-2 px-6 py-3 bg-white border border-gray-200 text-gray-700 rounded-2xl font-black text-sm hover:bg-gray-50 transition-all shadow-sm"
                    >
                      <Eye size={18} /> PREVIEW
                    </button>
                    <button 
                      onClick={handleSave}
                      disabled={saving}
                      className={`flex-1 flex items-center justify-center gap-2 px-8 py-3 rounded-2xl font-black text-sm transition-all ${success ? 'bg-green-500 text-white shadow-lg' : 'bg-teal-600 text-white hover:bg-teal-700 shadow-xl shadow-teal-600/20'} disabled:opacity-50`}
                    >
                      {saving ? 'SAVING...' : success ? 'SAVED!' : <><Save size={18} /> PUBLISH TEMPLATE</>}
                    </button>
                  </div>
              </div>
           </div>

           {/* Sidebar Info */}
           <div className="space-y-6">
              <div className="bg-gray-900 text-white p-8 rounded-3xl shadow-xl space-y-6">
                 <div className="flex items-center gap-3">
                    <Variable size={24} className="text-teal-400" />
                    <h3 className="font-black uppercase tracking-tighter text-lg">Dynamic Variables</h3>
                 </div>
                 <p className="text-gray-400 text-sm leading-relaxed">
                    Use these tags in your subject or body to insert real data from the order.
                 </p>
                 <div className="space-y-2">
                    {current.placeholders.map(p => (
                       <div key={p} className="flex items-center justify-between p-3 bg-white/5 rounded-xl border border-white/10 group hover:border-teal-400/50 transition-all cursor-pointer" onClick={() => {
                          const textArea = document.querySelector('textarea');
                          if (textArea) {
                             const start = textArea.selectionStart;
                             const end = textArea.selectionEnd;
                             const text = textArea.value;
                             const newText = text.substring(0, start) + `{{${p}}}` + text.substring(end);
                             updateSetting(current.bodyKey, newText);
                          }
                       }}>
                          <code className="text-teal-400 font-black text-xs">{"{{"}{p}{"}}"}</code>
                          <span className="text-[10px] text-gray-500 font-bold uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-all">Click to add</span>
                       </div>
                    ))}
                 </div>
              </div>

              <div className="bg-white border border-gray-100 p-8 rounded-3xl shadow-sm space-y-4">
                 <div className="flex items-center gap-3 text-amber-500">
                    <Info size={20} />
                    <h4 className="font-black uppercase tracking-tighter">Pro Tip</h4>
                 </div>
                 <p className="text-xs text-gray-500 leading-relaxed">
                    You can use standard HTML tags like <b>&lt;b&gt;</b> for bold or <b>&lt;br&gt;</b> for new lines to style your emails.
                 </p>
                 <button 
                   onClick={handleSendTest}
                   disabled={sendingTest}
                   className={`w-full py-3 rounded-xl text-xs font-bold transition-all border border-gray-100 flex items-center justify-center gap-2 disabled:opacity-50 ${
                     testSent ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-50 text-gray-900 hover:bg-gray-100'
                   }`}
                 >
                    <Send size={16} />
                    {sendingTest ? 'SENDING...' : testSent ? 'TEST SENT!' : 'SEND TEST EMAIL'}
                 </button>
              </div>
           </div>
        </div>
      </div>

      <Modal 
        isOpen={isPreviewOpen} 
        onClose={() => setIsPreviewOpen(false)} 
        title="Email Template Preview"
        size="3xl"
      >
        <div className="bg-gray-200 p-4 sm:p-10 flex justify-center -m-6 rounded-b-3xl">
          <div className="w-full max-w-2xl bg-white shadow-2xl rounded-sm overflow-hidden">
            <div className="bg-gray-50 border-b border-gray-200 px-6 py-4">
              <p className="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-1">Subject</p>
              <p className="text-sm font-bold text-gray-900">
                {(settings[current.subjectKey] || '').replace(/\{\{(.*?)\}\}/g, '$1')}
              </p>
            </div>
            <div className="p-8">
              <div 
                className="prose prose-sm max-w-none text-gray-700 email-preview"
                dangerouslySetInnerHTML={{ 
                  __html: (settings[current.bodyKey] || '<i>No content yet...</i>')
                    .replace(/\{\{(.*?)\}\}/g, '<span style="color: #0d9488; font-weight: bold; background: #f0fdfa; padding: 2px 4px; border-radius: 4px;">$1</span>')
                    .replace(/\n/g, '<br/>')
                }} 
              />
              <div className="mt-12 pt-8 border-t border-gray-100 text-center">
                <div className="w-8 h-8 bg-teal-600 text-white rounded-lg flex items-center justify-center font-black text-xs mx-auto mb-3">OS</div>
                <p className="text-[10px] text-gray-400 font-bold uppercase tracking-widest">OmniShop Limited © 2026</p>
              </div>
            </div>
          </div>
        </div>
      </Modal>
    </>
  );
}
