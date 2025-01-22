<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TradingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'trade_id' => 'nullable|integer',
            'status' => 'required|integer',
            'remain' => 'required|integer',
            'member_code' => 'required|integer',
            'date' => 'required|date',
            'trade_type' => 'required|integer',
            'amount' => 'required|integer',
            'change_detail' => 'nullable|integer',
            'details' => 'nullable|array',
            'details.*.product_id' => 'nullable|integer',
            'details.*.amount' => 'nullable|integer',
        ];
    }
}
