<div class="w-full max-w-md bg-navy/80 backdrop-blur-md border border-slate-700/50 p-8 rounded-2xl shadow-2xl text-center relative overflow-hidden">
    <div class="absolute -top-10 -right-10 w-40 h-40 bg-orange/20 rounded-full blur-3xl"></div>
    <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-teal/20 rounded-full blur-3xl"></div>

    <div class="relative">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-rose-500/10 text-rose-500 mb-6 border border-rose-500/20">
            <i class="fa-solid fa-triangle-exclamation text-4xl"></i>
        </div>
        <h1 class="text-6xl font-black text-white leading-none"><?= e($code) ?></h1>
        <h2 class="text-xl font-bold text-white mt-4">System Notice</h2>
        <p class="text-slate-400 text-sm mt-2 px-4"><?= e($message) ?></p>
        
        <div class="mt-8">
            <a href="<?= route('/') ?>" class="inline-flex items-center justify-center bg-orange hover:bg-orange/95 text-white font-semibold py-3 px-6 rounded-xl shadow-lg transition duration-200">
                <i class="fa-solid fa-house mr-2"></i>
                Return to Dashboard
            </a>
        </div>
    </div>
</div>
