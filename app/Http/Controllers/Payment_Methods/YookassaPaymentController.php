<?php

namespace App\Http\Controllers\Payment_Methods;

use App\Models\PaymentRequest;
use App\Traits\Processor;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use YooKassa\Client;

class YookassaPaymentController extends Controller
{
    use Processor;

    private mixed $config_values;
    private PaymentRequest $payment;
    private Client $client;

    public function __construct(PaymentRequest $payment)
    {
        $config = $this->payment_config('yookassa', 'payment_config');
        if (!is_null($config) && $config->mode == 'live') {
            $this->config_values = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $this->config_values = json_decode($config->test_values);
        }

        $this->payment = $payment;

        // Инициализируем YooKassa Client с SSL настройками
        if (isset($this->config_values)) {
            $this->initializeYooKassaClient();
        }
    }

    /**
     * Инициализация YooKassa клиента с настройками SSL для локальной среды
     */
    private function initializeYooKassaClient()
    {
        // Агрессивная настройка для полного обхода SSL проблем в локальной среде
        if (env('YOOKASSA_DISABLE_SSL_VERIFY', false)) {
            // Устанавливаем переменные окружения для полного отключения SSL проверки
            putenv('CURL_CA_BUNDLE=');
            putenv('SSL_CERT_FILE=');
            putenv('SSL_CERT_DIR=');

            // Отключаем все SSL проверки через ini_set
            ini_set('curl.cainfo', '');
            ini_set('openssl.cafile', '');
            ini_set('openssl.capath', '');

            // Настройка по умолчанию для всех stream context
            $defaultContext = stream_context_set_default([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                    'disable_compression' => true,
                    'crypto_method' => STREAM_CRYPTO_METHOD_ANY_CLIENT,
                    'ciphers' => 'DEFAULT:!DH',
                ],
                'http' => [
                    'timeout' => 60,
                    'ignore_errors' => true,
                    'user_agent' => 'YooKassa-PHP-SDK-LocalDev/1.0',
                    'protocol_version' => 1.1,
                ]
            ]);

            // Глобальные настройки cURL
            if (function_exists('curl_init')) {
                $ch = curl_init();
                $curlOpts = [
                    CURLOPT_SSL_VERIFYPEER => 0,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSLVERSION => CURL_SSLVERSION_DEFAULT,
                    CURLOPT_TIMEOUT => 60,
                    CURLOPT_CONNECTTIMEOUT => 30,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_MAXREDIRS => 3,
                    CURLOPT_USERAGENT => 'YooKassa-PHP-SDK-LocalDev/1.0',
                    CURLOPT_RETURNTRANSFER => true,
                ];
                curl_setopt_array($ch, $curlOpts);
                curl_close($ch);
            }
        }

        // Создаем YooKassa клиент
        $this->client = new Client();

        // Дополнительные настройки для локальной среды - отключаем SSL проверку
        if (env('YOOKASSA_DISABLE_SSL_VERIFY', false)) {
            $customCurlClient = new YookassaCurlClient();
            $this->client->setApiClient($customCurlClient);
        }

        // Устанавливаем авторизацию после настройки клиента
        $this->client->setAuth($this->config_values->shop_id, $this->config_values->secret_key);
    }

    /**
     * Настройка cURL опций для YooKassa клиента в локальной среде
     */
    private function configureCurlForClient()
    {
        // Переопределяем глобальные настройки cURL
        if (function_exists('curl_setopt')) {
            $curlDefaults = [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_USERAGENT => 'YooKassa-PHP-SDK-LocalDev',
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
            ];

            // Применяем настройки ко всем новым cURL соединениям
            foreach ($curlDefaults as $option => $value) {
                curl_setopt(curl_init(), $option, $value);
            }
        }
    }

    public function index(Request $request): View|Factory|JsonResponse|Application
    {
        Log::info('YooKassa index called', ['payment_id' => $request['payment_id']]);

        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            Log::error('YooKassa validation failed', ['errors' => $validator->errors()]);
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)), 400);
        }

        $data = $this->payment::where(['id' => $request['payment_id']])->where(['is_paid' => 0])->first();
        if (!isset($data)) {
            Log::warning('YooKassa payment not found or already paid', ['payment_id' => $request['payment_id']]);
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }

        try {
            $payer = json_decode($data['payer_information']);

            // Получаем информацию о бизнесе
            if ($data['additional_data'] != null) {
                $business = json_decode($data['additional_data']);
                $business_name = $business->business_name ?? "Online Store";
            } else {
                $business_name = "Online Store";
            }

            Log::info('YooKassa creating payment', ['amount' => $data->payment_amount, 'currency' => $data->currency_code]);

            // Создаем платеж с embedded confirmation для widget интеграции
            $payment = $this->client->createPayment([
                'amount' => [
                    'value' => number_format($data->payment_amount, 2, '.', ''),
                    'currency' => $data->currency_code ?? 'RUB',
                ],
                'confirmation' => [
                    'type' => 'embedded'
                ],
                'capture' => true,
                'description' => 'Order #' . $data->attribute_id,
                'metadata' => [
                    'order_id' => $data->attribute_id,
                    'payment_request_id' => $data->id,
                    'platform' => 'manysales'
                ],
                'receipt' => [
                    'customer' => [
                        'email' => $payer->email
                    ],
                    'items' => [
                        [
                            'description' => $business_name . ' - Order #' . $data->attribute_id,
                            'quantity' => 1,
                            'amount' => [
                                'value' => number_format($data->payment_amount, 2, '.', ''),
                                'currency' => $data->currency_code ?? 'RUB'
                            ],
                            'vat_code' => 1,
                            'payment_mode' => 'full_prepayment',
                            'payment_subject' => 'commodity'
                        ]
                    ]
                ]
            ], uniqid('', true));

            // Сохраняем transaction_id
            $data->transaction_id = $payment->getId();
            $data->save();

            // Получаем confirmation token для widget
            $confirmationToken = $payment->getConfirmation()->getConfirmationToken();

            $config = $this->config_values;

            Log::info('YooKassa payment created successfully', ['payment_id' => $payment->getId()]);

            return view('payment.yookassa', compact('data', 'config') + [
                'confirmation_token' => $confirmationToken,
                'payment_id' => $payment->getId()
            ]);

        } catch (\Exception $e) {
            $error_message = $e->getMessage();
            Log::error('YooKassa payment creation error', ['message' => $error_message]);

            // Дополнительная информация для SSL ошибок
            if (strpos($error_message, 'SSL certificate') !== false || strpos($error_message, 'self-signed certificate') !== false) {
                $error_message .= ' (Suggestion: Set YOOKASSA_DISABLE_SSL_VERIFY=true in .env for local development)';
            }

            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, [['error_code' => 'payment_failed', 'message' => $error_message]]), 400);
        }
    }

    public function payment(Request $request): JsonResponse|RedirectResponse
    {
        Log::info('YooKassa payment method called', ['payment_id' => $request['payment_id']]);

        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)), 400);
        }

        $payment_data = $this->payment::where(['id' => $request['payment_id']])->where(['is_paid' => 0])->first();
        if (!isset($payment_data)) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }

        try {
            $payer = json_decode($payment_data['payer_information']);

            // Получаем информацию о бизнесе
            if ($payment_data['additional_data'] != null) {
                $business = json_decode($payment_data['additional_data']);
                $business_name = $business->business_name ?? "Online Store";
            } else {
                $business_name = "Online Store";
            }

            // Создаем платеж через SDK
            $payment = $this->client->createPayment([
                'amount' => [
                    'value' => number_format($payment_data->payment_amount, 2, '.', ''),
                    'currency' => $payment_data->currency_code ?? 'RUB',
                ],
                'confirmation' => [
                    'type' => 'redirect',
                    'return_url' => route('yookassa.callback', ['payment_id' => $request['payment_id']]),
                ],
                'capture' => true,
                'description' => 'Order #' . $payment_data->attribute_id,
                'receipt' => [
                    'customer' => [
                        'email' => $payer->email
                    ],
                    'items' => [
                        [
                            'description' => $business_name . ' - Order #' . $payment_data->attribute_id,
                            'quantity' => 1,
                            'amount' => [
                                'value' => number_format($payment_data->payment_amount, 2, '.', ''),
                                'currency' => $payment_data->currency_code ?? 'RUB'
                            ],
                            'vat_code' => 1,
                            'payment_mode' => 'full_prepayment',
                            'payment_subject' => 'commodity'
                        ]
                    ]
                ]
            ], uniqid('', true));

            // Сохраняем transaction_id
            $payment_data->transaction_id = $payment->getId();
            $payment_data->save();

            Log::info('YooKassa redirecting to payment page', ['confirmation_url' => $payment->getConfirmation()->getConfirmationUrl()]);

            // Перенаправляем на страницу оплаты
            return redirect($payment->getConfirmation()->getConfirmationUrl());

        } catch (\Exception $e) {
            $error_message = $e->getMessage();
            Log::error('YooKassa payment error', ['message' => $error_message]);

            // Дополнительная информация для SSL ошибок
            if (strpos($error_message, 'SSL certificate') !== false || strpos($error_message, 'self-signed certificate') !== false) {
                $error_message .= ' (Suggestion: Set YOOKASSA_DISABLE_SSL_VERIFY=true in .env for local development)';
            }

            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, [['error_code' => 'payment_failed', 'message' => $error_message]]), 400);
        }
    }

    public function return(Request $request): Application|JsonResponse|Redirector|RedirectResponse
    {
        try {
            $paymentId = $request->input('payment_id');

            if (!$paymentId) {
                throw new \Exception('Payment ID not provided');
            }

            // Получаем информацию о платеже через SDK
            $payment = $this->client->getPaymentInfo($paymentId);

            // Находим payment request по transaction_id
            $payment_data = $this->payment::where(['transaction_id' => $paymentId])->first();

            if (!$payment_data) {
                return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
            }

            if ($payment->getStatus() === 'succeeded') {
                // Обновляем статус платежа
                $this->payment::where(['transaction_id' => $paymentId])->update([
                    'payment_method' => 'yookassa',
                    'is_paid' => 1,
                ]);

                $data = $this->payment::where(['transaction_id' => $paymentId])->first();

                if (isset($data) && function_exists($data->success_hook)) {
                    call_user_func($data->success_hook, $data);
                }

                return $this->payment_response($data, 'success');
            } else {
                if (isset($payment_data) && function_exists($payment_data->failure_hook)) {
                    call_user_func($payment_data->failure_hook, $payment_data);
                }
                return $this->payment_response($payment_data, 'fail');
            }

        } catch (\Exception $e) {
            Log::error('YooKassa return error', ['message' => $e->getMessage()]);
            // Попытаемся найти по другим критериям если не удалось найти по payment_id
            if (isset($request['payment_id'])) {
                $payment_data = $this->payment::where(['id' => $request['payment_id']])->first();
                if (isset($payment_data) && function_exists($payment_data->failure_hook)) {
                    call_user_func($payment_data->failure_hook, $payment_data);
                }
                return $this->payment_response($payment_data, 'fail');
            }

            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, [['error_code' => 'return_error', 'message' => $e->getMessage()]]), 400);
        }
    }

    public function callback(Request $request): Application|JsonResponse|Redirector|RedirectResponse
    {
        Log::info('YooKassa callback called', ['payment_id' => $request['payment_id']]);

        sleep(2); // Небольшая задержка для обработки webhook

        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)), 400);
        }

        $payment_data = $this->payment::where(['id' => $request['payment_id']])->where(['is_paid' => 0])->first();
        if (!isset($payment_data)) {
            // Payment might already be processed
            $payment_data = $this->payment::where(['id' => $request['payment_id']])->first();
            if ($payment_data && $payment_data->is_paid == 1) {
                return $this->payment_response($payment_data, 'success');
            }
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }

        try {
            // Получаем информацию о платеже через SDK
            $payment = $this->client->getPaymentInfo($payment_data->transaction_id);

            if ($payment->getStatus() === 'succeeded') {
                $this->payment::where(['id' => $request['payment_id']])->update([
                    'payment_method' => 'yookassa',
                    'is_paid' => 1,
                ]);

                $data = $this->payment::where(['id' => $request['payment_id']])->first();

                if (isset($data) && function_exists($data->success_hook)) {
                    call_user_func($data->success_hook, $data);
                }

                return $this->payment_response($data, 'success');
            } else {
                $payment_data = $this->payment::where(['id' => $request['payment_id']])->first();
                if (isset($payment_data) && function_exists($payment_data->failure_hook)) {
                    call_user_func($payment_data->failure_hook, $payment_data);
                }
                return $this->payment_response($payment_data, 'fail');
            }

        } catch (\Exception $e) {
            Log::error('YooKassa callback error', ['message' => $e->getMessage()]);
            $payment_data = $this->payment::where(['id' => $request['payment_id']])->first();
            if (isset($payment_data) && function_exists($payment_data->failure_hook)) {
                call_user_func($payment_data->failure_hook, $payment_data);
            }
            return $this->payment_response($payment_data, 'fail');
        }
    }

    public function webhook(Request $request): JsonResponse
    {
        try {
            $source = $request->getContent();
            $requestBody = json_decode($source, true);

            Log::info('YooKassa webhook received', $requestBody);

            if (!isset($requestBody['object']['id'])) {
                return response()->json(['error' => 'Invalid webhook data'], 400);
            }

            $paymentId = $requestBody['object']['id'];
            $payment_data = $this->payment::where(['transaction_id' => $paymentId])->first();

            if (!$payment_data) {
                return response()->json(['error' => 'Payment not found'], 404);
            }

            // Получаем актуальную информацию о платеже через SDK
            $payment = $this->client->getPaymentInfo($paymentId);

            if ($payment->getStatus() === 'succeeded' && !$payment_data->is_paid) {
                $this->payment::where(['transaction_id' => $paymentId])->update([
                    'payment_method' => 'yookassa',
                    'is_paid' => 1,
                ]);

                $data = $this->payment::where(['transaction_id' => $paymentId])->first();

                if (isset($data) && function_exists($data->success_hook)) {
                    call_user_func($data->success_hook, $data);
                }

                Log::info('YooKassa webhook: payment succeeded', ['payment_id' => $paymentId]);
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('YooKassa webhook error', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function cancel(Request $request): Application|JsonResponse|Redirector|RedirectResponse
    {
        $payment_data = $this->payment::where(['id' => $request['payment_id']])->first();
        return $this->payment_response($payment_data, 'cancel');
    }
}
