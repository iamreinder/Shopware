<?php

// Mollie Shopware Plugin Version: 1.4.9

namespace Mollie\Api\Endpoints;

use Mollie\Api\Resources\Capture;
use Mollie\Api\Resources\CaptureCollection;
use Mollie\Api\Resources\Payment;
class PaymentCaptureEndpoint extends \Mollie\Api\Endpoints\CollectionEndpointAbstract
{
    protected $resourcePath = "payments_captures";
    /**
     * Get the object that is used by this API endpoint. Every API endpoint uses one type of object.
     *
     * @return Capture
     */
    protected function getResourceObject()
    {
        return new \Mollie\Api\Resources\Capture($this->client);
    }
    /**
     * Get the collection object that is used by this API endpoint. Every API endpoint uses one type of collection object.
     *
     * @param int $count
     * @param object[] $_links
     *
     * @return \Mollie\Api\Resources\CaptureCollection
     */
    protected function getResourceCollectionObject($count, $_links)
    {
        return new \Mollie\Api\Resources\CaptureCollection($this->client, $count, $_links);
    }
    /**
     * @param Payment $payment
     * @param string $captureId
     * @param array $parameters
     *
     * @return Capture
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function getFor(\Mollie\Api\Resources\Payment $payment, $captureId, array $parameters = [])
    {
        return $this->getForId($payment->id, $captureId, $parameters);
    }
    /**
     * @param string $paymentId
     * @param string $captureId
     * @param array $parameters
     *
     * @return \Mollie\Api\Resources\BaseResource|\Mollie\Api\Resources\Capture
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function getForId($paymentId, $captureId, array $parameters = [])
    {
        $this->parentId = $paymentId;
        return parent::rest_read($captureId, $parameters);
    }
}
