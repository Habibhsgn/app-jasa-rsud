<?php

namespace App\Services;

/**
 * Class MenuService
 * @package App\Services
 */
class MenuService
{
    public function getMenus(): array
    {
        $path = resource_path('menu/menu.json');

        if (! file_exists($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true) ?? [];
    }
}
