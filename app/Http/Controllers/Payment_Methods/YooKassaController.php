<?php

namespace App\Http\Controllers\Payment_Methods;

use App\Models\PaymentRequest;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Traits\Processor;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class YooKassaController extends Controller
{
    use Processor;

    private const API_URL = 'https://api.yookassa.ru/v3/';
    private $config_values;
    private PaymentRequest $payment;

    public function __construct(PaymentRequest $payment)
    {
        $config = $this->payment_config('yookassa', 'payment_config');
        if (!is_null($config) && $config->mode == 'live') {
            $this->config_values = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $this->config_values = json_decode($config->test_values);
        }
        $this->payment = $payment;
    }

    /**
     * Display payment page
     */
    public function index(Request $request)
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

        Log::info('YooKassa proceeding to payment', ['payment_id' => $request['payment_id'], 'amount' => $data->payment_amount]);
        return $this->payment($request);
    }

    /**
     * Create payment and redirect to YooKassa
     */
    public function payment(Request $request): JsonResponse|RedirectResponse
    {
        Log::info('YooKassa payment method called', ['payment_id' => $request['payment_id']]);

        $data = $this->payment::where(['id' => $request['payment_id']])->where(['is_paid' => 0])->first();
        if (!isset($data)) {
            Log::warning('YooKassa payment: data not found');
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }

        $shopId = $this->config_values->shop_id ?? '';
        $secretKey = $this->config_values->secret_key ?? '';

        Log::info('YooKassa config check', ['has_shop_id' => !empty($shopId), 'has_secret_key' => !empty($secretKey)]);

        if (empty($shopId) || empty($secretKey)) {
            Log::error('YooKassa not configured - missing credentials');
            return redirect()->back()->with('error', 'YooKassa is not configured properly');
        }

        $paymentAmount = round($data->payment_amount, 2);
        $idempotenceKey = Str::uuid()->toString();

        // Get customer info
        $customerEmail = null;
        $customerPhone = null;
        if ($data->payer_information) {
            $payerInfo = json_decode($data->payer_information);
            $customerEmail = $payerInfo->email ?? null;
            $customerPhone = $payerInfo->phone ?? null;
        }

        // Build receipt items from order
        $receiptItems = $this->buildReceiptItems($data);

        // Build payment request
        $paymentData = [
            'amount' => [
                'value' => number_format($paymentAmount, 2, '.', ''),
                'currency' => 'RUB',
            ],
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => route('yookassa.success', ['payment_id' => $data->id]),
            ],
            'capture' => true,
            'description' => 'Order payment #' . ($data->attribute_id ?? $data->id),
            'metadata' => [
                'payment_id' => $data->id,
            ],
        ];

        // Add receipt if we have items and customer email
        if (!empty($receiptItems) && $customerEmail) {
            $paymentData['receipt'] = [
                'customer' => [
                    'email' => $customerEmail,
                ],
                'items' => $receiptItems,
            ];

            if ($customerPhone) {
                $paymentData['receipt']['customer']['phone'] = $customerPhone;
            }
        }

        try {
            $response = Http::withBasicAuth($shopId, $secretKey)
                ->withHeaders([
                    'Idempotence-Key' => $idempotenceKey,
                    'Content-Type' => 'application/json',
                ])
                ->post(self::API_URL . 'payments', $paymentData);

            if ($response->successful()) {
                $paymentResponse = $response->json();

                // Store YooKassa payment ID for later verification
                $this->payment::where('id', $data->id)->update([
                    'transaction_id' => $paymentResponse['id'],
                ]);

                // Redirect to YooKassa payment page
                $confirmationUrl = $paymentResponse['confirmation']['confirmation_url'] ?? null;
                if ($confirmationUrl) {
                    return redirect($confirmationUrl);
                }

                return redirect()->back()->with('error', 'Failed to get payment URL');
            }

            Log::error('YooKassa payment creation failed', [
                'response' => $response->json(),
                'status' => $response->status(),
            ]);

            return redirect()->back()->with('error', 'Payment creation failed');

        } catch (\Exception $e) {
            Log::error('YooKassa payment exception', [
                'message' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Payment service error');
        }
    }

    /**
     * Handle success redirect from YooKassa
     */
    public function success(Request $request): Application|JsonResponse|Redirector|RedirectResponse
    {
        $data = $this->payment::where(['id' => $request['payment_id']])->first();

        if (!$data) {
            return redirect()->route('payment-fail');
        }

        $shopId = $this->config_values->shop_id ?? '';
        $secretKey = $this->config_values->secret_key ?? '';
        $yookassaPaymentId = $data->transaction_id;

        if ($yookassaPaymentId) {
            try {
                // Verify payment status via API
                $response = Http::withBasicAuth($shopId, $secretKey)
                    ->get(self::API_URL . 'payments/' . $yookassaPaymentId);

                if ($response->successful()) {
                    $paymentInfo = $response->json();

                    if ($paymentInfo['status'] === 'succeeded') {
                        $this->payment::where(['id' => $request['payment_id']])->update([
                            'payment_method' => 'yookassa',
                            'is_paid' => 1,
                            'transaction_id' => $paymentInfo['id'],
                        ]);

                        $data = $this->payment::where(['id' => $request['payment_id']])->first();

                        if (isset($data) && function_exists($data->success_hook)) {
                            call_user_func($data->success_hook, $data);
                        }

                        return $this->payment_response($data, 'success');
                    }
                }
            } catch (\Exception $e) {
                Log::error('YooKassa verification error', ['message' => $e->getMessage()]);
            }
        }

        // Payment failed or pending
        $payment_data = $this->payment::where(['id' => $request['payment_id']])->first();
        if (isset($payment_data) && function_exists($payment_data->failure_hook)) {
            call_user_func($payment_data->failure_hook, $payment_data);
        }
        return $this->payment_response($payment_data, 'fail');
    }

    /**
     * Handle webhook notifications from YooKassa
     */
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('YooKassa webhook received', $payload);

        if (!isset($payload['event']) || !isset($payload['object'])) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $event = $payload['event'];
        $paymentObject = $payload['object'];

        // Get our payment_id from metadata
        $paymentId = $paymentObject['metadata']['payment_id'] ?? null;

        if (!$paymentId) {
            Log::warning('YooKassa webhook: payment_id not found in metadata');
            return response()->json(['status' => 'ok']);
        }

        $data = $this->payment::where('id', $paymentId)->first();

        if (!$data) {
            Log::warning('YooKassa webhook: payment not found', ['payment_id' => $paymentId]);
            return response()->json(['status' => 'ok']);
        }

        // Already processed
        if ($data->is_paid == 1) {
            return response()->json(['status' => 'ok']);
        }

        switch ($event) {
            case 'payment.succeeded':
                $this->payment::where('id', $paymentId)->update([
                    'payment_method' => 'yookassa',
                    'is_paid' => 1,
                    'transaction_id' => $paymentObject['id'],
                ]);

                $data = $this->payment::where('id', $paymentId)->first();
                if (isset($data) && function_exists($data->success_hook)) {
                    call_user_func($data->success_hook, $data);
                }

                Log::info('YooKassa payment succeeded', ['payment_id' => $paymentId]);
                break;

            case 'payment.canceled':
                $data = $this->payment::where('id', $paymentId)->first();
                if (isset($data) && function_exists($data->failure_hook)) {
                    call_user_func($data->failure_hook, $data);
                }

                Log::info('YooKassa payment canceled', ['payment_id' => $paymentId]);
                break;

            default:
                Log::info('YooKassa webhook event', ['event' => $event, 'payment_id' => $paymentId]);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Build receipt items for fiscal data (54-FZ compliance)
     */
    private function buildReceiptItems(PaymentRequest $paymentRequest): array
    {
        $items = [];

        // Try to get order details from attribute_id (order_id)
        if ($paymentRequest->attribute_id) {
            $orderDetails = OrderDetail::whereIn('order_id', function ($query) use ($paymentRequest) {
                $query->select('id')
                    ->from('orders')
                    ->where('id', $paymentRequest->attribute_id);
            })->with('product')->get();

            foreach ($orderDetails as $detail) {
                $items[] = [
                    'description' => mb_substr($detail->product->name ?? 'Product', 0, 128),
                    'quantity' => (string) $detail->qty,
                    'amount' => [
                        'value' => number_format($detail->price, 2, '.', ''),
                        'currency' => 'RUB',
                    ],
                    'vat_code' => 1, // Without VAT
                    'payment_mode' => 'full_payment',
                    'payment_subject' => 'commodity',
                ];
            }
        }

        // If no items found, create single item for total amount
        if (empty($items)) {
            $items[] = [
                'description' => 'Payment for order',
                'quantity' => '1',
                'amount' => [
                    'value' => number_format($paymentRequest->payment_amount, 2, '.', ''),
                    'currency' => 'RUB',
                ],
                'vat_code' => 1,
                'payment_mode' => 'full_payment',
                'payment_subject' => 'commodity',
            ];
        }

        return $items;
    }
}
