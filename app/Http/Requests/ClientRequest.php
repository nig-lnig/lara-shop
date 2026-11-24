<?php

namespace App\Http\Requests;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validation of the admin client form (create and edit). */
class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'street' => ['required', 'string', 'max:190'],
            'postal_code' => ['required', 'string', 'max:10', 'regex:/^[0-9A-Za-z \-]+$/'],
            'city' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(array_keys(Client::TYPES))],
            'special_discount' => ['required', 'integer', 'between:0,100'],
        ];
    }
}
