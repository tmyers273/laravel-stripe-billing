<?php

namespace TMyers\StripeBilling;


use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stripe\Customer;
use TMyers\StripeBilling\Facades\StripeCustomer;
use TMyers\StripeBilling\Models\Card;

interface BillableContract {

    public function retrieveStripeCustomer(): Customer;
    public function retrieveOrCreateStripeCustomer($token = null, array $options = []);
    public function createCustomerWithDefaultCardFromToken($token, array $options = []);

}
