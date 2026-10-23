{{-- The lines table. Expects $v; $class styles the table. --}}
<table class="{{ $class ?? '' }}">
    <thead>
    <tr>
        <th style="width: 4%">#</th>
        <th>{{ $v->t('labels.description') }}</th>
        <th class="num">{{ $v->t('labels.quantity') }}</th>
        @if ($v->hasUnit())<th>{{ $v->t('labels.unit') }}</th>@endif
        <th class="num">{{ $v->t('labels.unit_price') }}</th>
        @if ($v->hasDiscount())<th class="num">{{ $v->t('labels.discount') }}</th>@endif
        <th class="num">{{ $v->t('labels.vat_rate') }}</th>
        <th class="num">{{ $v->t('labels.total') }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($v->invoice->getLines() as $i => $line)
        <tr class="{{ $i % 2 ? 'odd' : 'even' }}">
            <td>{{ $i + 1 }}</td>
            <td>
                {{ $line->description }}
                @if ($line->reference)<br><span class="muted" dir="ltr">{{ $line->reference }}</span>@endif
            </td>
            <td class="num">{{ $v->quantity($line) }}</td>
            @if ($v->hasUnit())<td>{{ $line->unit }}</td>@endif
            <td class="num">{{ $v->money($line->unitPrice, false) }}</td>
            @if ($v->hasDiscount())<td class="num">{{ $v->discount($line) }}</td>@endif
            <td class="num">{{ $line->vatRate }} %</td>
            <td class="num">{{ $v->money($line->total(), false) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
