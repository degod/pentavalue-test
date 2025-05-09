import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Make sure the Pusher instance is available globally
window.Pusher = Pusher;

// Initialize Echo with Pusher configuration from environment variables
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.PUSHER_APP_KEY,
    cluster: process.env.PUSHER_APP_CLUSTER,
    forceTLS: true,
    encryption: true
});

// window.Echo.channel('orders')
//     .listen('.order.created', (e) => {
//         console.log('New Order:', e.order);
//     });