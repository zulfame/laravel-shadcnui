<?php

namespace App\Http\Requests\Menu;

class UpdateMenuRequest extends StoreMenuRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        // Menu tidak boleh menjadi induk bagi dirinya sendiri.
        $rules['parent_id'][] = function (string $attribute, $value, $fail) {
            if ((int) $value === (int) $this->route('menu')->id) {
                $fail('Menu tidak dapat dijadikan induk bagi dirinya sendiri.');
            }
        };

        return $rules;
    }
}
