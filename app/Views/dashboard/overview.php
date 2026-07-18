<?php
// Overview page view - Rich Data Redesign
?>
<div class="space-y-6">
    <!-- Header Title Summary -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-900 leading-tight">Dashboard Overview</h2>
            <p class="text-sm text-slate-500 mt-0.5">Comprehensive real-time performance indicators and financial summaries.</p>
        </div>
        <div class="flex items-center space-x-2">
            <span class="inline-flex items-center rounded-xl bg-orange/10 px-3 py-1.5 text-xs font-bold text-orange border border-orange/20 shadow-sm">
                <i class="fa-solid fa-calendar-days mr-2"></i>Filter: <?= e($range->getLabel()) ?>
            </span>
        </div>
    </div>

    <!-- Row 1 KPIs: Core Financials -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5">
        <?php 
        App\Core\View::render('partials/kpi-card', [
            'title' => "Today's Orders",
            'value' => $aggregates['total_orders'],
            'icon' => 'receipt',
            'theme' => 'orange'
        ]); 
        App\Core\View::render('partials/kpi-card', [
            'title' => 'Gross Turnover',
            'value' => '$' . number_format((float)($aggregates['total_revenue'] ?? 0), 2),
            'icon' => 'money-bill-wave',
            'theme' => 'teal'
        ]);
        App\Core\View::render('partials/kpi-card', [
            'title' => 'Net Revenue',
            'value' => '$' . number_format((float)($aggregates['total_taxable'] ?? 0), 2),
            'icon' => 'file-invoice-dollar',
            'theme' => 'teal'
        ]);
        App\Core\View::render('partials/kpi-card', [
            'title' => 'GST Tax Collected',
            'value' => '$' . number_format((float)($aggregates['total_gst'] ?? 0), 2),
            'icon' => 'percent',
            'theme' => 'orange'
        ]);
        App\Core\View::render('partials/kpi-card', [
            'title' => 'Average Order Value',
            'value' => '$' . number_format((float)($aggregates['avg_order_value'] ?? 0), 2),
            'icon' => 'chart-bar',
            'theme' => 'navy'
        ]);
        ?>
    </div>

    <!-- Row 2 KPIs: Operations & Satisfaction -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5">
        <?php 
        App\Core\View::render('partials/kpi-card', [
            'title' => 'Customer Satisfaction',
            'value' => number_format((float)($ratingsSummary['avg_overall'] ?? 0), 1) . ' / 5.0',
            'icon' => 'star',
            'theme' => 'orange'
        ]); 
        App\Core\View::render('partials/kpi-card', [
            'title' => 'Service Charges',
            'value' => '$' . number_format((float)($aggregates['total_discount'] ?? 0), 2),
            'icon' => 'tags',
            'theme' => 'navy'
        ]);
        App\Core\View::render('partials/kpi-card', [
            'title' => 'Pending Orders',
            'value' => $aggregates['pending_orders'],
            'icon' => 'clock',
            'theme' => 'orange'
        ]);
        App\Core\View::render('partials/kpi-card', [
            'title' => 'Completed Orders',
            'value' => $aggregates['completed_orders'],
            'icon' => 'circle-check',
            'theme' => 'teal'
        ]);
        App\Core\View::render('partials/kpi-card', [
            'title' => 'Cancelled Orders',
            'value' => $aggregates['cancelled_orders'],
            'icon' => 'circle-xmark',
            'theme' => 'navy'
        ]);
        ?>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sales Trend Chart -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md transition-all duration-300 lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900 flex items-center">
                    <i class="fa-solid fa-arrow-trend-up text-orange mr-2"></i>Revenue & Sales Trend
                </h3>
                <div class="flex bg-slate-100 p-1 rounded-xl text-xs space-x-1 font-semibold">
                    <button class="trend-toggle px-3 py-1.5 rounded-lg bg-white shadow-sm text-slate-800 font-bold" data-interval="daily">Daily</button>
                    <button class="trend-toggle px-3 py-1.5 rounded-lg text-slate-500 hover:text-slate-800" data-interval="weekly">Weekly</button>
                    <button class="trend-toggle px-3 py-1.5 rounded-lg text-slate-500 hover:text-slate-800" data-interval="monthly">Monthly</button>
                </div>
            </div>
            <div class="relative h-72">
                <canvas id="revenueTrendChart"></canvas>
            </div>
        </div>

        <!-- Order status distribution donut -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md transition-all duration-300 space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900 flex items-center">
                    <i class="fa-solid fa-chart-pie text-teal mr-2"></i>Order Status Distribution
                </h3>
            </div>
            <div class="relative h-60 flex items-center justify-center">
                <canvas id="orderStatusChart"></canvas>
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs pt-2">
                <div class="flex items-center text-slate-600">
                    <span class="w-3 h-3 rounded-full bg-emerald-500 mr-2"></span>
                    <span>Completed (<?= e($aggregates['completed_orders']) ?>)</span>
                </div>
                <div class="flex items-center text-slate-600">
                    <span class="w-3 h-3 rounded-full bg-amber-500 mr-2"></span>
                    <span>Preparing (<?= e($aggregates['preparing_orders']) ?>)</span>
                </div>
                <div class="flex items-center text-slate-600">
                    <span class="w-3 h-3 rounded-full bg-sky-500 mr-2"></span>
                    <span>Pending (<?= e($aggregates['pending_orders']) ?>)</span>
                </div>
                <div class="flex items-center text-slate-600">
                    <span class="w-3 h-3 rounded-full bg-rose-500 mr-2"></span>
                    <span>Cancelled (<?= e($aggregates['cancelled_orders']) ?>)</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Panels Column Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- 1. Top 5 Selling Items Preview -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm flex flex-col justify-between space-y-4">
            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 flex items-center">
                    <i class="fa-solid fa-fire text-orange mr-2"></i>Top Dishes & Drinks
                </h3>
                <a href="<?= route('/reports/top-items') ?>" class="text-[10px] text-orange hover:underline font-bold">Details</a>
            </div>
            <div class="space-y-4">
                <?php if (empty($topItems)): ?>
                    <?php App\Core\View::render('partials/empty-state', ['title' => 'No items sold', 'message' => 'Items split shows after order collections.', 'icon' => 'award']) ?>
                <?php else: ?>
                    <?php 
                    $maxQty = (int)($topItems[0]['quantity_sold'] ?? 1);
                    foreach ($topItems as $item): 
                        $percentage = round(($item['quantity_sold'] / $maxQty) * 100);
                    ?>
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-800 truncate max-w-[150px]"><?= e($item['item_name']) ?></span>
                                <span class="text-slate-500 font-bold"><?= e($item['quantity_sold']) ?> sold ($<?= e(number_format($item['revenue_generated'], 2)) ?>)</span>
                            </div>
                            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                <div class="bg-orange h-full rounded-full" style="width: <?= $percentage ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2. Waiter Leaderboard Preview -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm flex flex-col justify-between space-y-4">
            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 flex items-center">
                    <i class="fa-solid fa-users text-teal mr-2"></i>Waiter Performance
                </h3>
                <a href="<?= route('/reports/waiter') ?>" class="text-[10px] text-orange hover:underline font-bold">Details</a>
            </div>
            <div class="space-y-3 flex-1 flex flex-col justify-center">
                <?php if (empty($topWaiters)): ?>
                    <?php App\Core\View::render('partials/empty-state', ['title' => 'No waiter logs', 'message' => 'Workload stats will appear here.', 'icon' => 'users']) ?>
                <?php else: ?>
                    <?php foreach ($topWaiters as $waiter): ?>
                        <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 hover:bg-slate-100/70 border border-slate-100 transition">
                            <div class="flex items-center space-x-2.5">
                                <div class="w-8 h-8 rounded-full bg-teal/10 border border-teal/20 text-teal flex items-center justify-center font-bold text-xs">
                                    <?= e($waiter['waiter_avatar']) ?>
                                </div>
                                <div>
                                    <span class="block text-xs font-bold text-slate-800 leading-none"><?= e($waiter['waiter_name']) ?></span>
                                    <span class="text-[10px] text-slate-400 font-bold"><?= e($waiter['orders_handled']) ?> orders handled</span>
                                </div>
                            </div>
                            <div class="inline-flex items-center space-x-0.5 text-xs text-amber-500 font-bold bg-amber-50 px-2 py-0.5 rounded-full border border-amber-100">
                                <i class="fa-solid fa-star"></i>
                                <span><?= e(number_format((float)$waiter['avg_rating'], 1)) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- 3. Recent Customer Reviews Preview -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm flex flex-col justify-between space-y-4">
            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 flex items-center">
                    <i class="fa-solid fa-comments text-slate-500 mr-2"></i>Recent Feedback
                </h3>
                <a href="<?= route('/reports/feedback') ?>" class="text-[10px] text-orange hover:underline font-bold">Details</a>
            </div>
            <div class="space-y-3 flex-1 flex flex-col justify-center">
                <?php if (empty($recentFeedback)): ?>
                    <?php App\Core\View::render('partials/empty-state', ['title' => 'No comments', 'message' => 'Latest reviews will list here.', 'icon' => 'comments']) ?>
                <?php else: ?>
                    <?php foreach ($recentFeedback as $fb): ?>
                        <div class="text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-100 hover:bg-slate-100/70 transition space-y-1 relative">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-800"><?= e($fb['customer_name']) ?></span>
                                <div class="flex space-x-0.5 text-[10px]">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fa-solid fa-star <?= $i <= $fb['rating_overall'] ? 'text-amber-400' : 'text-slate-200' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="text-slate-500 italic truncate max-w-[200px]">"<?= e($fb['comments'] ?: 'Rated order only.') ?>"</p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Orders Table -->
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-clock-rotate-left text-slate-500 mr-2"></i>Recent Orders
            </h3>
            <a href="<?= route('/reports/sales') ?>" class="text-xs text-orange hover:underline font-bold flex items-center">
                View All Sales<i class="fa-solid fa-arrow-right ml-1"></i>
            </a>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 font-medium">
                <thead class="bg-slate-50 text-slate-700 text-xs font-bold uppercase tracking-wider border-b border-slate-200/50">
                    <tr>
                        <th class="px-6 py-3">Order Code</th>
                        <th class="px-6 py-3">Service Unit</th>
                        <th class="px-6 py-3">Waiter</th>
                        <th class="px-6 py-3">Date & Time</th>
                        <th class="px-6 py-3">Subtotal</th>
                        <th class="px-6 py-3">GST Tax</th>
                        <th class="px-6 py-3">Total Amount</th>
                        <th class="px-6 py-3 text-center">Payment</th>
                        <th class="px-6 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($recentOrders)): ?>
                        <tr>
                            <td colspan="9" class="px-6 py-10">
                                <?php App\Core\View::render('partials/empty-state', [
                                    'title' => 'No orders recorded yet',
                                    'message' => 'Start order placements inside tables/rooms to collect reports.',
                                    'icon' => 'receipt'
                                ]) ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4 font-bold text-slate-900 font-mono"><?= e($order['order_number']) ?></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center text-xs font-semibold text-slate-700">
                                        <i class="fa-solid fa-square-poll-horizontal mr-1 text-slate-400"></i><?= e($order['service_unit_name'] ?? 'Direct Delivery') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center text-xs">
                                        <i class="fa-solid fa-user-tag mr-1 text-slate-400"></i><?= e($order['waiter_name'] ?? 'Self service') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500"><?= e(date('M d, Y h:i A', strtotime($order['created_at']))) ?></td>
                                <td class="px-6 py-4 text-slate-700">$<?= e(number_format((float)$order['subtotal'], 2)) ?></td>
                                <td class="px-6 py-4 text-slate-500">$<?= e(number_format((float)$order['gst_amount'], 2)) ?></td>
                                <td class="px-6 py-4 font-bold text-slate-900">$<?= e(number_format((float)$order['total'], 2)) ?></td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xs uppercase font-semibold tracking-wider text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md">
                                        <?= e($order['payment_method']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php
                                    $status = $order['status'];
                                    $statusClass = 'bg-sky-50 text-sky-600 border-sky-100';
                                    $icon = 'circle-info';
                                    if ($status === 'completed') {
                                        $statusClass = 'bg-emerald-50 text-emerald-600 border-emerald-100';
                                        $icon = 'circle-check';
                                    } elseif ($status === 'preparing') {
                                        $statusClass = 'bg-amber-50 text-amber-600 border-amber-100';
                                        $icon = 'fire';
                                    } elseif ($status === 'cancelled') {
                                        $statusClass = 'bg-rose-50 text-rose-600 border-rose-100';
                                        $icon = 'circle-xmark';
                                    }
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border <?= $statusClass ?>">
                                        <i class="fa-solid fa-<?= $icon ?> mr-1 text-[10px]"></i><?= e(ucfirst($status)) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
