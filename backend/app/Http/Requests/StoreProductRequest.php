<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:1'],
            'stock' => ['required', 'integer', 'min:0'],
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
            'name.required' => 'Nama barang harus diisi.',
            'price.required' => 'Harga barang harus diisi.',
            'price.integer' => 'Harga barang harus berupa bilangan bulat.',
            'price.min' => 'Harga harus lebih dari 0.',
            'stock.required' => 'Stok awal harus diisi.',
            'stock.integer' => 'Stok awal harus berupa bilangan bulat.',
            'stock.min' => 'Stok awal tidak boleh negatif.',
        ];
    }
}
