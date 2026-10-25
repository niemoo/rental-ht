<main class="relative flex min-h-screen items-center justify-center overflow-hidden bg-background p-6">
    <div class="pointer-events-none absolute top-[-15%] left-1/2 size-155 max-w-[120vw] -translate-x-1/2 rounded-full bg-primary/10 blur-3xl"></div>

    <div class="relative w-full max-w-93 rounded-2xl border border-border bg-card p-7 shadow-lg">
        <div class="mb-6 flex justify-center"><x-brand /></div>
        <h1 class="text-center text-xl font-semibold tracking-tight">Masuk</h1>
        <p class="mt-1 mb-6 text-center text-sm text-muted-foreground">Selamat datang — masuk untuk mengelola penyewaan.</p>

        <form wire:submit="login" class="flex flex-col gap-3.5">
            <x-field label="Email" for="email" error="email">
                <x-input id="email" type="email" wire:model="email" placeholder="you@company.com" autocomplete="email" required autofocus />
            </x-field>

            <x-field label="Kata sandi" for="password" error="password">
                <x-input id="password" type="password" wire:model="password" placeholder="••••••••" autocomplete="current-password" required />
            </x-field>

            <x-button type="submit" class="mt-1 w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="login">Masuk</span>
                <span wire:loading wire:target="login">Memproses…</span>
            </x-button>
        </form>

        <p class="mt-4.5 text-center text-xs leading-relaxed text-muted-foreground">
            Akun dibuat oleh pemilik.<br>Hubungi pemilik jika Anda butuh akses.
        </p>
    </div>
</main>
