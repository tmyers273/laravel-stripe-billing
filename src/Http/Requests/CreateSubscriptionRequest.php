<?php

namespace TMyers\StripeBilling\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use TMyers\StripeBilling\Models\PricingPlan;

class CreateSubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'stripeToken' => 'required',
            'stripeEmail' => 'required|email',
            'pricingPlan' => 'required|numeric'
        ];
    }

    /**
     * @return PricingPlan
     */
    public function getPricingPlan(): PricingPlan {
        return PricingPlan::findOrFail($this->pricingPlan);
    }
}
