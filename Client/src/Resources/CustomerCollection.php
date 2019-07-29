<?php

// Mollie Shopware Plugin Version: 1.4.9

namespace Mollie\Api\Resources;

class CustomerCollection extends \Mollie\Api\Resources\CursorCollection
{
    /**
     * @return string
     */
    public function getCollectionResourceName()
    {
        return "customers";
    }
    /**
     * @return BaseResource
     */
    protected function createResourceObject()
    {
        return new \Mollie\Api\Resources\Customer($this->client);
    }
}
