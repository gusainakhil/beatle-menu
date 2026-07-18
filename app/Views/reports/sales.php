<?php
// Sales Report view
?>
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-900 leading-tight">Sales & Revenue Reports</h2>
            <p class="text-sm text-slate-500 mt-0.5">Track financial performance, growth indexes, and order totals.</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="<?= route('/reports/sales/export?' . http_build_query($_GET)) ?>" class="inline-flex items-center justify-center bg-orange hover:bg-orange/95 text-white font-semibold text-xs py-2.5 px-4 rounded-xl shadow-md transition duration-200">
                <i class="fa-solid fa-download mr-1.5"></i>Export CSV
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <?php
        App\Core\View::render('partials/kpi-card', [
            'title' => 'Gross Revenue',
            'value' => '$' . number_format((float)($summary['current']['total_revenue'] ?? 0), 2),
            'icon' => 'money-bill-wave',
            'theme' => 'teal',
            'delta' => $summary['deltas']['revenue']
        ]);
        App\Core\View::render('partials/kpi-card', [
            'title' => 'Completed Orders',
            'value' => $summary['current']['completed_orders'],
            'icon' => 'circle-check',
            'theme' => 'teal',
            'delta' => $summary['deltas']['orders']
        ]);
        App\Core\View::render('partials/kpi-card', [
            'title' => 'Average Order Size',
            'value' => '$' . number_format((float)($summary['current']['avg_order_value'] ?? 0), 2),
            'icon' => 'chart-bar',
            'theme' => 'navy',
            'delta' => $summary['deltas']['aov']
        ]);
        ?>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md transition-all duration-300 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-base font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-chart-line text-orange mr-2"></i>Sales Interval Trend
            </h3>
            <div class="flex bg-slate-100 p-1 rounded-xl text-xs space-x-1 font-semibold">
                <button class="trend-toggle px-3 py-1.5 rounded-lg bg-white shadow-sm text-slate-800 font-bold" data-interval="daily">Daily</button>
                <button class="trend-toggle px-3 py-1.5 rounded-lg text-slate-500 hover:text-slate-800" data-interval="weekly">Weekly</button>
                <button class="trend-toggle px-3 py-1.5 rounded-lg text-slate-500 hover:text-slate-800" data-interval="monthly">Monthly</button>
            </div>
        </div>
        <div class="relative h-72">
            <canvas id="salesTrendChart"></canvas>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-list-check text-slate-500 mr-2"></i>All Order Transactions
            </h3>
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
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="9" class="px-6 py-10">
                                <?php App\Core\View::render('partials/empty-state', [
                                    'title' => 'No matching orders found',
                                    'message' => 'Try expanding your date range filter to locate order logs.',
                                    'icon' => 'receipt'
                                ]) ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4 font-bold text-slate-900 font-mono"><?= e($order['order_number']) ?></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center text-xs font-semibold text-slate-700">
                                        <i class="fa-solid fa-square-poll-horizontal mr-1 text-slate-400"></i><?= e($order['service_unit'] ?? 'Direct Takeaway') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center text-xs">
                                        <i class="fa-solid fa-user-tag mr-1 text-slate-400"></i><?= e($order['waiter'] ?? 'Self service') ?>
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
