<?php

namespace App\Http\Requests;

use App\Concerns\ValidatesDateRange;
use Illuminate\Contracts\Validation\ValidationRule;

class BookOrderRequest extends ApiFormRequest
{
    use ValidatesDateRange;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge($this->dateRangeRules(), [
            'room_type_id' => ['required', 'uuid', 'exists:room_types,id'],
            'tariff_plan_id' => ['required', 'uuid', 'exists:tariff_plans,id'],
            'adults' => ['required', 'integer', 'min:1'],
            'childrens' => ['required', 'integer', 'min:0'],
        ]);
    }
}
