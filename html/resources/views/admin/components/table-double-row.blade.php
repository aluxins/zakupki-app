@props([
    'cells' => [],
    'double_row' => $attributes->get('data-double-row')
])
<tr {{ $attributes }}>
    @foreach($cells as $td)
        @if($td->getAttributes()->get('data-column-selection') != $double_row)
            {!! $td !!}
        @endif
    @endforeach
</tr>
@foreach($cells as $td)
    @if($td->getAttributes()->get('data-column-selection') == $double_row)
        <tr class="shadow-2xl">
            {!! $td->setAttribute('colspan', count($cells) - 1)->mergeAttribute('class', 'font-semibold') !!}
        </tr>
    @endif
@endforeach

