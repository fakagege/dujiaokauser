<?php

namespace App\Http\Controllers\Pay;

use App\Exceptions\RuleValidationException;
use App\Http\Controllers\PayController;
use Illuminate\Http\Request;
use Yansongda\Pay\Pay;
use Illuminate\Support\Facades\Log;

class AlipayController extends PayController
{

    /**
     * 支付宝支付网关
     *
     * @param string $payway
     * @param string $orderSN
     */
  public function gateway(string $payway, string $orderSN)
{
    try {
        // 加载网关
        $this->loadGateWay($orderSN, $payway);
        $config = [
            'app_id' => $this->payGateway->merchant_id,
            'alipay_public_cert_path' => storage_path('certs/alipayCertPublicKey_RSA2.crt'), // 支付宝公钥证书路径
            'app_public_cert_path' => storage_path('certs/appCertPublicKey_2017070907690014.crt'), // 应用公钥证书路径
            'alipay_root_cert_path' => storage_path('certs/alipayRootCert.crt'), // 支付宝根证书路径
            'private_key' => $this->payGateway->merchant_pem, // 商户私钥
            'notify_url' => url($this->payGateway->pay_handleroute . '/notify_url'),
            'return_url' => url('detail-order-sn', ['orderSN' => $this->order->order_sn]),
            'http' => [ // optional
                'timeout' => 10.0,
                'connect_timeout' => 10.0,
            ],
        ];

        // Log the config for debugging purposes
        Log::info('Alipay Config', $config);

        $order = [
            'out_trade_no' => $this->order->order_sn,
            'total_amount' => (float)$this->order->actual_price,
            'subject' => $this->order->order_sn
        ];

        switch ($payway) {
            case 'zfbf2f':
            case 'alipayscan':
                try {
                    $result = Pay::alipay($config)->scan($order)->toArray();
                    $result['payname'] = $this->order->order_sn;
                    $result['actual_price'] = (float)$this->order->actual_price;
                    $result['orderid'] = $this->order->order_sn;
                    $result['jump_payuri'] = $result['qr_code'];
                    return $this->render('static_pages/qrpay', $result, __('dujiaoka.scan_qrcode_to_pay'));
                } catch (\Exception $e) {
                    // Log detailed error message
                    Log::error('Alipay scan payment error', ['message' => $e->getMessage(), 'stack' => $e->getTraceAsString()]);
                    return $this->err(__('dujiaoka.prompt.abnormal_payment_channel') . $e->getMessage());
                }
            case 'aliweb':
                try {
                    $result = Pay::alipay($config)->web($order);
                    return $result;
                } catch (\Exception $e) {
                    // Log detailed error message
                    Log::error('Alipay web payment error', ['message' => $e->getMessage(), 'stack' => $e->getTraceAsString()]);
                    return $this->err(__('dujiaoka.prompt.abnormal_payment_channel') . $e->getMessage());
                }
            case 'aliwap':
                try {
                    $result = Pay::alipay($config)->wap($order);
                    return $result;
                } catch (\Exception $e) {
                    // Log detailed error message
                    Log::error('Alipay wap payment error', ['message' => $e->getMessage(), 'stack' => $e->getTraceAsString()]);
                    return $this->err(__('dujiaoka.prompt.abnormal_payment_channel') . $e->getMessage());
                }
        }
    } catch (RuleValidationException $exception) {
        // Log validation exception message
        Log::error('Rule validation exception', ['message' => $exception->getMessage(), 'stack' => $exception->getTraceAsString()]);
        return $this->err($exception->getMessage());
    }
}



    /**
     * 异步通知
     */
  public function notifyUrl(Request $request)
{
    Log::info('Alipay notify callback', $request->all());

    $orderSN = $request->input('out_trade_no');
    $order = $this->orderService->detailOrderSN($orderSN);
    if (!$order) {
        Log::error('Order not found', ['orderSN' => $orderSN]);
        return 'error';
    }

    $payGateway = $this->payService->detail($order->pay_id);
    if (!$payGateway) {
        Log::error('Payment gateway not found', ['pay_id' => $order->pay_id]);
        return 'error';
    }

    if ($payGateway->pay_handleroute != '/pay/alipay') {
        Log::error('Payment handler route mismatch', [
            'expected' => '/pay/alipay',
            'actual' => $payGateway->pay_handleroute
        ]);
        return 'fail';
    }

    $config = [
        'app_id' => $payGateway->merchant_id,
        'alipay_public_cert_path' => storage_path('certs/alipayCertPublicKey_RSA2.crt'), // 支付宝公钥证书路径
        'app_public_cert_path' => storage_path('certs/appCertPublicKey_2017070907690014.crt'), // 应用公钥证书路径
        'alipay_root_cert_path' => storage_path('certs/alipayRootCert.crt'), // 支付宝根证书路径
        'private_key' => $payGateway->merchant_pem, // 商户私钥
    ];

    $pay = Pay::alipay($config);
    try {
        // 验证签名
        $data = $pay->verify();
        Log::info('Alipay notify verification result', $data);

        if ($data['trade_status'] == 'TRADE_SUCCESS' || $data['trade_status'] == 'TRADE_FINISHED') {
            $this->orderProcessService->completedOrder($data['out_trade_no'], $data['total_amount'], $data['trade_no']);
        }
        return response('success');
    } catch (\Exception $exception) {
        Log::error('Alipay notify verification failed', [
            'exception' => $exception->getMessage(),
            'request' => $request->all()
        ]);
        return response('fail');
    }
}



}
