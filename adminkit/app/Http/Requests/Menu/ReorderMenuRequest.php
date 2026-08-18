<?php

namespace App\Http\Requests\Menu;

use App\Models\Menu;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderMenuRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nodes' => ['required', 'array', 'min:1'],
            'nodes.*.id' => ['required', 'integer', Rule::exists('menus', 'id')],
            'nodes.*.parent_id' => ['nullable', 'integer', Rule::exists('menus', 'id')],
            'nodes.*.area' => ['required', Rule::in(array_keys(Menu::AREAS))],
        ];
    }

    public function attributes(): array
    {
        return ['nodes' => 'susunan menu'];
    }
}
