{{--
    Printable invoice for one rental. Always rendered light, like paper, and laid out to print on one A4 page.
    Business details come from config/invoice.php.
--}}
@php
    use App\Support\RentalBill;

    $business = config('invoice.business');
    $bank = config('invoice.bank');
    $status = $bill->status();
    $rowNumber = 0;
@endphp
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Invoice {{ $rental->invoice_number }} · {{ config('invoice.business.name') }}</title>
        @fonts
        @include('partials.velzon-styles')
        @vite(['resources/css/app.css'])
        <style>
            @page { size: A4; margin: 0; }
        </style>
    </head>
    <body class="min-h-screen bg-slate-100 py-8 text-slate-800 antialiased print:bg-white print:py-0">
        <div class="mx-auto mb-4 flex max-w-[210mm] items-center justify-between gap-3 px-4 print:hidden">
            <a href="{{ route('rentals.show', $rental) }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-900">
                <x-icon name="arrow-left" /> Kembali
            </a>
            <button type="button" onclick="window.print()" class="inline-flex h-9 items-center gap-2 rounded-lg bg-primary px-4 text-sm font-medium text-white shadow-sm hover:bg-primary/90">
                <x-icon name="printer" class="size-4" /> Cetak / PDF
            </button>
        </div>

        <main class="relative mx-auto flex min-h-[297mm] max-w-[210mm] flex-col overflow-hidden bg-white shadow-xl shadow-slate-300/50 print:shadow-none">
            <div class="h-2 bg-primary"></div>

            <div class="flex flex-1 flex-col px-[14mm] pt-10 pb-8">
                {{-- Header --}}
                <header class="flex items-start justify-between gap-8">
                    <div>
                        <img src="{{ asset('images/krc-logo.png') }}" alt="KRC — {{ $business['name'] }}" class="h-20 w-auto">
                        <div class="mt-3 text-xs leading-relaxed text-slate-500">
                            {{ $business['address'] }}<br>
                            {{ $business['phone'] }} · {{ $business['email'] }}
                        </div>
                    </div>
                    <div class="text-right">
                        <h1 class="text-4xl font-bold tracking-tight text-slate-900">INVOICE</h1>
                        <p class="mt-1 text-sm font-medium tabular-nums text-slate-500">{{ $rental->invoice_number }}</p>
                        @if ($bill->isCancelled)
                            <span class="mt-3 inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold tracking-wide text-slate-600 ring-1 ring-slate-300">DIBATALKAN</span>
                        @endif
                        <span @class([
                            'mt-3 inline-flex rounded-full px-3 py-1 text-xs font-semibold tracking-wide uppercase ring-1',
                            'bg-emerald-50 text-emerald-700 ring-emerald-200' => $status === \App\Enums\PaymentStatus::Paid,
                            'bg-amber-50 text-amber-700 ring-amber-200' => $status === \App\Enums\PaymentStatus::Partial,
                            'bg-red-50 text-red-700 ring-red-200' => $status === \App\Enums\PaymentStatus::Unpaid,
                            'bg-violet-50 text-violet-700 ring-violet-200' => $status === \App\Enums\PaymentStatus::Overpaid,
                        ])>{{ $status === \App\Enums\PaymentStatus::Partial ? 'DP · belum lunas' : $status->label() }}</span>
                    </div>
                </header>

                {{-- Parties & dates --}}
                <section class="mt-10 grid grid-cols-3 gap-6 rounded-xl bg-slate-50 p-5 text-sm">
                    <div>
                        <h2 class="text-[11px] font-semibold tracking-wider text-slate-400">DITAGIHKAN KEPADA</h2>
                        <p class="mt-1.5 font-semibold text-slate-900">{{ $rental->customer->name }}</p>
                        <p class="tabular-nums text-slate-600">{{ $rental->customer->phone }}</p>
                    </div>
                    <div>
                        <h2 class="text-[11px] font-semibold tracking-wider text-slate-400">ACARA</h2>
                        @if ($rental->hasEventDetails())
                            <p class="mt-1.5 font-semibold text-slate-900">{{ $rental->event_name ?? '—' }}</p>
                            @if ($rental->event_address)
                                <p class="text-slate-600">{{ $rental->event_address }}</p>
                            @endif
                        @else
                            <p class="mt-1.5 text-slate-400">—</p>
                        @endif
                    </div>
                    <dl class="flex flex-col gap-1.5">
                        @foreach ([
                            'Tanggal invoice' => $rental->created_at->translatedFormat('j M Y'),
                            'Masa sewa' => $rental->start_date->translatedFormat('j M').' – '.$rental->due_date->translatedFormat('j M Y'),
                            'Durasi' => $bill->days.' hari',
                        ] as $label => $value)
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">{{ $label }}</dt>
                                <dd class="text-right font-medium tabular-nums text-slate-900">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>

                @if ($bill->isCancelled)
                    <p class="mt-6 rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-600">
                        Penyewaan ini dibatalkan pada {{ $rental->cancelled_at->translatedFormat('j M Y') }}{{ $rental->cancellation_reason ? ': '.$rental->cancellation_reason : '' }}.
                        Yang ditagihkan hanya biaya pembatalan{{ $bill->charges > 0 ? ' dan biaya tambahan' : '' }}.
                    </p>
                @endif

                {{-- Lines --}}
                <table class="mt-8 w-full text-sm">
                    <thead>
                        <tr class="border-b-2 border-slate-900 text-left text-[11px] font-semibold tracking-wider text-slate-500">
                            <th class="w-8 pb-2.5">NO</th>
                            <th class="pb-2.5">BARANG / BIAYA</th>
                            <th class="pb-2.5 text-right">QTY</th>
                            <th class="pb-2.5 text-right">HARI</th>
                            <th class="pb-2.5 text-right">TARIF / HARI</th>
                            <th class="pb-2.5 text-right">JUMLAH</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bill->lines as $line)
                            <tr @class(['border-b border-slate-200 align-top', 'text-slate-400 line-through' => $bill->isCancelled])>
                                <td class="py-3.5 tabular-nums text-slate-400">{{ ++$rowNumber }}</td>
                                <td class="py-3.5">
                                    <p @class(['font-medium', 'text-slate-900' => ! $bill->isCancelled])>Sewa {{ $line['category'] }}</p>
                                    <p class="mt-1 flex flex-wrap gap-1">
                                        @foreach ($line['units'] as $code)
                                            <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-medium tabular-nums text-slate-600">{{ $code }}</span>
                                        @endforeach
                                    </p>
                                </td>
                                <td class="py-3.5 text-right tabular-nums">{{ $line['quantity'] }}</td>
                                <td class="py-3.5 text-right tabular-nums">{{ $bill->days }}</td>
                                <td class="py-3.5 text-right tabular-nums">{{ RentalBill::rupiah($line['rate']) }}</td>
                                <td @class(['py-3.5 text-right font-medium tabular-nums', 'text-slate-900' => ! $bill->isCancelled])>{{ RentalBill::rupiah($line['amount']) }}</td>
                            </tr>
                        @endforeach
                        @if ($bill->isCancelled)
                            <tr class="border-b border-slate-200 align-top">
                                <td class="py-3.5 tabular-nums text-slate-400">{{ ++$rowNumber }}</td>
                                <td class="py-3.5 font-medium text-slate-900" colspan="4">Biaya pembatalan</td>
                                <td class="py-3.5 text-right font-medium tabular-nums text-slate-900">{{ RentalBill::rupiah($bill->cancellationFee) }}</td>
                            </tr>
                        @endif
                        @foreach ($rental->charges as $charge)
                            <tr class="border-b border-slate-200 align-top">
                                <td class="py-3.5 tabular-nums text-slate-400">{{ ++$rowNumber }}</td>
                                <td class="py-3.5" colspan="4">
                                    <p class="font-medium text-slate-900">{{ $charge->type->label() }}</p>
                                    @if ($charge->description)
                                        <p class="text-xs text-slate-500">{{ $charge->description }}</p>
                                    @endif
                                </td>
                                <td class="py-3.5 text-right font-medium tabular-nums text-slate-900">{{ RentalBill::rupiah($charge->amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Payment info & totals --}}
                <section class="mt-6 grid grid-cols-[1fr_280px] items-start gap-10">
                    <div class="text-sm">
                        <h2 class="text-[11px] font-semibold tracking-wider text-slate-400">PEMBAYARAN</h2>
                        <div class="mt-2 rounded-lg border border-slate-200 p-3.5">
                            <p class="font-semibold text-slate-900">{{ $bank['name'] }} · {{ $bank['account'] }}</p>
                            <p class="text-slate-600">a.n. {{ $bank['holder'] }}</p>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">Cantumkan nomor invoice pada keterangan transfer.</p>

                        @if ($rental->payments->isNotEmpty())
                            <h2 class="mt-5 text-[11px] font-semibold tracking-wider text-slate-400">RIWAYAT PEMBAYARAN</h2>
                            <table class="mt-1.5 w-full text-xs">
                                @foreach ($rental->payments as $payment)
                                    <tr class="border-b border-slate-100 last:border-0">
                                        <td class="py-1.5 tabular-nums text-slate-500">{{ $payment->paid_at->translatedFormat('j M Y') }}</td>
                                        <td class="py-1.5 text-slate-600">{{ $payment->isRefund() ? 'Pengembalian dana' : $payment->method->label() }}</td>
                                        <td class="py-1.5 text-right font-medium tabular-nums text-slate-900">{{ RentalBill::rupiah($payment->amount) }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        @endif
                    </div>

                    <dl class="flex flex-col gap-2 text-sm">
                        @unless ($bill->isCancelled)
                            <div class="flex justify-between"><dt class="text-slate-500">Subtotal sewa</dt><dd class="tabular-nums">{{ RentalBill::rupiah($bill->subtotal) }}</dd></div>
                            @if ($bill->discount > 0)
                                <div class="flex justify-between">
                                    <dt class="text-slate-500">Diskon{{ $rental->discount_type === \App\Enums\DiscountType::Percent ? ' '.$rental->discount_value.'%' : '' }}</dt>
                                    <dd class="tabular-nums text-red-600">−{{ RentalBill::rupiah($bill->discount) }}</dd>
                                </div>
                            @endif
                        @endunless
                        @if ($bill->charges > 0)
                            <div class="flex justify-between"><dt class="text-slate-500">Biaya tambahan</dt><dd class="tabular-nums">{{ RentalBill::rupiah($bill->charges) }}</dd></div>
                        @endif
                        <div class="flex justify-between border-t border-slate-200 pt-2"><dt class="font-medium text-slate-900">Total</dt><dd class="font-semibold tabular-nums text-slate-900">{{ RentalBill::rupiah($bill->total) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Sudah dibayar</dt><dd class="tabular-nums">−{{ RentalBill::rupiah($bill->paid) }}</dd></div>
                        <div @class([
                            'mt-1 flex items-center justify-between rounded-lg px-3.5 py-3 text-white',
                            'bg-primary' => $bill->balance() > 0,
                            'bg-emerald-600' => $bill->balance() === 0,
                            'bg-violet-600' => $bill->balance() < 0,
                        ])>
                            <dt class="font-medium">{{ $bill->balance() < 0 ? 'Lebih bayar' : 'Sisa tagihan' }}</dt>
                            <dd class="text-lg font-bold tabular-nums">{{ RentalBill::rupiah(abs($bill->balance())) }}</dd>
                        </div>
                    </dl>
                </section>

                {{-- Terms & signatures --}}
                <section class="mt-auto grid grid-cols-[1fr_auto] items-end gap-10 pt-12 text-sm">
                    <div>
                        <h2 class="text-[11px] font-semibold tracking-wider text-slate-400">SYARAT & KETENTUAN</h2>
                        <ol class="mt-2 list-decimal space-y-1 pl-4 text-xs leading-relaxed text-slate-500">
                            @foreach (config('invoice.terms') as $term)
                                <li>{{ $term }}</li>
                            @endforeach
                        </ol>
                    </div>
                    <div class="flex gap-10 text-center text-xs text-slate-500">
                        <div class="w-36">
                            <p>Penyewa</p>
                            <div class="mt-16 border-t border-slate-300 pt-1.5 font-medium text-slate-900">{{ $rental->customer->name }}</div>
                        </div>
                        <div class="w-36">
                            <p>Hormat kami,</p>
                            <div class="mt-16 border-t border-slate-300 pt-1.5 font-medium text-slate-900">{{ $business['name'] }}</div>
                        </div>
                    </div>
                </section>
            </div>

            <footer class="flex items-center justify-between border-t border-slate-200 px-[14mm] py-4 text-xs text-slate-400">
                <span>Terima kasih telah menyewa di {{ $business['name'] }}.</span>
                <span class="tabular-nums">{{ $rental->invoice_number }}</span>
            </footer>
        </main>
    </body>
</html>
