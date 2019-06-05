<?php

// Mollie Shopware Plugin Version: 1.4.7

namespace Mollie\Api\Resources;

class Permission extends \Mollie\Api\Resources\BaseResource
{
    /**
     * @var string
     */
    public $resource;
    /**
     * @var string
     * @example payments.read
     */
    public $id;
    /**
     * @var string
     */
    public $description;
    /**
     * @var bool
     */
    public $granted;
    /**
     * @var object[]
     */
    public $_links;
}
