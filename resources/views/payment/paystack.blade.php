<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ translate('Payment') }} - Paystack</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f0f2f5;
            background-image: radial-gradient(#e0e4e8 1px, transparent 1px);
            background-size: 20px 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #1f2937;
        }
        .payment-container {
            width: 100%;
            max-width: 600px;
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            text-align: center;
        }
        .payment-header {
            padding: 32px 40px 20px;
            border-bottom: 1px solid #f3f4f6;
        }
        .logo-container {
            margin-bottom: 20px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo-container img {
            max-height: 100%;
            max-width: 200px;
            object-fit: contain;
        }
        .order-info { margin-bottom: 12px; }
        .order-label {
            font-size: 14px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }
        .order-id {
            font-size: 18px;
            font-weight: 500;
            color: #374151;
            margin-top: 4px;
        }
        .payment-amount {
            font-size: 36px;
            font-weight: 700;
            color: #111827;
            letter-spacing: -0.02em;
        }
        .currency-symbol {
            font-size: 20px;
            color: #6b7280;
            font-weight: 500;
            vertical-align: super;
        }
        .payment-body {
            padding: 60px 40px;
        }
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #e5e7eb;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .loading-text {
            font-size: 16px;
            color: #4b5563;
            margin-bottom: 8px;
        }
        .sub-text {
            font-size: 14px;
            color: #9ca3af;
        }
        .payment-footer {
            background: #f9fafb;
            padding: 20px 40px;
            border-top: 1px solid #f3f4f6;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }
        .security-badges {
            display: flex;
            gap: 20px;
            align-items: center;
            opacity: 0.7;
            filter: grayscale(100%);
            transition: all 0.3s ease;
        }
        .security-badges:hover { opacity: 1; filter: grayscale(0%); }
        .security-badges img { height: 24px; object-fit: contain; }
        .secured-by {
            font-size: 12px;
            color: #9ca3af;
            display: flex;
            align-items: center;
            gap: 6px;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-header">
            <div class="logo-container">
                <img src="{{ asset('assets/back-end/img/logo.png') }}" alt="Logo" onerror="this.style.display='none'">
            </div>
            <div class="order-info">
                <div class="order-label">{{ translate('Order Payment') }}</div>
                <div class="order-id">#{{ $data->attribute_id }}</div>
            </div>
            <div class="payment-amount">
                {{ number_format($data->payment_amount, 2, '.', ' ') }} 
                <span class="currency-symbol">{{ $data->currency_code }}</span>
            </div>
        </div>

        <div class="payment-body">
            <div class="spinner"></div>
            <div class="loading-text">{{ translate('Initializing Paystack Payment') }}...</div>
            <div class="sub-text">{{ translate('Please do not refresh this page') }}</div>
        </div>

        <form method="POST" action="{!! route('paystack.payment',['token'=>$data->id]) !!}" accept-charset="UTF-8"
              class="form-horizontal" role="form" style="display: none;">
            @csrf
            <div class="row">
                <div class="col-md-8 col-md-offset-2">
                    <input type="hidden" name="email"
                           value="{{ $payer->email != null ? $payer->email : 'required@email.com' }}">
                    <input type="hidden" name="orderID" value="{{ $data->attribute_id }}">
                    <input type="hidden" name="amount" value="{{ $data->payment_amount*100 }}">
                    <input type="hidden" name="quantity" value="1">
                    <input type="hidden" name="currency" value="{{ $data->currency_code }}">
                    <input type="hidden" name="metadata" value="{{ json_encode($array = ['orderID' => $data->attribute_id]) }}">
                    <input type="hidden" name="metadata"
                           value="{{ json_encode($array = ['orderID' => $data->attribute_id,'cancel_action'=> route('paystack.cancel', ['payments_id' => $data->id])]) }}">
                    <input type="hidden" name="reference" value="{{ $reference }}">
                    <button class="btn btn-block d--none" id="pay-button" type="submit"></button>
                </div>
            </div>
        </form>

        <div class="payment-footer">
            <div class="security-badges">
                <img src="{{ asset('assets/back-end/img/visa.png') }}" alt="Visa">
                <img src="{{ asset('assets/back-end/svg/brands/mastercard.svg') }}" alt="Mastercard">
            </div>
            <div class="secured-by">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                </svg>
                {{ translate('Secured by Paystack') }}
            </div>
        </div>
    </div>

    <script type="text/javascript">
        "use strict";
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById("pay-button").click();
        });
    </script>
</body>
</html>
