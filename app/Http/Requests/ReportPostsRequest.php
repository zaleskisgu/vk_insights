<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ReportPostsRequest extends ReportPeriodRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return array_merge($this->periodRules(), [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:10', 'max:100'],
            'sort' => ['sometimes', Rule::in(['date', 'likes', 'comments', 'reposts', 'engagement', 'type', 'text'])],
            'order' => ['sometimes', Rule::in(['asc', 'desc'])],
            'q' => ['nullable', 'string', 'max:200'],
            'type' => ['sometimes', Rule::in(['all', 'photo', 'multi', 'video', 'text', 'link'])],
        ]);
    }
}
