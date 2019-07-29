<?php

// Mollie Shopware Plugin Version: 1.4.9

namespace Mollie\Api\Resources;

class SettlementCollection extends \Mollie\Api\Resources\CursorCollection
{
    /**
     * @return string
     */
    public function getCollectionResourceName()
    {
        return "settlements";
    }
    /**
     * @return BaseResource
     */
    protected function createResourceObject()
    {
        return new \Mollie\Api\Resources\Settlement($this->client);
    }
}
