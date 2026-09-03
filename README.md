# DiscordPlus

Admin Discord user search, Discord ↔ panel lookup APIs, optional force-link gate.

## User API (auth required)

| Method | Path |
|--------|------|
| GET | `/api/user/discordplus/status` |
| GET | `/api/user/discordplus/me` |
| GET | `/api/user/discordplus/lookup?discord_id=` / `?discord_username=` / `?q=` |
| GET | `/api/user/discordplus/search?q=` |
| GET | `/api/user/discordplus/users/{uuid\|username\|id}` |

Returns `uuid`, `username`, `avatar`, `discord_linked`, `discord.{id,username,name}`.

## Admin API (`admin.users.view`)

| Method | Path |
|--------|------|
| GET | `/api/admin/discordplus/users?q=` |
| GET | `/api/admin/discordplus/users/{uuid\|username\|id}` |
| GET | `/api/admin/discordplus/settings` |

## Settings

Plugins → DiscordPlus: require link, admin bypass, block API keys, gate message.
