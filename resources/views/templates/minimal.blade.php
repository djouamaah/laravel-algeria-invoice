{{-- Minimal: black and white, fully bordered grid, compact; prints well on any printer. --}}
<!doctype html>
<html lang="{{ $v->locale }}" dir="{{ $v->rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $v->title() }} {{ $v->invoice->getNumber() }}</title>
    <style>
        @page { margin: 10mm; }
        body { font-family: "DejaVu Sans", {{ $v->rtl ? 'Tahoma,' : '' }} Arial, sans-serif; font-size: 9.5pt; color: #000; margin: 0; }
        .sheet { max-width: 190mm; margin: 0 auto; padding: 4mm 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; text-align: {{ $v->start() }}; }
        .title { text-align: center; font-size: 15pt; font-weight: bold; letter-spacing: 2px; text-transform: uppercase; padding: 4px 0; border-top: 2px solid #000; border-bottom: 2px solid #000; }
        .refs { text-align: center; margin: 6px 0 12px; }
        .refs span.ref { margin: 0 8px; }
        .parties td.party { width: 50%; border: 1px solid #000; padding: 6px 8px; }
        .label { font-size: 8pt; font-weight: bold; text-transform: uppercase; margin-bottom: 3px; }
        .name { font-weight: bold; font-size: 10.5pt; }
        .lines { margin-top: 10px; }
        .lines th, .lines td { border: 1px solid #000; padding: 4px 5px; }
        .lines th { font-size: 8.5pt; }
        .num { text-align: {{ $v->end() }}; white-space: nowrap; }
        .totals td { border: 1px solid #000; padding: 4px 6px; }
        .grand td { font-weight: bold; border-width: 2px; }
        .muted { font-size: 8pt; }
        .words { margin-top: 10px; padding: 6px 0; border-top: 1px solid #000; border-bottom: 1px solid #000; font-style: italic; }
        .sign td { padding-top: 18px; height: 22mm; }
    </style>
</head>
<body>
<div class="sheet">
    <div class="title">{{ $v->title() }}</div>
    <div class="refs">
        @foreach ($v->references() as $ref)
            <span class="ref">{{ $ref['label'] }} : <b @if ($ref['ltr']) dir="ltr" @endif>{{ $ref['value'] }}</b></span>
        @endforeach
    </div>

    <table class="parties">
        <tr>
            <td class="party">
                <div class="label">{{ $v->t('labels.seller') }}</div>
                @include('algeria-invoice::partials.party', ['party' => $v->invoice->getSeller()])
            </td>
            <td class="party">
                <div class="label">{{ $v->t('labels.buyer') }}</div>
                @include('algeria-invoice::partials.party', ['party' => $v->invoice->getBuyer()])
            </td>
        </tr>
    </table>

    @include('algeria-invoice::partials.lines', ['class' => 'lines'])

    <table style="margin-top: 10px">
        <tr>
            <td style="width: 50%; padding-{{ $v->end() }}: 10px">
                @if ($v->payment())
                    <b>{{ $v->t('labels.payment_method') }}</b> : {{ $v->payment() }}<br>
                @endif
                @if ($v->invoice->getNotes())
                    <b>{{ $v->t('labels.notes') }}</b> : {{ $v->invoice->getNotes() }}
                @endif
            </td>
            <td style="width: 50%">
                @include('algeria-invoice::partials.totals', ['class' => 'totals'])
            </td>
        </tr>
    </table>

    <div class="words">{{ $v->sentence() }}</div>

    <table class="sign">
        <tr><td style="width: 50%"></td><td style="width: 50%; text-align: center"><b>{{ $v->t('labels.signature') }}</b></td></tr>
    </table>
</div>
</body>
</html>
