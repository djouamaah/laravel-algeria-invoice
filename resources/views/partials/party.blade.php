{{-- A seller or buyer: name, details, legal identifiers. Expects $v and $party. --}}
@if ($party)
    <div class="name">{{ $party->name }}</div>
    @foreach ($v->partyLines($party) as $line)
        {{ $line }}<br>
    @endforeach
    @foreach ($v->partyIds($party) as $id)
        <b>{{ $id['label'] }}</b> : <span dir="ltr">{{ $id['value'] }}</span><br>
    @endforeach
@endif
