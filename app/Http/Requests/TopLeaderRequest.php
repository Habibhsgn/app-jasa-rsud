<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TopLeaderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:100'],
            'posisi' => ['required', 'string', 'max:50', Rule::in(['Direktur', 'Kabid', 'Kasie', 'Bendahara', 'Casemix', 'Costing'])],
            'is_active' => ['boolean'],
        ];
    }
}