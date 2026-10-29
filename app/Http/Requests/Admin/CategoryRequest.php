<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;

        return [
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['required', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($categoryId)
                ->where(fn ($q) => \App\Support\Tenancy::active() ? $q->where('tenant_id', \App\Support\Tenancy::id()) : $q)],
            'parent_id'   => ['nullable', \App\Support\Tenancy::exists('categories')],
            'description' => ['nullable', 'string', 'max:1000'],
            'image'       => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['nullable', 'boolean'],
        ];
    }
}
