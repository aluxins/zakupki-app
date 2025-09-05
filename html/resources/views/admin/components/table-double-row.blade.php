@props([
    'cells' => [],
    'double_row' => explode(',', $attributes->get('data-double-row', '')),
    'td_double' => [],
])
<tr {{ $attributes }}>
    @foreach($cells as $td)
        @if(in_array($td->getAttributes()->get('data-column-selection'), $double_row)
            and !is_null($td->getAttributes()->get('data-column-selection')))
                <?php $td_double[] = $td ?>
        @else
            {!! $td !!}
        @endif
    @endforeach
</tr>
@if(count($td_double))
    <tr {{ $attributes }}>
        @foreach($td_double as $key => $td)
            {!! $td->setAttribute('colspan',
                    intdiv(count($cells) - count($td_double), count($td_double)) +
                    ($key === array_key_last($td_double) and count($cells) % count($td_double) === 1 ? 1 : 0)
                )
                ->mergeAttribute('class', '') !!}
        @endforeach
    </tr>
    <tr>
    </tr>
@endif

