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

use App\App;
use App\Permissions;
use App\Helpers\ApiResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;
use App\Addons\discordplus\Controllers\Admin\DiscordPlusController as AdminController;
use App\Addons\discordplus\Controllers\User\DiscordPlusController as UserController;

return function (RouteCollection $routes): void {
    App::getInstance(true)->registerAuthRoute(
        $routes,
        'discordplus-user-status',
        '/api/user/discordplus/status',
        function (Request $request) {
            return (new UserController())->status($request);
        },
        ['GET']
    );

    App::getInstance(true)->registerAuthRoute(
        $routes,
        'discordplus-user-me',
        '/api/user/discordplus/me',
        function (Request $request) {
            return (new UserController())->me($request);
        },
        ['GET']
    );

    App::getInstance(true)->registerAuthRoute(
        $routes,
        'discordplus-user-lookup',
        '/api/user/discordplus/lookup',
        function (Request $request) {
            return (new UserController())->lookup($request);
        },
        ['GET']
    );

    App::getInstance(true)->registerAuthRoute(
        $routes,
        'discordplus-user-search',
        '/api/user/discordplus/search',
        function (Request $request) {
            return (new UserController())->search($request);
        },
        ['GET']
    );

    App::getInstance(true)->registerAuthRoute(
        $routes,
        'discordplus-user-by-identifier',
        '/api/user/discordplus/users/{identifier}',
        function (Request $request, array $args) {
            $identifier = $args['identifier'] ?? null;
            if (!$identifier || !is_string($identifier)) {
                return ApiResponse::error('Missing or invalid identifier', 'INVALID_IDENTIFIER', 400);
            }

            return (new UserController())->userDiscord($request, $identifier);
        },
        ['GET']
    );

    App::getInstance(true)->registerAdminRoute(
        $routes,
        'discordplus-admin-user',
        '/api/admin/discordplus/users/{identifier}',
        function (Request $request, array $args) {
            $identifier = $args['identifier'] ?? null;
            if (!$identifier || !is_string($identifier)) {
                return ApiResponse::error('Missing or invalid identifier', 'INVALID_IDENTIFIER', 400);
            }

            return (new AdminController())->user($request, $identifier);
        },
        Permissions::ADMIN_USERS_VIEW,
        ['GET']
    );

    App::getInstance(true)->registerAdminRoute(
        $routes,
        'discordplus-admin-search',
        '/api/admin/discordplus/users',
        function (Request $request) {
            return (new AdminController())->search($request);
        },
        Permissions::ADMIN_USERS_VIEW,
        ['GET']
    );

    App::getInstance(true)->registerAdminRoute(
        $routes,
        'discordplus-admin-settings',
        '/api/admin/discordplus/settings',
        function (Request $request) {
            return (new AdminController())->settings($request);
        },
        Permissions::ADMIN_USERS_VIEW,
        ['GET']
    );
};
