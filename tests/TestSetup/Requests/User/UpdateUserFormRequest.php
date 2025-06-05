<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserFormRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var int $id */
        $id = $this->route()?->parameter('id');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                "unique:users,email,{$id}",
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
            ],
            'status' => [
                'required',
                'boolean',
            ],
            'active' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
