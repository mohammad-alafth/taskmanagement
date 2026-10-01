<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|min:3|max:255',
            'description' => 'required|string',
            'assignee_id' => 'required|exists:users,id',
            'checker_id' => 'nullable|exists:users,id',
            'priority' => 'required|string|in:LOW,MEDIUM,HIGH',
            'due_date' => 'nullable|date|after_or_equal:today',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpeg,png,zip|max:10240',
        ];
    }
    }