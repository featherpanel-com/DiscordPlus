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

namespace App\Addons\discordplus\Controllers\User;

use App\Helpers\ApiResponse;
use App\Middleware\AuthMiddleware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Addons\discordplus\Helpers\DiscordUserSearch;
use App\Addons\discordplus\Helpers\DiscordPlusConfig;

class DiscordPlusController
{
    public function status(Request $request): Response
    {
        $user = AuthMiddleware::getCurrentUser($request);
        if ($user === null) {
            return ApiResponse::error('You are not allowed to access this resource!', 'INVALID_ACCOUNT_TOKEN', 400);
        }

        $linked = DiscordPlusConfig::isUserDiscordLinked($user);
        $adminBypass = DiscordPlusConfig::isAdminBypassed($user);
        $required = DiscordPlusConfig::requireDiscordLink()
            && DiscordPlusConfig::isDiscordOAuthEnabled();
        $blocked = DiscordPlusConfig::shouldBlockUser($user);

        return ApiResponse::success([
            'required' => $required && !$adminBypass,
            'blocked' => $blocked,
            'linked' => $linked,
            'admin_bypass' => $adminBypass,
            'discord_oauth_enabled' => DiscordPlusConfig::isDiscordOAuthEnabled(),
            'message' => DiscordPlusConfig::gateMessage(),
            'link_url' => '/api/user/auth/discord/link',
            'account_url' => '/dashboard/account?tab=settings',
            'discord' => [
                'id' => $user['discord_oauth2_id'] ?? null,
                'username' => $user['discord_oauth2_username'] ?? null,
                'name' => $user['discord_oauth2_name'] ?? null,
            ],
        ], 'DiscordPlus status fetched', 200);
    }

    public function me(Request $request): Response
    {
        $user = AuthMiddleware::getCurrentUser($request);
        if ($user === null) {
            return ApiResponse::error('You are not allowed to access this resource!', 'INVALID_ACCOUNT_TOKEN', 400);
        }

        return ApiResponse::success(
            DiscordUserSearch::toPublicUser($user),
            'Discord profile fetched',
            200
        );
    }

    public function lookup(Request $request): Response
    {
        if (AuthMiddleware::getCurrentUser($request) === null) {
            return ApiResponse::error('You are not allowed to access this resource!', 'INVALID_ACCOUNT_TOKEN', 400);
        }

        $discordId = trim((string) $request->query->get('discord_id', ''));
        $discordUsername = trim((string) $request->query->get('discord_username', ''));
        $query = trim((string) $request->query->get('q', ''));

        $user = null;
        if ($discordId !== '') {
            $user = DiscordUserSearch::findByDiscordId($discordId);
        } elseif ($discordUsername !== '') {
            $user = DiscordUserSearch::findByDiscordUsername($discordUsername);
        } elseif ($query !== '') {
            if (preg_match('/^\d{5,32}$/', $query) === 1) {
                $user = DiscordUserSearch::findByDiscordId($query);
            } else {
                $user = DiscordUserSearch::findByDiscordUsername($query);
            }
        } else {
            return ApiResponse::error(
                'Provide discord_id, discord_username, or q',
                'MISSING_QUERY',
                400
            );
        }

        if ($user === null) {
            return ApiResponse::error('No linked Discord user found', 'DISCORD_USER_NOT_FOUND', 404);
        }

        return ApiResponse::success([
            'user' => DiscordUserSearch::toPublicUser($user),
        ], 'Discord user found', 200);
    }

    public function userDiscord(Request $request, string $identifier): Response
    {
        if (AuthMiddleware::getCurrentUser($request) === null) {
            return ApiResponse::error('You are not allowed to access this resource!', 'INVALID_ACCOUNT_TOKEN', 400);
        }

        $user = DiscordUserSearch::findByPanelUser($identifier);
        if ($user === null) {
            return ApiResponse::error('User not found', 'USER_NOT_FOUND', 404);
        }

        return ApiResponse::success([
            'user' => DiscordUserSearch::toPublicUser($user),
        ], 'User Discord data fetched', 200);
    }

    public function search(Request $request): Response
    {
        if (AuthMiddleware::getCurrentUser($request) === null) {
            return ApiResponse::error('You are not allowed to access this resource!', 'INVALID_ACCOUNT_TOKEN', 400);
        }

        $query = trim((string) $request->query->get('q', ''));
        $limit = (int) $request->query->get('limit', 25);

        if ($query === '') {
            return ApiResponse::error('Provide a search query', 'MISSING_QUERY', 400);
        }

        $users = DiscordUserSearch::search($query, $limit);

        return ApiResponse::success([
            'users' => DiscordUserSearch::toPublicUsers($users),
            'query' => $query,
            'count' => count($users),
        ], 'Discord users searched', 200);
    }
}
