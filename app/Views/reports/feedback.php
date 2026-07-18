<?php
// Feedback reports view
?>
<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-black text-slate-900 leading-tight">Customer Feedback & Ratings</h2>
        <p class="text-sm text-slate-500 mt-0.5">Monitor service quality, food sentiment, and overall customer satisfaction.</p>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-5">
        <?php
        $ratings = [
            ['title' => 'Overall Rating', 'value' => $summary['avg_overall'], 'icon' => 'star', 'theme' => 'orange'],
            ['title' => 'Food Quality', 'value' => $summary['avg_food'], 'icon' => 'utensils', 'theme' => 'teal'],
            ['title' => 'Service Speed', 'value' => $summary['avg_service'], 'icon' => 'bell-concierge', 'theme' => 'orange'],
            ['title' => 'Staff Attitude', 'value' => $summary['avg_staff'], 'icon' => 'user-tie', 'theme' => 'navy'],
            ['title' => 'Cleanliness', 'value' => $summary['avg_cleanliness'], 'icon' => 'spray-can-sparkles', 'theme' => 'teal'],
        ];

        foreach ($ratings as $r):
            $starHtml = '';
            $val = (float)$r['value'];
            $fullStars = floor($val);
            $halfStar = ($val - $fullStars) >= 0.5;
            for ($i = 1; $i <= 5; $i++) {
                if ($i <= $fullStars) {
                    $starHtml .= '<i class="fa-solid fa-star text-amber-400"></i>';
                } elseif ($i == $fullStars + 1 && $halfStar) {
                    $starHtml .= '<i class="fa-solid fa-star-half-stroke text-amber-400"></i>';
                } else {
                    $starHtml .= '<i class="fa-regular fa-star text-slate-300"></i>';
                }
            }
            
            $themeClass = 'bg-orange/10 text-orange border-orange/20';
            if ($r['theme'] === 'teal') {
                $themeClass = 'bg-teal/10 text-teal border-teal/20';
            } elseif ($r['theme'] === 'navy') {
                $themeClass = 'bg-slate-700/10 text-slate-300 border-slate-700/20';
            }
        ?>
            <div class="bg-white rounded-2xl border border-slate-200/60 p-5 shadow-sm hover:shadow-md transition duration-300 flex flex-col justify-between space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider"><?= e($r['title']) ?></span>
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center <?= $themeClass ?> border text-xs">
                        <i class="fa-solid fa-<?= e($r['icon']) ?>"></i>
                    </span>
                </div>
                <div class="space-y-1">
                    <h3 class="text-2xl font-black text-slate-900"><?= e(number_format($val, 1)) ?> <span class="text-xs text-slate-400">/ 5.0</span></h3>
                    <div class="flex space-x-0.5 text-xs">
                        <?= $starHtml ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md transition-all duration-300 space-y-4">
        <div class="border-b border-slate-100 pb-3">
            <h3 class="text-base font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-chart-line text-orange mr-2"></i>Satisfaction Rating Trend
            </h3>
        </div>
        <div class="relative h-64">
            <canvas id="feedbackTrendChart"></canvas>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-comments text-slate-500 mr-2"></i>Customer Review Logs
            </h3>
            <span class="text-xs bg-slate-100 text-slate-600 font-bold px-2.5 py-1 rounded-full">
                Total reviews: <?= e($paginator->getTotalItems()) ?>
            </span>
        </div>
        
        <div class="divide-y divide-slate-100">
            <?php if (empty($feedbackList)): ?>
                <div class="p-8">
                    <?php App\Core\View::render('partials/empty-state', [
                        'title' => 'No reviews left yet',
                        'message' => 'Review ratings will show up here as customers leave comments.',
                        'icon' => 'comments'
                    ]) ?>
                </div>
            <?php else: ?>
                <?php foreach ($feedbackList as $fb): ?>
                    <div class="p-6 hover:bg-slate-50/40 transition duration-150 flex flex-col md:flex-row md:items-start md:justify-between gap-4 font-medium">
                        <div class="space-y-2">
                            <div class="flex items-center space-x-1 text-xs">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fa-solid fa-star <?= $i <= $fb['rating_overall'] ? 'text-amber-400' : 'text-slate-200' ?>"></i>
                                <?php endfor; ?>
                                <span class="text-[11px] text-slate-400 font-bold ml-1 font-mono">Order: #<?= e($fb['order_number'] ?? 'N/A') ?></span>
                            </div>
                            
                            <p class="text-sm font-semibold text-slate-800 italic">
                                <i class="fa-solid fa-quote-left text-orange/30 text-base mr-1.5 align-middle"></i><?= e($fb['comments'] ?: 'Rating left without written review.') ?>
                            </p>
                            
                            <div class="flex items-center space-x-2 text-xs text-slate-400 pt-1">
                                <span class="font-bold text-slate-500"><?= e($fb['customer_name']) ?></span>
                                <span class="w-[1px] h-3 bg-slate-300"></span>
                                <span><?= e($fb['customer_phone'] ?: 'No Phone') ?></span>
                                <span class="w-[1px] h-3 bg-slate-300"></span>
                                <span><?= e(date('M d, Y h:i A', strtotime($fb['created_at']))) ?></span>
                            </div>
                        </div>
                        
                        <div class="flex flex-wrap gap-2 text-[10px] font-bold text-slate-500">
                            <span class="bg-slate-100 px-2 py-1 rounded-md">Food: <strong class="text-slate-800 font-black"><?= e($fb['rating_food']) ?>★</strong></span>
                            <span class="bg-slate-100 px-2 py-1 rounded-md">Service: <strong class="text-slate-800 font-black"><?= e($fb['rating_service']) ?>★</strong></span>
                            <span class="bg-slate-100 px-2 py-1 rounded-md">Staff: <strong class="text-slate-800 font-black"><?= e($fb['rating_staff']) ?>★</strong></span>
                            <span class="bg-slate-100 px-2 py-1 rounded-md">Clean: <strong class="text-slate-800 font-black"><?= e($fb['rating_cleanliness']) ?>★</strong></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="px-6 py-4">
            <?= $paginator->render(route('/reports/feedback'), $_GET) ?>
        </div>
    </div>
</div>
