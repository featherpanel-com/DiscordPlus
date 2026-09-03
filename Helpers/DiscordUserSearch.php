<?php

/*
 * This file is part of FeatherPanel.
 *
 * Copyright (C) 2025 MythicalSystems Studios
 * Copyright (C) 2025 FeatherPanel Contributors
 * Copyright (C) 2025 Cassian Gherman (aka NaysKutzu)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See the LICENSE file or <https://www.gnu.org/licenses/>.
 */

namespace App\Addons\discordplus\Helpers;

use App\Chat\Database;
use App\Helpers\AvatarHelper;

class DiscordUserSearch
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function search(string $query, int $limit = 25): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $limit = max(1, min(100, $limit));

        if (preg_match('/^\d{5,32}$/', $query) === 1) {
            $exact = self::findByDiscordId($query);
            if ($exact !== null) {
                return [$exact];
            }
        }

        $pdo = Database::getPdoConnection();
        $stmt = $pdo->prepare(
            "SELECT id, uuid, username, email, first_name, last_name, role_id, banned,
                    discord_oauth2_id, discord_oauth2_linked, discord_oauth2_username, discord_oauth2_name,
                    avatar, first_seen
             FROM featherpanel_users
             WHERE deleted = 'false'
               AND (
                    discord_oauth2_id LIKE :q
                    OR discord_oauth2_username LIKE :q
                    OR discord_oauth2_name LIKE :q
                    OR username LIKE :q
               )
             ORDER BY id DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':q', '%' . $query . '%', \PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return self::enrich($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public static function findByDiscordId(string $discordId): ?array
    {
        $discordId = trim($discordId);
        if ($discordId === '') {
            return null;
        }

        $pdo = Database::getPdoConnection();
        $stmt = $pdo->prepare(
            "SELECT id, uuid, username, email, first_name, last_name, role_id, banned,
                    discord_oauth2_id, discord_oauth2_linked, discord_oauth2_username, discord_oauth2_name,
                    avatar, first_seen
             FROM featherpanel_users
             WHERE deleted = 'false'
               AND discord_oauth2_id = :discord_id
             LIMIT 1"
        );
        $stmt->execute(['discord_id' => $discordId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? self::enrich([$row])[0] : null;
    }

    public static function findByDiscordUsername(string $discordUsername): ?array
    {
        $discordUsername = trim($discordUsername);
        if ($discordUsername === '') {
            return null;
        }

        $pdo = Database::getPdoConnection();
        $stmt = $pdo->prepare(
            "SELECT id, uuid, username, email, first_name, last_name, role_id, banned,
                    discord_oauth2_id, discord_oauth2_linked, discord_oauth2_username, discord_oauth2_name,
                    avatar, first_seen
             FROM featherpanel_users
             WHERE deleted = 'false'
               AND (
                    discord_oauth2_username = :username
                    OR discord_oauth2_name = :username
               )
             LIMIT 1"
        );
        $stmt->execute(['username' => $discordUsername]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? self::enrich([$row])[0] : null;
    }

    public static function findByPanelUser(string $identifier): ?array
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        $pdo = Database::getPdoConnection();

        if (preg_match('/^[a-f0-9\-]{36}$/i', $identifier) === 1) {
            $stmt = $pdo->prepare(
                "SELECT id, uuid, username, email, first_name, last_name, role_id, banned,
                        discord_oauth2_id, discord_oauth2_linked, discord_oauth2_username, discord_oauth2_name,
                        avatar, first_seen
                 FROM featherpanel_users
                 WHERE deleted = 'false' AND uuid = :id
                 LIMIT 1"
            );
            $stmt->execute(['id' => $identifier]);
        } elseif (ctype_digit($identifier)) {
            $stmt = $pdo->prepare(
                "SELECT id, uuid, username, email, first_name, last_name, role_id, banned,
                        discord_oauth2_id, discord_oauth2_linked, discord_oauth2_username, discord_oauth2_name,
                        avatar, first_seen
                 FROM featherpanel_users
                 WHERE deleted = 'false' AND id = :id
                 LIMIT 1"
            );
            $stmt->execute(['id' => (int) $identifier]);
        } else {
            $stmt = $pdo->prepare(
                "SELECT id, uuid, username, email, first_name, last_name, role_id, banned,
                        discord_oauth2_id, discord_oauth2_linked, discord_oauth2_username, discord_oauth2_name,
                        avatar, first_seen
                 FROM featherpanel_users
                 WHERE deleted = 'false' AND username = :id
                 LIMIT 1"
            );
            $stmt->execute(['id' => $identifier]);
        }

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? self::enrich([$row])[0] : null;
    }

    /**
     * @param array<string, mixed> $user
     *
     * @return array<string, mixed>
     */
    public static function toPublicUser(array $user): array
    {
        $linked = ($user['discord_oauth2_linked'] ?? 'false') === 'true'
            && !empty($user['discord_oauth2_id']);

        return [
            'uuid' => $user['uuid'] ?? null,
            'username' => $user['username'] ?? null,
            'avatar' => $user['avatar'] ?? null,
            'discord_linked' => $linked,
            'discord' => [
                'id' => $linked ? ($user['discord_oauth2_id'] ?? null) : null,
                'username' => $linked ? ($user['discord_oauth2_username'] ?? null) : null,
                'name' => $linked ? ($user['discord_oauth2_name'] ?? null) : null,
            ],
        ];
    }

    /**
     * @param list<array<string, mixed>> $users
     *
     * @return list<array<string, mixed>>
     */
    public static function toPublicUsers(array $users): array
    {
        return array_map(static fn (array $user) => self::toPublicUser($user), $users);
    }

    /**
     * @param list<array<string, mixed>> $users
     *
     * @return list<array<string, mixed>>
     */
    private static function enrich(array $users): array
    {
        try {
            return AvatarHelper::enrichUsers($users);
        } catch (\Throwable) {
            return $users;
        }
    }
}
