// Beetle Analytics - Chart.js integrations and AJAX interactions

document.addEventListener('DOMContentLoaded', () => {
    const queryParams = window.location.search;

    async function fetchChartData(url) {
        try {
            const separator = url.includes('?') ? '&' : '?';
            const response = await fetch(url + separator + 'range=' + getActiveRange() + getCustomDatesQuery(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (!response.ok) throw new Error('Network response not ok');
            return await response.json();
        } catch (error) {
            console.error('Fetch error:', error);
            return null;
        }
    }

    function getActiveRange() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('range') || 'month';
    }

    function getCustomDatesQuery() {
        const urlParams = new URLSearchParams(window.location.search);
        const start = urlParams.get('start_date');
        const end = urlParams.get('end_date');
        if (start && end) {
            return `&start_date=${start}&end_date=${end}`;
        }
        return '';
    }

    // 1. Dashboard Overview Charts
    const revenueTrendCanvas = document.getElementById('revenueTrendChart');
    const orderStatusCanvas = document.getElementById('orderStatusChart');
    
    if (revenueTrendCanvas && orderStatusCanvas) {
        let trendChart;
        let donutChart;

        async function initOverviewCharts(interval = 'daily') {
            const data = await fetchChartData('/reports/sales/data?interval=' + interval);
            if (!data) return;

            const ctx1 = revenueTrendCanvas.getContext('2d');
            if (trendChart) trendChart.destroy();
            trendChart = new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: data.trend.labels,
                    datasets: [{
                        label: 'Revenue ($)',
                        data: data.trend.revenue,
                        borderColor: '#F26B3A',
                        backgroundColor: 'rgba(242, 107, 58, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0,0,0,0.05)' }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });

            const ctx2 = orderStatusCanvas.getContext('2d');
            if (donutChart) donutChart.destroy();
            donutChart = new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'Preparing', 'Pending', 'Cancelled'],
                    datasets: [{
                        data: [
                            data.status_breakdown.completed,
                            data.status_breakdown.preparing,
                            data.status_breakdown.pending,
                            data.status_breakdown.cancelled
                        ],
                        backgroundColor: ['#0E7C7B', '#F26B3A', '#1E2A3A', '#64748B'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    cutout: '70%'
                }
            });
        }

        document.querySelectorAll('.trend-toggle').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.trend-toggle').forEach(b => {
                    b.classList.remove('bg-white', 'shadow-sm', 'text-slate-800', 'font-bold');
                    b.classList.add('text-slate-500');
                });
                this.classList.add('bg-white', 'shadow-sm', 'text-slate-800', 'font-bold');
                this.classList.remove('text-slate-500');
                initOverviewCharts(this.dataset.interval);
            });
        });

        initOverviewCharts();
    }

    // 2. Sales Reports Page Charts
    const salesTrendCanvas = document.getElementById('salesTrendChart');
    if (salesTrendCanvas) {
        let salesChart;

        async function initSalesChart(interval = 'daily') {
            const data = await fetchChartData('/reports/sales/data?interval=' + interval);
            if (!data) return;

            const ctx = salesTrendCanvas.getContext('2d');
            if (salesChart) salesChart.destroy();
            salesChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.trend.labels,
                    datasets: [
                        {
                            label: 'Revenue ($)',
                            data: data.trend.revenue,
                            backgroundColor: '#F26B3A',
                            borderRadius: 6,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Orders Count',
                            data: data.trend.orders,
                            type: 'line',
                            borderColor: '#0E7C7B',
                            borderWidth: 2.5,
                            fill: false,
                            tension: 0.2,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: { display: true, text: 'Revenue ($)' }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: { drawOnChartArea: false },
                            title: { display: true, text: 'Orders Count' }
                        }
                    }
                }
            });
        }

        document.querySelectorAll('.trend-toggle').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.trend-toggle').forEach(b => {
                    b.classList.remove('bg-white', 'shadow-sm', 'text-slate-800', 'font-bold');
                    b.classList.add('text-slate-500');
                });
                this.classList.add('bg-white', 'shadow-sm', 'text-slate-800', 'font-bold');
                this.classList.remove('text-slate-500');
                initSalesChart(this.dataset.interval);
            });
        });

        initSalesChart();
    }

    // 3. Top Items Page Chart
    const topItemsCanvas = document.getElementById('topItemsChart');
    if (topItemsCanvas) {
        async function initTopItemsChart() {
            const data = await fetchChartData('/reports/top-items/data');
            if (!data) return;

            const labels = data.items.map(i => i.item_name);
            const quantities = data.items.map(i => i.quantity_sold);

            const ctx = topItemsCanvas.getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Quantity Sold (pcs)',
                        data: quantities,
                        backgroundColor: '#F26B3A',
                        borderRadius: 6
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { beginAtZero: true }
                    }
                }
            });
        }
        initTopItemsChart();
    }

    // 4. Category Sales Page Chart
    const categorySalesCanvas = document.getElementById('categorySalesChart');
    if (categorySalesCanvas) {
        async function initCategorySalesChart() {
            const data = await fetchChartData('/reports/category-sales/data');
            if (!data) return;

            const labels = data.categories.map(c => c.category_name);
            const revenues = data.categories.map(c => c.total_revenue);

            const ctx = categorySalesCanvas.getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: revenues,
                        backgroundColor: ['#1E2A3A', '#F26B3A', '#0E7C7B', '#64748B'],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }
        initCategorySalesChart();
    }

    // 5. Feedback Page Trend Chart
    const feedbackTrendCanvas = document.getElementById('feedbackTrendChart');
    if (feedbackTrendCanvas) {
        async function initFeedbackTrendChart() {
            const data = await fetchChartData('/reports/feedback/data');
            if (!data) return;

            const ctx = feedbackTrendCanvas.getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.trend.labels,
                    datasets: [{
                        label: 'Average Overall Rating (out of 5)',
                        data: data.trend.ratings,
                        borderColor: '#0E7C7B',
                        backgroundColor: 'rgba(14, 124, 123, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            min: 1,
                            max: 5,
                            grid: { color: 'rgba(0,0,0,0.05)' }
                        }
                    }
                }
            });
        }
        initFeedbackTrendChart();
    }

    // 6. Waiter Performance Chart
    const waitersCanvas = document.getElementById('waitersChart');
    if (waitersCanvas) {
        async function initWaitersChart() {
            const data = await fetchChartData('/reports/waiter/data');
            if (!data) return;

            const labels = data.waiters.map(w => w.waiter_name);
            const orders = data.waiters.map(w => w.orders_handled);

            const ctx = waitersCanvas.getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Orders Handled',
                        data: orders,
                        backgroundColor: '#0E7C7B',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }
        initWaitersChart();
    }
});
