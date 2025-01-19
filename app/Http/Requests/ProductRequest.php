<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストを行うことを許可するかどうかを判断します。
     *
     * @return bool
     */
    public function authorize()
    {
        return true; 
    }

    /**
     * リクエストに適用されるバリデーションルールを取得します。
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'id' => 'required|integer',
            'name' => 'required|string|max:255',
            'product_type' => 'required|integer|between:1,4',
            'remain' => 'nullable|integer',
        ];

        return $rules;
    }

    /**
     * バリデーションエラーメッセージをカスタマイズします。
     *
     * @return array
     */
    public function messages()
    {
        return [
            'id.required' => '商品IDは必須です。',
            'id.integer' => '商品IDは整数でなければなりません。',
            'name.required' => '商品名は必須です。',
            'name.string' => '商品名は文字列でなければなりません。',
            'name.max' => '商品名は255文字以内でなければなりません。',
            'product_type.required' => '商品種別を選択してください。',
            'product_type.integer' => '商品種別は整数でなければなりません。',
            'product_type.between' => '商品種別は1～4の整数値でなければなりません。',
        ];
    }
}

