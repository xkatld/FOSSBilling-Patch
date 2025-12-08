<?php

declare(strict_types=1);

namespace Box\Mod\Servicelxdapi\Controller;

class Client implements \FOSSBilling\InjectionAwareInterface
{
    protected ?\Pimple\Container $di = null;

    public function setDi(\Pimple\Container $di): void
    {
        $this->di = $di;
    }

    public function getDi(): ?\Pimple\Container
    {
        return $this->di;
    }

    public function register(\Box_App &$app): void
    {
        $app->get('/servicelxdapi/manage/:id', 'get_manage', ['id' => '[0-9]+'], static::class);
    }

    public function get_manage(\Box_App $app, $id): string
    {
        $this->di['is_client_logged'];
        
        $api = $this->di['api_client'];
        $order = $api->order_get(['id' => $id]);
        
        return $app->render('mod_servicelxdapi_manage', ['order' => $order]);
    }
}
