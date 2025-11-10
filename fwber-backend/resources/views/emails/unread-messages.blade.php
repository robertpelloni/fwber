<x-mail::message>
# You Have {{ $unreadCount }} Unread Message{{ $unreadCount > 1 ? 's' : '' }} 💬

Someone's waiting to hear from you!

@foreach($senders as $sender)
**{{ $sender['name'] }}** wrote:
> {{ $sender['message_preview'] }}

@endforeach

<x-mail::button :url="$appUrl . '/messages'">
Reply Now
</x-mail::button>

Don't keep them waiting! 😊

{{ config('app.name') }}
</x-mail::message>
