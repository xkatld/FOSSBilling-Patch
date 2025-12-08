<?php

declare(strict_types=1);

namespace Box\Mod\Servicelxdapi;

use Pimple\Container;

class Service implements \FOSSBilling\InjectionAwareInterface
{
    protected ?Container $di = null;

    public function setDi(Container|null $di): void
    {
        $this->di = $di;
    }

    public function getDi(): ?Container
    {
        return $this->di;
    }

    public function install(): bool
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS `service_lxdapi` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `client_id` INT(11) UNSIGNED NOT NULL,
                `order_id` INT(11) UNSIGNED NOT NULL,
                `server_id` INT(11) UNSIGNED DEFAULT NULL,
                `container_name` VARCHAR(255) DEFAULT NULL,
                `password` VARCHAR(255) DEFAULT NULL,
                `created_at` DATETIME DEFAULT NULL,
                `updated_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `order_id` (`order_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `service_lxdapi_server_group` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `fill_type` ENUM('least', 'round', 'random') DEFAULT 'least',
                `created_at` DATETIME DEFAULT NULL,
                `updated_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `service_lxdapi_server` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `group_id` INT(11) UNSIGNED DEFAULT NULL,
                `name` VARCHAR(255) NOT NULL,
                `hostname` VARCHAR(255) NOT NULL,
                `port` INT(11) DEFAULT 8443,
                `api_hash` VARCHAR(255) NOT NULL,
                `ssl_verify` TINYINT(1) DEFAULT 0,
                `active` TINYINT(1) DEFAULT 1,
                `max_containers` INT(11) UNSIGNED DEFAULT 0,
                `created_at` DATETIME DEFAULT NULL,
                `updated_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `group_id` (`group_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        $this->di['db']->exec($sql);
        
        $alterStatements = [
            "ALTER TABLE `service_lxdapi` ADD COLUMN `client_id` INT(11) UNSIGNED NOT NULL AFTER `id`",
            "ALTER TABLE `service_lxdapi` ADD COLUMN `order_id` INT(11) UNSIGNED NOT NULL AFTER `client_id`",
            "ALTER TABLE `service_lxdapi` ADD COLUMN `server_id` INT(11) UNSIGNED DEFAULT NULL AFTER `order_id`",
            "ALTER TABLE `service_lxdapi` ADD COLUMN `container_name` VARCHAR(255) DEFAULT NULL AFTER `server_id`",
            "ALTER TABLE `service_lxdapi` ADD COLUMN `password` VARCHAR(255) DEFAULT NULL AFTER `container_name`",
            "ALTER TABLE `service_lxdapi` ADD COLUMN `created_at` DATETIME DEFAULT NULL AFTER `password`",
            "ALTER TABLE `service_lxdapi` ADD COLUMN `updated_at` DATETIME DEFAULT NULL AFTER `created_at`",
            "ALTER TABLE `service_lxdapi_server_group` ADD COLUMN `name` VARCHAR(255) NOT NULL AFTER `id`",
            "ALTER TABLE `service_lxdapi_server_group` ADD COLUMN `fill_type` ENUM('least', 'round', 'random') DEFAULT 'least' AFTER `name`",
            "ALTER TABLE `service_lxdapi_server_group` ADD COLUMN `created_at` DATETIME DEFAULT NULL AFTER `fill_type`",
            "ALTER TABLE `service_lxdapi_server_group` ADD COLUMN `updated_at` DATETIME DEFAULT NULL AFTER `created_at`",
            "ALTER TABLE `service_lxdapi_server` ADD COLUMN `group_id` INT(11) UNSIGNED DEFAULT NULL AFTER `id`",
            "ALTER TABLE `service_lxdapi_server` ADD COLUMN `name` VARCHAR(255) NOT NULL AFTER `group_id`",
            "ALTER TABLE `service_lxdapi_server` ADD COLUMN `hostname` VARCHAR(255) NOT NULL AFTER `name`",
            "ALTER TABLE `service_lxdapi_server` ADD COLUMN `port` INT(11) DEFAULT 8443 AFTER `hostname`",
            "ALTER TABLE `service_lxdapi_server` ADD COLUMN `api_hash` VARCHAR(255) NOT NULL AFTER `port`",
            "ALTER TABLE `service_lxdapi_server` ADD COLUMN `ssl_verify` TINYINT(1) DEFAULT 0 AFTER `api_hash`",
            "ALTER TABLE `service_lxdapi_server` ADD COLUMN `active` TINYINT(1) DEFAULT 1 AFTER `ssl_verify`",
            "ALTER TABLE `service_lxdapi_server` ADD COLUMN `max_containers` INT(11) UNSIGNED DEFAULT 0 AFTER `active`",
            "ALTER TABLE `service_lxdapi_server` ADD COLUMN `created_at` DATETIME DEFAULT NULL AFTER `max_containers`",
            "ALTER TABLE `service_lxdapi_server` ADD COLUMN `updated_at` DATETIME DEFAULT NULL AFTER `created_at`",
        ];
        
        foreach ($alterStatements as $stmt) {
            try {
                $this->di['db']->exec($stmt);
            } catch (\Exception $e) {
            }
        }
        
        return true;
    }

    public function uninstall(): bool
    {
        $this->di['db']->exec("DROP TABLE IF EXISTS `service_lxdapi`");
        $this->di['db']->exec("DROP TABLE IF EXISTS `service_lxdapi_server`");
        $this->di['db']->exec("DROP TABLE IF EXISTS `service_lxdapi_server_group`");
        return true;
    }

    public function getConfig(): array
    {
        return [
            'cpus' => [
                'type' => 'text',
                'label' => 'CPU 核心数',
                'description' => 'CPU 核心数量',
                'default' => '1',
            ],
            'memory' => [
                'type' => 'text',
                'label' => '内存 (MB)',
                'description' => '内存大小，单位 MB',
                'default' => '512',
            ],
            'disk' => [
                'type' => 'text',
                'label' => '硬盘 (MB)',
                'description' => '硬盘大小，单位 MB',
                'default' => '10240',
            ],
            'image' => [
                'type' => 'text',
                'label' => '系统镜像',
                'description' => '系统镜像名称',
                'default' => 'debian12',
            ],
            'ingress' => [
                'type' => 'text',
                'label' => '入站带宽 (Mbit)',
                'description' => '下载速度限制',
                'default' => '100',
            ],
            'egress' => [
                'type' => 'text',
                'label' => '出站带宽 (Mbit)',
                'description' => '上传速度限制',
                'default' => '100',
            ],
            'traffic_limit' => [
                'type' => 'text',
                'label' => '月流量限制 (GB)',
                'description' => '每月流量限制',
                'default' => '100',
            ],
            'ipv4_pool_limit' => [
                'type' => 'text',
                'label' => 'IPv4 地址数量',
                'description' => 'IPv4 独立地址数量上限',
                'default' => '0',
            ],
            'ipv4_mapping_limit' => [
                'type' => 'text',
                'label' => 'IPv4 端口映射数',
                'description' => '端口转发规则数量上限',
                'default' => '5',
            ],
            'ipv6_pool_limit' => [
                'type' => 'text',
                'label' => 'IPv6 地址数量',
                'description' => 'IPv6 独立地址数量上限',
                'default' => '0',
            ],
            'ipv6_mapping_limit' => [
                'type' => 'text',
                'label' => 'IPv6 端口映射数',
                'description' => 'IPv6 端口规则数量上限',
                'default' => '0',
            ],
            'reverse_proxy_limit' => [
                'type' => 'text',
                'label' => '反向代理数量',
                'description' => '反向代理域名数量上限',
                'default' => '0',
            ],
            'cpu_allowance' => [
                'type' => 'text',
                'label' => 'CPU 使用率限制 (%)',
                'description' => 'CPU 占用百分比上限',
                'default' => '100',
            ],
            'io_read' => [
                'type' => 'text',
                'label' => '磁盘读取 (MB/s)',
                'description' => '磁盘读取速度限制',
                'default' => '100',
            ],
            'io_write' => [
                'type' => 'text',
                'label' => '磁盘写入 (MB/s)',
                'description' => '磁盘写入速度限制',
                'default' => '50',
            ],
            'processes_limit' => [
                'type' => 'text',
                'label' => '最大进程数',
                'description' => '进程数量上限',
                'default' => '512',
            ],
            'allow_nesting' => [
                'type' => 'select',
                'label' => '嵌套虚拟化',
                'description' => '支持 Docker 等虚拟化',
                'default' => 'true',
                'options' => ['true' => '启用', 'false' => '禁用'],
            ],
            'memory_swap' => [
                'type' => 'select',
                'label' => 'Swap 交换分区',
                'description' => '启用虚拟内存',
                'default' => 'true',
                'options' => ['true' => '启用', 'false' => '禁用'],
            ],
            'privileged' => [
                'type' => 'select',
                'label' => '特权模式',
                'description' => '启用特权容器',
                'default' => 'false',
                'options' => ['true' => '启用', 'false' => '禁用'],
            ],
        ];
    }

    public function create($order): object
    {
        $model = $this->di['db']->dispense('service_lxdapi');
        $model->client_id = $order->client_id;
        $model->order_id = $order->id;
        $model->created_at = date('Y-m-d H:i:s');
        $model->updated_at = date('Y-m-d H:i:s');
        $this->di['db']->store($model);

        return $model;
    }

    public function activate($order, $model): array
    {
        if (!is_object($model)) {
            throw new \Box_Exception('Service not found');
        }

        $product = $this->di['db']->load('product', $order->product_id);
        $productConfig = json_decode($product->config, true) ?? [];

        $server = null;
        
        if (!empty($productConfig['server_group_id'])) {
            $server = $this->selectServerFromGroup((int)$productConfig['server_group_id']);
            if (!$server) {
                throw new \Box_Exception('服务器组内没有可用的服务器');
            }
        } elseif (!empty($productConfig['server_id'])) {
            $server = $this->di['db']->load('service_lxdapi_server', $productConfig['server_id']);
            if (!$server) {
                throw new \Box_Exception('服务器不存在');
            }
        } else {
            throw new \Box_Exception('产品未配置服务器或服务器组');
        }

        $containerName = 'lxd11451' . $order->client_id . $order->id;
        $password = $this->di['tools']->generatePassword(16, 4);

        $requestData = [
            'name' => $containerName,
            'image' => $productConfig['image'] ?? 'debian12',
            'username' => 'user_' . $order->client_id,
            'password' => $password,
            'cpu' => (int)($productConfig['cpus'] ?? 1),
            'memory' => (int)($productConfig['memory'] ?? 512),
            'disk' => (int)($productConfig['disk'] ?? 10240),
            'ingress' => (int)($productConfig['ingress'] ?? 100),
            'egress' => (int)($productConfig['egress'] ?? 100),
            'traffic_limit' => (int)($productConfig['traffic_limit'] ?? 100),
            'allow_nesting' => ($productConfig['allow_nesting'] ?? 'true') === 'true',
            'memory_swap' => ($productConfig['memory_swap'] ?? 'true') === 'true',
            'privileged' => ($productConfig['privileged'] ?? 'false') === 'true',
            'cpu_allowance' => (int)($productConfig['cpu_allowance'] ?? 100),
            'io_read' => (int)($productConfig['io_read'] ?? 100),
            'io_write' => (int)($productConfig['io_write'] ?? 50),
            'processes_limit' => (int)($productConfig['processes_limit'] ?? 512),
            'ipv4_pool_limit' => (int)($productConfig['ipv4_pool_limit'] ?? 0),
            'ipv4_mapping_limit' => (int)($productConfig['ipv4_mapping_limit'] ?? 5),
            'ipv6_pool_limit' => (int)($productConfig['ipv6_pool_limit'] ?? 0),
            'ipv6_mapping_limit' => (int)($productConfig['ipv6_mapping_limit'] ?? 0),
            'reverse_proxy_limit' => (int)($productConfig['reverse_proxy_limit'] ?? 0),
        ];

        $response = $this->apiRequest($server, '/api/system/containers', $requestData, 'POST');

        if ($response === null) {
            throw new \Box_Exception('无法连接到 LXD 服务器: ' . $server->hostname);
        }

        if (!isset($response['code']) || $response['code'] !== 200) {
            throw new \Box_Exception($response['msg'] ?? '容器创建失败');
        }

        $model->server_id = $server->id;
        $model->container_name = $containerName;
        $model->password = $password;
        $model->updated_at = date('Y-m-d H:i:s');
        $this->di['db']->store($model);

        return [
            'hostname' => $containerName,
            'username' => 'root',
            'password' => $password,
            'server' => $server->hostname,
        ];
    }

    public function suspend($order, $model): bool
    {
        if (!is_object($model) || !$model->server_id) {
            return true;
        }

        $server = $this->di['db']->load('service_lxdapi_server', $model->server_id);
        if (!$server) {
            throw new \Box_Exception('服务器不存在或已被删除');
        }

        $endpoint = '/api/system/containers/' . urlencode($model->container_name) . '/action?action=pause';
        $response = $this->apiRequest($server, $endpoint, [], 'POST');

        if ($response === null) {
            throw new \Box_Exception('无法连接到服务器: ' . $server->hostname);
        }

        $model->updated_at = date('Y-m-d H:i:s');
        $this->di['db']->store($model);

        return true;
    }

    public function unsuspend($order, $model): bool
    {
        if (!is_object($model) || !$model->server_id) {
            return true;
        }

        $server = $this->di['db']->load('service_lxdapi_server', $model->server_id);
        if (!$server) {
            throw new \Box_Exception('服务器不存在或已被删除');
        }

        $endpoint = '/api/system/containers/' . urlencode($model->container_name) . '/action?action=resume';
        $response = $this->apiRequest($server, $endpoint, [], 'POST');

        if ($response === null) {
            throw new \Box_Exception('无法连接到服务器: ' . $server->hostname);
        }

        $model->updated_at = date('Y-m-d H:i:s');
        $this->di['db']->store($model);

        return true;
    }

    public function cancel($order, $model): bool
    {
        return $this->suspend($order, $model);
    }

    public function uncancel($order, $model): bool
    {
        return $this->unsuspend($order, $model);
    }

    public function delete($order, $model): bool
    {
        if (!is_object($model)) {
            return true;
        }

        if ($model->server_id) {
            $server = $this->di['db']->load('service_lxdapi_server', $model->server_id);
            if ($server && $model->container_name) {
                $endpoint = '/api/system/containers/' . urlencode($model->container_name);
                $this->apiRequest($server, $endpoint, [], 'DELETE');
            }
        }

        $this->di['db']->trash($model);

        return true;
    }

    public function toApiArray($model): array
    {
        $server = null;
        if ($model->server_id) {
            $server = $this->di['db']->load('service_lxdapi_server', $model->server_id);
        }

        return [
            'id' => $model->id,
            'client_id' => $model->client_id,
            'order_id' => $model->order_id,
            'container_name' => $model->container_name,
            'server' => $server ? [
                'hostname' => $server->hostname,
                'port' => $server->port,
            ] : null,
        ];
    }

    public function getServiceLxdapiByOrderId(int $orderId): object
    {
        $service = $this->di['db']->findOne('service_lxdapi', 'order_id = ?', [$orderId]);
        if (!$service) {
            throw new \Box_Exception('Service not found');
        }
        return $service;
    }

    public function vmStart($order, $model): bool
    {
        $server = $this->di['db']->load('service_lxdapi_server', $model->server_id);
        if (!$server) {
            throw new \Box_Exception('服务器不存在或已被删除');
        }

        $endpoint = '/api/system/containers/' . urlencode($model->container_name) . '/action?action=start';
        $response = $this->apiRequest($server, $endpoint, [], 'POST');

        return isset($response['code']) && $response['code'] === 200;
    }

    public function vmStop($order, $model): bool
    {
        $server = $this->di['db']->load('service_lxdapi_server', $model->server_id);
        if (!$server) {
            throw new \Box_Exception('服务器不存在或已被删除');
        }

        $endpoint = '/api/system/containers/' . urlencode($model->container_name) . '/action?action=stop';
        $response = $this->apiRequest($server, $endpoint, [], 'POST');

        return isset($response['code']) && $response['code'] === 200;
    }

    public function vmReboot($order, $model): bool
    {
        $server = $this->di['db']->load('service_lxdapi_server', $model->server_id);
        if (!$server) {
            throw new \Box_Exception('服务器不存在或已被删除');
        }

        $endpoint = '/api/system/containers/' . urlencode($model->container_name) . '/action?action=restart';
        $response = $this->apiRequest($server, $endpoint, [], 'POST');

        return isset($response['code']) && $response['code'] === 200;
    }

    public function vmReinstall($order, $model, string $image, string $password = ''): bool
    {
        if (empty($image)) {
            throw new \Box_Exception('Image is required');
        }

        $server = $this->di['db']->load('service_lxdapi_server', $model->server_id);
        if (!$server) {
            throw new \Box_Exception('服务器不存在或已被删除');
        }

        $endpoint = '/api/system/containers/' . urlencode($model->container_name) . '/action?action=reinstall';
        
        $requestData = ['image' => $image];
        if (!empty($password)) {
            $requestData['password'] = $password;
            $model->password = $password;
            $model->updated_at = date('Y-m-d H:i:s');
            $this->di['db']->store($model);
        }

        $response = $this->apiRequest($server, $endpoint, $requestData, 'POST');

        return isset($response['code']) && $response['code'] === 200;
    }

    public function vmResetPassword($order, $model, string $password): bool
    {
        $server = $this->di['db']->load('service_lxdapi_server', $model->server_id);
        if (!$server) {
            throw new \Box_Exception('服务器不存在或已被删除');
        }

        $endpoint = '/api/system/containers/' . urlencode($model->container_name) . '/action?action=reset-password';
        $response = $this->apiRequest($server, $endpoint, ['password' => $password], 'POST');

        if (isset($response['code']) && $response['code'] === 200) {
            $model->password = $password;
            $model->updated_at = date('Y-m-d H:i:s');
            $this->di['db']->store($model);
            return true;
        }

        return false;
    }

    public function vmInfo($order, $model): array
    {
        $server = $this->di['db']->load('service_lxdapi_server', $model->server_id);
        if (!$server) {
            return ['status' => 'unknown', 'error' => '服务器不存在'];
        }

        $endpoint = '/api/system/containers/' . urlencode($model->container_name);
        $response = $this->apiRequest($server, $endpoint, [], 'GET');

        if (!isset($response['code']) || $response['code'] !== 200) {
            return ['status' => 'unknown'];
        }

        return $response['data'] ?? ['status' => 'unknown'];
    }

    public function getConsoleUrl($order, $model): string
    {
        $server = $this->di['db']->load('service_lxdapi_server', $model->server_id);
        if (!$server) {
            throw new \Box_Exception('服务器不存在或已被删除');
        }

        $endpoint = '/api/system/console/create-token';
        $response = $this->apiRequest($server, $endpoint, ['hostname' => $model->container_name], 'POST');

        if (!isset($response['code']) || $response['code'] !== 200 || !isset($response['data']['token'])) {
            throw new \Box_Exception('Failed to create console token');
        }

        return 'https://' . $server->hostname . ':' . $server->port . '/console?token=' . $response['data']['token'];
    }

    public function trafficReset($order, $model): bool
    {
        $server = $this->di['db']->load('service_lxdapi_server', $model->server_id);
        if (!$server) {
            throw new \Box_Exception('服务器不存在或已被删除');
        }

        $endpoint = '/api/system/traffic/reset?name=' . urlencode($model->container_name);
        $response = $this->apiRequest($server, $endpoint, [], 'POST');

        return isset($response['code']) && $response['code'] === 200;
    }

    public function getTemplates($model): array
    {
        $server = $this->di['db']->load('service_lxdapi_server', $model->server_id);
        if (!$server) {
            return [];
        }

        $endpoint = '/api/system/containers/' . urlencode($model->container_name);
        
        $containerResponse = $this->apiRequest($server, $endpoint, [], 'GET');
        
        return $containerResponse['data']['templates'] ?? [];
    }

    protected function findAvailableServer(): ?object
    {
        return $this->di['db']->findOne('service_lxdapi_server', 'active = 1 ORDER BY id ASC');
    }

    public function apiRequest(object $server, string $endpoint, array $data = [], string $method = 'POST'): ?array
    {
        $url = 'https://' . $server->hostname . ':' . $server->port . $endpoint;

        $ch = curl_init();
        
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => (bool)($server->ssl_verify ?? false),
            CURLOPT_SSL_VERIFYHOST => (bool)($server->ssl_verify ?? false) ? 2 : 0,
            CURLOPT_HTTPHEADER => [
                'X-API-Hash: ' . $server->api_hash,
                'Content-Type: application/json',
            ],
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            if (!empty($data)) {
                $options[CURLOPT_POSTFIELDS] = json_encode($data);
            }
        } elseif ($method === 'DELETE') {
            $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        } elseif ($method === 'PUT') {
            $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
            if (!empty($data)) {
                $options[CURLOPT_POSTFIELDS] = json_encode($data);
            }
        }

        curl_setopt_array($ch, $options);
        
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno) {
            return null;
        }

        return json_decode($response, true);
    }

    public function testConnection(object $server): array
    {
        $url = 'https://' . $server->hostname . ':' . $server->port . '/api/system/containers';
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => (bool)($server->ssl_verify ?? false),
            CURLOPT_SSL_VERIFYHOST => (bool)($server->ssl_verify ?? false) ? 2 : 0,
            CURLOPT_HTTPHEADER => [
                'X-API-Hash: ' . $server->api_hash,
                'Content-Type: application/json',
            ],
        ]);
        
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($errno) {
            return ['success' => false, 'message' => '连接失败: ' . $error];
        }
        
        if ($httpCode === 0) {
            return ['success' => false, 'message' => '无法连接到服务器，请检查地址和端口'];
        }
        
        $data = json_decode($response, true);
        
        if ($httpCode === 401 || (isset($data['code']) && $data['code'] === 401)) {
            return ['success' => false, 'message' => 'API 密钥错误或无权限'];
        }
        
        if ($httpCode === 403 || (isset($data['code']) && $data['code'] === 403)) {
            return ['success' => false, 'message' => 'API 密钥无效或已过期'];
        }
        
        if ($httpCode !== 200 && (!isset($data['code']) || $data['code'] !== 200)) {
            $msg = $data['msg'] ?? $data['message'] ?? '未知错误';
            return ['success' => false, 'message' => '请求失败: ' . $msg];
        }
        
        return ['success' => true, 'message' => '连接成功'];
    }

    public function getServers(): array
    {
        return $this->di['db']->find('service_lxdapi_server');
    }

    public function createServer(array $data): int
    {
        $server = $this->di['db']->dispense('service_lxdapi_server');
        $server->group_id = !empty($data['group_id']) ? (int)$data['group_id'] : null;
        $server->name = $data['name'];
        $server->hostname = $data['hostname'];
        $server->port = $data['port'] ?? 8443;
        $server->api_hash = $data['api_hash'];
        $server->ssl_verify = $data['ssl_verify'] ?? 0;
        $server->active = $data['active'] ?? 1;
        $server->max_containers = (int)($data['max_containers'] ?? 0);
        $server->created_at = date('Y-m-d H:i:s');
        $server->updated_at = date('Y-m-d H:i:s');

        return $this->di['db']->store($server);
    }

    public function updateServer(int $id, array $data): bool
    {
        $server = $this->di['db']->load('service_lxdapi_server', $id);
        if (!$server) {
            throw new \Box_Exception('Server not found');
        }

        foreach (['group_id', 'name', 'hostname', 'port', 'api_hash', 'ssl_verify', 'active', 'max_containers'] as $field) {
            if (array_key_exists($field, $data)) {
                if ($field === 'group_id') {
                    $server->$field = !empty($data[$field]) ? (int)$data[$field] : null;
                } elseif ($field === 'max_containers') {
                    $server->$field = (int)($data[$field] ?? 0);
                } elseif ($field === 'api_hash') {
                    if (!empty($data[$field])) {
                        $server->$field = $data[$field];
                    }
                } else {
                    $server->$field = $data[$field];
                }
            }
        }
        $server->updated_at = date('Y-m-d H:i:s');

        $this->di['db']->store($server);
        return true;
    }

    public function deleteServer(int $id): bool
    {
        $server = $this->di['db']->load('service_lxdapi_server', $id);
        if (!$server) {
            throw new \Box_Exception('Server not found');
        }

        $this->di['db']->trash($server);
        return true;
    }

    public function getServerGroups(): array
    {
        return $this->di['db']->find('service_lxdapi_server_group');
    }

    public function getServerGroup(int $id): ?object
    {
        return $this->di['db']->load('service_lxdapi_server_group', $id);
    }

    public function createServerGroup(array $data): int
    {
        $group = $this->di['db']->dispense('service_lxdapi_server_group');
        $group->name = $data['name'];
        $group->fill_type = $data['fill_type'] ?? 'least';
        $group->created_at = date('Y-m-d H:i:s');
        $group->updated_at = date('Y-m-d H:i:s');

        return $this->di['db']->store($group);
    }

    public function updateServerGroup(int $id, array $data): bool
    {
        $group = $this->di['db']->load('service_lxdapi_server_group', $id);
        if (!$group) {
            throw new \Box_Exception('服务器组不存在');
        }

        if (isset($data['name'])) {
            $group->name = $data['name'];
        }
        if (isset($data['fill_type'])) {
            $group->fill_type = $data['fill_type'];
        }
        $group->updated_at = date('Y-m-d H:i:s');

        $this->di['db']->store($group);
        return true;
    }

    public function deleteServerGroup(int $id): bool
    {
        $group = $this->di['db']->load('service_lxdapi_server_group', $id);
        if (!$group) {
            throw new \Box_Exception('服务器组不存在');
        }

        $this->di['db']->exec("UPDATE service_lxdapi_server SET group_id = NULL WHERE group_id = ?", [$id]);

        $this->di['db']->trash($group);
        return true;
    }

    public function getServersByGroup(int $groupId): array
    {
        return $this->di['db']->find('service_lxdapi_server', 'group_id = ? AND active = 1', [$groupId]);
    }

    public function selectServerFromGroup(int $groupId): ?object
    {
        $group = $this->di['db']->load('service_lxdapi_server_group', $groupId);
        if (!$group) {
            return null;
        }

        $servers = $this->di['db']->find('service_lxdapi_server', 'group_id = ? AND active = 1', [$groupId]);
        if (empty($servers)) {
            return null;
        }

        $availableServers = $this->filterAvailableServers($servers);
        if (empty($availableServers)) {
            return null;
        }

        $fillType = $group->fill_type ?? 'least';

        switch ($fillType) {
            case 'random':
                return $availableServers[array_rand($availableServers)];

            case 'round':
                return $this->selectLeastLoadedServer($availableServers);

            case 'least':
            default:
                return $this->selectLeastLoadedServer($availableServers);
        }
    }

    protected function filterAvailableServers(array $servers): array
    {
        $available = [];
        foreach ($servers as $server) {
            $maxContainers = (int)($server->max_containers ?? 0);
            if ($maxContainers === 0) {
                $available[] = $server;
                continue;
            }
            
            $count = $this->di['db']->getCell(
                "SELECT COUNT(*) FROM service_lxdapi WHERE server_id = ?",
                [$server->id]
            );
            
            if ((int)$count < $maxContainers) {
                $available[] = $server;
            }
        }
        return $available;
    }

    protected function selectLeastLoadedServer(array $servers): ?object
    {
        if (empty($servers)) {
            return null;
        }

        $serverLoads = [];
        foreach ($servers as $server) {
            $count = $this->di['db']->getCell(
                "SELECT COUNT(*) FROM service_lxdapi WHERE server_id = ?",
                [$server->id]
            );
            $serverLoads[$server->id] = [
                'server' => $server,
                'count' => (int)$count,
            ];
        }

        usort($serverLoads, fn($a, $b) => $a['count'] <=> $b['count']);

        return $serverLoads[0]['server'] ?? null;
    }

    public function getServerGroupsWithCount(): array
    {
        $groups = $this->di['db']->find('service_lxdapi_server_group');
        $result = [];

        foreach ($groups as $group) {
            $serverCount = $this->di['db']->getCell(
                "SELECT COUNT(*) FROM service_lxdapi_server WHERE group_id = ?",
                [$group->id]
            );
            $result[] = [
                'id' => $group->id,
                'name' => $group->name,
                'fill_type' => $group->fill_type,
                'server_count' => (int)$serverCount,
            ];
        }

        return $result;
    }
}
