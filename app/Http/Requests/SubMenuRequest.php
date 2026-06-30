<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'menu_id' => 'required|exists:menus,id',
            'submenu_name' => ['required', 'array', 'min:1'],
            'submenu_name.*' => ['required', 'string', 'max:255'],

        ];
    }
}
