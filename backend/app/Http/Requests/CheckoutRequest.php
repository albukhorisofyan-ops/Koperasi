<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Daftar item harus diisi.',
            'items.array' => 'Format item tidak valid.',
            'items.min' => 'Keranjang belanja minimal berisi 1 item.',
            'items.*.product_id.required' => 'Product ID harus diisi.',
            'items.*.product_id.integer' => 'Product ID harus berupa bilangan bulat.',
            'items.*.quantity.required' => 'Jumlah beli harus diisi.',
            'items.*.quantity.integer' => 'Jumlah beli harus berupa bilangan bulat.',
            'items.*.quantity.min' => 'Jumlah beli minimal 1.',
        ];
    }
}
