<?php

namespace TMyers\StripeBilling\Gateways;


use Stripe\Charge;

class StripeChargeGateway extends StripeGateway {

    /**
     * @param array $params
     * @return \Stripe\Charge
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function charge(array $params = []): Charge {
        return $this->client->charges->create($params);
    }
}
