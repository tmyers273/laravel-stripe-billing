<?php
/**
 * Created by PhpStorm.
 * User: denismitr
 * Date: 19.10.2018
 * Time: 14:28
 */

namespace TMyers\StripeBilling\Exceptions;


class StripeGatewayException extends StripeBillingException {
    protected $cardWasDeclined;

    /**
     * @param \Exception $e
     * @return StripeGatewayException
     */
    public static function cardDeclined(\Exception $e): self {
        $e = new static($e->getMessage(), $e->getCode(), $e);
        $e->cardWasDeclined = true;

        return $e;
    }

    public function cardWasDeclined(): bool {
        return ! ! $this->cardWasDeclined;
    }
}
