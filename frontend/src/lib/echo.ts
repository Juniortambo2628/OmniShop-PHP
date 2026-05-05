import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// We assign Pusher to window so Laravel Echo can pick it up.
if (typeof window !== 'undefined') {
  (window as any).Pusher = Pusher;
}

export const getEchoInstance = (token: string) => {
  const isHttps = (process.env.NEXT_PUBLIC_REVERB_SCHEME ?? 'http') === 'https';
  
  return new Echo({
    broadcaster: 'reverb',
    key: process.env.NEXT_PUBLIC_REVERB_APP_KEY || 'qvxseuoqmjz1x1au93su',
    wsHost: process.env.NEXT_PUBLIC_REVERB_HOST || window.location.hostname,
    wsPort: Number(process.env.NEXT_PUBLIC_REVERB_PORT) || 80,
    wssPort: Number(process.env.NEXT_PUBLIC_REVERB_PORT) || 443,
    forceTLS: isHttps,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: `${process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8008/api'}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    },
  });
};
