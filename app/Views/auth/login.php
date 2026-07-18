<?php
use App\Core\Session;
$old = Session::flash('old') ?? [];
$error = Session::flash('error');
$errors = Session::flash('errors') ?? [];
$success = Session::flash('success');
?>
<div class="w-full max-w-md bg-navy/80 backdrop-blur-md border border-slate-700/50 p-8 rounded-2xl shadow-2xl relative overflow-hidden">
    <div class="absolute -top-10 -right-10 w-40 h-40 bg-orange/20 rounded-full blur-3xl"></div>
    <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-teal/20 rounded-full blur-3xl"></div>

    <div class="text-center mb-8 relative">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-orange/10 text-orange mb-4 border border-orange/20 shadow-inner">
            <i class="fa-solid fa-bug text-3xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-white tracking-tight"><?= e(brand('company_name')) ?></h1>
        <p class="text-slate-400 text-sm mt-1"><?= e(brand('tagline')) ?></p>
    </div>

    <?php if ($success): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-3 rounded-lg text-sm mb-6 flex items-center">
            <i class="fa-solid fa-circle-check mr-2"></i>
            <span><?= e($success) ?></span>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-rose-500/10 border border-rose-500/20 text-rose-400 p-3 rounded-lg text-sm mb-6 flex items-center">
            <i class="fa-solid fa-triangle-exclamation mr-2"></i>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <form action="<?= route('/login') ?>" method="POST" class="space-y-5 relative">
        <?= csrf_field() ?>
        <div>
            <label class="block text-slate-300 text-xs font-semibold mb-2 uppercase tracking-wider" for="email">Owner Email</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <i class="fa-solid fa-envelope"></i>
                </span>
                <input class="w-full bg-slate-900/60 border border-slate-700/50 rounded-xl py-3 pl-10 pr-4 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-orange focus:ring-1 focus:ring-orange transition duration-200"
                       id="email" type="email" name="email" value="<?= e($old['email'] ?? '') ?>" placeholder="owner@beetlebistro.com" required>
            </div>
            <?php if (isset($errors['email'])): ?>
                <p class="text-rose-400 text-xs mt-1"><i class="fa-solid fa-xs fa-circle-exclamation mr-1"></i><?= e($errors['email'][0]) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label class="block text-slate-300 text-xs font-semibold mb-2 uppercase tracking-wider" for="password">Password</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <i class="fa-solid fa-lock"></i>
                </span>
                <input class="w-full bg-slate-900/60 border border-slate-700/50 rounded-xl py-3 pl-10 pr-4 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-orange focus:ring-1 focus:ring-orange transition duration-200"
                       id="password" type="password" name="password" placeholder="••••••••" required>
            </div>
            <?php if (isset($errors['password'])): ?>
                <p class="text-rose-400 text-xs mt-1"><i class="fa-solid fa-xs fa-circle-exclamation mr-1"></i><?= e($errors['password'][0]) ?></p>
            <?php endif; ?>
        </div>

        <button class="w-full bg-orange hover:bg-orange/95 text-white font-semibold py-3 px-4 rounded-xl shadow-lg hover:shadow-orange/20 transition duration-200 flex items-center justify-center group" type="submit">
            <span>Log In to Dashboard</span>
            <i class="fa-solid fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
        </button>
    </form>

    <div class="mt-8 text-center text-slate-500 text-xs">
        <p>Demo Account: <span class="text-slate-300 font-mono">owner@beetlebistro.com</span> / <span class="text-slate-300 font-mono">password</span></p>
    </div>
</div>
