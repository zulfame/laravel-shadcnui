<?php

namespace App\Http\Requests\Menu;

use App\Models\Menu;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMenuRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'min:2', 'max:50'],
            'area' => ['required', Rule::in(array_keys(Menu::AREAS))],
            'parent_id' => ['nullable', 'integer', Rule::exists('menus', 'id')],
            'href' => ['nullable', 'string', 'max:120', 'regex:/^\/[A-Za-z0-9\-\/_]*$/'],
            'icon' => ['nullable', 'string', 'max:40'],
            'permission' => ['nullable', 'string', Rule::exists('permissions', 'name')],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'label' => 'label menu',
            'area' => 'area',
            'parent_id' => 'menu induk',
            'href' => 'alamat',
            'icon' => 'ikon',
            'permission' => 'izin',
        ];
    }

    public function messages(): array
    {
        return ['href.regex' => 'Alamat harus dimulai dengan "/" tanpa spasi, mis. /users.'];
    }
}
