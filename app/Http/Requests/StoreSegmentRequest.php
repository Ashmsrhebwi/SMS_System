<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSegmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Segment::class);
    }

    public function rules(): array
    {
        return [
            'name'                   => 'required|string|max:255',
            'description'            => 'nullable|string|max:500',
            'conditions'             => 'nullable|array',
            'conditions.*.field'     => 'required|string|in:tag,opted_in,country,phone_country,created_at,created_before,created_after',
            'conditions.*.operator'  => 'nullable|string|in:is,is_not,has,not_has,before,after,within_days',
            'conditions.*.value'     => 'required|string|max:255',
        ];
    }
}
