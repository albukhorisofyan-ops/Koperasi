<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SimulateOnlinePurchaseRequest extends FormRequest
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
            'product_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1'],
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
            'product_id.required' => 'Product ID harus diisi.',
            'product_id.integer' => 'Product ID harus berupa bilangan bulat.',
            'quantity.required' => 'Jumlah beli harus diisi.',
            'quantity.integer' => 'Jumlah beli harus berupa bilangan bulat.',
            'quantity.min' => 'Jumlah beli minimal 1.',
        ];
    }
}
