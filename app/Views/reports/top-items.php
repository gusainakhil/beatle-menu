<?php
// Top Items view
?>
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-900 leading-tight">Top Selling Items</h2>
            <p class="text-sm text-slate-500 mt-0.5">Identify your highest volume dishes and beverage revenue drivers.</p>
        </div>
        <div>
            <a href="<?= route('/reports/top-items/export?' . http_build_query($_GET)) ?>" class="inline-flex items-center justify-center bg-orange hover:bg-orange/95 text-white font-semibold text-xs py-2.5 px-4 rounded-xl shadow-md transition duration-200">
                <i class="fa-solid fa-download mr-1.5"></i>Export CSV
            </a>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md transition-all duration-300 space-y-4">
        <div class="border-b border-slate-100 pb-3">
            <h3 class="text-base font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-award text-orange mr-2"></i>Top 10 Items (By Quantity Sold)
            </h3>
        </div>
        <div class="relative h-80">
            <canvas id="topItemsChart"></canvas>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-table text-slate-500 mr-2"></i>Detailed Performance Ranking
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 font-medium">
                <thead class="bg-slate-50 text-slate-700 text-xs font-bold uppercase tracking-wider border-b border-slate-200/50">
                    <tr>
                        <th class="px-6 py-3 text-center w-16">Rank</th>
                        <th class="px-6 py-3">Menu Item</th>
                        <th class="px-6 py-3">Category</th>
                        <th class="px-6 py-3 text-right">Quantity Sold</th>
                        <th class="px-6 py-3 text-right">Revenue Generated</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($topItems)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-10">
                                <?php App\Core\View::render('partials/empty-state', [
                                    'title' => 'No item data available',
                                    'message' => 'Items will rank here after completing orders during this range.',
                                    'icon' => 'award'
                                ]) ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $rank = 1; foreach ($topItems as $item): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4 text-center">
                                    <?php if ($rank === 1): ?>
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 text-amber-600 font-bold text-xs">
                                            <i class="fa-solid fa-crown text-[10px]"></i>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400 font-bold font-mono">#<?= $rank ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <span class="mr-2 text-slate-400">
                                            <i class="fa-solid fa-utensils"></i>
                                        </span>
                                        <span class="font-bold text-slate-800"><?= e($item['item_name']) ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center text-xs font-semibold text-slate-600">
                                        <?= e($item['category_name']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-slate-700"><?= e($item['quantity_sold']) ?> pcs</td>
                                <td class="px-6 py-4 text-right font-black text-slate-900">$<?= e(number_format((float)$item['revenue_generated'], 2)) ?></td>
                            </tr>
                        <?php $rank++; endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
