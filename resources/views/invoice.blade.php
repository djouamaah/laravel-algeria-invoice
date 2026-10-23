@php
    use Djouamaah\AlgeriaInvoice\Money;
    $t = fn (string $key, array $replace = []) => __('algeria-invoice::invoice.'.$key, $replace);
    $money = fn (int $centimes) => Money::format($centimes, $locale);
    $seller = $invoice->getSeller();
    $buyer = $invoice->getBuyer();
    $start = $rtl ? 'right' : 'left';
    $end = $rtl ? 'left' : 'right';
    $hasDiscount = collect($invoice->getLines())->contains(fn ($l) => $l->discount > 0);
    $hasUnit = collect($invoice->getLines())->contains(fn ($l) => filled($l->unit));
    $party = function ($p) use ($t, $money) {
        if (! $p) return '';
        $rows = [];
        if ($p->legalForm) {
            $rows[] = e($p->legalForm).($p->capital !== null ? ' - '.e($t('labels.capital')).' : '.e($money(Money::toCentimes($p->capital))) : '');
        }
        if ($p->address) $rows[] = e($p->address);
        $contact = array_filter([$p->phone ? $t('labels.phone').' : '.$p->phone : null, $p->email]);
        if ($contact) $rows[] = e(implode(' - ', $contact));
        $ids = [];
        foreach (['rc', 'nif', 'nis', 'ai'] as $id) {
            if ($p->{$id}) $ids[] = '<b>'.e($t('labels.'.$id)).'</b> : <span dir="ltr">'.e($p->{$id}).'</span>';
        }
        if ($ids) $rows[] = implode('<br>', $ids);
        if ($p->rib) $rows[] = '<b>'.e($t('labels.rib')).'</b> : <span dir="ltr">'.e($p->rib).'</span>';

        return '<div class="party-name">'.e($p->name).'</div>'.implode('<br>', $rows);
    };
@endphp
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $t('titles.'.$invoice->getType()->value) }} {{ $invoice->getNumber() }}</title>
    <style>
        @page { margin: 12mm; }
        body { font-family: {{ $rtl ? '"DejaVu Sans", Tahoma, Arial' : '"DejaVu Sans", Arial, Helvetica' }}, sans-serif; font-size: 10.5pt; color: #222; margin: 0; }
        .sheet { max-width: 190mm; margin: 0 auto; padding: 6mm 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; text-align: {{ $start }}; }
        .head td { padding: 0; }
        .party-name { font-size: 12.5pt; font-weight: bold; margin-bottom: 2px; }
        .title { font-size: 20pt; font-weight: bold; color: #0b6e4f; text-align: {{ $end }}; }
        .meta { text-align: {{ $end }}; line-height: 1.6; }
        .box { border: 1px solid #cfd6d2; padding: 8px 10px; }
        .box-title { font-size: 8.5pt; text-transform: uppercase; color: #66736b; margin-bottom: 4px; }
        .lines { margin-top: 14px; }
        .lines th { background: #0b6e4f; color: #fff; padding: 6px; font-size: 9pt; }
        .lines td { padding: 6px; border-bottom: 1px solid #e3e7e4; }
        .num { text-align: {{ $end }}; white-space: nowrap; }
        .totals td { padding: 4px 6px; }
        .totals .grand td { font-weight: bold; font-size: 11.5pt; border-top: 2px solid #0b6e4f; }
        .muted { color: #66736b; font-size: 9pt; }
        .words { margin-top: 14px; padding: 8px 10px; background: #eef5f1; }
    </style>
</head>
<body>
<div class="sheet">
    <table class="head">
        <tr>
            <td style="width: 58%">{!! $party($seller) !!}</td>
            <td style="width: 42%">
                <div class="title">{{ $t('titles.'.$invoice->getType()->value) }}</div>
                <div class="meta">
                    @if ($invoice->getNumber())
                        <b>{{ $t('labels.number') }}</b> <span dir="ltr">{{ $invoice->getNumber() }}</span><br>
                    @endif
                    <b>{{ $t('labels.date') }}</b> : {{ $invoice->getDate()->format('d/m/Y') }}<br>
                    @if ($invoice->getDueDate())
                        <b>{{ $t('labels.due_date') }}</b> : {{ $invoice->getDueDate()->format('d/m/Y') }}<br>
                    @endif
                    @if ($invoice->getOriginalNumber())
                        <b>{{ $t('labels.original') }}</b> : <span dir="ltr">{{ $invoice->getOriginalNumber() }}</span><br>
                    @endif
                    @if ($invoice->getOrderReference())
                        <b>{{ $t('labels.order') }}</b> : <span dir="ltr">{{ $invoice->getOrderReference() }}</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table style="margin-top: 14px">
        <tr>
            <td style="width: 42%"></td>
            <td style="width: 58%" class="box">
                <div class="box-title">{{ $t('labels.buyer') }}</div>
                {!! $party($buyer) !!}
            </td>
        </tr>
    </table>

    <table class="lines">
        <thead>
        <tr>
            <th style="width: 4%">#</th>
            <th>{{ $t('labels.description') }}</th>
            <th class="num">{{ $t('labels.quantity') }}</th>
            @if ($hasUnit)<th>{{ $t('labels.unit') }}</th>@endif
            <th class="num">{{ $t('labels.unit_price') }}</th>
            @if ($hasDiscount)<th class="num">{{ $t('labels.discount') }}</th>@endif
            <th class="num">{{ $t('labels.vat_rate') }}</th>
            <th class="num">{{ $t('labels.total') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($invoice->getLines() as $i => $line)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>
                    {{ $line->description }}
                    @if ($line->reference)<br><span class="muted" dir="ltr">{{ $line->reference }}</span>@endif
                </td>
                <td class="num">{{ rtrim(rtrim(number_format($line->quantity, 3, ',', ' '), '0'), ',') }}</td>
                @if ($hasUnit)<td>{{ $line->unit }}</td>@endif
                <td class="num">{{ Money::format($line->unitPrice, $locale, false) }}</td>
                @if ($hasDiscount)<td class="num">{{ $line->discount ? rtrim(rtrim(number_format($line->discount, 2, ',', ''), '0'), ',').' %' : '' }}</td>@endif
                <td class="num">{{ $line->vatRate }} %</td>
                <td class="num">{{ Money::format($line->total(), $locale, false) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table style="margin-top: 12px">
        <tr>
            <td style="width: 50%">
                @if ($invoice->getPaymentMethod())
                    <b>{{ $t('labels.payment_method') }}</b> : {{ $t('payment.'.$invoice->getPaymentMethod()->value) }}
                @endif
                @if ($invoice->getNotes())
                    <p><b>{{ $t('labels.notes') }}</b> : {{ $invoice->getNotes() }}</p>
                @endif
            </td>
            <td style="width: 50%">
                <table class="totals">
                    <tr><td>{{ $t('labels.total_ht') }}</td><td class="num">{{ $money($totals->totalHt) }}</td></tr>
                    @foreach ($totals->vatBreakdown as $rate => $row)
                        @if ((float) $rate > 0)
                            <tr><td>{{ $t('labels.vat', ['rate' => $rate]) }} <span class="muted">({{ $money($row['base']) }})</span></td><td class="num">{{ $money($row['vat']) }}</td></tr>
                        @else
                            <tr><td>{{ $t('labels.vat_exempt') }}</td><td class="num muted">{{ $money($row['base']) }}</td></tr>
                        @endif
                    @endforeach
                    <tr><td>{{ $t('labels.total_ttc') }}</td><td class="num">{{ $money($totals->totalTtc) }}</td></tr>
                    @if ($totals->stampDuty > 0)
                        <tr><td>{{ $t('labels.stamp_duty') }}</td><td class="num">{{ $money($totals->stampDuty) }}</td></tr>
                    @endif
                    <tr class="grand"><td>{{ $t('labels.net_to_pay') }}</td><td class="num">{{ $money($totals->netToPay) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="words">{{ $sentence }}</div>
</div>
</body>
</html>
