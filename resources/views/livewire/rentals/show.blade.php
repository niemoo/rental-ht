@php
    use App\Enums\Permission;
    use App\Support\RentalBill;

    $user = auth()->user();
    $canEdit = $user->can(Permission::EditRentals) && ! $rental->isCompleted();
    $canCancel = $user->can(Permission::CancelRentals) && $rental->isCancellable();
    $canManageCharges = $user->can(Permission::ManageCharges);
    $canDeletePayments = $user->can(Permission::DeletePayments);
    $paymentStatus = $bill->status();
@endphp
<div class="mx-auto flex max-w-2xl flex-col gap-4">
    <div>
        <a href="{{ route('rentals.index') }}" class="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
            <x-icon name="arrow-left" /> Kembali
        </a>
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-xl font-semibold tracking-tight lg:text-2xl">{{ $rental->customer->name }}</h1>
            <x-status-badge :status="$timing->status">{{ $timing->pill }}</x-status-badge>
        </div>
        <div class="mt-3 flex flex-wrap items-center gap-2">
            <x-button variant="outline" size="sm" :href="route('rentals.invoice', $rental)"><x-icon name="file-text" class="size-3.5" /> Invoice</x-button>
            @if ($canEdit)
                <x-button variant="outline" size="sm" :href="route('rentals.edit', $rental)"><x-icon name="pencil" class="size-3.5" /> Ubah</x-button>
            @endif
            @if ($canCancel)
                <x-button variant="outline" size="sm" :href="route('rentals.cancel', $rental)" class="text-red-600 hover:text-red-700 dark:text-red-400"><x-icon name="x" class="size-3.5" /> Batalkan</x-button>
            @endif
        </div>
    </div>

    @if ($rental->isCancelled())
        <section class="rounded-2xl border border-border bg-muted/40 p-4 text-sm">
            <p class="font-medium">Dibatalkan {{ $rental->cancelled_at->translatedFormat('j M Y') }}{{ $rental->canceller ? ' · '.$rental->canceller->name : '' }}</p>
            @if ($rental->cancellation_reason)
                <p class="mt-1 text-muted-foreground">{{ $rental->cancellation_reason }}</p>
            @endif
        </section>
    @endif

    {{-- Overview --}}
    <section class="rounded-2xl border border-border bg-card p-4 shadow-xs">
        <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
            <div><dt class="text-muted-foreground">Nomor HP</dt><dd class="tabular-nums">{{ $rental->customer->phone }}</dd></div>
            <div><dt class="text-muted-foreground">Tanggal</dt><dd class="tabular-nums">{{ $rental->start_date->translatedFormat('j M') }} → {{ $rental->due_date->translatedFormat('j M') }}</dd></div>
            <div><dt class="text-muted-foreground">Dipesan oleh</dt><dd>{{ $rental->creator?->name ?? '—' }}</dd></div>
            <div><dt class="text-muted-foreground">Dipesan pada</dt><dd class="tabular-nums">{{ $rental->created_at->translatedFormat('j M') }}</dd></div>
        </dl>
    </section>

    {{-- Event --}}
    @if ($rental->hasEventDetails())
        <section class="rounded-2xl border border-border bg-card p-4 shadow-xs">
            <h2 class="mb-3 text-sm font-semibold">Acara</h2>
            <dl class="flex flex-col gap-3 text-sm">
                @foreach (['event_name' => 'Nama', 'event_address' => 'Alamat', 'event_social' => 'Media sosial'] as $field => $label)
                    @if (filled($rental->{$field}))
                        <div><dt class="text-muted-foreground">{{ $label }}</dt><dd>{{ $rental->{$field} }}</dd></div>
                    @endif
                @endforeach
            </dl>
        </section>
    @endif

    {{-- Units --}}
    <section class="rounded-2xl border border-border bg-card p-4 shadow-xs">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold">
                Unit <span class="font-medium tabular-nums text-muted-foreground">· {{ $rental->items->count() }}</span>
            </h2>
            @unless ($rental->isCompleted())
                <x-return-button :rental="$rental" />
            @endunless
        </div>
        <div class="flex flex-col divide-y divide-border">
            @foreach ($rental->items as $item)
                <div wire:key="item-{{ $item->id }}" class="flex items-center justify-between gap-3 py-2.5 first:pt-0 last:pb-0">
                    <x-unit-chip :code="$item->unit->code" />
                    @if ($rental->isCancelled())
                        <span class="text-xs text-muted-foreground">Dibatalkan</span>
                    @elseif ($item->isReturned())
                        <span class="flex items-center gap-2 text-xs tabular-nums text-muted-foreground">
                            @if ($item->return_condition === \App\Enums\UnitCondition::Lost)
                                Dicatat hilang {{ $item->returned_at->translatedFormat('j M') }}{{ $item->updater ? ' · '.$item->updater->name : '' }}
                            @else
                                Dikembalikan {{ $item->returned_at->translatedFormat('j M') }}{{ $item->updater ? ' · '.$item->updater->name : '' }}
                            @endif
                            @if ($item->return_condition && ! $item->return_condition->isUsable())
                                <x-condition-badge :condition="$item->return_condition" />
                            @endif
                        </span>
                    @else
                        <span class="text-xs font-medium text-amber-600 dark:text-amber-400">Belum kembali</span>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- Bill --}}
    <section class="rounded-2xl border border-border bg-card p-4 shadow-xs">
        <div class="mb-3 flex items-center justify-between gap-3">
            <h2 class="text-sm font-semibold">
                Tagihan <span class="font-medium tabular-nums text-muted-foreground">· {{ $rental->invoice_number }}</span>
            </h2>
            <x-payment-badge :status="$paymentStatus" />
        </div>

        <x-notice :message="$notice" class="mb-3" />

        <dl class="flex flex-col gap-2 text-sm">
            @if ($bill->isCancelled)
                <div class="flex justify-between gap-3">
                    <dt class="text-muted-foreground">Biaya pembatalan</dt>
                    <dd class="tabular-nums">{{ RentalBill::rupiah($bill->cancellationFee) }}</dd>
                </div>
            @else
                @foreach ($bill->lines as $line)
                    <div class="flex justify-between gap-3" wire:key="bill-line-{{ $loop->index }}">
                        <dt class="text-muted-foreground">
                            Sewa {{ $line['category'] }} <span class="tabular-nums">· {{ $line['quantity'] }} × {{ RentalBill::rupiah($line['rate']) }} × {{ $bill->days }}h</span>
                        </dt>
                        <dd class="tabular-nums">{{ RentalBill::rupiah($line['amount']) }}</dd>
                    </div>
                @endforeach
                @if ($bill->discount > 0)
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">
                            Diskon{{ $rental->discount_type === \App\Enums\DiscountType::Percent ? ' '.$rental->discount_value.'%' : '' }}
                            @if ($rental->discount_note)
                                <span class="text-xs">· {{ $rental->discount_note }}</span>
                            @endif
                        </dt>
                        <dd class="tabular-nums text-red-600 dark:text-red-400">−{{ RentalBill::rupiah($bill->discount) }}</dd>
                    </div>
                @endif
            @endif

            @foreach ($rental->charges as $charge)
                <div class="flex items-center justify-between gap-3" wire:key="charge-{{ $charge->id }}">
                    <dt class="text-muted-foreground">
                        {{ $charge->type->label() }}
                        @if ($charge->description)
                            <span class="text-xs">· {{ $charge->description }}</span>
                        @endif
                    </dt>
                    <dd class="flex items-center gap-1.5">
                        <span class="tabular-nums">{{ RentalBill::rupiah($charge->amount) }}</span>
                        @if ($canManageCharges)
                            <x-rentals.confirm-delete
                                :label="'biaya '.$charge->type->label()"
                                title="Hapus biaya"
                                :action="'deleteCharge('.$charge->id.')'"
                            >Hapus {{ strtolower($charge->type->label()) }} <strong class="text-foreground">{{ RentalBill::rupiah($charge->amount) }}</strong> dari tagihan?</x-rentals.confirm-delete>
                        @endif
                    </dd>
                </div>
            @endforeach

            <div class="flex justify-between gap-3 border-t border-border pt-2 font-medium">
                <dt>Total</dt>
                <dd class="tabular-nums">{{ RentalBill::rupiah($bill->total) }}</dd>
            </div>
            <div class="flex justify-between gap-3">
                <dt class="text-muted-foreground">Sudah dibayar</dt>
                <dd class="tabular-nums">{{ RentalBill::rupiah($bill->paid) }}</dd>
            </div>
            <div class="flex justify-between gap-3 font-semibold">
                <dt>{{ $bill->balance() < 0 ? 'Lebih bayar' : 'Sisa tagihan' }}</dt>
                <dd @class(['tabular-nums', 'text-violet-600 dark:text-violet-400' => $bill->balance() < 0, 'text-emerald-600 dark:text-emerald-400' => $bill->balance() === 0])>
                    {{ RentalBill::rupiah(abs($bill->balance())) }}
                </dd>
            </div>
        </dl>

        {{-- Payments --}}
        <div class="mt-4 border-t border-border pt-3">
            <h3 class="mb-2 text-xs font-medium text-muted-foreground">Riwayat pembayaran</h3>
            @forelse ($rental->payments as $payment)
                <div wire:key="payment-{{ $payment->id }}" class="flex items-center justify-between gap-3 py-1.5 text-sm">
                    <div class="min-w-0">
                        <div class="tabular-nums">
                            {{ $payment->paid_at->translatedFormat('j M Y') }}
                            · {{ $payment->isRefund() ? 'Pengembalian dana' : $payment->method->label() }}
                        </div>
                        <div class="truncate text-xs text-muted-foreground">
                            {{ $payment->note ? $payment->note.' · ' : '' }}dicatat {{ $payment->creator?->name ?? '—' }}
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span @class(['font-medium tabular-nums', 'text-violet-600 dark:text-violet-400' => $payment->isRefund()])>{{ RentalBill::rupiah($payment->amount) }}</span>
                        @if ($canDeletePayments)
                            <x-rentals.confirm-delete
                                :label="'pembayaran '.$payment->paid_at->translatedFormat('j M Y')"
                                title="Hapus pembayaran"
                                :action="'deletePayment('.$payment->id.')'"
                            >Hapus {{ $payment->isRefund() ? 'pengembalian dana' : 'pembayaran' }} <strong class="text-foreground">{{ RentalBill::rupiah(abs($payment->amount)) }}</strong> tanggal {{ $payment->paid_at->translatedFormat('j M Y') }}? Penghapusan tetap tercatat.</x-rentals.confirm-delete>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-muted-foreground">Belum ada pembayaran.</p>
            @endforelse
        </div>

        <div class="mt-4 flex flex-wrap justify-end gap-2">
            @if ($canManageCharges && ! $rental->isCancelled())
                <x-button variant="outline" size="sm" :href="route('rentals.charges.create', $rental)"><x-icon name="plus" class="size-3.5" /> Tambah biaya</x-button>
            @endif
            @can(Permission::RecordPayments)
                <x-button size="sm" :href="route('rentals.payments.create', $rental)"><x-icon name="plus" class="size-3.5" /> Catat pembayaran</x-button>
            @endcan
        </div>
    </section>
</div>
