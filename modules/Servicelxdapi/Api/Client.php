<?php

declare(strict_types=1);

namespace Box\Mod\Servicelxdapi\Api;

class Client extends \Api_Abstract
{
    public function vm_info(array $data): array
    {
        $required = ['order_id' => 'Order ID is required'];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        [$order, $service] = $this->getOrderAndService((int)$data['order_id']);
        $server = $this->di['db']->load('service_lxdapi_server', $service->server_id);

        if (!$server) {
            return [
                'container_name' => $service->container_name,
                'password' => $service->password,
                'panel_url' => '',
                'iframe_url' => '',
                'error' => '服务器不存在或已被删除',
            ];
        }

        $baseUrl = 'https://' . $server->hostname . ':' . $server->port;
        $panelUrl = '';
        $iframeUrl = '';

        $endpoint = '/api/system/containers/' . urlencode($service->container_name) . '/credential';
        $response = $this->getService()->apiRequest($server, $endpoint, [], 'GET');
        
        if (isset($response['code']) && $response['code'] == 200 && isset($response['data']['access_code'])) {
            $accessCode = $response['data']['access_code'];
            $panelUrl = $baseUrl . '/container/dashboard?hash=' . $accessCode;
            $iframeUrl = $baseUrl . '/container/dashboard/lite?hash=' . $accessCode;
        }

        return [
            'container_name' => $service->container_name,
            'password' => $service->password,
            'panel_url' => $panelUrl,
            'iframe_url' => $iframeUrl,
        ];
    }

    public function vm_start(array $data): bool
    {
        $required = ['order_id' => 'Order ID is required'];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        [$order, $service] = $this->getOrderAndService((int)$data['order_id']);

        return $this->getService()->vmStart($order, $service);
    }

    public function vm_stop(array $data): bool
    {
        $required = ['order_id' => 'Order ID is required'];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        [$order, $service] = $this->getOrderAndService((int)$data['order_id']);

        return $this->getService()->vmStop($order, $service);
    }

    public function vm_reboot(array $data): bool
    {
        $required = ['order_id' => 'Order ID is required'];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        [$order, $service] = $this->getOrderAndService((int)$data['order_id']);

        return $this->getService()->vmReboot($order, $service);
    }

    public function vm_reinstall(array $data): bool
    {
        $required = [
            'order_id' => 'Order ID is required',
            'image' => 'Image is required',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        [$order, $service] = $this->getOrderAndService((int)$data['order_id']);

        $password = $data['password'] ?? '';
        return $this->getService()->vmReinstall($order, $service, $data['image'], $password);
    }

    public function vm_reset_password(array $data): bool
    {
        $required = [
            'order_id' => 'Order ID is required',
            'password' => 'Password is required',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        [$order, $service] = $this->getOrderAndService((int)$data['order_id']);

        return $this->getService()->vmResetPassword($order, $service, $data['password']);
    }

    public function console_url(array $data): string
    {
        $required = ['order_id' => 'Order ID is required'];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        [$order, $service] = $this->getOrderAndService((int)$data['order_id']);

        return $this->getService()->getConsoleUrl($order, $service);
    }

    public function templates(array $data): array
    {
        $required = ['order_id' => 'Order ID is required'];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        [$order, $service] = $this->getOrderAndService((int)$data['order_id']);

        return $this->getService()->getTemplates($service);
    }

    protected function getOrderAndService(int $orderId): array
    {
        $order = $this->di['db']->findOne('client_order', 'id = ?', [$orderId]);
        if (!$order) {
            throw new \Box_Exception('Order not found');
        }

        $identity = $this->getIdentity();
        if ($order->client_id !== $identity->id) {
            throw new \Box_Exception('Access denied');
        }

        $service = $this->getService()->getServiceLxdapiByOrderId($orderId);

        return [$order, $service];
    }
}
