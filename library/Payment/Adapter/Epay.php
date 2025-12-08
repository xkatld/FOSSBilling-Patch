<?php

/**
 * ========== 易支付-支付宝 by xkatld 开始 ==========
 * 
 * @link https://github.com/xkatld/FOSSBilling-Patch
 * @version v1.0.0
 * 
 * ========== 易支付-支付宝 by xkatld ==========
 */

class Payment_Adapter_Epay implements FOSSBilling\InjectionAwareInterface
{
    protected ?Pimple\Container $di = null;

    private $config;

    public function setDi(Pimple\Container $di): void
    {
        $this->di = $di;
    }

    public function getDi(): ?Pimple\Container
    {
        return $this->di;
    }

    public function __construct($config)
    {
        $this->config = $config;
    }

    public static function getConfig()
    {
        return [
            'supports_one_time_payments' => true,
            'supports_subscriptions' => false,
            'description' => '易支付-支付宝 by xkatld',
            'logo' => [
                'logo' => 'alipay.png',
                'height' => '50px',
                'width' => '50px',
            ],
            'form' => [
                'apiurl' => [
                    'text', [
                        'label' => '易支付网关地址:',
                    ],
                ],
                'pid' => [
                    'text', [
                        'label' => '商户ID:',
                    ],
                ],
                'key' => [
                    'text', [
                        'label' => '商户密钥:',
                    ],
                ],
            ],
        ];
    }

    public function getHtml($api_admin, $invoice_id, $subscription)
    {
        if (empty($this->config['apiurl'])) {
            throw new Payment_Exception('易支付网关地址未配置');
        }
        if (empty($this->config['pid'])) {
            throw new Payment_Exception('易支付商户ID未配置');
        }
        if (empty($this->config['key'])) {
            throw new Payment_Exception('易支付商户密钥未配置');
        }

        $invoiceModel = $this->di['db']->load('Invoice', $invoice_id);
        $invoiceService = $this->di['mod_service']('Invoice');
        $payGatewayService = $this->di['mod_service']('Invoice', 'PayGateway');
        $payGateway = $this->di['db']->findOne('PayGateway', 'gateway = "Epay"');

        $invoiceTotal = $invoiceService->getTotalWithTax($invoiceModel);
        $callbackUrl = $payGatewayService->getCallbackUrl($payGateway, $invoiceModel);

        $shortHash = substr(md5($invoiceModel->hash), 0, 8);
        $out_trade_no = $invoiceModel->id . '_' . $shortHash . '_' . time();
        $name = '订单 #' . $invoiceModel->serie . sprintf('%05s', $invoiceModel->nr);
        $money = number_format($invoiceTotal, 2, '.', '');

        $apiurl = rtrim($this->config['apiurl'], '/');

        $param = [
            'pid' => strval($this->config['pid']),
            'type' => 'alipay',
            'notify_url' => $callbackUrl,
            'return_url' => $callbackUrl . '&redirect=1&invoice_hash=' . $invoiceModel->hash,
            'out_trade_no' => $out_trade_no,
            'name' => $name,
            'money' => $money,
        ];

        $param['sign'] = $this->buildSign($param);
        $param['sign_type'] = 'MD5';

        $submitUrl = $apiurl . '/submit.php';

        $html = '<form id="dopay" action="' . $submitUrl . '" method="post">';
        foreach ($param as $k => $v) {
            $html .= '<input type="hidden" name="' . $k . '" value="' . $v . '"/>';
        }
        $html .= '<input type="submit" class="btn btn-primary" value="正在跳转到支付页面..."></form>';
        $html .= '<script>document.getElementById("dopay").submit();</script>';

        return $html;
    }

    public function getInvoiceId($data)
    {
        $out_trade_no = $data['get']['out_trade_no'] ?? '';
        if (empty($out_trade_no)) {
            return $data['get']['invoice_id'] ?? null;
        }
        
        $parts = explode('_', $out_trade_no);
        if (count($parts) >= 2 && is_numeric($parts[0])) {
            return (int) $parts[0];
        }
        return $data['get']['invoice_id'] ?? null;
    }

    public function processTransaction($api_admin, $id, $data, $gateway_id)
    {
        $tx = $this->di['db']->load('Transaction', $id);
        if (!$tx) {
            throw new Payment_Exception('交易记录不存在: ' . $id);
        }

        if (empty($tx->invoice_id)) {
            $tx->invoice_id = $this->getInvoiceId($data);
            $this->di['db']->store($tx);
        }

        $invoice = $this->di['db']->load('Invoice', $tx->invoice_id);
        if (!$invoice) {
            throw new Payment_Exception('账单不存在: ' . $tx->invoice_id);
        }

        $get = $data['get'];

        $epayParams = [];
        $epayKeys = ['pid', 'trade_no', 'out_trade_no', 'type', 'name', 'money', 'trade_status', 'sign', 'sign_type'];
        foreach ($epayKeys as $key) {
            if (isset($get[$key])) {
                $epayParams[$key] = $get[$key];
            }
        }

        if (!$this->verifySign($epayParams)) {
            throw new Payment_Exception('易支付签名验证失败');
        }

        $trade_status = $get['trade_status'] ?? '';
        $trade_no = $get['trade_no'] ?? '';
        $money = $get['money'] ?? 0;

        $tx->txn_id = $trade_no;
        $tx->txn_status = $trade_status;
        $tx->amount = $money;
        $tx->currency = $invoice->currency;
        $tx->updated_at = date('Y-m-d H:i:s');

        if ($trade_status == 'TRADE_SUCCESS') {
            if ($tx->status !== 'processed') {
                $clientService = $this->di['mod_service']('client');
                $invoiceService = $this->di['mod_service']('Invoice');

                $client = $this->di['db']->load('Client', $invoice->client_id);
                if (!$client) {
                    throw new Payment_Exception('客户不存在: ' . $invoice->client_id);
                }

                $bd = [
                    'amount' => $money,
                    'description' => '易支付交易 ' . $trade_no,
                    'type' => 'transaction',
                    'rel_id' => $tx->id,
                ];
                $clientService->addFunds($client, $bd['amount'], $bd['description'], $bd);
                $invoiceService->payInvoiceWithCredits($invoice);

                $tx->status = 'processed';
            }
        } else {
            $tx->status = 'received';
        }

        $this->di['db']->store($tx);

        $hasRedirect = isset($get['redirect']) || isset($get['amp;redirect']);
        if ($hasRedirect) {
            $url = $this->di['url']->link('invoice/' . $invoice->hash);
            echo '<html><head><meta http-equiv="refresh" content="0;url=' . $url . '"><script>window.location.href="' . $url . '";</script></head><body>正在跳转...</body></html>';
            exit;
        }
        
        echo 'success';
        exit;
    }

    private function buildSign($param)
    {
        ksort($param);
        reset($param);
        $signstr = '';
        foreach ($param as $k => $v) {
            if ($k != 'sign' && $k != 'sign_type' && $v != '') {
                $signstr .= $k . '=' . $v . '&';
            }
        }
        $signstr = substr($signstr, 0, -1);
        $signstr .= $this->config['key'];
        return md5($signstr);
    }

    private function verifySign($param)
    {
        if (empty($param['sign'])) {
            return false;
        }
        $sign = $this->buildSign($param);
        return $sign === $param['sign'];
    }
}
// ========== 易支付-支付宝 by xkatld 结束 ==========
