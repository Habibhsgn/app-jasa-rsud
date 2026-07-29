<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolePermissionMenuSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedModules();
        $this->seedPermissions();
        $this->seedRolesAndAttachPermissions();
        $this->seedMenus();
        $this->migrateExistingUsersRoleId();
    }

    protected function seedModules(): void
    {
        $modules = [
            ['code' => 'jasa',          'name' => 'Jasa & Index Scoring',   'order' => 1],
            ['code' => 'inacbg',        'name' => 'INA-CBG',                'order' => 2],
            ['code' => 'laporan',       'name' => 'Laporan',                'order' => 3],
            ['code' => 'ruangan',       'name' => 'Master Ruangan',         'order' => 4],
            ['code' => 'pegawai',       'name' => 'Pegawai',                'order' => 5],
            ['code' => 'user',          'name' => 'User Management',        'order' => 6],
            ['code' => 'setting',       'name' => 'Setting',                'order' => 7],
            ['code' => 'top-leader',    'name' => 'Top Leader',             'order' => 8],
        ];

        foreach ($modules as $m) {
            Module::updateOrCreate(['code' => $m['code']], $m);
        }
    }

    protected function seedPermissions(): void
    {
        // code = nama route yang sudah ada di routes/web.php.
        $permissions = [
            ['code' => 'dashboard',                              'name' => 'Dashboard',                     'module' => null],

            ['code' => 'karu.jasa',                              'name' => 'Isi Jasa Ruangan 30%',          'module' => 'jasa'],
            ['code' => 'jasa.index',                             'name' => 'Master Input Jasa',              'module' => 'jasa'],
            ['code' => 'index.scoring.index',                    'name' => 'Index Scoring',                  'module' => 'jasa'],
            ['code' => 'management.index.scoring.index',         'name' => 'Verifikasi Jasa (Review Index)', 'module' => 'jasa'],

            ['code' => 'inacbg.index',                           'name' => 'INA-CBG',                        'module' => 'inacbg'],

            ['code' => 'laporan.jasa.index',                     'name' => 'Laporan Jasa',                   'module' => 'laporan'],

            ['code' => 'master.ruangan.index',                   'name' => 'Master Ruangan',                 'module' => 'ruangan'],

            ['code' => 'pegawai.index',                          'name' => 'Manajemen Data Pegawai',         'module' => 'pegawai'],
            ['code' => 'ruang-tunggu.index',                     'name' => 'Ruang Tunggu Pindah Pegawai',    'module' => 'pegawai'],

            ['code' => 'users.index',                            'name' => 'Manajemen User Login',           'module' => 'user'],

            ['code' => 'setting.index',                          'name' => 'Pengaturan Jasa',                'module' => 'setting'],
            ['code' => 'rbac.manage',                            'name' => 'Kelola Role & Akses',            'module' => 'setting'],
            ['code' => 'topleader.index',                        'name' => 'Top Leader (Perhitungan)',     'module' => 'top-leader'],
            ['code' => 'topleader.manage',                       'name' => 'Manajemen Top Leader',           'module' => 'top-leader'],
        ];

        foreach ($permissions as $p) {
            Permission::updateOrCreate(
                ['code' => $p['code']],
                [
                    'name' => $p['name'],
                    'module_id' => $p['module'] ? Module::where('code', $p['module'])->value('id') : null,
                ]
            );
        }
    }

    protected function seedRolesAndAttachPermissions(): void
    {
        // Sama persis dengan $roleAccess di sidebar blade lama kamu.
        $roleMap = [
            'admin' => [
                'name' => 'Administrator',
                'permissions' => '*', // semua permission
            ],
            'karu' => [
                'name' => 'Kepala Ruangan',
                'permissions' => ['dashboard', 'karu.jasa', 'pegawai.index', 'index.scoring.index'],
            ],
            'koordinator_karu' => [
                'name' => 'Koordinator Karu',
                'permissions' => ['dashboard', 'karu.jasa', 'pegawai.index', 'index.scoring.index'],
            ],
            'manajemen' => [
                'name' => 'Manajemen',
                'permissions' => ['dashboard', 'management.index.scoring.index', 'laporan.jasa.index', 'topleader.index'],
            ],
        ];

        foreach ($roleMap as $code => $data) {
            $role = Role::updateOrCreate(['code' => $code], ['name' => $data['name']]);

            $permissionIds = $data['permissions'] === '*'
                ? Permission::pluck('id')
                : Permission::whereIn('code', $data['permissions'])->pluck('id');

            // sync() ke pivot role_has_permission (nama tabel sudah didefinisikan di model Role)
            $role->permissions()->sync($permissionIds);
        }
    }

    protected function seedMenus(): void
    {
        // Konversi 1:1 dari resources/data/menu.json yang lama.
        // 'permissions' => array kode permission yang di-attach ke menu_has_permission
        // (kosongkan array kalau menu terbuka untuk semua user login, mis. Dashboard).
        $structure = [
            [
                'header' => 'Menu',
                'items' => [
                    ['name' => 'Dashboard', 'icon' => 'sliders', 'route' => 'dashboard', 'permissions' => ['dashboard']],
                    ['name' => 'Input Jasa', 'icon' => 'dollar-sign', 'route' => 'jasa.index', 'permissions' => ['jasa.index']],
                ],
            ],
            [
                'header' => 'Isi Jasa',
                'items' => [
                    ['name' => 'Jasa Ruangan 30%', 'icon' => 'dollar-sign', 'route' => 'karu.jasa', 'permissions' => ['karu.jasa']],
                    ['name' => 'Index Scoring', 'icon' => 'dollar-sign', 'route' => 'index.scoring.index', 'permissions' => ['index.scoring.index']],
                    ['name' => 'INA-CBG', 'icon' => 'file-text', 'route' => 'inacbg.index', 'permissions' => ['inacbg.index']],
                    ['name' => 'Top Leader', 'icon' => 'users', 'route' => 'top-leader.perhitungan', 'permissions' => ['topleader.index']],
                ],
            ],
            [
                'header' => 'Manajemen',
                'items' => [
                    ['name' => 'Verifikasi Jasa', 'icon' => 'dollar-sign', 'route' => 'management.index.scoring.index', 'permissions' => ['management.index.scoring.index']],
                ],
            ],
            [
                'header' => 'Laporan',
                'items' => [
                    ['name' => 'Laporan Jasa', 'icon' => 'file-text', 'route' => 'laporan.jasa.index', 'permissions' => ['laporan.jasa.index']],
                ],
            ],
            [
                'header' => 'Setting',
                'items' => [
                    ['name' => 'Master Ruangan', 'icon' => 'folder', 'route' => 'master.ruangan.index', 'permissions' => ['master.ruangan.index']],
                    ['name' => 'Manajemen Data Pegawai', 'icon' => 'user', 'route' => 'pegawai.index', 'permissions' => ['pegawai.index']],
                    ['name' => 'Manajemen User Login', 'icon' => 'user-check', 'route' => 'users.index', 'permissions' => ['users.index']],
                    ['name' => 'Pengaturan Jasa', 'icon' => 'settings', 'route' => 'setting.index', 'permissions' => ['setting.index']],
                    ['name' => 'Kelola Role & Akses', 'icon' => 'shield', 'route' => 'rbac.roles.index', 'permissions' => ['rbac.manage']],
                    ['name' => 'Manajemen Top Leader', 'icon' => 'user-plus', 'route' => 'top-leader.index', 'permissions' => ['topleader.manage']],
                ],
            ],
        ];

        // Bersihkan dulu supaya seeder aman dijalankan berulang saat development.
        Menu::query()->delete();

        $order = 0;

        foreach ($structure as $group) {
            $header = Menu::create([
                'name' => $group['header'],
                'is_header' => true,
                'order' => $order++,
            ]);

            foreach ($group['items'] as $item) {
                $permissionIds = Permission::whereIn('code', $item['permissions'])->pluck('id');
                $moduleId = Permission::whereIn('code', $item['permissions'])->value('module_id');

                $menu = Menu::create([
                    'parent_id' => $header->id,
                    'module_id' => $moduleId,
                    'name' => $item['name'],
                    'icon' => $item['icon'],
                    'route_name' => $item['route'],
                    'order' => $order++,
                ]);

                $menu->permissions()->sync($permissionIds);
            }
        }
    }

    /**
     * Isi role_id pada tabel users berdasarkan kolom `role` (string) yang lama.
     * Aman dijalankan berkali-kali. Kolom `role` string BELUM dihapus di sini.
     */
    protected function migrateExistingUsersRoleId(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'role')) {
            return;
        }

        User::query()->whereNull('role_id')->whereNotNull('role')->each(function (User $user) {
            $roleId = Role::where('code', $user->role)->value('id');

            if ($roleId) {
                $user->update(['role_id' => $roleId]);
            }
        });
    }
}
