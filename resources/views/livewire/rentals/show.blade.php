@php
    use App\Enums\Permission;
    use App\Support\RentalBill;

    $user = auth()->user();
    $canEdit = $user->can(Permission::EditRentals) && ! $rental->isCompleted();
    $canCancel = $user->can(Permission::CancelRentals) && $rental->isCancellable();
    $canManageCharges = $user->can(Permission::ManageCharges);
    $canDeletePayments = $user->can(Permission::DeletePayments);
    $paymentStatus = $bill->status();
    $balance = $bill->balance();
    $facts = [
        ['phone', 'Nomor HP', $rental->customer->phone],
        ['calendar-days', 'Tanggal', $rental->start_date->translatedFormat('j M').' → '.$rental->due_date->translatedFormat('j M Y')],
        ['user', 'Dipesan oleh', $rental->creator?->name ?? '—'],
        ['clock', 'Dipesan pada', $rental->created_at->translatedFormat('j M Y')],
    ];
@endphp
<div class="vstack gap-4">
    <x-page-header title="Detail penyewaan" :subtitle="$rental->invoice_number">
        <x-slot:action>
            <x-button variant="outline" :href="route('rentals.index')"><x-icon name="arrow-left" />Kembali</x-button>
        </x-slot:action>
    </x-page-header>

    {{-- Overview --}}
    <div class="card mb-0">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div class="flex-grow-1" style="min-width: 0">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <h4 class="mb-0 text-break">{{ $rental->customer->name }}</h4>
                        <x-status-badge :status="$timing->status">{{ $timing->pill }}</x-status-badge>
                    </div>
                    <p class="text-muted mb-0 mt-1">
                        @if ($rental->event_name)
                            {{ $rental->event_name }} ·
                        @endif
                        <span class="tabular-nums">{{ $rental->invoice_number }}</span>
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('rentals.invoice', $rental) }}" class="btn btn-soft-secondary btn-sm d-inline-flex align-items-center"><x-icon name="file-text" class="me-1" /> Invoice</a>
                    @if ($canEdit)
                        <a href="{{ route('rentals.edit', $rental) }}" class="btn btn-soft-primary btn-sm d-inline-flex align-items-center"><x-icon name="pencil" class="me-1" /> Ubah</a>
                    @endif
                    @if ($canCancel)
                        <a href="{{ route('rentals.cancel', $rental) }}" class="btn btn-soft-danger btn-sm d-inline-flex align-items-center"><x-icon name="x" class="me-1" /> Batalkan</a>
                    @endif
                </div>
            </div>

            <div class="row g-3 mt-2 pt-3 border-top border-top-dashed">
                @foreach ($facts as [$icon, $label, $value])
                    <div class="col-lg-3 col-6">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-xs flex-shrink-0">
                                <span class="avatar-title bg-light text-primary rounded fs-16"><x-icon :name="$icon" /></span>
                            </div>
                            <div style="min-width: 0">
                                <p class="text-muted mb-0 fs-12">{{ $label }}</p>
                                <h6 class="mb-0 tabular-nums text-break">{{ $value }}</h6>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @if ($rental->isCancelled())
        <div class="alert alert-secondary alert-border-left mb-0" role="status">
            <p class="fw-medium mb-0">Dibatalkan {{ $rental->cancelled_at->translatedFormat('j M Y') }}{{ $rental->canceller ? ' · '.$rental->canceller->name : '' }}</p>
            @if ($rental->cancellation_reason)
                <p class="mb-0 mt-1">{{ $rental->cancellation_reason }}</p>
            @endif
        </div>
    @endif

    <div class="row g-4">
        {{-- Bill --}}
        <div class="col-xl-8">
            <div class="card mb-0">
                <div class="card-header d-flex flex-wrap align-items-center gap-2">
                    <h5 class="card-title mb-0 flex-grow-1">
                        Tagihan <span class="text-muted fw-normal fs-13 tabular-nums">· {{ $rental->invoice_number }}</span>
                    </h5>
                    <x-payment-badge :status="$paymentStatus" />
                </div>

                <div class="card-body">
                    <x-notice :message="$notice" class="mb-3" />

                    <div class="table-responsive table-card">
                        <table class="table table-borderless align-middle mb-0">
                            <tbody>
                                @if ($bill->isCancelled)
                                    <tr>
                                        <td>Biaya pembatalan</td>
                                        <td class="text-end tabular-nums">{{ RentalBill::rupiah($bill->cancellationFee) }}</td>
                                    </tr>
                                @else
                                    @foreach ($bill->lines as $line)
                                        <tr wire:key="bill-line-{{ $loop->index }}">
                                            <td>
                                                Sewa {{ $line['category'] }}
                                                <span class="text-muted fs-13 tabular-nums">· {{ Number::format($line['quantity']) }} × {{ RentalBill::rupiah($line['rate']) }} × {{ Number::format($bill->days) }}h</span>
                                            </td>
                                            <td class="text-end tabular-nums">{{ RentalBill::rupiah($line['amount']) }}</td>
                                        </tr>
                                    @endforeach
                                    @if ($bill->discount > 0)
                                        <tr>
                                            <td>
                                                Diskon{{ $rental->discount_type === \App\Enums\DiscountType::Percent ? ' '.$rental->discount_value.'%' : '' }}
                                                @if ($rental->discount_note)
                                                    <span class="text-muted fs-13">· {{ $rental->discount_note }}</span>
                                                @endif
                                            </td>
                                            <td class="text-end tabular-nums text-danger">−{{ RentalBill::rupiah($bill->discount) }}</td>
                                        </tr>
                                    @endif
                                @endif

                                @foreach ($rental->charges as $charge)
                                    <tr wire:key="charge-{{ $charge->id }}">
                                        <td>
                                            {{ $charge->type->label() }}
                                            @if ($charge->description)
                                                <span class="text-muted fs-13">· {{ $charge->description }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex align-items-center gap-1">
                                                <span class="tabular-nums">{{ RentalBill::rupiah($charge->amount) }}</span>
                                                @if ($canManageCharges)
                                                    <x-rentals.confirm-delete
                                                        :label="'biaya '.$charge->type->label()"
                                                        title="Hapus biaya"
                                                        :action="'deleteCharge('.$charge->id.')'"
                                                    >Hapus {{ strtolower($charge->type->label()) }} <strong class="text-body">{{ RentalBill::rupiah($charge->amount) }}</strong> dari tagihan?</x-rentals.confirm-delete>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                                <tr class="border-top border-top-dashed">
                                    <th>Total</th>
                                    <th class="text-end tabular-nums">{{ RentalBill::rupiah($bill->total) }}</th>
                                </tr>
                                <tr>
                                    <td class="text-muted">Sudah dibayar</td>
                                    <td class="text-end tabular-nums">{{ RentalBill::rupiah($bill->paid) }}</td>
                                </tr>
                                <tr class="table-active">
                                    <th>{{ $balance < 0 ? 'Lebih bayar' : 'Sisa tagihan' }}</th>
                                    <th @class(['text-end tabular-nums', 'text-primary' => $balance < 0, 'text-success' => $balance === 0, 'text-danger' => $balance > 0])>
                                        {{ RentalBill::rupiah(abs($balance)) }}
                                    </th>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Payments --}}
                <div class="card-body border-top border-top-dashed">
                    <h6 class="text-uppercase text-muted fs-12 mb-3">Riwayat pembayaran</h6>
                    <div class="vstack gap-3">
                        @forelse ($rental->payments as $payment)
                            <div wire:key="payment-{{ $payment->id }}" class="d-flex align-items-center gap-3">
                                <div class="avatar-xs flex-shrink-0">
                                    <span @class(['avatar-title rounded-circle fs-16', 'bg-primary-subtle text-primary' => $payment->isRefund(), 'bg-success-subtle text-success' => ! $payment->isRefund()])>
                                        <x-icon name="wallet" />
                                    </span>
                                </div>
                                <div class="flex-grow-1" style="min-width: 0">
                                    <h6 class="mb-0 tabular-nums">
                                        {{ $payment->paid_at->translatedFormat('j M Y') }}
                                        · {{ $payment->isRefund() ? 'Pengembalian dana' : $payment->method->label() }}
                                    </h6>
                                    <p class="text-muted mb-0 fs-13 text-truncate">
                                        {{ $payment->note ? $payment->note.' · ' : '' }}dicatat {{ $payment->creator?->name ?? '—' }}
                                    </p>
                                </div>
                                <span @class(['fw-medium tabular-nums', 'text-primary' => $payment->isRefund()])>{{ RentalBill::rupiah($payment->amount) }}</span>
                                @if ($canDeletePayments)
                                    <x-rentals.confirm-delete
                                        :label="'pembayaran '.$payment->paid_at->translatedFormat('j M Y')"
                                        title="Hapus pembayaran"
                                        :action="'deletePayment('.$payment->id.')'"
                                    >Hapus {{ $payment->isRefund() ? 'pengembalian dana' : 'pembayaran' }} <strong class="text-body">{{ RentalBill::rupiah(abs($payment->amount)) }}</strong> tanggal {{ $payment->paid_at->translatedFormat('j M Y') }}? Penghapusan tetap tercatat.</x-rentals.confirm-delete>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted mb-0">Belum ada pembayaran.</p>
                        @endforelse
                    </div>
                </div>

                @if (($canManageCharges && ! $rental->isCancelled()) || $user->can(Permission::RecordPayments))
                    <div class="card-footer d-flex flex-wrap justify-content-end gap-2">
                        @if ($canManageCharges && ! $rental->isCancelled())
                            <a href="{{ route('rentals.charges.create', $rental) }}" class="btn btn-soft-secondary btn-sm d-inline-flex align-items-center"><x-icon name="plus" class="me-1" /> Tambah biaya</a>
                        @endif
                        @can(Permission::RecordPayments)
                            <a href="{{ route('rentals.payments.create', $rental) }}" class="btn btn-primary btn-sm d-inline-flex align-items-center"><x-icon name="plus" class="me-1" /> Catat pembayaran</a>
                        @endcan
                    </div>
                @endif
            </div>
        </div>

        <div class="col-xl-4">
            <div class="vstack gap-4">
                {{-- Units --}}
                <div class="card mb-0">
                    <div class="card-header d-flex align-items-center gap-2">
                        <h5 class="card-title mb-0 flex-grow-1">
                            Unit <span class="text-muted fw-normal fs-13 tabular-nums">· {{ Number::format($rental->items->count()) }}</span>
                        </h5>
                        @unless ($rental->isCompleted())
                            <x-return-button :rental="$rental" />
                        @endunless
                    </div>
                    <ul class="list-group list-group-flush">
                        @foreach ($rental->items as $item)
                            <li wire:key="item-{{ $item->id }}" class="list-group-item d-flex align-items-center justify-content-between gap-3">
                                <x-unit-chip :code="$item->unit->code" />
                                @if ($rental->isCancelled())
                                    <span class="text-muted fs-13">Dibatalkan</span>
                                @elseif ($item->isReturned())
                                    <span class="d-flex flex-wrap align-items-center justify-content-end gap-2 text-muted fs-13 tabular-nums text-end">
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
                                    <span class="fw-medium fs-13 text-warning">Belum kembali</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Event --}}
                @if ($rental->hasEventDetails())
                    <div class="card mb-0">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Acara</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled vstack gap-3 mb-0">
                                @foreach (['event_name' => 'Nama', 'event_address' => 'Alamat', 'event_social' => 'Media sosial'] as $field => $label)
                                    @if (filled($rental->{$field}))
                                        <li>
                                            <p class="text-muted mb-1 fs-12">{{ $label }}</p>
                                            <h6 class="mb-0 text-break">{{ $rental->{$field} }}</h6>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
