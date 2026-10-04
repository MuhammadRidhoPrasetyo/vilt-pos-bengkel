import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

function createEchoInstance() {
    const isHttps = typeof window !== 'undefined' && window.location.protocol === 'https:';
    const currentHost = typeof window !== 'undefined' ? window.location.hostname : 'localhost';
    
    // When on HTTPS (e.g. Cloudflare Tunnel), proxy via port 443 through Nginx
    // When on HTTP, check window port or fallback to VITE_REVERB_PORT
    const currentPort = typeof window !== 'undefined' && window.location.port
        ? Number(window.location.port)
        : (isHttps ? 443 : 80);

    const wsPort = isHttps ? 443 : currentPort;

    return new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: currentHost,
        wsPort: wsPort,
        wssPort: isHttps ? 443 : wsPort,
        forceTLS: isHttps,
        enabledTransports: ['ws', 'wss'],
    });
}

export const echo = createEchoInstance();
window.Echo = echo;

export default echo;
