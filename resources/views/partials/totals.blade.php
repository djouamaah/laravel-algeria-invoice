{{-- Totals: HT, VAT per rate, TTC, stamp duty, net to pay. Expects $v; $class styles the table. --}}
<table class="{{ $class ?? '' }}">
    <tr><td>{{ $v->t('labels.total_ht') }}</td><td class="num">{{ $v->money($v->totals->totalHt) }}</td></tr>
    @foreach ($v->vatRows() as $row)
        @if ($row['exempt'])
            <tr><td>{{ $row['label'] }}</td><td class="num muted">{{ $row['base'] }}</td></tr>
        @else
            <tr><td>{{ $row['label'] }} <span class="muted">({{ $row['base'] }})</span></td><td class="num">{{ $row['amount'] }}</td></tr>
        @endif
    @endforeach
    <tr><td>{{ $v->t('labels.total_ttc') }}</td><td class="num">{{ $v->money($v->totals->totalTtc) }}</td></tr>
    @if ($v->totals->stampDuty > 0)
        <tr><td>{{ $v->t('labels.stamp_duty') }}</td><td class="num">{{ $v->money($v->totals->stampDuty) }}</td></tr>
    @endif
    <tr class="grand"><td>{{ $v->t('labels.net_to_pay') }}</td><td class="num">{{ $v->money($v->totals->netToPay) }}</td></tr>
</table>
