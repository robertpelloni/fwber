# Group Realtime Events

Private channel: `private-groups.{groupId}`

Authorize: routes/channels.php ensures only active group members can subscribe.

## Event names and payloads

- `group.role.changed`
  - `{ group_id, actor_user_id, target_user_id, new_role }`
- `group.ownership.transferred`
  - `{ group_id, from_user_id, to_user_id }`
- `group.member.banned`
  - `{ group_id, actor_user_id, target_user_id, reason }`
- `group.member.unbanned`
  - `{ group_id, actor_user_id, target_user_id }`
- `group.member.muted`
  - `{ group_id, actor_user_id, target_user_id, muted_until, reason }`
- `group.member.unmuted`
  - `{ group_id, actor_user_id, target_user_id }`
- `group.member.kicked`
  - `{ group_id, actor_user_id, target_user_id }`

## Frontend (Laravel Echo + Pusher example)

```ts
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

const echo = new Echo({
  broadcaster: 'pusher',
  key: import.meta.env.VITE_PUSHER_KEY,
  cluster: import.meta.env.VITE_PUSHER_CLUSTER,
  wsHost: window.location.hostname,
  wsPort: 6001,
  forceTLS: false,
  disableStats: true,
  authEndpoint: '/broadcasting/auth',
  auth: { headers: { Authorization: `Bearer ${token}` } },
})

const groupId = 123
echo.private(`groups.${groupId}`)
  .listen('.group.role.changed', (e) => {
    console.log('role changed', e)
  })
  .listen('.group.ownership.transferred', (e) => {
    console.log('ownership transferred', e)
  })
  .listen('.group.member.banned', (e) => {
    console.log('member banned', e)
  })
  .listen('.group.member.unbanned', (e) => {
    console.log('member unbanned', e)
  })
  .listen('.group.member.muted', (e) => {
    console.log('member muted', e)
  })
  .listen('.group.member.unmuted', (e) => {
    console.log('member unmuted', e)
  })
  .listen('.group.member.kicked', (e) => {
    console.log('member kicked', e)
  })
```

Notes:
- Use `.listen('.eventName', handler)` when events define `broadcastAs()`.
- Make sure your broadcast driver is configured in `.env` and `config/broadcasting.php` (Pusher or Laravel WebSockets).
- API requests must send the bearer token so `/broadcasting/auth` can authorize the private channel.
