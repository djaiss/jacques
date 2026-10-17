{{--
  @var string $url
  @var string $salon
  @var string $role
  @var string $firstName
  @var \App\Helpers\StaffInvitationCopy $copy
--}}
<x-mail::message>
# {{ $copy->title() }}

{{ $copy->greeting($firstName) }}

{{ $copy->body() }}

{{ $copy->purpose() }}

<x-mail::panel>
**{{ $copy->salonLabel() }}**<br>
{{ $salon }}

**{{ $copy->roleLabel() }}**<br>
{{ $role }}
</x-mail::panel>

<x-mail::button :url="$url">
{{ $copy->button() }}
</x-mail::button>

{{ $copy->expiry() }}

{{ $copy->footer() }}
</x-mail::message>
