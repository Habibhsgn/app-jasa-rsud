<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ModuleController extends Controller
{
    public function index()
    {
        $modules = Module::withCount(['permissions', 'menus'])->orderBy('order')->get();

        return view('admin.rbac.modules.index', compact('modules'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:modules,code'],
            'name' => ['required', 'string', 'max:100'],
            'icon' => ['nullable', 'string', 'max:50'],
            'order' => ['nullable', 'integer'],
        ]);

        Module::create($data);

        return back()->with('success', 'Module berhasil dibuat.');
    }

    public function update(Request $request, Module $module)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('modules', 'code')->ignore($module->id)],
            'name' => ['required', 'string', 'max:100'],
            'icon' => ['nullable', 'string', 'max:50'],
            'order' => ['nullable', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $module->update([
            ...$data,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Module berhasil diperbarui.');
    }

    public function destroy(Module $module)
    {
        if ($module->permissions()->exists() || $module->menus()->exists()) {
            return back()->with('error', 'Module masih dipakai oleh permission atau menu, pindahkan/hapus itu dulu.');
        }

        $module->delete();

        return back()->with('success', 'Module berhasil dihapus.');
    }
}
