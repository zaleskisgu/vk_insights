<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ReportExportRequest extends ReportPeriodRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return array_merge($this->periodRules(), [
            'format' => ['required', Rule::in(['json', 'csv'])],
        ]);
    }
}
