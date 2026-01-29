<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\BaseApiRequest;

class UpdateReminderRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'string', 'in:daily'],
            'times' => ['sometimes', 'array', 'min:1'],
            'times.*' => ['required_with:times', 'date_format:H:i'],
            'timezone' => ['sometimes', 'timezone'],
        ];
    }
}
