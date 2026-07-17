<?php

namespace App\Services;

use App\Models\Setting;

class SettingService
{
    /**
     * Get all settings ordered by id.
     */
    public function getAll(): iterable
    {
        return Setting::orderBy('id')->get();
    }

    /**
     * Get a single setting value by key with default fallback.
     */
    public function getValue(string $key, mixed $default = null): mixed
    {
        return Setting::getValue($key, $default);
    }

    /**
     * Validate and update settings from request payload.
     *
     * @param  array  $values  [id => value]
     * @param  array  $labels  [id => label]
     * @param  array  $descriptions  [id => description]
     * @return array{success: bool, message: string}
     */
    public function validateAndUpdate(array $values, array $labels, array $descriptions): array
    {
        // Get key-to-id mapping
        $keyIdMap = [];
        foreach (Setting::all() as $s) {
            $keyIdMap[$s->key] = $s->id;
        }

        // ---- Panel 1: Biaya Operasional & Total Jasa ----
        $operasionalId = $keyIdMap['persen_biaya_operasional'] ?? null;
        $jasaId        = $keyIdMap['persen_jasa'] ?? null;

        $op = (float) str_replace(',', '.', $values[$operasionalId] ?? 0);
        $js = (float) str_replace(',', '.', $values[$jasaId] ?? 0);

        if ($op < 0 || $op > 100) {
            return ['success' => false, 'message' => 'Biaya Operasional harus antara 0 - 100%.'];
        }

        if ($js < 0 || $js > 100) {
            return ['success' => false, 'message' => 'Total Jasa harus antara 0 - 100%.'];
        }

        if (($op + $js) > 100) {
            return [
                'success' => false,
                'message' => 'Total Biaya Operasional dan Jasa tidak boleh lebih dari 100%. (Saat ini: ' . number_format($op + $js, 1) . '%)',
            ];
        }

        // ---- Panel 2: Pembagian Jasa (Top Leader & Staff) ----
        $jasaTopLeaderId = $keyIdMap['persen_jasa_top_leader'] ?? null;
        $jasaStaffId     = $keyIdMap['persen_jasa_staff'] ?? null;

        $tl = (float) str_replace(',', '.', $values[$jasaTopLeaderId] ?? 0);
        $st = (float) str_replace(',', '.', $values[$jasaStaffId] ?? 0);

        if ($tl < 0 || $tl > 100) {
            return ['success' => false, 'message' => 'Jasa Top Leader harus antara 0 - 100%.'];
        }

        if ($st < 0 || $st > 100) {
            return ['success' => false, 'message' => 'Jasa Staff harus antara 0 - 100%.'];
        }

        if (abs(($tl + $st) - 100) > 0.01) {
            return [
                'success' => false,
                'message' => 'Total Jasa Top Leader dan Staff harus 100%. (Saat ini: ' . number_format($tl + $st, 1) . '%)',
            ];
        }

        // ---- Panel 3: Detail Top Leader breakdown ----
        $tlTotal = 0;
        $submittedCount = 0;
        foreach ($keyIdMap as $key => $id) {
            if (!str_starts_with($key, 'top_leader.')) {
                continue;
            }
            if (!array_key_exists($id, $values)) {
                continue; // skip if not submitted
            }
            $submittedCount++;
            $val = (float) str_replace(',', '.', $values[$id] ?? 0);
            if ($val < 0 || $val > 100) {
                $label = $labels[$id] ?? $key;
                return ['success' => false, 'message' => "'{$label}' harus antara 0 - 100%."];
            }
            $tlTotal += $val;
        }

        if ($submittedCount > 0 && abs($tlTotal - 100) > 0.01) {
            return [
                'success' => false,
                'message' => 'Total Detail Top Leader harus 100%. (Saat ini: ' . number_format($tlTotal, 1) . '%)',
            ];
        }

        // ---- Panel 4: Detail Staff breakdown ----
        $stTotal = 0;
        $stSubmitted = 0;
        foreach ($keyIdMap as $key => $id) {
            if (!str_starts_with($key, 'staff.')) {
                continue;
            }
            if (!array_key_exists($id, $values)) {
                continue;
            }
            $stSubmitted++;
            $val = (float) str_replace(',', '.', $values[$id] ?? 0);
            if ($val < 0 || $val > 100) {
                $label = $labels[$id] ?? $key;
                return ['success' => false, 'message' => "'{$label}' harus antara 0 - 100%."];
            }
            $stTotal += $val;
        }

        if ($stSubmitted > 0 && abs($stTotal - 100) > 0.01) {
            return [
                'success' => false,
                'message' => 'Total Detail Staff harus 100%. (Saat ini: ' . number_format($stTotal, 1) . '%)',
            ];
        }

        // Persist
        foreach ($values as $id => $val) {
            $setting = Setting::find($id);
            if (!$setting) {
                continue;
            }

            $setting->update([
                'value'       => $val,
                'label'       => $labels[$id] ?? $setting->label,
                'description' => $descriptions[$id] ?? $setting->description,
            ]);
        }

        return ['success' => true, 'message' => 'Pengaturan berhasil disimpan.'];
    }
}
