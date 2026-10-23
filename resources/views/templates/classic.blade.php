{{-- Classic: seller at the top, customer box, green header row. --}}
<!doctype html>
<html lang="{{ $v->locale }}" dir="{{ $v->rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $v->title() }} {{ $v->invoice->getNumber() }}</title>
    <style>
        @page { margin: 12mm; }
        body { font-family: "DejaVu Sans", {{ $v->rtl ? 'Tahoma,' : '' }} Arial, sans-serif; font-size: 10.5pt; color: #222; margin: 0; }
        .sheet { max-width: 190mm; margin: 0 auto; padding: 6mm 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; text-align: {{ $v->start() }}; }
        .name { font-size: 12.5pt; font-weight: bold; margin-bottom: 2px; }
        .title { font-size: 20pt; font-weight: bold; color: #0b6e4f; text-align: {{ $v->end() }}; }
        .meta { text-align: {{ $v->end() }}; line-height: 1.6; }
        .box { border: 1px solid #cfd6d2; padding: 8px 10px; }
        .box-title { font-size: 8.5pt; text-transform: uppercase; color: #66736b; margin-bottom: 4px; }
        .lines { margin-top: 14px; }
        .lines th { background: #0b6e4f; color: #fff; padding: 6px; font-size: 9pt; }
        .lines td { padding: 6px; border-bottom: 1px solid #e3e7e4; }
        .num { text-align: {{ $v->end() }}; white-space: nowrap; }
        .totals td { padding: 4px 6px; }
        .grand td { font-weight: bold; font-size: 11.5pt; border-top: 2px solid #0b6e4f; }
        .muted { color: #66736b; font-size: 9pt; }
        .words { margin-top: 14px; padding: 8px 10px; background: #eef5f1; }
    </style>
</head>
<body>
<div class="sheet">
    <table>
        <tr>
            <td style="width: 58%">
                @include('algeria-invoice::partials.party', ['party' => $v->invoice->getSeller()])
            </td>
            <td style="width: 42%">
                <div class="title">{{ $v->title() }}</div>
                <div class="meta">
                    @foreach ($v->references() as $ref)
                        <b>{{ $ref['label'] }}</b> : <span @if ($ref['ltr']) dir="ltr" @endif>{{ $ref['value'] }}</span><br>
                    @endforeach
                </div>
            </td>
        </tr>
    </table>

    <table style="margin-top: 14px">
        <tr>
            <td style="width: 42%"></td>
            <td style="width: 58%" class="box">
                <div class="box-title">{{ $v->t('labels.buyer') }}</div>
                @include('algeria-invoice::partials.party', ['party' => $v->invoice->getBuyer()])
            </td>
        </tr>
    </table>

    @include('algeria-invoice::partials.lines', ['class' => 'lines'])

    <table style="margin-top: 12px">
        <tr>
            <td style="width: 50%">
                @if ($v->payment())
                    <b>{{ $v->t('labels.payment_method') }}</b> : {{ $v->payment() }}
                @endif
                @if ($v->invoice->getNotes())
                    <p><b>{{ $v->t('labels.notes') }}</b> : {{ $v->invoice->getNotes() }}</p>
                @endif
            </td>
            <td style="width: 50%">
                @include('algeria-invoice::partials.totals', ['class' => 'totals'])
            </td>
        </tr>
    </table>

    <div class="words">{{ $v->sentence() }}</div>
</div>
</body>
</html>
