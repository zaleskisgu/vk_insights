<?php

namespace App\Http\Requests;

class ReportRequest extends ReportPeriodRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return $this->periodRules();
    }
}
