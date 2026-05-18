<x-mail::message>
# Site is DOWN

Your monitor for **{{ $monitor->url }}** has crossed its failure threshold and is now marked as **DOWN**.

<x-mail::button :url="$monitor->url">
Open Site
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
