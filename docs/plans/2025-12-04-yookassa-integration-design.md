# YooKassa Payment Gateway Integration Design

## Overview
Integration of YooKassa payment gateway for Russian customers with fiscal receipt support (54-FZ compliance).

## Requirements
- Currency: RUB only
- Webhook notifications: Required
- Fiscal receipts: Required (multiple items support)

## Architecture

### Files to Create
| File | Description |
|------|-------------|
| `app/Http/Controllers/Payment_Methods/YooKassaController.php` | Payment controller |

### Files to Modify
| File | Change |
|------|--------|
| `app/Enums/GlobalConstant.php` | Add `'yookassa'` to `DEFAULT_PAYMENT_GATEWAYS` |
| `app/Library/Constant.php` | Add `['key' => 'yookassa', 'value' => 'YooKassa']` |
| `app/Traits/PaymentGatewayTrait.php` | Add `'yookassa' => ['RUB' => 'Russian Ruble']` |
| `routes/web/routes.php` | Add yookassa route group |
| `database/seeders/AddonSettingSeeder.php` | Add initial yookassa config |

### Dependencies
```bash
composer require yoomoney/yookassa-sdk-php
```

## Payment Flow

### Step 1: Initialize Payment
```
User -> Checkout -> Select YooKassa -> /payment/yookassa/pay?payment_id={uuid}
```

### Step 2: Create Payment with Receipt
```php
$client = new \YooKassa\Client();
$client->setAuth($shopId, $secretKey);

$items = [];
foreach ($orderProducts as $product) {
    $items[] = [
        'description' => $product->name,
        'quantity' => $product->quantity,
        'amount' => ['value' => $product->price, 'currency' => 'RUB'],
        'vat_code' => 1,
        'payment_mode' => 'full_payment',
        'payment_subject' => 'commodity',
    ];
}

$response = $client->createPayment([
    'amount' => ['value' => $total, 'currency' => 'RUB'],
    'confirmation' => [
        'type' => 'redirect',
        'return_url' => route('yookassa.success', ['payment_id' => $paymentId]),
    ],
    'capture' => true,
    'description' => 'Order #' . $orderId,
    'metadata' => ['payment_id' => $paymentId],
    'receipt' => [
        'customer' => ['email' => $email],
        'items' => $items,
    ],
], uniqid('', true));
```

### Step 3: Redirect to YooKassa
User completes payment on YooKassa page.

### Step 4: Handle Response
- **Return URL**: Verify payment status via API
- **Webhook**: Receive automatic notifications (more reliable)

## Configuration Fields (Admin Panel)
- `shop_id` - Store ID from YooKassa
- `secret_key` - API Secret Key

## Routes
```php
Route::group(['prefix' => 'yookassa', 'as' => 'yookassa.'], function () {
    Route::get('pay', [YooKassaController::class, 'index'])->name('pay');
    Route::get('payment', [YooKassaController::class, 'payment'])->name('payment');
    Route::get('success', [YooKassaController::class, 'success'])->name('success');
    Route::post('webhook', [YooKassaController::class, 'webhook'])
        ->withoutMiddleware([VerifyCsrfToken::class])->name('webhook');
});
```

## Webhook Setup
URL to configure in YooKassa dashboard:
```
https://your-domain.com/payment/yookassa/webhook
```

## Sources
- [YooKassa PHP SDK](https://github.com/yoomoney/yookassa-sdk-php)
- [YooKassa API Documentation](https://yookassa.ru/developers/api)
- [Payment Examples](https://github.com/yoomoney/yookassa-sdk-php/blob/master/docs/examples/02-payments.md)
