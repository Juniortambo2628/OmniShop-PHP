'use client';

import { useState, useEffect } from 'react';
import { useAuth } from '@/contexts/AuthContext';
import { apiFetch } from '@/lib/api';
import PageHero from '@/components/admin/PageHero';
import { User, Mail, Shield, Key, Save, AlertCircle, CheckCircle2 } from 'lucide-react';
import Skeleton from '@/components/ui/Skeleton';
import ContextToolbar from '@/components/admin/ContextToolbar';

export default function ProfilePage() {
  const { token, user } = useAuth();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    current_password: '',
    new_password: '',
    new_password_confirmation: '',
  });

  useEffect(() => {
    if (user) {
      setFormData(prev => ({
        ...prev,
        name: user.name || '',
        email: user.email || '',
      }));
      setLoading(false);
    }
  }, [user]);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setSaving(true);

    try {
      await apiFetch('/user/profile', {
        method: 'PUT',
        token: token || undefined,
        body: JSON.stringify(formData),
      });
      setSuccess('Profile updated successfully');
      setFormData(prev => ({
        ...prev,
        current_password: '',
        new_password: '',
        new_password_confirmation: '',
      }));
    } catch (err: any) {
      setError(err.message || 'Failed to update profile');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="p-6 space-y-6">
        <Skeleton className="h-40 w-full" />
        <Skeleton className="h-96 w-full" />
      </div>
    );
  }

  return (
    <>
      <PageHero 
        title="Admin Profile" 
        subtitle="Update your personal details and password."
        breadcrumbs={[{ label: 'Profile' }]}
      />

      <ContextToolbar>
          <div className="flex items-center gap-4 text-white flex-1 justify-between px-4">
            <div>
               <h3 className="text-xs font-black uppercase tracking-widest text-teal-400">Account Control</h3>
               <p className="text-[10px] text-white/60 font-bold uppercase tracking-widest">Manage your login details</p>
            </div>
            <button 
              onClick={handleSubmit}
              disabled={saving}
              className="px-8 py-3 bg-teal-500 text-white rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-teal-400 transition-all flex items-center gap-3 shadow-xl disabled:opacity-50"
            >
              {saving ? 'Updating...' : (
                <>
                  <Save size={16} />
                  Update Profile
                </>
              )}
            </button>
          </div>
      </ContextToolbar>

      <div className="p-6 max-w-4xl mx-auto w-full space-y-6">
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div className="h-32 bg-gradient-to-r from-teal-600 to-teal-800 relative">
            <div className="absolute -bottom-12 left-8 p-1 bg-white rounded-2xl shadow-lg">
              <div className="w-24 h-24 bg-teal-50 rounded-xl flex items-center justify-center text-teal-600">
                <User size={48} />
              </div>
            </div>
          </div>
          <div className="pt-16 pb-8 px-8">
            <h2 className="text-2xl font-bold text-gray-900">{user?.name}</h2>
            <p className="text-gray-500 text-sm">Administrator</p>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="md:col-span-2 space-y-6">
            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
              <h3 className="text-sm font-bold text-gray-900 uppercase tracking-wider mb-6 flex items-center gap-2">
                <Shield size={16} className="text-teal-600" />
                Personal Information
              </h3>
              
              <div className="space-y-4">
                <div>
                  <label className="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Full Name</label>
                  <div className="relative">
                    <User size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                    <input 
                      type="text" 
                      name="name"
                      value={formData.name}
                      onChange={handleChange}
                      className="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:border-teal-500 focus:outline-none"
                    />
                  </div>
                </div>
                <div>
                  <label className="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Email Address</label>
                  <div className="relative">
                    <Mail size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                    <input 
                      type="email" 
                      name="email"
                      value={formData.email}
                      onChange={handleChange}
                      className="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:border-teal-500 focus:outline-none"
                    />
                  </div>
                </div>
              </div>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
              <h3 className="text-sm font-bold text-gray-900 uppercase tracking-wider mb-6 flex items-center gap-2">
                <Key size={16} className="text-teal-600" />
                Security & Password
              </h3>
              
              <div className="space-y-4">
                <div>
                  <label className="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Current Password</label>
                  <input 
                    type="password" 
                    name="current_password"
                    value={formData.current_password}
                    onChange={handleChange}
                    placeholder="••••••••"
                    className="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:border-teal-500 focus:outline-none"
                  />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">New Password</label>
                    <input 
                      type="password" 
                      name="new_password"
                      value={formData.new_password}
                      onChange={handleChange}
                      placeholder="••••••••"
                      className="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:border-teal-500 focus:outline-none"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Confirm New Password</label>
                    <input 
                      type="password" 
                      name="new_password_confirmation"
                      value={formData.new_password_confirmation}
                      onChange={handleChange}
                      placeholder="••••••••"
                      className="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:border-teal-500 focus:outline-none"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div className="space-y-6">
            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
              <p className="text-xs text-gray-500 leading-relaxed italic mb-4">
                Use the floating action bar below to synchronize your profile changes with the central database.
              </p>
              
              {error && (
                <div className="mt-4 p-3 bg-red-50 text-red-600 rounded-lg text-xs flex items-center gap-2 border border-red-100 animate-in fade-in zoom-in-95 duration-200">
                  <AlertCircle size={14} />
                  {error}
                </div>
              )}
              {success && (
                <div className="mt-4 p-3 bg-teal-50 text-teal-600 rounded-lg text-xs flex items-center gap-2 border border-teal-100 animate-in fade-in zoom-in-95 duration-200">
                  <CheckCircle2 size={14} />
                  {success}
                </div>
              )}
            </div>
            
            <div className="bg-amber-50 rounded-xl border border-amber-100 p-5">
              <p className="text-xs text-amber-800 leading-relaxed font-medium">
                Changing your email or password will not log you out, but please ensure you remember your new credentials for the next session.
              </p>
            </div>
          </div>
        </form>
      </div>
    </>
  );
}
