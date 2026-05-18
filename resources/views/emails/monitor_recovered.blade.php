<x-mail::message>
# Site recovered

Your monitor for **{{ $monitor->url }}** is responding again and is now marked as **UP**.

<x-mail::button :url="$monitor->url">
Open Site
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
