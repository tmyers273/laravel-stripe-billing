<?php

namespace TMyers\StripeBilling\Services;

use Stripe\Error\SignatureVerification;
use Stripe\Event;
use Stripe\Webhook;
use Stripe\WebhookSignature;
use Illuminate\Contracts\Config\Repository as Config;

class StripeWebhookManager
{
    /**
     * @var Config
     */
    private $config;

    /**
     * StripeWebhookManager constructor.
     * @param Config $config
     */
    public function __construct(Config $config) {
        $this->config = $config;
    }

    /**
     * @param $payload
     * @param $header
     * @return bool
     * @throws \Stripe\Exception\SignatureVerificationException
     */
    public function hasValidSignature($payload, $header): bool {
        try {
            WebhookSignature::verifyHeader(
                $payload,
                $header,
                $this->getEndpointSecret()
            );
        } catch (SignatureVerification $e) {
            return false;
        }

        return true;
    }

    /**
     * @param string $payload
     * @param string $stripeSignature
     * @return Event
     * @throws SignatureVerification
     * @throws \Stripe\Exception\SignatureVerificationException
     */
    public function constructStripeEvent(string $payload, string $stripeSignature): Event {
        return Webhook::constructEvent($payload, $stripeSignature, $this->getEndpointSecret());
    }

    protected function getEndpointSecret() {
        return $this->config->get('services.stripe.webhook.secret');
    }
}
