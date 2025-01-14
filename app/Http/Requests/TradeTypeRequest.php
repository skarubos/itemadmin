<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Trading;
use App\Models\TradeType;
use Illuminate\Validation\Rule;

class TradeTypeRequest extends FormRequest
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
            'id' => 'nullable|integer',
            'trade_type' => 'required|integer',
            'trade_type_old' => 'nullable|integer',
            'name' => 'required|string|max:12',
            'caption' => 'required|string|max:255',
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $newTradeType = $this->input('trade_type');
            $oldTradeType = $this->input('trade_type_old');
    
            // unique違反のエラーメッセージ
            $uniqueMessage = 'この取引種別は既に存在するため作成できません。';
            // 外部制約キー違反のエラーメッセージ
            $foreignKeyMessage = 'この取引種別は、既に取引が存在するため変更できません。';
    
            // 新規作成時のバリデーション
            if (!$oldTradeType && TradeType::tradeTypeExists($newTradeType)) {
                $validator->errors()->add('trade_type', $uniqueMessage);
            }
    
            // 編集時のバリデーション
            if ($oldTradeType && $newTradeType !== $oldTradeType) {
                // 変更後trade_typeが既に存在する場合のチェック
                if (TradeType::tradeTypeExists($newTradeType)) {
                    $validator->errors()->add('trade_type', $uniqueMessage);
                }
    
                // 変更前trade_typeが既に取引に使用されている場合のチェック
                if (Trading::tradeTypeExists($oldTradeType)) {
                    $validator->errors()->add('trade_type', $foreignKeyMessage);
                }
            }
        });
    }
}