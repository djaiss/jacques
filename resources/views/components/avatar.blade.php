{{-- How somebody is shown: the avatar they uploaded when they have one, and their initials otherwise. --}}
{{--
  @var \App\Models\Employee|null $employee
  @var string $name
  @var int $size
--}}
@props([
  'employee' => null,
  'name',
  'size' => 34,
])

@if ($employee?->hasAvatar())
  @php
    $url = fn (int $pixels): string => route('settings.avatar.show', ['employee' => $employee, 'size' => $pixels]);
  @endphp

  <x-image
    :src="$url(\App\Models\Employee::AVATAR_SIZE)"
    :srcset="$url(\App\Models\Employee::AVATAR_SIZE).' 1x, '.$url(\App\Models\Employee::AVATAR_SIZE * 2).' 2x'"
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
