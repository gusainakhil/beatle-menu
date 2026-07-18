<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$currentPath = $currentPath === '/' ? '/' : rtrim($currentPath, '/');

$navItems = [
    ['label' => 'Dashboard Overview', 'route' => '/', 'icon' => 'house'],
    ['label' => 'Sales Reports', 'route' => '/reports/sales', 'icon' => 'money-bill-trend-up'],
    ['label' => 'Top Items', 'route' => '/reports/top-items', 'icon' => 'fire'],
    ['label' => 'Category Sales', 'route' => '/reports/category-sales', 'icon' => 'chart-pie'],
    ['label' => 'Feedback', 'route' => '/reports/feedback', 'icon' => 'star'],
    ['label' => 'Waiter Performance', 'route' => '/reports/waiter', 'icon' => 'bell-concierge'],
    ['label' => 'GST Reports', 'route' => '/reports/gst', 'icon' => 'receipt'],
    ['label' => 'QR Codes', 'route' => '/qr-codes', 'icon' => 'qrcode'],
    ['label' => 'Settings', 'route' => '/settings', 'icon' => 'sliders'],
];
?>
<div id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-navy text-white transition-transform -translate-x-full lg:translate-x-0 flex flex-col justify-between border-r border-slate-700/30">
    <div>
        <div class="h-16 flex items-center px-6 border-b border-slate-700/30 bg-navy-deep">
            <div class="flex items-center space-x-3">
                <div class="bg-orange/10 text-orange w-8 h-8 rounded-lg flex items-center justify-center border border-orange/20 shadow-inner">
                    <i class="fa-solid fa-bug text-lg"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold tracking-tight text-white leading-none"><?= e(brand('company_name')) ?></h2>
                    <span class="text-[10px] text-slate-400 font-semibold tracking-wider uppercase"><?= e(brand('tagline')) ?></span>
                </div>
            </div>
        </div>

        <nav class="mt-6 px-4 space-y-1">
            <?php foreach ($navItems as $item): ?>
                <?php 
                $isActive = ($currentPath === $item['route']);
                $activeClass = $isActive 
                    ? 'bg-orange text-white font-bold shadow-lg shadow-orange/15' 
                    : 'text-slate-300 hover:bg-slate-800/40 hover:text-white';
                ?>
                <a href="<?= route($item['route']) ?>" class="flex items-center px-4 py-3 rounded-xl transition duration-200 group text-sm font-medium <?= $activeClass ?>">
                    <span class="mr-3 w-5 text-center flex items-center justify-center group-hover:scale-110 transition-transform">
                        <?php App\Core\View::render('partials/icon', ['name' => $item['icon']]) ?>
                    </span>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="p-4 border-t border-slate-700/30 bg-navy-deep">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-full bg-slate-700 flex items-center justify-center text-sm font-bold text-slate-300">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
                <div class="truncate max-w-[130px]">
                    <span class="block text-xs font-bold text-white leading-none truncate"><?= e(App\Core\Session::get('business_name')) ?></span>
                    <span class="text-[10px] text-slate-400 truncate"><?= e(App\Core\Session::get('owner_email')) ?></span>
                </div>
            </div>
        </div>
        <a href="<?= route('/logout') ?>" class="w-full flex items-center justify-center px-4 py-2.5 rounded-xl border border-rose-500/20 bg-rose-500/5 text-rose-400 hover:bg-rose-500 hover:text-white text-xs font-bold transition duration-200">
            <i class="fa-solid fa-right-from-bracket mr-2"></i>Log Out
        </a>
    </div>
</div>
