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

use App\App;
use App\Permissions;
use App\Config\ConfigInterface;
use App\Plugins\PluginSettings;
use App\Helpers\PermissionHelper;
use App\Addons\discordplus\DiscordPlus;

class DiscordPlusConfig
{
    public static function get(string $key, string $default = ''): string
    {
        $value = PluginSettings::getSetting(DiscordPlus::IDENTIFIER, $key);

        return $value === null || $value === '' ? $default : $value;
    }

    public static function isTruthy(string $key, string $default = 'false'): bool
    {
        return self::get($key, $default) === 'true';
    }

    public static function requireDiscordLink(): bool
    {
        return self::isTruthy('require_discord_link', 'false');
    }

    public static function allowAdminBypass(): bool
    {
        return self::isTruthy('allow_admin_bypass', 'true');
    }

    public static function blockApiKeys(): bool
    {
        return self::isTruthy('block_api_keys', 'true');
    }

    public static function gateMessage(): string
    {
        return self::get(
            'gate_message',
            'Link your Discord account to continue using this panel.'
        );
    }

    public static function isDiscordOAuthEnabled(): bool
    {
        return App::getInstance(true)
            ->getConfig()
            ->getSetting(ConfigInterface::DISCORD_OAUTH_ENABLED, 'false') === 'true';
    }

    public static function isUserDiscordLinked(array $user): bool
    {
        return ($user['discord_oauth2_linked'] ?? 'false') === 'true'
            && !empty($user['discord_oauth2_id']);
    }

    public static function isAdminBypassed(array $user): bool
    {
        if (!self::allowAdminBypass()) {
            return false;
        }

        $uuid = (string) ($user['uuid'] ?? '');
        if ($uuid === '') {
            return false;
        }

        return PermissionHelper::hasPermission($uuid, Permissions::ADMIN_ROOT, $user);
    }

    public static function shouldBlockUser(array $user): bool
    {
        if (!self::requireDiscordLink() || !self::isDiscordOAuthEnabled()) {
            return false;
        }

        if (self::isUserDiscordLinked($user)) {
            return false;
        }

        if (self::isAdminBypassed($user)) {
            return false;
        }

        return true;
    }
}
