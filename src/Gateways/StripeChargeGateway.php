<?php

namespace TMyers\StripeBilling\Gateways;


use Stripe\Charge;

class StripeChargeGateway extends StripeGateway {
    /**
     * @param array $params
     * @return \Stripe\Charge
     * @throws \TMyers\StripeBilling\Exceptions\StripeGatewayException
     */
    public function charge(array $params = []): Charge {
        return Charge::create($params, ['api_key' => $this->getApiKey()]);
    }
}
