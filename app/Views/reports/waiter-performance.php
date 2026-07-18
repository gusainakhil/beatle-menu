<?php
// Waiter performance view
?>
<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-black text-slate-900 leading-tight">Waiter & Staff Performance</h2>
        <p class="text-sm text-slate-500 mt-0.5">Evaluate employee workloads, service counts, and rating feedback averages.</p>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md transition-all duration-300 space-y-4">
        <div class="border-b border-slate-100 pb-3">
            <h3 class="text-base font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-chart-bar text-orange mr-2"></i>Orders Handled comparison
            </h3>
        </div>
        <div class="relative h-64">
            <canvas id="waitersChart"></canvas>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-users text-slate-500 mr-2"></i>Staff Directory & Aggregates
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 font-medium">
                <thead class="bg-slate-50 text-slate-700 text-xs font-bold uppercase tracking-wider border-b border-slate-200/50">
                    <tr>
                        <th class="px-6 py-3">Waiter Name</th>
                        <th class="px-6 py-3">Email Address</th>
                        <th class="px-6 py-3 text-center">Orders Handled</th>
                        <th class="px-6 py-3 text-right">Avg Order size</th>
                        <th class="px-6 py-3 text-center">Avg rating</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($waiters)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-10">
                                <?php App\Core\View::render('partials/empty-state', [
                                    'title' => 'No staff registered',
                                    'message' => 'Register waiters in your setting pages or database directory first.',
                                    'icon' => 'users'
                                ]) ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($waiters as $waiter): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-full bg-orange/10 border border-orange/20 text-orange flex items-center justify-center font-bold text-xs">
                                            <?= e($waiter['waiter_avatar']) ?>
                                        </div>
                                        <span class="font-bold text-slate-800"><?= e($waiter['waiter_name']) ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-400 font-mono"><?= e($waiter['waiter_email'] ?: 'No email configured') ?></td>
                                <td class="px-6 py-4 text-center font-bold text-slate-700"><?= e($waiter['orders_handled']) ?> orders</td>
                                <td class="px-6 py-4 text-right font-black text-slate-900">$<?= e(number_format((float)$waiter['avg_order_value'], 2)) ?></td>
                                <td class="px-6 py-4 text-center">
                                    <div class="inline-flex items-center space-x-0.5 text-xs text-amber-500 font-bold bg-amber-50 border border-amber-100 rounded-full px-2.5 py-0.5">
                                        <i class="fa-solid fa-star"></i>
                                        <span><?= e(number_format((float)$waiter['avg_rating'], 1)) ?></span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
