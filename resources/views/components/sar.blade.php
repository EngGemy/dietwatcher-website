@props([
    'amount' => null,
    'class' => '',
    'size' => 'inherit',
    'decimals' => 2,
])

<span dir="ltr" {{ $attributes->merge(['class' => 'inline-flex items-baseline gap-1 '.$class]) }}>
    @if($amount !== null)
        <span class="font-semibold tabular-nums">{{ number_format((float) $amount, (int) $decimals) }}</span>
    @endif
    <span class="sar-symbol" style="font-size: {{ $size }};" aria-label="{{ __('currency.symbol_label') }}">&#xFDFC;</span>
</span>
