<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use TMyers\StripeBilling\Models\PricingPlan;
use TMyers\StripeBilling\Models\StripePrice;

class UpdateSubscriptionRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        // User must be already subscribed
        return auth()->check() && auth()->user()->hasActiveSubscriptions();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'pricingPlan' => 'required|numeric'
        ];
    }

    /**
     * @return StripePrice
     */
    public function getStripePrice(): StripePrice
    {
        return StripePrice::findOrFail($this->pricingPlan);
    }
}
