<?php
$rangeType = isset($range) ? $range->getType() : 'month';
$startDate = isset($range) ? $range->getStartDate()->format('Y-m-d') : '';
$endDate = isset($range) ? $range->getEndDate()->format('Y-m-d') : '';
?>
<header class="h-16 bg-navy text-white flex items-center justify-between px-6 border-b border-slate-700/30 sticky top-0 z-30">
    <div class="flex items-center space-x-4">
        <button id="mobile-toggle" class="lg:hidden text-slate-300 hover:text-white p-1 rounded-lg focus:outline-none hover:bg-slate-800/40">
            <i class="fa-solid fa-bars text-xl"></i>
        </button>
        
        <h1 class="text-sm font-bold text-white hidden md:block">
            <i class="fa-solid fa-chart-line mr-2 text-orange"></i>Performance Workspace
        </h1>
    </div>

    <div class="flex items-center space-x-3">
        <form id="date-range-form" method="GET" class="flex items-center bg-slate-900/60 border border-slate-700/50 rounded-xl px-3 py-1.5 space-x-2 text-xs">
            <span class="text-slate-400 flex items-center">
                <i class="fa-solid fa-calendar mr-1.5 text-orange"></i>
            </span>
            
            <select name="range" id="range-select" class="bg-transparent text-white font-medium focus:outline-none cursor-pointer">
                <option value="today" <?= $rangeType === 'today' ? 'selected' : '' ?> class="bg-navy">Today</option>
                <option value="week" <?= $rangeType === 'week' ? 'selected' : '' ?> class="bg-navy">This Week</option>
                <option value="month" <?= $rangeType === 'month' ? 'selected' : '' ?> class="bg-navy">This Month</option>
                <option value="year" <?= $rangeType === 'year' ? 'selected' : '' ?> class="bg-navy">This Year</option>
                <option value="custom" <?= $rangeType === 'custom' ? 'selected' : '' ?> class="bg-navy">Custom Range</option>
            </select>

            <div id="custom-date-inputs" class="<?= $rangeType === 'custom' ? 'flex' : 'hidden' ?> items-center space-x-1.5 border-l border-slate-700/50 pl-2 ml-2">
                <input type="date" name="start_date" id="start-date-input" value="<?= e($startDate) ?>" class="bg-transparent text-white focus:outline-none">
                <span class="text-slate-500">to</span>
                <input type="date" name="end_date" id="end-date-input" value="<?= e($endDate) ?>" class="bg-transparent text-white focus:outline-none">
            </div>
            
            <button type="submit" class="text-orange hover:text-orange/80 ml-1">
                <i class="fa-solid fa-circle-play text-sm"></i>
            </button>
        </form>

        <div class="relative">
            <button id="notif-btn" class="w-10 h-10 flex items-center justify-center text-slate-300 hover:text-white rounded-xl hover:bg-slate-800/40 transition relative">
                <i class="fa-solid fa-bell text-lg"></i>
                <?php if (isset($notificationsCount) && $notificationsCount > 0): ?>
                    <span class="absolute top-1.5 right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[9px] font-bold text-white ring-2 ring-navy animate-pulse">
                        <?= e($notificationsCount) ?>
                    </span>
                <?php endif; ?>
            </button>
            
            <div id="notif-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-xl border border-slate-200 py-2 text-slate-800 z-50">
                <div class="px-4 py-2 border-b border-slate-100 flex items-center justify-between">
                    <span class="font-bold text-sm">Unread Alerts</span>
                    <span class="text-xs text-orange font-semibold"><?= e($notificationsCount ?? 0) ?> active</span>
                </div>
                <div class="max-h-64 overflow-y-auto">
                    <?php if (empty($notifications)): ?>
                        <div class="p-4 text-center text-xs text-slate-400">
                            No unread notifications.
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $n): ?>
                            <div class="px-4 py-3 hover:bg-slate-50 border-b border-slate-100 last:border-0">
                                <div class="flex items-start">
                                    <span class="text-orange mt-0.5 mr-2">
                                        <i class="fa-solid fa-circle-info text-xs"></i>
                                    </span>
                                    <div>
                                        <p class="text-xs font-bold text-slate-800"><?= e($n['title']) ?></p>
                                        <p class="text-[11px] text-slate-500 mt-0.5"><?= e($n['message']) ?></p>
                                        <span class="text-[9px] text-slate-400 block mt-1"><?= e(date('M d, H:i', strtotime($n['created_at']))) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <span class="w-[1px] h-6 bg-slate-700/50 hidden sm:block"></span>

        <a href="<?= route('/logout') ?>" class="w-10 h-10 items-center justify-center text-slate-300 hover:text-rose-400 rounded-xl hover:bg-rose-500/10 transition hidden sm:flex" title="Log Out">
            <i class="fa-solid fa-right-from-bracket text-lg"></i>
        </a>
    </div>
</header>
