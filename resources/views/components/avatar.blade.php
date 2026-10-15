{{-- How somebody is shown: the avatar they uploaded when they have one, and their initials otherwise. --}}
{{--
  @var \App\Models\Staff|null $staff
  @var string $name
  @var int $size
--}}
@props([
  'staff' => null,
  'name',
  'size' => 34,
])

@if ($staff?->hasAvatar())
  @php
    $url = fn (int $pixels): string => route('settings.avatar.show', ['staff' => $staff, 'size' => $pixels]);
  @endphp

  <x-image
    :src="$url(\App\Models\Staff::AVATAR_SIZE)"
    :srcset="$url(\App\Models\Staff::AVATAR_SIZE).' 1x, '.$url(\App\Models\Staff::AVATAR_SIZE * 2).' 2x'"
    alt=""
    :width="$size"
    :height="$size"
    loading="eager"
    {{ $attributes->class(['shrink-0 rounded-full object-cover']) }}
    style="width: {{ $size }}px; height: {{ $size }}px;"
  />
@else
  <x-avatar-initials :name="$name" :size="$size" {{ $attributes }} />
@endif
