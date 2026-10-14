{{-- The drawing on the square beside a setting, one per kind of setting. --}}
{{-- @var string $name --}}
@props([
  'name',
])

<svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  @switch($name)
    @case('profile')
      <circle cx="12" cy="8" r="3.4"></circle>
      <path d="M5 20c0-3.9 3.1-6.2 7-6.2s7 2.3 7 6.2"></path>
      @break

    @case('security')
      <rect x="5" y="10.5" width="14" height="9.5" rx="2.2"></rect>
      <path d="M8.5 10.5V8a3.5 3.5 0 0 1 7 0v2.5"></path>
      @break

    @case('password')
      <rect x="3.5" y="9.5" width="17" height="6" rx="3"></rect>
      <path d="M8 12.5h.01M12 12.5h.01M16 12.5h.01"></path>
      @break

    @case('two-factor')
      <rect x="7" y="2.5" width="10" height="19" rx="2.4"></rect>
      <path d="M9.6 11.4l1.7 1.8 3.3-3.6"></path>
      @break

    @case('api-keys')
      <circle cx="15.4" cy="8.6" r="4"></circle>
      <path d="M12.6 11.4 4.5 19.5"></path>
      <path d="M7 17l2 2"></path>
      @break

    @case('preferences')
      <path d="M4 8h16M4 16h16"></path>
      <circle cx="9.5" cy="8" r="2.2"></circle>
      <circle cx="15" cy="16" r="2.2"></circle>
      @break

    @case('emails')
      <rect x="3" y="5.5" width="18" height="13" rx="2.2"></rect>
      <path d="m3.6 7 8.4 6 8.4-6"></path>
      @break

    @case('logs')
      <rect x="5" y="3" width="14" height="18" rx="2.2"></rect>
      <path d="M9 8.5h6M9 12.5h6M9 16.5h4"></path>
      @break

    @case('details')
      <rect x="5" y="3" width="14" height="18" rx="2.2"></rect>
      <path d="M9 8.5h6M9 12.5h6M9 16.5h4"></path>
      @break

    @case('locations')
      <rect x="4.5" y="4" width="15" height="16" rx="2"></rect>
      <path d="M8.5 8h2M13.5 8h2M8.5 12h2M13.5 12h2M8.5 16h2M13.5 16h2"></path>
      @break

    @case('countries')
      <circle cx="12" cy="12" r="8.5"></circle>
      <path d="M3.5 12h17M12 3.5c2.2 2.4 3.3 5.2 3.3 8.5S14.2 18.1 12 20.5c-2.2-2.4-3.3-5.2-3.3-8.5S9.8 5.9 12 3.5Z"></path>
      @break

    @case('time-zones')
      <circle cx="12" cy="12" r="8.5"></circle>
      <path d="M12 7.5V12l3 2"></path>
      @break

    @case('roles')
      <path d="M12 3.2 19 6v5.6c0 4.4-2.9 7.4-7 9.2-4.1-1.8-7-4.8-7-9.2V6z"></path>
      @break
  @endswitch
</svg>
