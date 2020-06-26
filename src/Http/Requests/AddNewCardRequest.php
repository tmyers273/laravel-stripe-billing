<?php

namespace TMyers\StripeBilling\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class AddNewCardRequest
 * @package App\Http\Requests
 *
 * @property string $stripeToken
 */
class AddNewCardRequest extends FormRequest
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
            'stripeEmail' => 'required',
        ];
    }
}
