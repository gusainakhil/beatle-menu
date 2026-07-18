<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(brand('company_name')) ?> - Dashboard</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: '<?= brand('colors.navy') ?>',
                        'navy-deep': '<?= brand('colors.navy_deep') ?>',
                        orange: '<?= brand('colors.orange') ?>',
                        teal: '<?= brand('colors.teal') ?>',
                        ink: '<?= brand('colors.ink') ?>',
                        muted: '<?= brand('colors.muted') ?>',
                        tint: '<?= brand('colors.card_tint') ?>',
                        'tint-warm': '<?= brand('colors.card_tint_warm') ?>',
                    }
                }
            }
        }
    </script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Print Stylesheet -->
    <link rel="stylesheet" href="<?= route('/assets/css/print.css') ?>" media="print">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }
        /* Custom scrollbar for premium feel */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen text-slate-800 flex">

    <!-- Left Sidebar -->
    <?php App\Core\View::render('partials/sidebar') ?>

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div id="sidebar-backdrop" class="fixed inset-0 bg-slate-900/40 z-30 hidden transition-opacity duration-300 lg:hidden"></div>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col lg:pl-64 min-h-screen overflow-x-hidden">
        <!-- Top Header -->
        <?php App\Core\View::render('partials/topbar', ['range' => $range ?? null]) ?>

        <!-- Main Body -->
        <main class="flex-1 p-6 space-y-6">
            <!-- Flash Message Alerts -->
            <?php if ($successMsg = App\Core\Session::flash('success')): ?>
                <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 p-4 rounded-2xl text-sm flex items-center shadow-sm">
                    <i class="fa-solid fa-circle-check mr-2.5 text-base"></i>
                    <span class="font-semibold"><?= e($successMsg) ?></span>
                </div>
            <?php endif; ?>
            <?php if ($errorMsg = App\Core\Session::flash('error')): ?>
                <div class="bg-rose-500/10 border border-rose-500/20 text-rose-600 p-4 rounded-2xl text-sm flex items-center shadow-sm">
                    <i class="fa-solid fa-triangle-exclamation mr-2.5 text-base"></i>
                    <span class="font-semibold"><?= e($errorMsg) ?></span>
                </div>
            <?php endif; ?>

            <?= $content ?>
        </main>
    </div>

    <!-- Global Application Scripts -->
    <script>
        // Hamburger & Sidebar backdrop Toggle Logic
        const mobileToggle = document.getElementById('mobile-toggle');
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');

        function toggleSidebar() {
            sidebar.classList.toggle('-translate-x-full');
            backdrop.classList.toggle('hidden');
        }

        if (mobileToggle) {
            mobileToggle.addEventListener('click', toggleSidebar);
        }
        if (backdrop) {
            backdrop.addEventListener('click', toggleSidebar);
        }

        // Notification Dropdown Toggle
        const notifBtn = document.getElementById('notif-btn');
        const notifDropdown = document.getElementById('notif-dropdown');
        if (notifBtn && notifDropdown) {
            notifBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                notifDropdown.classList.toggle('hidden');
            });
            document.addEventListener('click', () => {
                notifDropdown.classList.add('hidden');
            });
        }

        // Date Picker Dynamic Show/Hide Custom Inputs
        const rangeSelect = document.getElementById('range-select');
        const customDateInputs = document.getElementById('custom-date-inputs');
        if (rangeSelect && customDateInputs) {
            rangeSelect.addEventListener('change', () => {
                if (rangeSelect.value === 'custom') {
                    customDateInputs.classList.remove('hidden');
                    customDateInputs.classList.add('flex');
                } else {
                    customDateInputs.classList.add('hidden');
                    customDateInputs.classList.remove('flex');
                    // Automatically submit standard filters on select change
                    document.getElementById('date-range-form').submit();
                }
            });
        }
    </script>
    <script src="<?= route('/assets/js/dashboard.js') ?>"></script>
</body>
</html>
