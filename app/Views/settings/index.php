<?php
// Settings view
use App\Core\Session;
$errors = Session::flash('errors') ?? [];
?>
<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-black text-slate-900 leading-tight">Business Settings</h2>
        <p class="text-sm text-slate-500 mt-0.5">Manage your profile, preferences, and review system action audit logs.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md transition duration-300 lg:col-span-2 space-y-5">
            <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center">
                <i class="fa-solid fa-sliders text-orange mr-2"></i>Update Business Profile
            </h3>

            <form action="<?= route('/settings') ?>" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-slate-500 text-xs font-bold mb-1.5" for="name">Company Name</label>
                        <input type="text" name="name" id="name" value="<?= e($business['name']) ?>" required
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-orange transition duration-200">
                        <?php if (isset($errors['name'])): ?>
                            <p class="text-rose-500 text-xs mt-1"><?= e($errors['name'][0]) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <label class="block text-slate-500 text-xs font-bold mb-1.5" for="email">Owner Email</label>
                        <input type="email" name="email" id="email" value="<?= e($business['email']) ?>" required
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-orange transition duration-200">
                        <?php if (isset($errors['email'])): ?>
                            <p class="text-rose-500 text-xs mt-1"><?= e($errors['email'][0]) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <label class="block text-slate-500 text-xs font-bold mb-1.5" for="phone">Phone Number</label>
                    <input type="text" name="phone" id="phone" value="<?= e($business['phone']) ?>"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-orange transition duration-200">
                </div>

                <div>
                    <label class="block text-slate-500 text-xs font-bold mb-1.5" for="address">Postal Address</label>
                    <textarea name="address" id="address" rows="3"
                              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-orange transition duration-200"><?= e($business['address']) ?></textarea>
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-end">
                    <button type="submit" class="bg-orange hover:bg-orange/95 text-white font-semibold text-xs py-2.5 px-6 rounded-xl shadow-md transition duration-200">
                        Save Configuration Changes
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden flex flex-col justify-between">
            <div>
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900 flex items-center">
                        <i class="fa-solid fa-clock-rotate-left text-slate-500 mr-2"></i>Audit History Logs
                    </h3>
                </div>
                
                <div class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
                    <?php if (empty($logs)): ?>
                        <div class="p-6 text-center text-xs text-slate-400">
                            No logs recorded.
                        </div>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <div class="p-4 space-y-1.5 hover:bg-slate-50/50 transition">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-700">
                                        <?= e(str_replace('_', ' ', $log['action'])) ?>
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-mono"><?= e(date('M d, H:i', strtotime($log['created_at']))) ?></span>
                                </div>
                                <div class="text-[11px] text-slate-400 flex justify-between">
                                    <span>IP: <?= e($log['ip_address']) ?></span>
                                    <span class="truncate max-w-[150px]" title="<?= e($log['user_agent']) ?>"><?= e($log['user_agent']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
