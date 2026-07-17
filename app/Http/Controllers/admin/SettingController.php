<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(
        protected SettingService $settingService
    ) {}

    /**
     * Display settings page.
     */
    public function index()
    {
        $settings = $this->settingService->getAll();

        return view('admin.setting.index', compact('settings'));
    }

    /**
     * Update settings.
     */
    public function update(Request $request)
    {
        $request->validate([
            'value'       => 'required|array',
            'value.*'     => 'nullable|string|max:500',
            'label.*'     => 'nullable|string|max:200',
            'description.*' => 'nullable|string|max:500',
        ]);

        $result = $this->settingService->validateAndUpdate(
            $request->value ?? [],
            $request->label ?? [],
            $request->description ?? [],
        );

        if (!$result['success']) {
            return redirect()->route('setting.index')
                ->with('error', $result['message']);
        }

        return redirect()->route('setting.index')
            ->with('success', $result['message']);
    }
}
