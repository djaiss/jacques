{{-- The neutral button, for the action that walks away from the primary one. --}}
{{--
  @var string|null $href
  @var string $type
--}}
@props([
  'href',
  'type' => 'submit',
])

@php
  $classes = 'relative inline-flex cursor-pointer items-center justify-center gap-2 rounded-[10px] border-[1.5px] border-hairline-strong bg-card py-2.5 text-[15px] font-semibold whitespace-nowrap text-ink transition-colors duration-150 hover:border-ink hover:bg-hover focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none disabled:pointer-events-none disabled:bg-disabled disabled:text-on-disabled [:where(&)]:px-4';
@endphp

@isset($href)
  <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    @isset($icon)
      <span class="shrink-0">{{ $icon }}</span>
    @endisset

    {{ $slot }}
  </a>
@else
  <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    @isset($icon)
      <span class="shrink-0">{{ $icon }}</span>
    @endisset

    {{ $slot }}
  </button>
@endif
