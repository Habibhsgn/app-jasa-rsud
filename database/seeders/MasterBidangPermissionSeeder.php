<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class MasterBidangPermissionSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Module
        |--------------------------------------------------------------------------
        */

        $module = Module::updateOrCreate(
            ['code' => 'bidang'],
            [
                'name'  => 'Master Bidang',
                'order' => 5,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Permission
        |--------------------------------------------------------------------------
        */

        $permission = Permission::updateOrCreate(
            ['code' => 'master.bidang.index'],
            [
                'name'      => 'Master Bidang',
                'module_id' => $module->id,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Cari Header Setting
        |--------------------------------------------------------------------------
        */

        $settingHeader = Menu::where('is_header', true)
            ->where('name', 'Setting')
            ->first();

        if (!$settingHeader) {
            $settingHeader = Menu::create([
                'name'      => 'Setting',
                'is_header' => true,
                'order'     => 999,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Menu
        |--------------------------------------------------------------------------
        */

        $menu = Menu::updateOrCreate(
            [
                'route_name' => 'master.bidang.index',
            ],
            [
                'parent_id' => $settingHeader->id,
                'module_id' => $module->id,
                'name'      => 'Master Bidang',
                'icon'      => 'layers',
                'order'     => 0,
            ]
        );

        $menu->permissions()->syncWithoutDetaching([
            $permission->id,
        ]);
    }
}
