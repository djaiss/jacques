{{-- The logo of the application, drawn at its own size with a sharper copy for dense screens. --}}
<img
  {{ $attributes->class(['shrink-0']) }}
  src="{{ asset('img/logo.webp') }}"
  srcset="{{ asset('img/logo.webp') }} 1x, {{ asset('img/logo@2x.webp') }} 2x"
  width="64"
  height="64"
  alt="{{ config('app.name') }}"
/>
