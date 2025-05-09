<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Real-Time Orders & Analytics</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: sans-serif;
            padding: 20px;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
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

        canvas {
            border: 1px solid #ccc;
            margin-top: 30px;
        }

        .stats {
            margin-top: 20px;
        }

        .stats div {
            margin-bottom: 5px;
        }

        #recommendations-link {
            display: none;
        }

        .recommendations {
            margin-top: 30px;
            padding: 15px;
            background-color: #f5f8ff;
            border-left: 4px solid #4287f5;
            border-radius: 3px;
        }

        .recommendations h3 {
            margin-top: 0;
            color: #2c3e50;
        }

        .recommendations-content {
            white-space: pre-line;
            line-height: 1.5;
        }

        .ai-badge {
            background: #4287f5;
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 12px;
            margin-left: 10px;
            vertical-align: middle;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 20px;
            margin-top: 20px;
        }

        .weather-widget {
            background: linear-gradient(135deg, #71b7e6, #9b59b6);
            border-radius: 8px;
            padding: 15px;
            color: white;
            text-align: center;
            margin-bottom: 20px;
        }

        .weather-icon {
            font-size: 40px;
            margin: 10px 0;
        }

        .weather-temp {
            font-size: 28px;
            font-weight: bold;
        }

        .weather-condition {
            font-size: 16px;
            margin: 5px 0;
        }

        .weather-details {
            display: flex;
            justify-content: space-around;
            margin-top: 15px;
            font-size: 14px;
        }

        .weather-details div {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .section h2 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
    </style>
</head>

<body>
    <h1>Real-Time Orders & Analytics Dashboard</h1>

    <div class="dashboard-grid">
        <div class="main-content">
            <div class="section">
                <h2>Orders per Product</h2>
                <canvas id="orderChart" width="800" height="400"></canvas>
            </div>
            <div class="recommendations">
                <h2>
                    Product Recommendations <span class="ai-badge">AI Powered</span>
                    <a href="{{ url('/recommendations') }}" id="recommendations-link">
                        <button style="background-color:red;border-radius:3px;border:none;padding:5px 12px;color:white;position:relative;float:right;cursor:pointer;margin-top:-25px;">See More...</button>
                    </a>
                </h2>
                <div id="recommendations-content" class="recommendations-content">Loading recommendations...</div>
            </div>
        </div>

        <div class="sidebar">
            <div class="weather-widget">
                <h3>Current Weather</h3>
                <div class="weather-icon" id="weather-icon">🌤️</div>
                <div class="weather-temp" id="weather-temp">--°C</div>
                <div class="weather-condition" id="weather-condition">Loading...</div>
                <div class="weather-details">
                    <div>
                        <div>Humidity</div>
                        <div id="weather-humidity">--%</div>
                    </div>
                    <div>
                        <div>Wind</div>
                        <div id="weather-wind">-- km/h</div>
                    </div>
                </div>
            </div>

            <div class="section stats">
                <h2>Analytics Summary</h2>
                <div>Total Revenue: $<span id="total-revenue">0</span></div>
                <div>Orders in Last Minute: <span id="orders-last-minute">0</span></div>
                <div>Revenue in Last Minute: $<span id="revenue-last-minute">0</span></div>
            </div>
        </div>
    </div>

    <!-- Pusher + Echo -->
    <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.11.3/dist/echo.iife.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            const canvas = document.getElementById('orderChart');
            const ctx = canvas.getContext('2d');
            const recommendationsContent = document.getElementById('recommendations-content');

            const weatherIcon = document.getElementById('weather-icon');
            const weatherTemp = document.getElementById('weather-temp');
            const weatherCondition = document.getElementById('weather-condition');
            const weatherHumidity = document.getElementById('weather-humidity');
            const weatherWind = document.getElementById('weather-wind');

            const orderData = {};
            let totalRevenue = 0;
            let ordersLastMinute = 0;
            let revenueLastMinute = 0;

            function drawChart(data) {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                const productArray = Object.entries(data).map(([name, quantity]) => ({
                    name,
                    quantity
                }));
                productArray.sort((a, b) => b.quantity - a.quantity);

                const values = productArray.map(item => item.quantity);
                const maxVal = Math.max(...values, 1);
                const padding = 50;
                const barWidth = 40;
                const gap = 20;
                const scale = (canvas.height - 2 * padding) / maxVal;
                const yAxisSteps = 5;
                const stepValue = maxVal / yAxisSteps;

                ctx.strokeStyle = "#ddd";
                ctx.fillStyle = "#666";
                ctx.font = '10px sans-serif';

                for (let i = 0; i <= yAxisSteps; i++) {
                    const y = canvas.height - padding - (i * stepValue * scale);
                    const value = Math.round(i * stepValue);

                    ctx.beginPath();
                    ctx.moveTo(padding - 5, y);
                    ctx.lineTo(canvas.width - padding, y);
                    ctx.stroke();

                    ctx.fillText(value, padding - 30, y + 4);
                }

                productArray.forEach((product, index) => {
                    const barHeight = product.quantity * scale;
                    const x = padding + index * (barWidth + gap);
                    const y = canvas.height - padding - barHeight;

                    ctx.fillStyle = '#4287f5';
                    ctx.fillRect(x, y, barWidth, barHeight);
                    ctx.fillStyle = '#000';
                    ctx.font = '12px sans-serif';
                    ctx.save();
                    ctx.translate(x + barWidth / 2, canvas.height - padding + 15);
                    ctx.rotate(-Math.PI / 4);
                    ctx.textAlign = 'right';
                    ctx.fillText(product.name, 0, 0);
                    ctx.restore();
                    ctx.textAlign = 'center';
                    ctx.fillText(product.quantity, x + barWidth / 2, y - 5);
                });

                ctx.strokeStyle = "#000";
                ctx.beginPath();
                ctx.moveTo(padding, padding);
                ctx.lineTo(padding, canvas.height - padding);
                ctx.lineTo(canvas.width - padding, canvas.height - padding);
                ctx.stroke();
            }

            function truncateHtml(html, wordLimit = 50) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                let wordCount = 0;
                let lastValidNode = null;
                let lastValidParent = null;

                // Recursive function to traverse nodes
                function traverseNodes(node, limit) {
                    if (wordCount >= limit) return false;

                    if (node.nodeType === Node.TEXT_NODE) {
                        const words = node.textContent.trim().split(/\s+/).filter(w => w.length > 0);
                        wordCount += words.length;
                        if (wordCount <= limit) {
                            lastValidNode = node;
                            lastValidParent = node.parentNode;
                            return true;
                        } else {
                            // Truncate text node to fit within limit
                            const excess = wordCount - limit;
                            const keepWords = words.slice(0, words.length - excess);
                            node.textContent = keepWords.join(' ') + (keepWords.length > 0 ? '...' : '');
                            return false;
                        }
                    } else if (node.nodeType === Node.ELEMENT_NODE) {
                        for (let i = 0; i < node.childNodes.length; i++) {
                            const child = node.childNodes[i];
                            if (!traverseNodes(child, limit)) {
                                // Remove remaining siblings
                                while (i + 1 < node.childNodes.length) {
                                    node.removeChild(node.childNodes[i + 1]);
                                }
                                return false;
                            }
                        }
                        lastValidNode = node;
                        lastValidParent = node.parentNode;
                        return true;
                    }
                    return true;
                }

                traverseNodes(doc.body, wordLimit);

                // Ensure ellipsis is added if truncated
                if (wordCount > wordLimit && lastValidParent) {
                    const ellipsis = doc.createTextNode('...');
                    lastValidParent.appendChild(ellipsis);
                }

                return doc.body.innerHTML;
            }

            function updateStats() {
                document.getElementById('total-revenue').textContent = totalRevenue.toFixed(2);
                document.getElementById('orders-last-minute').textContent = ordersLastMinute;
                document.getElementById('revenue-last-minute').textContent = revenueLastMinute.toFixed(2);
            }

            function updateWeatherWidget(weatherData) {
                if (!weatherData) return;

                weatherTemp.textContent = `${Math.round(weatherData.temperature)}°C`;
                weatherCondition.textContent = weatherData.description;
                weatherHumidity.textContent = `${weatherData.humidity}%`;
                weatherWind.textContent = `${weatherData.wind_speed} m/s`;

                let icon = '🌤️';
                const condition = weatherData.condition?.toLowerCase();
                if (condition.includes('clear')) {
                    icon = '☀️';
                } else if (condition.includes('cloud')) {
                    icon = '☁️';
                } else if (condition.includes('rain') || condition.includes('drizzle')) {
                    icon = '🌧️';
                } else if (condition.includes('thunderstorm')) {
                    icon = '⛈️';
                } else if (condition.includes('snow')) {
                    icon = '❄️';
                } else if (condition.includes('mist') || condition.includes('fog')) {
                    icon = '🌫️';
                }

                weatherIcon.textContent = icon;
                const weatherWidget = document.querySelector('.weather-widget');
                if (weatherData.temperature > 25) {
                    weatherWidget.style.background = 'linear-gradient(135deg, #ff9d6c, #ff701f)';
                } else if (weatherData.temperature < 10) {
                    weatherWidget.style.background = 'linear-gradient(135deg, #71b7e6, #4b6cb7)';
                } else {
                    weatherWidget.style.background = 'linear-gradient(135deg, #71b7e6, #9b59b6)';
                }
            }

            try {
                const res = await fetch('/api/analytics');
                const data = await res.json();

                totalRevenue = data.total_revenue || 0;
                ordersLastMinute = data.orders_last_minute || 0;
                revenueLastMinute = data.revenue_last_minute || 0;

                if (data.recommendations) {
                    recommendationsContent.innerHTML = truncateHtml(data.recommendations, 60);
                    document.getElementById('recommendations-link').style.display = 'block';
                }
                (data.top_products || []).forEach(item => {
                    const label = item.product_name || `Product ${item.product_id}`;
                    orderData[label] = item.total_quantity || item.total_orders;
                });

                if (data.weather) {
                    updateWeatherWidget(data.weather);
                }

                updateStats();
                drawChart(orderData);
            } catch (err) {
                document.getElementById('recommendations-link').style.display = 'block';
                console.error('Failed to load analytics:', err);
                recommendationsContent.innerHTML = 'Unable to load recommendations.';
            }

            try {
                window.Echo = new Echo({
                    broadcaster: 'pusher',
                    key: "{{ config('broadcasting.connections.pusher.key') }}",
                    cluster: "{{ config('broadcasting.connections.pusher.options.cluster') }}",
                    forceTLS: true
                });

                window.Echo.channel('orders')
                    .listen('.order.created', (e) => {
                        const order = e.order;
                        const productName = order.product_name || `Product ${order.product_id}`;
                        orderData[productName] = (orderData[productName] || 0) + (order.quantity || 1);

                        const orderPrice = parseFloat(order.price || 0);
                        const orderQuantity = parseInt(order.quantity || 1);
                        const orderTotal = orderPrice * orderQuantity;

                        totalRevenue += orderTotal;
                        revenueLastMinute += orderTotal;
                        ordersLastMinute += 1;

                        setTimeout(() => {
                            revenueLastMinute -= orderTotal;
                            ordersLastMinute -= 1;
                            updateStats();
                        }, 60000);

                        drawChart(orderData);
                        updateStats();

                        if (e.weather) {
                            setTimeout(async () => {
                                try {
                                    const res = await fetch('/api/analytics');
                                    const data = await res.json();
                                    if (data.weather) {
                                        updateWeatherWidget(data.weather);
                                    }
                                    if (data.recommendations) {
                                        recommendationsContent.innerHTML = data.recommendations;
                                    }
                                } catch (err) {
                                    console.error('Failed to refresh weather:', err);
                                }
                            }, 1000);
                        }
                    });

                window.Echo.channel('recommendations')
                    .listen('.recommendations.generated', (e) => {
                        console.log('Recommendations received:', e.recommendations);
                        if (e.recommendations) {
                            recommendationsContent.innerHTML = e.recommendations;
                        } else {
                            recommendationsContent.innerHTML = 'No recommendations available.';
                        }
                    });
            } catch (error) {
                console.error('Echo initialization error:', error);
            }
        });
    </script>

</body>

</html>