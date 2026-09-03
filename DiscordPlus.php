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

namespace App\Addons\discordplus;

use App\Plugins\AppPlugin;
use App\Plugins\PluginEvents;
use App\Plugins\PluginSettings;
use App\Plugins\Events\Events\AppEvent;
use App\Addons\discordplus\Helpers\RouteGuard;

class DiscordPlus implements AppPlugin
{
    public const IDENTIFIER = 'discordplus';

    public static function processEvents(PluginEvents $event): void
    {
        $event->on(AppEvent::onRouterReady(), function ($payload) {
            $router = is_array($payload) ? ($payload['router'] ?? null) : $payload;
            if ($router !== null) {
                RouteGuard::attach($router);
            }
        });
    }

    public static function pluginInstall(): void
    {
        $defaults = [
            'require_discord_link' => 'false',
            'allow_admin_bypass' => 'true',
            'block_api_keys' => 'true',
            'gate_message' => 'Link your Discord account to continue using this panel.',
        ];

        foreach ($defaults as $key => $value) {
            if (PluginSettings::getSetting(self::IDENTIFIER, $key) === null) {
                PluginSettings::setSetting(self::IDENTIFIER, $key, $value);
            }
        }
    }

    public static function pluginUpdate(?string $oldVersion, ?string $newVersion): void
    {
        self::pluginInstall();
    }

    public static function pluginUninstall(): void
    {
        // Settings remain in featherpanel_addons_settings until manually cleared.
    }
}
