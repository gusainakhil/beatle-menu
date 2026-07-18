<?php
/**
 * Renders a KPI card.
 * $title: string
 * $value: string
 * $icon: string
 * $theme: 'orange' | 'teal' | 'navy' | 'muted'
 * $delta: optional percentage float (e.g. +12.4)
 */
$themeClass = 'bg-orange/10 text-orange border-orange/20';
if (($theme ?? 'orange') === 'teal') {
    $themeClass = 'bg-teal/10 text-teal border-teal/20';
} elseif (($theme ?? 'orange') === 'navy') {
    $themeClass = 'bg-slate-700/10 text-slate-300 border-slate-700/20';
}
?>
<div class="bg-white rounded-2xl border border-slate-200/60 p-6 shadow-sm hover:shadow-md transition-all duration-300 flex items-center justify-between">
    <div class="space-y-1">
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block"><?= e($title) ?></span>
        <h3 class="text-2xl font-black text-slate-900 leading-tight"><?= e($value) ?></h3>
        
        <?php if (isset($delta)): ?>
            <div class="flex items-center text-xs pt-1">
                <?php if ($delta > 0): ?>
                    <span class="inline-flex items-center text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full font-semibold">
                        <i class="fa-solid fa-arrow-trend-up mr-1 text-[10px]"></i>+<?= e($delta) ?>%
                    </span>
                <?php elseif ($delta < 0): ?>
                    <span class="inline-flex items-center text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full font-semibold">
                        <i class="fa-solid fa-arrow-trend-down mr-1 text-[10px]"></i><?= e($delta) ?>%
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center text-slate-500 bg-slate-50 px-2 py-0.5 rounded-full font-semibold">
                        0.0%
                    </span>
                <?php endif; ?>
                <span class="text-slate-400 ml-1.5">vs prev period</span>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="w-12 h-12 rounded-xl flex items-center justify-center <?= $themeClass ?> shadow-inner border">
        <?php App\Core\View::render('partials/icon', ['name' => $icon, 'class' => 'text-xl']) ?>
    </div>
</div>
