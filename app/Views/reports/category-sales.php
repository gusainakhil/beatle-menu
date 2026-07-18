<?php
// Category Sales view
?>
<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-black text-slate-900 leading-tight">Category Sales Split</h2>
        <p class="text-sm text-slate-500 mt-0.5">Understand which categories drive the largest portions of business revenue.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md transition-all duration-300 space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900 flex items-center">
                    <i class="fa-solid fa-chart-pie text-orange mr-2"></i>Revenue Contribution Share
                </h3>
            </div>
            <div class="relative h-64 flex items-center justify-center">
                <canvas id="categorySalesChart"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden lg:col-span-2 flex flex-col justify-between">
            <div>
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900 flex items-center">
                        <i class="fa-solid fa-list text-slate-500 mr-2"></i>Category Breakdown
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600 font-medium">
                        <thead class="bg-slate-50 text-slate-700 text-xs font-bold uppercase tracking-wider border-b border-slate-200/50">
                            <tr>
                                <th class="px-6 py-3">Category</th>
                                <th class="px-6 py-3 text-center">Orders Placed</th>
                                <th class="px-6 py-3 text-right">Revenue Collected</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($categorySales)): ?>
                                <tr>
                                    <td colspan="3" class="px-6 py-10">
                                        <?php App\Core\View::render('partials/empty-state', [
                                            'title' => 'No category sales',
                                            'message' => 'Complete tables/rooms dining sessions to populate aggregates.',
                                            'icon' => 'chart-pie'
                                        ]) ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categorySales as $row): ?>
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-orange/10 text-orange mr-3 border border-orange/20">
                                                    <?php App\Core\View::render('partials/icon', ['name' => $row['category_icon']]) ?>
                                                </span>
                                                <span class="font-bold text-slate-800"><?= e($row['category_name']) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center text-slate-700 font-bold"><?= e($row['total_orders']) ?> orders</td>
                                        <td class="px-6 py-4 text-right font-black text-slate-900">$<?= e(number_format((float)$row['total_revenue'], 2)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
