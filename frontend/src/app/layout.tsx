import type { Metadata } from 'next';
import { Inter } from 'next/font/google';
import './globals.css';
import { AuthProvider } from '@/contexts/AuthContext';
import { ToastProvider } from '@/components/ui/Toast';
import SupportChat from '@/components/catalog/SupportChat';

const inter = Inter({ subsets: ['latin'] });

export const metadata: Metadata = {
  title: 'OmniShop — Admin',
  description: 'OmniSpace 3D Events furniture rental management platform.',
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body className={`${inter.className} antialiased`}>
        <AuthProvider>
          <ToastProvider>
            <SupportChat />
            {children}
          </ToastProvider>
        </AuthProvider>
      </body>
    </html>
  );
}
