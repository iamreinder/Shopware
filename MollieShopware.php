<?php

// Mollie Shopware Plugin Version: 1.3.15

namespace MollieShopware;

use Smarty;

use Enlight_Event_EventArgs;
use Enlight_Controller_EventArgs;

use MollieShopware\Models\Transaction;
use MollieShopware\Models\OrderLines;
use MollieShopware\Components\Schema;
use MollieShopware\Components\Attributes;
use MollieShopware\Components\Config;
use MollieShopware\Components\MollieApiFactory;
use MollieShopware\Components\Logger;
use MollieShopware\Commands\Mollie\GetIdealBanksCommand;
use MollieShopware\Commands\Mollie\GetPaymentMethodsCommand;

use Shopware\Components\Console\Application;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use Shopware\Components\Plugin\Context\UninstallContext;

class MollieShopware extends Plugin
{
    /**
     * MollieShopware\Components\ShopwareConfig
     */
    protected $config;

    /**
     * Return Shopware events subscribed to
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Front_StartDispatch' => 'requireDependencies',
            'Shopware_Console_Add_Command' => 'requireDependencies',
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Order' => 'onOrderPostDispatch',
            'Enlight_Controller_Front_RouteStartup' => [ 'fixLanguageShopPush', -10 ],
        ];
    }

    /**
     * Require composer libraries on a new request
     */
    public function requireDependencies()
    {
        // Load composer libraries
        if (file_exists($this->getPath() . '/Client/vendor/autoload.php')) {
            require_once $this->getPath() . '/Client/vendor/autoload.php';
        }

        // Load guzzle functions
        if (file_exists($this->getPath() . '/Client/vendor/guzzlehttp/guzzle/src/functions_include.php')) {
            require_once $this->getPath() . '/Client/vendor/guzzlehttp/guzzle/src/functions_include.php';
        }

        // Load promises functions
        if (file_exists($this->getPath() . '/Client/vendor/guzzlehttp/promises/src/functions_include.php')) {
            require_once $this->getPath() . '/Client/vendor/guzzlehttp/promises/src/functions_include.php';
        }

        // Load psr7 functions
        if (file_exists($this->getPath() . '/Client/vendor/guzzlehttp/psr7/src/functions_include.php')) {
            require_once $this->getPath() . '/Client/vendor/guzzlehttp/psr7/src/functions_include.php';
        }

        // Load client
        if (file_exists($this->getPath() . '/Client/src/MollieApiClient.php')) {
            require_once $this->getPath() . '/Client/src/MollieApiClient.php';
        }
    }

    /**
     * In engine/Shopware/Plugins/Default/Core/Router/Bootstrap.php
     * the current shop is determined
     *
     * When a POST request is made with the __shop GET variable,
     * this variable isn't used to get the shop,
     * so when an order is created in a language shop,
     * the push always fails because it can't access the session
     *
     * This is done on the Enlight_Controller_Front_RouteStartup event,
     * because this is the first event in de frontcontroller
     * (engine\Library\Enlight\Controller\Front.php)
     * where the Request has been populated.
     *
     * @param  Enlight_Controller_EventArgs $args
     */
    public function fixLanguageShopPush(Enlight_Controller_EventArgs $args)
    {
        $request = $args->getRequest();

        if ($request->getQuery('__shop')) {
            $request->setPost('__shop', $request->getQuery('__shop'));
        }
    }

    /**
     * Register Mollie controller
     */
    public function registerController(Enlight_Event_EventArgs $args)
    {
        return $this->getPath() . '/Controllers/Frontend/Mollie.php';
    }

    /**
     * Register Console commands
     */
    public function registerCommands(Application $application)
    {
        $application->add(new GetPaymentMethodsCommand());
        $application->add(new GetIdealBanksCommand());
        return parent::__construct($application);
    }


    /**
     * Inject some backend ext.js extensions for the order module
     */
    public function onOrderPostDispatch(Enlight_Event_EventArgs $args)
    {
        /** @var \Enlight_Controller_Action $controller */
        $controller = $args->getSubject();
        $view = $controller->View();
        $request = $controller->Request();

        $view->addTemplateDir(__DIR__ . '/Resources/views');

        if ($request->getActionName() == 'load') {
            $view->extendsTemplate('backend/mollie_extend_order/view/list/list.js');
            $view->extendsTemplate('backend/mollie_extend_order/controller/list.js');
        }
    }

    /**
     * @param InstallContext $context
     */
    public function install(InstallContext $context)
    {
        // Payments are not created at install,
        // because the user hasn't had the ability to put in an API-key at this time
        //
        // Payments are added on activation of the plugin
        // The user should put in an API key between install and activation

        // clear config cache
        $context->scheduleClearCache(InstallContext::CACHE_LIST_DEFAULT);

        // create database tables
        $this->updateDbTables();

        // add extra attributes
        $this->updateAttributes();

        parent::install($context);
    }

    /**
     * @param UpdateContext $context
     */
    public function update(UpdateContext $context)
    {
        // clear config cache
        $context->scheduleClearCache(InstallContext::CACHE_LIST_DEFAULT);

        // create database tables
        $this->updateDbTables();

        // add extra attributes
        $this->updateAttributes();

        parent::update($context);
    }

    /**
     * @param UninstallContext $context
     */
    public function uninstall(UninstallContext $context)
    {
        // Don't remove payment methods but set them to inactive.
        // So orders paid still reference an existing payment method
        $this->deactivatePayments();

        // remove extra attributes
        $this->removeAttributes();

        parent::uninstall($context);
    }

    /**
     * @param DeactivateContext $context
     */
    public function deactivate(DeactivateContext $context)
    {
        $this->deactivatePayments();

        parent::deactivate($context);
    }

    /**
     * @param ActivateContext $contextOrderLines
     */
    public function activate(ActivateContext $context)
    {
        // clear config cache
        $context->scheduleClearCache(InstallContext::CACHE_LIST_DEFAULT);

        // update db tables
        $this->updateDbTables();

        // first set all payment methods to inactive
        // $this->setActiveFlag($context->getPlugin()->getPayments(), false);
        $this->deactivatePayments();

        /** @var \Shopware\Components\Plugin\PaymentInstaller $installer */
        $installer = $this->container->get('shopware.plugin_payment_installer');

        $paymentOptions = $this->getPaymentOptions();

        foreach ($paymentOptions as $key => $options) {
            $installer->createOrUpdate($context->getPlugin(), $options);
        }

        parent::activate($context);
    }

    /**
     * Deactivate all Mollie payment methods
     */
    protected function deactivatePayments()
    {
        $em = $this->container->get('models');

        $qb = $em->createQueryBuilder();

        $query = $qb->update('Shopware\Models\Payment\Payment', 'p')
            ->set('p.active', '?1')
            ->where($qb->expr()->like('p.name', '?2'))
            ->setParameter(1, false)
            ->setParameter(2, 'mollie_%')
            ->getQuery();

        $query->execute();
    }

    /**
     * Get the current payment methods via the Mollie API
     * @return array[] $options
     */
    protected function getPaymentOptions()
    {
        $mollie = $this->getMollieClient();

        // TODO: get methods in the correct locale (de_DE en_US es_ES fr_FR nl_BE fr_BE nl_NL)
        $methods = $mollie->methods->all(['resource' => 'orders']);

        $options = [];
        $position = 0;

        // path to template dir for extra payment-mean options
        $paymentTemplateDir = __DIR__ . '/Resources/views/frontend/plugins/payment/methods';

        foreach ($methods as $key => $method) {
            $name = 'mollie_' . $method->id;

            $smarty = new Smarty;
            $smarty->assign('method', $method);
            $smarty->assign('router', Shopware()->Router());

            // template path
            $adTemplate = $paymentTemplateDir . '/' . strtolower($method->id) . '.tpl';

            // set default template if no specific template exists
            if (!file_exists($adTemplate)) {
                $adTemplate =  $paymentTemplateDir . '/main.tpl';
            }

            $additionalDescription = $smarty->fetch('file:' . $adTemplate);

            $option = [
                'name' => $name,
                'description' => $method->description,
                'action' => 'frontend/Mollie',
                'active' => 1,
                'position' => $position,
                'additionalDescription' => $additionalDescription
            ];

            // check template exist
            if (file_exists($paymentTemplateDir . $name . '.tpl')) {
                $option['template'] = $name . '.tpl';
            }

            $options[] = $option;
        }

        return $options;
    }

    /**
     * @return \Mollie\Api\MollieApiClient
     */
    protected function getMollieClient()
    {
        $this->requireDependencies();

        $render= $this->container->get('shopware.plugin.cached_config_reader');

        $config = new Config($render);
        $factory = new MollieApiFactory($config);
        return $factory->create();
    }

    /**
     * Update extra database tables
     */
    protected function updateDbTables()
    {
        try {
            $schema = new Schema($this->container->get('models'));
            $schema->update([Transaction::class]);
            $schema->update([OrderLines::class]);
        }
        catch (\Exception $ex) {
            Logger::log(
                'error',
                $ex->getMessage(),
                $ex
            );
        }
    }

    /**
     * Remove extra database tables
     */
    protected function removeDBTables()
    {
        try {
            $schema = new Schema($this->container->get('models'));
            $schema->remove(Transaction::class);
        }
        catch (\Exception $ex) {
            Logger::log(
                'error',
                $ex->getMessage(),
                $ex
            );
        }
    }

    /**
     * Create a new Attributes object
     */
    protected function makeAttributes()
    {
        return new Attributes(
            $this->container->get('models'),
            $this->container->get('shopware_attribute.crud_service')
        );
    }

    /**
     * Update extra attributes
     */
    protected function updateAttributes()
    {
        $this->makeAttributes()->create([ [ 's_user_attributes', 'mollie_shopware_ideal_issuer', 'string', [] ] ]);
    }

    /**
     * Remove extra attributes
     */
    protected function removeAttributes()
    {
        $this->makeAttributes()->remove([ [ 's_user_attributes', 'mollie_shopware_ideal_issuer' ] ]);
    }
}
