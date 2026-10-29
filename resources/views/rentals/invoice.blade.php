{{--
    Printable invoice for one rental. Always rendered light, like paper, and laid out to print on one A4 page.
    Business details come from config/invoice.php.
--}}
@php
    use App\Enums\PaymentStatus;
    use App\Support\RentalBill;

    $business = config('invoice.business');
    $bank = config('invoice.bank');
    $status = $bill->status();
    $balance = $bill->balance();
    $rowNumber = 0;
    $statusBadge = match ($status) {
        PaymentStatus::Paid => 'bg-success-subtle text-success',
        PaymentStatus::Partial => 'bg-warning-subtle text-warning',
        PaymentStatus::Unpaid => 'bg-danger-subtle text-danger',
        PaymentStatus::Overpaid => 'bg-primary-subtle text-primary',
    };
@endphp
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Invoice {{ $rental->invoice_number }} · {{ config('invoice.business.name') }}</title>
        @include('partials.favicon')
        @fonts
        @include('partials.velzon-styles')
        @vite(['resources/css/app.css'])
        <style>
            @page { size: A4; margin: 0; }

            body { padding: 1.5rem 0; background: #f3f3f9; color: #495057; }
            .invoice-toolbar { max-width: 210mm; }
            .invoice-sheet { position: relative; display: flex; flex-direction: column; max-width: 210mm; min-height: 297mm; margin: 0 auto; overflow: hidden; background: #fff; box-shadow: 0 1rem 2.5rem rgba(56, 65, 74, 0.12); }
            .invoice-accent { height: 0.5rem; background: var(--vz-primary); }
            .invoice-body { display: flex; flex: 1; flex-direction: column; padding: 2.5rem 14mm 2rem; }
            .invoice-label { font-size: 0.6875rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: #878a99; }
            .invoice-panel { background: #f3f6f9; border-radius: 0.5rem; }
            .invoice-lines > thead > tr > th { border-bottom: 2px solid #212529; padding: 0 0.5rem 0.6rem; }
            .invoice-lines > tbody > tr > td { padding: 0.85rem 0.5rem; vertical-align: top; }
            .invoice-lines > :not(caption) > * > *:first-child { padding-left: 0; }
            .invoice-lines > :not(caption) > * > *:last-child { padding-right: 0; }
            .invoice-totals { width: 280px; flex-shrink: 0; }
            .invoice-balance { color: #fff; border-radius: 0.5rem; }
            .invoice-signature { width: 9rem; }
            .invoice-signature-line { margin-top: 4rem; padding-top: 0.375rem; border-top: 1px solid #ced4da; }
            .invoice-cancelled td { color: #adb5bd !important; text-decoration: line-through; }

            @media print {
                body { padding: 0; background: #fff; }
                /* Fills one A4 page (a hair under, so rounding never pushes the footer onto a second); a very long invoice still flows on. */
                .invoice-sheet { max-width: none; min-height: 296mm; overflow: visible; box-shadow: none; }
            }
        </style>
    </head>
    <body>
        <div class="invoice-toolbar d-flex align-items-center justify-content-between gap-3 mx-auto mb-3 px-3 d-print-none">
            <a href="{{ route('rentals.show', $rental) }}" class="btn btn-light d-inline-flex align-items-center gap-1"><x-icon name="arrow-left" /> Kembali</a>
            <button type="button" onclick="window.print()" class="btn btn-primary d-inline-flex align-items-center gap-1"><x-icon name="printer" /> Cetak / PDF</button>
        </div>

        <main class="invoice-sheet">
            <div class="invoice-accent"></div>

            <div class="invoice-body">
                {{-- Header --}}
                <header class="d-flex align-items-start justify-content-between gap-4">
                    <div>
                        <img src="{{ asset('images/krc-logo.png') }}" alt="KRC — {{ $business['name'] }}" style="height: 5rem; width: auto">
                        <p class="text-muted fs-12 lh-base mb-0 mt-3">
                            {{ $business['address'] }}<br>
                            {{ $business['phone'] }} · {{ $business['email'] }}
                        </p>
                    </div>
                    <div class="text-end">
                        <h1 class="display-6 fw-bold text-dark mb-1">INVOICE</h1>
                        <p class="text-muted fw-medium tabular-nums mb-3">{{ $rental->invoice_number }}</p>
                        <div class="d-flex justify-content-end flex-wrap gap-1">
                            @if ($bill->isCancelled)
                                <span class="badge bg-light text-body border fs-11 px-3 py-2">DIBATALKAN</span>
                            @endif
                            <span class="badge {{ $statusBadge }} text-uppercase fs-11 px-3 py-2">{{ $status === PaymentStatus::Partial ? 'DP · belum lunas' : $status->label() }}</span>
                        </div>
                    </div>
                </header>

                {{-- Parties & dates --}}
                <section class="invoice-panel row g-0 mt-5 p-3 fs-13">
                    <div class="col-4 pe-3">
                        <h2 class="invoice-label mb-2">Ditagihkan kepada</h2>
                        <p class="fw-semibold text-dark mb-0">{{ $rental->customer->name }}</p>
                        <p class="tabular-nums mb-0">{{ $rental->customer->phone }}</p>
                    </div>
                    <div class="col-3 pe-3">
                        <h2 class="invoice-label mb-2">Acara</h2>
                        @if ($rental->hasEventDetails())
                            <p class="fw-semibold text-dark mb-0">{{ $rental->event_name ?? '—' }}</p>
                            @if ($rental->event_address)
                                <p class="mb-0">{{ $rental->event_address }}</p>
                            @endif
                        @else
                            <p class="text-muted mb-0">—</p>
                        @endif
                    </div>
                    <div class="col-5">
                        <table class="table table-borderless table-sm fs-13 mb-0 bg-transparent">
                            @foreach ([
                                'Tanggal invoice' => $rental->created_at->translatedFormat('j M Y'),
                                'Masa sewa' => $rental->start_date->translatedFormat('j M').' – '.$rental->due_date->translatedFormat('j M Y'),
                                'Durasi' => Number::format($bill->days).' hari',
                            ] as $label => $value)
                                <tr>
                                    <td class="text-muted text-nowrap p-0 pb-1 pe-3 bg-transparent">{{ $label }}</td>
                                    <td class="text-end text-nowrap fw-medium text-dark tabular-nums p-0 pb-1 bg-transparent">{{ $value }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </section>

                @if ($bill->isCancelled)
                    <p class="border rounded px-3 py-2 fs-13 mt-4 mb-0">
                        Penyewaan ini dibatalkan pada {{ $rental->cancelled_at->translatedFormat('j M Y') }}{{ $rental->cancellation_reason ? ': '.$rental->cancellation_reason : '' }}.
                        Yang ditagihkan hanya biaya pembatalan{{ $bill->charges > 0 ? ' dan biaya tambahan' : '' }}.
                    </p>
                @endif

                {{-- Lines --}}
                <table class="table invoice-lines fs-13 mt-4 mb-0">
                    <thead>
                        <tr>
                            <th class="invoice-label" style="width: 2rem">No</th>
                            <th class="invoice-label">Barang / biaya</th>
                            <th class="invoice-label text-end">Qty</th>
                            <th class="invoice-label text-end">Hari</th>
                            <th class="invoice-label text-end">Tarif / hari</th>
                            <th class="invoice-label text-end">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bill->lines as $line)
                            <tr @class(['invoice-cancelled' => $bill->isCancelled])>
                                <td class="text-muted tabular-nums">{{ ++$rowNumber }}</td>
                                <td>
                                    <p class="fw-medium text-dark mb-1">Sewa {{ $line['category'] }}</p>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach ($line['units'] as $code)
                                            <x-unit-chip :code="$code" />
                                        @endforeach
                                    </div>
                                </td>
                                <td class="text-end tabular-nums">{{ Number::format($line['quantity']) }}</td>
                                <td class="text-end tabular-nums">{{ Number::format($bill->days) }}</td>
                                <td class="text-end tabular-nums">{{ RentalBill::rupiah($line['rate']) }}</td>
                                <td class="text-end fw-medium text-dark tabular-nums">{{ RentalBill::rupiah($line['amount']) }}</td>
                            </tr>
                        @endforeach
                        @if ($bill->isCancelled)
                            <tr>
                                <td class="text-muted tabular-nums">{{ ++$rowNumber }}</td>
                                <td class="fw-medium text-dark" colspan="4">Biaya pembatalan</td>
                                <td class="text-end fw-medium text-dark tabular-nums">{{ RentalBill::rupiah($bill->cancellationFee) }}</td>
                            </tr>
                        @endif
                        @foreach ($rental->charges as $charge)
                            <tr>
                                <td class="text-muted tabular-nums">{{ ++$rowNumber }}</td>
                                <td colspan="4">
                                    <p class="fw-medium text-dark mb-0">{{ $charge->type->label() }}</p>
                                    @if ($charge->description)
                                        <p class="text-muted fs-12 mb-0">{{ $charge->description }}</p>
                                    @endif
                                </td>
                                <td class="text-end fw-medium text-dark tabular-nums">{{ RentalBill::rupiah($charge->amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Payment info & totals --}}
                <section class="d-flex align-items-start gap-5 mt-4">
                    <div class="flex-grow-1 fs-13">
                        <h2 class="invoice-label mb-2">Pembayaran</h2>
                        <div class="border rounded p-3">
                            <p class="fw-semibold text-dark mb-0">{{ $bank['name'] }} · {{ $bank['account'] }}</p>
                            <p class="mb-0">a.n. {{ $bank['holder'] }}</p>
                        </div>
                        <p class="text-muted fs-12 mt-2 mb-0">Cantumkan nomor invoice pada keterangan transfer.</p>

                        @if ($rental->payments->isNotEmpty())
                            <h2 class="invoice-label mt-4 mb-1">Riwayat pembayaran</h2>
                            <table class="table table-sm fs-12 mb-0">
                                @foreach ($rental->payments as $payment)
                                    <tr>
                                        <td class="text-muted tabular-nums ps-0">{{ $payment->paid_at->translatedFormat('j M Y') }}</td>
                                        <td>{{ $payment->isRefund() ? 'Pengembalian dana' : $payment->method->label() }}</td>
                                        <td class="text-end fw-medium text-dark tabular-nums pe-0">{{ RentalBill::rupiah($payment->amount) }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        @endif
                    </div>

                    <div class="invoice-totals fs-13">
                        <table class="table table-borderless table-sm mb-2">
                            @unless ($bill->isCancelled)
                                <tr>
                                    <td class="text-muted ps-0">Subtotal sewa</td>
                                    <td class="text-end tabular-nums pe-0">{{ RentalBill::rupiah($bill->subtotal) }}</td>
                                </tr>
                                @if ($bill->discount > 0)
                                    <tr>
                                        <td class="text-muted ps-0">Diskon{{ $rental->discount_type === \App\Enums\DiscountType::Percent ? ' '.$rental->discount_value.'%' : '' }}</td>
                                        <td class="text-end tabular-nums text-danger pe-0">−{{ RentalBill::rupiah($bill->discount) }}</td>
                                    </tr>
                                @endif
                            @endunless
                            @if ($bill->charges > 0)
                                <tr>
                                    <td class="text-muted ps-0">Biaya tambahan</td>
                                    <td class="text-end tabular-nums pe-0">{{ RentalBill::rupiah($bill->charges) }}</td>
                                </tr>
                            @endif
                            <tr class="border-top">
                                <td class="fw-medium text-dark ps-0 pt-2">Total</td>
                                <td class="text-end fw-semibold text-dark tabular-nums pe-0 pt-2">{{ RentalBill::rupiah($bill->total) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0">Sudah dibayar</td>
                                <td class="text-end tabular-nums pe-0">−{{ RentalBill::rupiah($bill->paid) }}</td>
                            </tr>
                        </table>
                        <div @class([
                            'invoice-balance d-flex align-items-center justify-content-between px-3 py-2',
                            'bg-primary' => $balance > 0,
                            'bg-success' => $balance === 0,
                            'bg-info' => $balance < 0,
                        ])>
                            <span class="fw-medium">{{ $balance < 0 ? 'Lebih bayar' : 'Sisa tagihan' }}</span>
                            <span class="fs-17 fw-bold tabular-nums">{{ RentalBill::rupiah(abs($balance)) }}</span>
                        </div>
                    </div>
                </section>

                {{-- Terms & signatures --}}
                <section class="d-flex align-items-end gap-5 mt-auto pt-5 fs-13">
                    <div class="flex-grow-1">
                        <h2 class="invoice-label mb-2">Syarat &amp; ketentuan</h2>
                        <ol class="text-muted fs-12 lh-base ps-3 mb-0">
                            @foreach (config('invoice.terms') as $term)
                                <li>{{ $term }}</li>
                            @endforeach
                        </ol>
                    </div>
                    <div class="d-flex gap-5 text-center text-muted fs-12">
                        <div class="invoice-signature">
                            <p class="mb-0">Penyewa</p>
                            <div class="invoice-signature-line fw-medium text-dark">{{ $rental->customer->name }}</div>
                        </div>
                        <div class="invoice-signature">
                            <p class="mb-0">Hormat kami,</p>
                            <div class="invoice-signature-line fw-medium text-dark">{{ $business['name'] }}</div>
                        </div>
                    </div>
                </section>
            </div>

            <footer class="d-flex align-items-center justify-content-between border-top text-muted fs-12 py-3" style="padding-left: 14mm; padding-right: 14mm">
                <span>Terima kasih telah menyewa di {{ $business['name'] }}.</span>
                <span class="tabular-nums">{{ $rental->invoice_number }}</span>
            </footer>
        </main>
    </body>
</html>
