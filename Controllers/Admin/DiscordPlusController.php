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

namespace App\Addons\discordplus\Controllers\Admin;

use App\Chat\Role;
use App\Helpers\ApiResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Addons\discordplus\Helpers\DiscordUserSearch;
use App\Addons\discordplus\Helpers\DiscordPlusConfig;

class DiscordPlusController
{
    public function search(Request $request): Response
    {
        $query = trim((string) $request->query->get('q', $request->query->get('discord_id', '')));
        $limit = (int) $request->query->get('limit', 25);

        if ($query === '') {
            return ApiResponse::error('Provide a Discord ID or username to search', 'MISSING_QUERY', 400);
        }

        $users = DiscordUserSearch::search($query, $limit);
        $roles = Role::getAllRoles();
        $rolesMap = [];
        foreach ($roles as $role) {
            $rolesMap[(int) $role['id']] = [
                'name' => $role['name'] ?? 'unknown',
                'display_name' => $role['display_name'] ?? 'Unknown',
                'color' => $role['color'] ?? '#666666',
            ];
        }

        foreach ($users as &$user) {
            $roleId = (int) ($user['role_id'] ?? 0);
            $user['role'] = $rolesMap[$roleId] ?? [
                'name' => 'unknown',
                'display_name' => 'Unknown',
                'color' => '#666666',
            ];
            unset($user['role_id']);
        }
        unset($user);

        return ApiResponse::success([
            'users' => $users,
            'query' => $query,
            'count' => count($users),
            'settings' => [
                'require_discord_link' => DiscordPlusConfig::requireDiscordLink(),
                'allow_admin_bypass' => DiscordPlusConfig::allowAdminBypass(),
                'discord_oauth_enabled' => DiscordPlusConfig::isDiscordOAuthEnabled(),
            ],
        ], 'Discord users fetched', 200);
    }

    public function settings(Request $request): Response
    {
        return ApiResponse::success([
            'require_discord_link' => DiscordPlusConfig::requireDiscordLink(),
            'allow_admin_bypass' => DiscordPlusConfig::allowAdminBypass(),
            'block_api_keys' => DiscordPlusConfig::blockApiKeys(),
            'gate_message' => DiscordPlusConfig::gateMessage(),
            'discord_oauth_enabled' => DiscordPlusConfig::isDiscordOAuthEnabled(),
        ], 'DiscordPlus settings fetched', 200);
    }

    public function user(Request $request, string $identifier): Response
    {
        $user = DiscordUserSearch::findByPanelUser($identifier);
        if ($user === null) {
            return ApiResponse::error('User not found', 'USER_NOT_FOUND', 404);
        }

        $linked = DiscordPlusConfig::isUserDiscordLinked($user);

        return ApiResponse::success([
            'user' => [
                'uuid' => $user['uuid'] ?? null,
                'username' => $user['username'] ?? null,
                'email' => $user['email'] ?? null,
                'avatar' => $user['avatar'] ?? null,
                'discord_linked' => $linked,
                'discord_oauth2_id' => $user['discord_oauth2_id'] ?? null,
                'discord_oauth2_username' => $user['discord_oauth2_username'] ?? null,
                'discord_oauth2_name' => $user['discord_oauth2_name'] ?? null,
                'discord_oauth2_linked' => $user['discord_oauth2_linked'] ?? 'false',
            ],
        ], 'User Discord data fetched', 200);
    }
}
