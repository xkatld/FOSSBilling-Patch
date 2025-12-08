<?php

declare(strict_types=1);

namespace Box\Mod\Servicelxdapi\Api;

class Admin extends \Api_Abstract
{
    public function vm_info(array $data): array
    {
        $required = ['order_id' => 'Order ID is required'];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        [$order, $service] = $this->getOrderAndService((int)$data['order_id']);

        $info = $this->getService()->vmInfo($order, $service);
        $server = $this->di['db']->load('service_lxdapi_server', $service->server_id);

        return [
            'container_name' => $service->container_name,
            'status' => $info['status'] ?? 'unknown',
            'server' => $server?->hostname ?? '',
            'password' => $service->password,
            'info' => $info,
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

    public function traffic_reset(array $data): bool
    {
        $required = ['order_id' => 'Order ID is required'];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        [$order, $service] = $this->getOrderAndService((int)$data['order_id']);

        return $this->getService()->trafficReset($order, $service);
    }

    public function console_url(array $data): string
    {
        $required = ['order_id' => 'Order ID is required'];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        [$order, $service] = $this->getOrderAndService((int)$data['order_id']);

        return $this->getService()->getConsoleUrl($order, $service);
    }

    public function server_list(array $data = []): array
    {
        $page = (int)($data['page'] ?? 1);
        $perPage = (int)($data['per_page'] ?? 20);
        $offset = ($page - 1) * $perPage;

        $total = $this->di['db']->getCell("SELECT COUNT(*) FROM service_lxdapi_server");
        $servers = $this->di['db']->find('service_lxdapi_server', 'ORDER BY id DESC LIMIT ? OFFSET ?', [$perPage, $offset]);
        
        $groups = $this->getService()->getServerGroups();
        $groupMap = [];
        foreach ($groups as $g) {
            $groupMap[$g->id] = $g->name;
        }

        $list = [];
        foreach ($servers as $server) {
            $containerCount = $this->di['db']->getCell(
                "SELECT COUNT(*) FROM service_lxdapi WHERE server_id = ?",
                [$server->id]
            );
            $list[] = [
                'id' => $server->id,
                'group_id' => $server->group_id ?? null,
                'group_name' => isset($server->group_id) ? ($groupMap[$server->group_id] ?? '') : '',
                'name' => $server->name,
                'hostname' => $server->hostname,
                'port' => $server->port,
                'ssl_verify' => (bool)($server->ssl_verify ?? false),
                'active' => (bool)$server->active,
                'max_containers' => (int)($server->max_containers ?? 0),
                'container_count' => (int)$containerCount,
            ];
        }

        return [
            'list' => $list,
            'total' => (int)$total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => (int)ceil($total / $perPage),
        ];
    }

    public function server_create(array $data): int
    {
        $required = [
            'name' => 'Server name is required',
            'hostname' => 'Hostname is required',
            'api_hash' => 'API Hash is required',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        return $this->getService()->createServer($data);
    }

    public function server_update(array $data): bool
    {
        $required = ['id' => 'Server ID is required'];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        return $this->getService()->updateServer((int)$data['id'], $data);
    }

    public function server_delete(array $data): bool
    {
        $required = ['id' => 'Server ID is required'];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        return $this->getService()->deleteServer((int)$data['id']);
    }

    public function server_test(array $data): string
    {
        $required = ['id' => 'Server ID is required'];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $server = $this->di['db']->load('service_lxdapi_server', $data['id']);
        if (!$server) {
            throw new \Box_Exception('服务器不存在');
        }

        $result = $this->getService()->testConnection($server);
        if ($result['success']) {
            return $result['message'];
        }
        throw new \Box_Exception($result['message']);
    }

    protected function getOrderAndService(int $orderId): array
    {
        $order = $this->di['db']->findOne('client_order', 'id = ?', [$orderId]);
        if (!$order) {
            throw new \Box_Exception('Order not found');
        }

        $service = $this->getService()->getServiceLxdapiByOrderId($orderId);

        return [$order, $service];
    }

    public function server_group_list(array $data = []): array
    {
        $page = (int)($data['page'] ?? 1);
        $perPage = (int)($data['per_page'] ?? 20);
        $offset = ($page - 1) * $perPage;

        $total = $this->di['db']->getCell("SELECT COUNT(*) FROM service_lxdapi_server_group");
        $groups = $this->di['db']->find('service_lxdapi_server_group', 'ORDER BY id DESC LIMIT ? OFFSET ?', [$perPage, $offset]);
        
        $list = [];
        foreach ($groups as $group) {
            $serverCount = $this->di['db']->getCell(
                "SELECT COUNT(*) FROM service_lxdapi_server WHERE group_id = ?",
                [$group->id]
            );
            $list[] = [
                'id' => $group->id,
                'name' => $group->name,
                'fill_type' => $group->fill_type,
                'server_count' => (int)$serverCount,
            ];
        }

        return [
            'list' => $list,
            'total' => (int)$total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => (int)ceil($total / $perPage),
        ];
    }

    public function server_group_create(array $data): int
    {
        $required = ['name' => '组名称不能为空'];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        return $this->getService()->createServerGroup($data);
    }

    public function server_group_update(array $data): bool
    {
        $required = ['id' => '组ID不能为空'];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        return $this->getService()->updateServerGroup((int)$data['id'], $data);
    }

    public function server_group_delete(array $data): bool
    {
        $required = ['id' => '组ID不能为空'];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        return $this->getService()->deleteServerGroup((int)$data['id']);
    }
}
