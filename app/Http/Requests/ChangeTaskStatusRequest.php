<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string'],
            'comment' => ['nullable', 'string'],
        ];
    }

    public function message(): string
    {
        return 'Status changed successfully.';
    }
}