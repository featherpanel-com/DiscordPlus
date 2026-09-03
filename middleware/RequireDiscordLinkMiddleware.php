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

namespace App\Addons\discordplus\middleware;

use App\Helpers\ApiResponse;
use App\Middleware\MiddlewareInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Addons\discordplus\Helpers\DiscordPlusConfig;

class RequireDiscordLinkMiddleware implements MiddlewareInterface
{
    /** @var list<string> */
    public const BLOCKED_PREFIXES = [
        '/api/user/servers',
        '/api/user/webspaces',
        '/api/user/vm-instances',
        '/api/user/vms',
        '/api/user/vds',
        '/api/user/vds-chatbot',
        '/api/server/',
    ];

    public function handle(Request $request, callable $next): Response
    {
        if (!DiscordPlusConfig::requireDiscordLink()) {
            return $next($request);
        }

        if (!DiscordPlusConfig::isDiscordOAuthEnabled()) {
            return $next($request);
        }

        $path = $request->getPathInfo();
        if (!self::isBlockedPath($path)) {
            return $next($request);
        }

        if (str_starts_with($path, '/api/admin/') && DiscordPlusConfig::allowAdminBypass()) {
            return $next($request);
        }

        $user = $request->attributes->get('user');
        if (!is_array($user)) {
            return $next($request);
        }

        $authType = (string) $request->attributes->get('auth_type', 'session');
        if ($authType === 'api_key' && !DiscordPlusConfig::blockApiKeys()) {
            return $next($request);
        }

        if (!DiscordPlusConfig::shouldBlockUser($user)) {
            return $next($request);
        }

        return ApiResponse::error(
            DiscordPlusConfig::gateMessage(),
            'DISCORD_LINK_REQUIRED',
            403,
            [
                'require_discord_link' => true,
                'link_url' => '/api/user/auth/discord/link',
                'account_url' => '/dashboard/account?tab=settings',
            ]
        );
    }

    public static function isBlockedPath(string $path): bool
    {
        foreach (self::BLOCKED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
