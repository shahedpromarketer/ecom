<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (!$this->slug && $this->name) {
            $this->merge(['slug' => Str::slug($this->name)]);
        }

        if (!$this->has('categories')) {
            $this->merge(['categories' => []]);
        }
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'name'              => ['required', 'string', 'max:255'],
            'slug'              => ['required', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($productId)],
            'description'       => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'price'             => ['required', 'numeric', 'min:0'],
            'sale_price'        => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'sku'               => ['nullable', 'string', 'max:255'],
            'status'            => ['required', Rule::in(['active', 'inactive', 'draft'])],
            'is_featured'       => ['nullable', 'boolean'],
            'meta_title'        => ['nullable', 'string', 'max:255'],
            'meta_description'  => ['nullable', 'string'],
            'categories'        => ['required', 'array', 'min:1'],
            'categories.*'      => ['exists:categories,id'],
            'images'            => ['nullable', 'array'],
            'images.*'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'variants'          => ['nullable', 'array'],
            'variants.*.size'        => ['nullable', 'string', 'max:50'],
            'variants.*.color'       => ['nullable', 'string', 'max:50'],
            'variants.*.color_hex'   => ['nullable', 'string', 'max:10'],
            'variants.*.stock'       => ['nullable', 'integer', 'min:0'],
            'variants.*.sku'         => ['nullable', 'string', 'max:255'],
            'variants.*.extra_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
