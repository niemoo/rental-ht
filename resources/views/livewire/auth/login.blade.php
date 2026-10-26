<div class="auth-page-wrapper pt-5">
    <div class="auth-one-bg-position auth-one-bg">
        <div class="bg-overlay"></div>
        <div class="shape">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 120" aria-hidden="true"><path d="M 0,36 C 144,53.6 432,123.2 720,124 C 1008,124.8 1296,56.8 1440,40L1440 140L0 140z"></path></svg>
        </div>
    </div>

    <div class="auth-page-content">
        <div class="container">
            <div class="text-center mt-sm-5 mb-4 text-white-50">
                <x-brand class="text-white fs-20" />
                <p class="mt-3 fs-15 fw-medium">{{ config('invoice.business.name') }}</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="card mt-4">
                        <div class="card-body p-4">
                            <div class="text-center mt-2">
                                <h5 class="text-primary">Masuk</h5>
                                <p class="text-muted">Selamat datang — masuk untuk mengelola penyewaan.</p>
                            </div>

                            <form wire:submit="login" class="p-2 mt-4 vstack gap-3">
                                <x-field label="Email" for="email" error="email">
                                    <x-input id="email" type="email" wire:model="email" placeholder="you@company.com" autocomplete="email" required autofocus />
                                </x-field>

                                <x-field label="Kata sandi" for="password" error="password" x-data="{ reveal: false }">
                                    <div class="position-relative auth-pass-inputgroup">
                                        <x-input id="password" wire:model="password" placeholder="••••••••" autocomplete="current-password" required class="pe-5" x-bind:type="reveal ? 'text' : 'password'" type="password" />
                                        <button type="button" class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted" x-on:click="reveal = ! reveal" x-bind:aria-label="reveal ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'" aria-label="Tampilkan kata sandi">
                                            <x-icon name="eye" class="align-middle" />
                                        </button>
                                    </div>
                                </x-field>

                                <x-button type="submit" variant="success" class="w-100 mt-2" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="login">Masuk</span>
                                    <span wire:loading wire:target="login">Memproses…</span>
                                </x-button>
                            </form>
                        </div>
                    </div>

                    <p class="mt-4 text-center text-muted">Akun dibuat oleh pemilik.<br>Hubungi pemilik jika Anda butuh akses.</p>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container text-center">
            <p class="mb-0 text-muted">&copy; {{ now()->year }} {{ config('invoice.business.name') }}</p>
        </div>
    </footer>
</div>
