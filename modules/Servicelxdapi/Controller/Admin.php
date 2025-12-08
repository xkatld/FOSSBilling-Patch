<?php

declare(strict_types=1);

namespace Box\Mod\Servicelxdapi\Controller;

class Admin implements \FOSSBilling\InjectionAwareInterface
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

    public function fetchNavigation(): array
    {
        return [
            'group' => [
                'index' => 500,
                'location' => 'servicelxdapi',
                'label' => 'LXDAPI',
                'uri' => $this->di['url']->adminLink('servicelxdapi'),
                'class' => 'server',
            ],
            'subpages' => [
                [
                    'location' => 'servicelxdapi',
                    'index' => 10,
                    'label' => '服务器',
                    'uri' => $this->di['url']->adminLink('servicelxdapi/servers'),
                    'class' => '',
                ],
                [
                    'location' => 'servicelxdapi',
                    'index' => 20,
                    'label' => '服务器组',
                    'uri' => $this->di['url']->adminLink('servicelxdapi/groups'),
                    'class' => '',
                ],
            ],
        ];
    }

    public function register(\Box_App &$app): void
    {
        $app->get('/servicelxdapi', 'get_index', [], static::class);
        $app->get('/servicelxdapi/servers', 'get_servers', [], static::class);
        $app->get('/servicelxdapi/groups', 'get_groups', [], static::class);
    }

    public function get_index(\Box_App $app): string
    {
        $this->di['is_admin_logged'];
        
        return $app->render('mod_servicelxdapi_index');
    }

    public function get_servers(\Box_App $app): string
    {
        $this->di['is_admin_logged'];
        
        return $app->render('mod_servicelxdapi_servers');
    }

    public function get_groups(\Box_App $app): string
    {
        $this->di['is_admin_logged'];
        
        return $app->render('mod_servicelxdapi_groups');
    }
}
