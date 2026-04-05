<x-mail::message>
# {{ $alertTitle }}

{{ $alertBody }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
