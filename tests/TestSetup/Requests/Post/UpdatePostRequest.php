<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'desc' => [
                'required',
                'string',
                'max:25500',
            ],
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
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
