'use client';

export default function RootPage() {
  if (typeof window !== 'undefined') {
    window.location.href = '/admin';
  }
  return null;
}
