<?php
/**
 * Renders a loading skeleton.
 * $type: 'chart' | 'table'
 */
$type = $type ?? 'table';
?>
<?php if ($type === 'chart'): ?>
    <div class="animate-pulse space-y-4">
        <div class="h-4 bg-slate-200 rounded w-1/4"></div>
        <div class="h-64 bg-slate-100 rounded-2xl flex items-end justify-between p-4 space-x-2">
            <div class="h-32 bg-slate-200 rounded w-full"></div>
            <div class="h-48 bg-slate-200 rounded w-full"></div>
            <div class="h-16 bg-slate-200 rounded w-full"></div>
            <div class="h-56 bg-slate-200 rounded w-full"></div>
            <div class="h-40 bg-slate-200 rounded w-full"></div>
            <div class="h-24 bg-slate-200 rounded w-full"></div>
        </div>
    </div>
<?php else: ?>
    <div class="animate-pulse space-y-4">
        <div class="h-6 bg-slate-200 rounded w-1/3"></div>
        <div class="space-y-3">
            <div class="h-10 bg-slate-100 rounded-xl"></div>
            <div class="h-10 bg-slate-100 rounded-xl"></div>
            <div class="h-10 bg-slate-100 rounded-xl"></div>
        </div>
    </div>
<?php endif; ?>
