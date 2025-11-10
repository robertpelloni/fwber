<x-mail::message>
# It's a Match! 💕

Congratulations! You and **{{ $matchName }}** liked each other.

@if($matchAvatarUrl)
![{{ $matchName }}]({{ $matchAvatarUrl }})
@endif

**About {{ $matchName }}:**
{{ $matchBio }}

**Compatibility Score:** {{ $compatibilityScore }}%

Start chatting now and see where it goes!

<x-mail::button :url="$appUrl . '/matches'">
View Match & Start Chatting
</x-mail::button>

Good luck! 🌟

{{ config('app.name') }}
</x-mail::message>
