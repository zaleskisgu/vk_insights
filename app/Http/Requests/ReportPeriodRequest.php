<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

abstract class ReportPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Базовые правила: сообщество и период. Верхняя граница «не позже сегодня»
     * и максимальная ширина окна проверяются в {@see self::withValidator()}
     * с учётом {@see config('vk.timezone')}.
     *
     * @return array<string, list<string>>
     */
    protected function periodRules(): array
    {
        return [
            'group' => ['required', 'string', 'max:512'],
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if ($v->errors()->hasAny(['from', 'to'])) {
                return;
            }

            $fromRaw = $this->input('from');
            $toRaw = $this->input('to');
            if (! is_string($fromRaw) || ! is_string($toRaw) || $fromRaw === '' || $toRaw === '') {
                return;
            }

            $tz = (string) config('vk.timezone', config('app.timezone', 'UTC'));
            $tomorrow = Carbon::now($tz)->addDay()->startOfDay();
            $from = Carbon::parse($fromRaw)->setTimezone($tz);
            $to = Carbon::parse($toRaw)->setTimezone($tz);

            if ($from->greaterThanOrEqualTo($tomorrow)) {
                $v->errors()->add('from', 'Дата начала не может быть позже сегодняшней.');
            }
            if ($to->greaterThanOrEqualTo($tomorrow)) {
                $v->errors()->add('to', 'Дата окончания не может быть позже сегодняшней.');
            }
            if ($v->errors()->hasAny(['from', 'to'])) {
                return;
            }

            $maxDays = (int) config('vk.period_max_days', 365);
            if ($maxDays <= 0) {
                return;
            }

            $fromDay = $from->copy()->startOfDay();
            $toDay = $to->copy()->startOfDay();
            if ($fromDay->diffInDays($toDay) > $maxDays) {
                $v->errors()->add('to', "Период не должен превышать {$maxDays} дней.");
            }
        });
    }
}
