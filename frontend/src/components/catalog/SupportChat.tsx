'use client';

import { useEffect, useState } from 'react';
import { apiFetch } from '@/lib/api';

export default function SupportChat() {
  const [script, setScript] = useState('');

  useEffect(() => {
    apiFetch<any[]>('/storefront/settings')
      .then(settings => {
        const chatScript = settings.find(s => s.key === 'chat_widget_script')?.value;
        if (chatScript) {
          setScript(chatScript);
          
          // If it's a script tag string, we need to inject it
          const div = document.createElement('div');
          div.innerHTML = chatScript;
          const scripts = div.getElementsByTagName('script');
          
          for (let i = 0; i < scripts.length; i++) {
            const s = document.createElement('script');
            if (scripts[i].src) {
              s.src = scripts[i].src;
            } else {
              s.innerHTML = scripts[i].innerHTML;
            }
            s.async = true;
            document.body.appendChild(s);
          }
        }
      })
      .catch(() => {});
  }, []);

  return null;
}
