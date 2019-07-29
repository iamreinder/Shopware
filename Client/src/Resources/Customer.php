<?php

// Mollie Shopware Plugin Version: 1.4.9

namespace Mollie\Api\Resources;

use Mollie\Api\MollieApiClient;
class Customer extends \Mollie\Api\Resources\BaseResource
{
    /**
     * @var string
     */
    public $resource;
    /**
     * Id of the customer.
     *
     * @var string
     */
    public $id;
    /**
     * Either "live" or "test". Indicates this being a test or a live (verified) customer.
     *
     * @var string
     */
    public $mode;
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $email;
    /**
     * @var string|null
     */
    public $locale;
    /**
     * @var object|mixed|null
     */
    public $metadata;
    /**
     * @var string[]|array
     */
    public $recentlyUsedMethods;
    /**
     * @var string
     */
    public $createdAt;
    /**
     * @var object[]
     */
    public $_links;
    /**
     * @return Customer
     */
    public function update()
    {
        if (!isset($this->_links->self->href)) {
            return $this;
        }
        $body = \json_encode(array("name" => $this->name, "email" => $this->email, "locale" => $this->locale, "metadata" => $this->metadata));
        $result = $this->client->performHttpCallToFullUrl(\Mollie\Api\MollieApiClient::HTTP_PATCH, $this->_links->self->href, $body);
        return \Mollie\Api\Resources\ResourceFactory::createFromApiResult($result, new \Mollie\Api\Resources\Customer($this->client));
    }
    /**
     * @param array $options
     * @param array $filters
     *
     * @return Payment
     */
    public function createPayment(array $options = [], array $filters = [])
    {
        return $this->client->customerPayments->createFor($this, $options, $filters);
    }
    /**
     * Get all payments for this customer
     *
     * @return PaymentCollection
     */
    public function payments()
    {
        return $this->client->customerPayments->listFor($this, null, null, $this->getPresetOptions());
    }
    /**
     * @param array $options
     * @param array $filters
     *
     * @return Subscription
     */
    public function createSubscription(array $options = [], array $filters = [])
    {
        return $this->client->subscriptions->createFor($this, $options, $filters);
    }
    /**
     * @param string $subscriptionId
     * @param array $parameters
     *
     * @return Subscription
     */
    public function getSubscription($subscriptionId, array $parameters = [])
    {
        return $this->client->subscriptions->getFor($this, $subscriptionId, $parameters);
    }
    /**
     * @param string $subscriptionId
     *
     * @return null
     */
    public function cancelSubscription($subscriptionId)
    {
        return $this->client->subscriptions->cancelFor($this, $subscriptionId, $this->getPresetOptions());
    }
    /**
     * Get all subscriptions for this customer
     *
     * @return SubscriptionCollection
     */
    public function subscriptions()
    {
        return $this->client->subscriptions->listFor($this, null, null, $this->getPresetOptions());
    }
    /**
     * @param array $options
     * @param array $filters
     *
     * @return Mandate
     */
    public function createMandate(array $options = [], array $filters = [])
    {
        return $this->client->mandates->createFor($this, $options, $filters);
    }
    /**
     * @param string $mandateId
     * @param array $parameters
     *
     * @return Mandate
     */
    public function getMandate($mandateId, array $parameters = [])
    {
        return $this->client->mandates->getFor($this, $mandateId, $parameters);
    }
    /**
     * @param string $mandateId
     *
     * @return null
     */
    public function revokeMandate($mandateId)
    {
        return $this->client->mandates->revokeFor($this, $mandateId, $this->getPresetOptions());
    }
    /**
     * Get all mandates for this customer
     *
     * @return MandateCollection
     */
    public function mandates()
    {
        return $this->client->mandates->listFor($this, null, null, $this->getPresetOptions());
    }
    /**
     * Helper function to check for mandate with status valid
     *
     * @return bool
     */
    public function hasValidMandate()
    {
        $mandates = $this->mandates();
        foreach ($mandates as $mandate) {
            if ($mandate->isValid()) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * Helper function to check for specific payment method mandate with status valid
     *
     * @return bool
     */
    public function hasValidMandateForMethod($method)
    {
        $mandates = $this->mandates();
        foreach ($mandates as $mandate) {
            if ($mandate->method === $method && $mandate->isValid()) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * When accessed by oAuth we want to pass the testmode by default
     *
     * @return array
     */
    private function getPresetOptions()
    {
        $options = [];
        if ($this->client->usesOAuth()) {
            $options["testmode"] = $this->mode === "test" ? \true : \false;
        }
        return $options;
    }
}
