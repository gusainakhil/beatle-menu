<?php
// GST Reports view
?>
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 print:hidden">
        <div>
            <h2 class="text-2xl font-black text-slate-900 leading-tight">GST Tax Reports</h2>
            <p class="text-sm text-slate-500 mt-0.5">Summary of taxable revenues, tax collections, and active rate liability sheets.</p>
        </div>
        <div class="flex items-center space-x-2">
            <button onclick="window.print()" class="inline-flex items-center justify-center bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs py-2.5 px-4 rounded-xl shadow-md transition duration-200">
                <i class="fa-solid fa-print mr-1.5"></i>Print Report
            </button>
            <a href="<?= route('/reports/gst/export?' . http_build_query($_GET)) ?>" class="inline-flex items-center justify-center bg-orange hover:bg-orange/95 text-white font-semibold text-xs py-2.5 px-4 rounded-xl shadow-md transition duration-200">
                <i class="fa-solid fa-download mr-1.5"></i>Export CSV
            </a>
        </div>
    </div>

    <div class="hidden print:block border-b border-slate-300 pb-4 mb-6">
        <h1 class="text-3xl font-black text-slate-900"><?= e(brand('company_name')) ?> - GST Statement</h1>
        <p class="text-sm text-slate-500 mt-1">Generated: <?= date('M d, Y h:i A') ?> | Period: <?= e($range->getLabel()) ?></p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <?php
        App\Core\View::render('partials/kpi-card', [
            'title' => 'Taxable Subtotal',
            'value' => '$' . number_format((float)($summary['total_taxable'] ?? 0), 2),
            'icon' => 'file-invoice-dollar',
            'theme' => 'navy'
        ]);
        App\Core\View::render('partials/kpi-card', [
            'title' => 'Total GST Liability (Collected)',
            'value' => '$' . number_format((float)($summary['total_gst'] ?? 0), 2),
            'icon' => 'percent',
            'theme' => 'orange'
        ]);
        App\Core\View::render('partials/kpi-card', [
            'title' => 'Gross Turnover (Sales)',
            'value' => '$' . number_format((float)($summary['total_gross'] ?? 0), 2),
            'icon' => 'money-bill-wave',
            'theme' => 'teal'
        ]);
        ?>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 print:bg-slate-50">
            <h3 class="text-base font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-receipt text-slate-500 mr-2 print:hidden"></i>GST Tax Slabs Breakdown
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 font-medium">
                <thead class="bg-slate-50 text-slate-700 text-xs font-bold uppercase tracking-wider border-b border-slate-200/50">
                    <tr>
                        <th class="px-6 py-3">Tax category</th>
                        <th class="px-6 py-3 text-center">Tax rate</th>
                        <th class="px-6 py-3 text-right">Taxable base ($)</th>
                        <th class="px-6 py-3 text-right">GST collected ($)</th>
                        <th class="px-6 py-3 text-center">Total orders</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($breakdown)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-10">
                                <?php App\Core\View::render('partials/empty-state', [
                                    'title' => 'No tax collections',
                                    'message' => 'Tax statements will register as completed order sales occur.',
                                    'icon' => 'receipt'
                                ]) ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($breakdown as $row): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4">
                                    <span class="font-bold text-slate-800"><?= e($row['rate_name']) ?></span>
                                </td>
                                <td class="px-6 py-4 text-center font-bold text-slate-500"><?= e($row['rate_percent']) ?>%</td>
                                <td class="px-6 py-4 text-right text-slate-700">$<?= e(number_format($row['taxable'], 2)) ?></td>
                                <td class="px-6 py-4 text-right font-black text-slate-900">$<?= e(number_format($row['gst'], 2)) ?></td>
                                <td class="px-6 py-4 text-center text-slate-500 font-bold"><?= e($row['orders']) ?> orders</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden print:mt-8">
        <div class="px-6 py-4 border-b border-slate-100 print:bg-slate-50">
            <h3 class="text-base font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-calendar-check text-slate-500 mr-2 print:hidden"></i>Quarterly & Monthly Statements
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 font-medium">
                <thead class="bg-slate-50 text-slate-700 text-xs font-bold uppercase tracking-wider border-b border-slate-200/50">
                    <tr>
                        <th class="px-6 py-3">Month/Period</th>
                        <th class="px-6 py-3 text-right">Taxable Subtotal ($)</th>
                        <th class="px-6 py-3 text-right">GST Collected ($)</th>
                        <th class="px-6 py-3 text-right">Gross Total ($)</th>
                        <th class="px-6 py-3 text-center">Orders Served</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($monthly)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-10">
                                <?php App\Core\View::render('partials/empty-state', [
                                    'title' => 'No statements logged',
                                    'message' => 'Statements will group monthly once sales are processed.',
                                    'icon' => 'calendar-days'
                                ]) ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($monthly as $row): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4 font-bold text-slate-800 font-mono"><?= e(date('F Y', strtotime($row['month'] . '-01'))) ?></td>
                                <td class="px-6 py-4 text-right text-slate-700">$<?= e(number_format((float)$row['taxable'], 2)) ?></td>
                                <td class="px-6 py-4 text-right font-black text-slate-900">$<?= e(number_format((float)$row['gst'], 2)) ?></td>
                                <td class="px-6 py-4 text-right text-slate-900 font-bold">$<?= e(number_format((float)$row['gross'], 2)) ?></td>
                                <td class="px-6 py-4 text-center text-slate-500 font-bold"><?= e($row['orders_count']) ?> transactions</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
