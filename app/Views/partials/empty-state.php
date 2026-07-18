<?php
/**
 * Renders an empty state view.
 * $title: string
 * $message: string
 * $icon: string
 */
?>
<div class="flex flex-col items-center justify-center p-8 bg-slate-50 border border-dashed border-slate-300 rounded-2xl text-center">
    <div class="w-16 h-16 rounded-full bg-slate-200/50 flex items-center justify-center text-slate-400 mb-4">
        <?php App\Core\View::render('partials/icon', ['name' => $icon ?? 'folder-open', 'class' => 'text-2xl']) ?>
    </div>
    <h4 class="text-base font-bold text-slate-800"><?= e($title ?? 'No Data Available') ?></h4>
    <p class="text-sm text-slate-500 mt-1 max-w-xs"><?= e($message ?? 'Try adjusting your date range filter or query parameters.') ?></p>
</div>
