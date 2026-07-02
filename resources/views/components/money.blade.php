@props(['cents' => 0])
<span {{ $attributes }}>{{ \App\Support\Money::format((int) $cents) }}</span>
