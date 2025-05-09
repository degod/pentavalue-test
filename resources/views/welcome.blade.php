<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Real-Time Orders</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: sans-serif;
            padding: 20px;
        }

        ul {
            padding: 0;
            list-style: none;
        }

        li {
            background: #eef;
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>

<body>

    <h2>New Orders</h2>
    <ul id="orders-list"></ul>

    <!-- Load Pusher first -->
    <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.11.3/dist/echo.iife.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            try {
                window.Echo = new Echo({
                    broadcaster: 'pusher',
                    key: "{{ config('broadcasting.connections.pusher.key') }}",
                    cluster: "{{ config('broadcasting.connections.pusher.options.cluster') }}",
                    forceTLS: true
                });

                // Listen for events
                window.Echo.channel('orders')
                    .listen('.order.created', (e) => {
                        const order = e.order;

                        const li = document.createElement('li');
                        li.textContent = `Order #${order.id}: ${order.product_name || order.product_id} (Qty: ${order.quantity})`;

                        document.getElementById('orders-list').prepend(li);
                    });
            } catch (error) {
                console.error('Echo initialization error:', error);
            }
        });
    </script>

</body>

</html>