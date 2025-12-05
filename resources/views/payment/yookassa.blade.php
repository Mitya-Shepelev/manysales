<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ translate('Payment') }} - YooKassa</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
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
            max-width: 600px; /* Increased width for large widgets */
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            position: relative;
        }
        .payment-header {
            padding: 20px 40px;
            border-bottom: 1px solid #f3f4f6;
        }
        .logo-container {
            margin-bottom: 15px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo-container img {
            max-height: 100%;
            max-width: 180px;
            object-fit: contain;
        }
        .payment-details-grid {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 20px;
        }
        .detail-col {
            display: flex;
            flex-direction: column;
        }
        .detail-col.text-left { text-align: left; }
        .detail-col.text-right { text-align: right; }
        
        .detail-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .order-id {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }
        .payment-amount {
            font-size: 20px; /* Reduced from 28px to be closer to order-id size but slightly distinct */
            font-weight: 700;
            color: #111827;
            line-height: 1;
        }
        .currency-symbol {
            font-size: 16px;
            color: #6b7280;
            font-weight: 500;
            vertical-align: top;
            margin-left: 2px;
        }
        .payment-widget {
            padding: 30px 40px;
            min-height: 400px; /* Ensure space for widget */
            background: #fff;
        }
        #payment-form {
            width: 100%;
        }
        /* Ensure widget iframe or contents take full width/height without shifting */
        #payment-form iframe {
            width: 100% !important;
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
        .security-badges:hover {
            opacity: 1;
            filter: grayscale(0%);
        }
        .security-badges img {
            height: 24px;
            object-fit: contain;
        }
        .secured-by {
            font-size: 12px;
            color: #9ca3af;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .loading {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }
        .loading-spinner {
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
        .error-message {
            display: none;
            background: #fef2f2;
            border: 1px solid #fee2e2;
            color: #b91c1c;
            padding: 16px;
            margin: 0 40px 20px;
            border-radius: 12px;
            text-align: center;
            font-size: 14px;
        }
        
        @media (max-width: 640px) {
            .payment-container {
                border-radius: 0;
                box-shadow: none;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
            }
            .payment-widget {
                flex: 1;
                padding: 20px;
            }
            .payment-header, .payment-footer {
                padding: 20px;
            }
            body {
                padding: 0;
                background: #fff;
            }
        }
    </style>
</head>
<body>
<div class="payment-container">
    <div class="payment-header">
        <div class="logo-container">
            <img src="{{ asset('assets/back-end/img/logo.png') }}" alt="Logo" onerror="this.style.display='none'">
        </div>
        <div class="payment-details-grid">
            <div class="detail-col text-left">
                <div class="detail-label">{{ translate('Order ID') }}</div>
                <div class="order-id">#{{ $data->attribute_id }}</div>
            </div>
            <div class="detail-col text-right">
                <div class="detail-label">{{ translate('Amount') }}</div>
                <div class="payment-amount">
                    {{ number_format($data->payment_amount, 2, '.', ' ') }} 
                    <span class="currency-symbol">{{ $data->currency_code ?? 'RUB' }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="payment-widget">
        <div id="payment-form"></div>
        
        <div id="loading" class="loading">
            <div class="loading-spinner"></div>
            <p>{{ translate('Initializing secure checkout') }}...</p>
        </div>
    </div>

    <div class="error-message" id="error-message"></div>

    <div class="payment-footer">
        <div class="secured-by">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
            </svg>
            {{ translate('Защищено YooKassa') }}
        </div>
    </div>
</div>

@if(isset($confirmation_token))
<script src="https://yookassa.ru/checkout-widget/v1/checkout-widget.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const loading = document.getElementById('loading');
    const errorMessage = document.getElementById('error-message');

    try {
        const checkout = new window.YooMoneyCheckoutWidget({
            confirmation_token: '{{ $confirmation_token }}',
            return_url: '{{ route("yookassa.callback") }}?payment_id={{ $data->id }}',
            error_callback: function(error) {
                console.error('Payment error:', error);
                loading.style.display = 'none';
                errorMessage.textContent = error.error || '{{ translate("Payment error") }}';
                errorMessage.style.display = 'block';
            }
        });

        checkout.render('payment-form')
            .then(function() {
                loading.style.display = 'none';
            })
            .catch(function(error) {
                console.error('Widget error:', error);
                loading.style.display = 'none';
                errorMessage.textContent = '{{ translate("Failed to load payment form") }}';
                errorMessage.style.display = 'block';
            });

    } catch (error) {
        console.error('Init error:', error);
        loading.style.display = 'none';
        errorMessage.textContent = error.message || '{{ translate("Error") }}';
        errorMessage.style.display = 'block';
    }
});
</script>
@else
<script>
    // Fallback: redirect to payment method
    document.getElementById('loading').innerHTML = '<div class="loading-spinner"></div><p>{{ translate("Redirecting to payment") }}...</p>';
    setTimeout(function() {
        window.location.href = '{{ route("yookassa.payment") }}?payment_id={{ $data->id }}';
    }, 1000);
</script>
@endif
</body>
</html>
