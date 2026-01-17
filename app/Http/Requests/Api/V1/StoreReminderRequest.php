<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\BaseApiRequest;

class StoreReminderRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:daily'],
            'times' => ['required', 'array', 'min:1'],
            'times.*' => ['required', 'date_format:H:i'],
            'timezone' => ['required', 'timezone'],
        ];
    }
}
