<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSegmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('segment'));
    }

    public function rules(): array
    {
        return [
            'name'              => 'required|string|max:255',
            'description'       => 'nullable|string|max:500',
            'conditions'        => 'nullable|array',
            'conditions.*.field'    => 'required|string|in:tag,opted_in,phone_country,created_after,created_before',
            'conditions.*.value'    => 'required|string|max:255',
        ];
    }
}
