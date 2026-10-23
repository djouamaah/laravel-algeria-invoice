{{-- Modern: coloured header band, seller and customer side by side, net to pay highlighted. --}}
<!doctype html>
<html lang="{{ $v->locale }}" dir="{{ $v->rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $v->title() }} {{ $v->invoice->getNumber() }}</title>
    <style>
        @page { margin: 10mm; }
        body { font-family: "DejaVu Sans", {{ $v->rtl ? 'Tahoma,' : '' }} Arial, sans-serif; font-size: 10pt; color: #1f2937; margin: 0; }
        .sheet { max-width: 190mm; margin: 0 auto; padding: 4mm 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; text-align: {{ $v->start() }}; }
        .band td { background: #1e3a5f; color: #fff; padding: 14px 16px; }
        .band .title { font-size: 22pt; font-weight: bold; letter-spacing: .5px; }
        .band .seller { font-size: 11pt; opacity: .85; }
        .band .refs { text-align: {{ $v->end() }}; line-height: 1.7; font-size: 9.5pt; }
        .accent td { background: #f59e0b; height: 4px; padding: 0; font-size: 1pt; }
        .cards { margin-top: 14px; }
        .cards > tbody > tr > td, .cards > tr > td { width: 50%; padding: 0 6px; }
        .card { background: #f3f6fa; padding: 10px 12px; }
        .card-title { font-size: 8.5pt; color: #1e3a5f; font-weight: bold; text-transform: uppercase; margin-bottom: 4px; border-bottom: 2px solid #f59e0b; padding-bottom: 3px; }
        .name { font-size: 12pt; font-weight: bold; color: #1e3a5f; margin-bottom: 2px; }
        .lines { margin-top: 16px; }
        .lines th { background: #e8eef5; color: #1e3a5f; padding: 7px 6px; font-size: 9pt; border-bottom: 2px solid #1e3a5f; }
        .lines td { padding: 7px 6px; }
        .lines tr.odd td { background: #f8fafc; }
        .num { text-align: {{ $v->end() }}; white-space: nowrap; }
        .totals td { padding: 5px 8px; }
        .totals .grand td { background: #1e3a5f; color: #fff; font-weight: bold; font-size: 12pt; padding: 8px; }
        .muted { color: #6b7280; font-size: 8.5pt; }
        .grand .muted { color: #cbd5e1; }
        .words { margin-top: 14px; padding: 9px 12px; border-{{ $v->start() }}: 4px solid #f59e0b; background: #fffbeb; }
        .footer { margin-top: 18px; padding-top: 8px; border-top: 1px solid #e5e7eb; font-size: 8.5pt; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
<div class="sheet">
    <table class="band">
        <tr>
            <td style="width: 55%">
                <div class="title">{{ $v->title() }}</div>
                <div class="seller">{{ $v->invoice->getSeller()?->name }}</div>
            </td>
            <td style="width: 45%" class="refs">
                @foreach ($v->references() as $ref)
                    {{ $ref['label'] }} : <b @if ($ref['ltr']) dir="ltr" @endif>{{ $ref['value'] }}</b><br>
                @endforeach
            </td>
        </tr>
    </table>
    <table class="accent"><tr><td></td></tr></table>

    <table class="cards">
        <tr>
            <td>
                <div class="card">
                    <div class="card-title">{{ $v->t('labels.seller') }}</div>
                    @include('algeria-invoice::partials.party', ['party' => $v->invoice->getSeller()])
                </div>
            </td>
            <td>
                <div class="card">
                    <div class="card-title">{{ $v->t('labels.buyer') }}</div>
                    @include('algeria-invoice::partials.party', ['party' => $v->invoice->getBuyer()])
                </div>
            </td>
        </tr>
    </table>

    @include('algeria-invoice::partials.lines', ['class' => 'lines'])

    <table style="margin-top: 14px">
        <tr>
            <td style="width: 48%; padding-top: 6px">
                @if ($v->payment())
                    <div class="card-title" style="border: 0">{{ $v->t('labels.payment_method') }}</div>
                    {{ $v->payment() }}
                @endif
                @if ($v->invoice->getNotes())
                    <div class="card-title" style="border: 0; margin-top: 10px">{{ $v->t('labels.notes') }}</div>
                    {{ $v->invoice->getNotes() }}
                @endif
            </td>
            <td style="width: 52%">
                @include('algeria-invoice::partials.totals', ['class' => 'totals'])
            </td>
        </tr>
    </table>

    <div class="words">{{ $v->sentence() }}</div>

    <div class="footer">
        {{ $v->invoice->getSeller()?->name }}
        @foreach ($v->partyIds($v->invoice->getSeller()) as $id)
            · {{ $id['label'] }} <span dir="ltr">{{ $id['value'] }}</span>
        @endforeach
    </div>
</div>
</body>
</html>
