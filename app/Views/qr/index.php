<?php
// QR code admin panel
$activeCount = 0;
foreach ($qrRows as $row) {
    if (!empty($row['qr_id'])) {
        $activeCount++;
    }
}
?>
<div class="space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-900 leading-tight">QR Code Management</h2>
            <p class="text-sm text-slate-500 mt-0.5">Generate and manage smart menu access codes for every table and room.</p>
        </div>
        <form action="<?= route('/qr-codes/generate-missing') ?>" method="POST">
            <?= csrf_field() ?>
            <button type="submit" class="inline-flex items-center justify-center bg-orange hover:bg-orange/95 text-white font-semibold text-xs py-2.5 px-4 rounded-xl shadow-md transition duration-200">
                <i class="fa-solid fa-wand-magic-sparkles mr-1.5"></i>Generate Missing
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <?php
        App\Core\View::render('partials/kpi-card', [
            'title' => 'Tables & Rooms',
            'value' => count($qrRows),
            'icon' => 'chair',
            'theme' => 'navy',
        ]);
        App\Core\View::render('partials/kpi-card', [
            'title' => 'Active QR Codes',
            'value' => $activeCount,
            'icon' => 'qrcode',
            'theme' => 'teal',
        ]);
        App\Core\View::render('partials/kpi-card', [
            'title' => 'Missing QR Codes',
            'value' => max(0, count($qrRows) - $activeCount),
            'icon' => 'triangle-exclamation',
            'theme' => 'orange',
        ]);
        ?>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        <?php if (empty($qrRows)): ?>
            <div class="md:col-span-2 xl:col-span-3 bg-white rounded-2xl border border-slate-200/60 shadow-sm p-8">
                <?php App\Core\View::render('partials/empty-state', [
                    'title' => 'No tables or rooms found',
                    'message' => 'Create table_room records first, then generate QR codes here.',
                    'icon' => 'qrcode',
                ]) ?>
            </div>
        <?php else: ?>
            <?php foreach ($qrRows as $index => $row): ?>
                <?php
                $hasQr = !empty($row['qr_id']) && !empty($row['encrypted_token']);
                $menuUrl = $hasQr ? $baseMenuUrl . rawurlencode($row['encrypted_token']) : '';
                $targetId = 'qr-preview-' . $index;
                ?>
                <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-black text-slate-900"><?= e($row['service_unit_name']) ?></h3>
                            <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400"><?= e($row['service_unit_type']) ?></span>
                        </div>
                        <?php if ($hasQr): ?>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border bg-emerald-50 text-emerald-600 border-emerald-100">
                                <i class="fa-solid fa-circle-check mr-1 text-[10px]"></i>Active
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border bg-amber-50 text-amber-600 border-amber-100">
                                <i class="fa-solid fa-clock mr-1 text-[10px]"></i>Missing
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="p-5 space-y-4">
                        <div class="aspect-square rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center p-5">
                            <?php if ($hasQr): ?>
                                <div id="<?= e($targetId) ?>" class="qr-preview" data-url="<?= e($menuUrl) ?>"></div>
                            <?php else: ?>
                                <div class="text-center text-slate-300">
                                    <i class="fa-solid fa-qrcode text-5xl"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($hasQr): ?>
                            <div class="space-y-2">
                                <div class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Menu URL</div>
                                <div class="flex items-center bg-slate-50 border border-slate-200 rounded-xl overflow-hidden">
                                    <input type="text" readonly value="<?= e($menuUrl) ?>" class="qr-url flex-1 min-w-0 bg-transparent px-3 py-2 text-xs text-slate-500 font-mono focus:outline-none">
                                    <button type="button" class="copy-qr-url px-3 py-2 text-slate-400 hover:text-orange transition" title="Copy QR URL">
                                        <i class="fa-solid fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="grid grid-cols-2 gap-2">
                            <form action="<?= route('/qr-codes/generate') ?>" method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="table_room_id" value="<?= e($row['table_room_id']) ?>">
                                <button type="submit" class="w-full inline-flex items-center justify-center bg-navy hover:bg-navy-deep text-white font-semibold text-xs py-2.5 px-3 rounded-xl transition duration-200">
                                    <i class="fa-solid fa-rotate mr-1.5"></i><?= $hasQr ? 'Regenerate' : 'Generate' ?>
                                </button>
                            </form>

                            <?php if ($hasQr): ?>
                                <form action="<?= route('/qr-codes/revoke') ?>" method="POST">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="qr_id" value="<?= e($row['qr_id']) ?>">
                                    <button type="submit" class="w-full inline-flex items-center justify-center bg-rose-500/10 hover:bg-rose-500 text-rose-600 hover:text-white border border-rose-500/20 font-semibold text-xs py-2.5 px-3 rounded-xl transition duration-200">
                                        <i class="fa-solid fa-ban mr-1.5"></i>Revoke
                                    </button>
                                </form>
                            <?php else: ?>
                                <button type="button" disabled class="w-full inline-flex items-center justify-center bg-slate-100 text-slate-300 font-semibold text-xs py-2.5 px-3 rounded-xl cursor-not-allowed">
                                    <i class="fa-solid fa-ban mr-1.5"></i>Revoke
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    document.querySelectorAll('.qr-preview').forEach((target) => {
        new QRCode(target, {
            text: target.dataset.url,
            width: 190,
            height: 190,
            colorDark: '#0f172a',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.H
        });
    });

    document.querySelectorAll('.copy-qr-url').forEach((button) => {
        button.addEventListener('click', async () => {
            const input = button.closest('div').querySelector('.qr-url');
            input.select();
            await navigator.clipboard.writeText(input.value);
            button.classList.add('text-emerald-500');
            setTimeout(() => button.classList.remove('text-emerald-500'), 900);
        });
    });
</script>
