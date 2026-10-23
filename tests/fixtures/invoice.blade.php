{{-- A custom template used by TemplatesTest: proves $v is available to any view. --}}
<p>SHOP {{ $v->invoice->getNumber() }} {{ $v->money($v->totals->netToPay, false) }}</p>
