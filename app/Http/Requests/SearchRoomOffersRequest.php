<?php

namespace App\Http\Requests;

use App\Concerns\ValidatesDateRange;
use Illuminate\Contracts\Validation\ValidationRule;

class SearchRoomOffersRequest extends ApiFormRequest
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
            'adults' => ['required', 'integer', 'min:1'],
            'childrens' => ['required', 'integer', 'min:0'],
            'max_price' => ['nullable', 'integer'],
            'services_ids' => ['nullable', 'array'],
            'services_ids.*' => ['uuid'],
        ]);
    }
}
