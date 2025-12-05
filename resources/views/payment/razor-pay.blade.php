<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ translate('Payment') }} - Razorpay</title>
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
            padding: 40px;
        }
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
            align-items: center;
        }
        .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            padding: 14px 30px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
            max-width: 250px;
            border: none;
        }
        .btn-primary {
            background: #3b82f6;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.5);
        }
        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 6px 8px -1px rgba(59, 130, 246, 0.6);
        }
        .btn-secondary {
            background: #f3f4f6;
            color: #4b5563;
        }
        .btn-secondary:hover {
            background: #e5e7eb;
            color: #374151;
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
            <div class="action-buttons">
                <button type="button" id="rzp-button1" class="btn btn-primary">{{ translate('Pay Now') }}</button>
                <button type="button" id="razorpay-cancel-button" class="btn btn-secondary">{{ translate('Cancel') }}</button>
            </div>
            <p style="margin-top: 20px; color: #6b7280; font-size: 14px;">{{ translate('The payment window will open automatically.') }}</p>
        </div>

        <div class="payment-footer">
            <div class="security-badges">
                <img src="{{ asset('assets/back-end/img/visa.png') }}" alt="Visa">
                <img src="{{ asset('assets/back-end/svg/brands/mastercard.svg') }}" alt="Mastercard">
            </div>
            <div class="secured-by">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                </svg>
                {{ translate('Secured by Razorpay') }}
            </div>
        </div>
    </div>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script type="text/javascript">
        "use strict";
        document.getElementById('razorpay-cancel-button').onclick = function () {
            window.location.href = '{{ route('razor-pay.cancel', ['payment_id' => $data->id]) }}';
        };
        
        document.addEventListener("DOMContentLoaded", function () {
            let rzpButton = document.getElementById('rzp-button1');
            if (rzpButton) {
                // Auto trigger if needed, or let user click
                setTimeout(function () {
                    rzpButton.click();
                }, 500);

                fetch("{{ route('razor-pay.create-order') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({
                        payment_request_id: "{{ $data->id }}",
                        payment_amount: "{{ $data->payment_amount }}",
                        currency_code: "{{ $data->currency_code }}"
                    })
                })
                .then(response => response.json())
                .then(orderData => {
                    var rzp1 = new Razorpay({
                        "key": "{{ config()->get('razor_config.api_key') }}",
                        "amount": orderData.amount,
                        "currency": orderData.currency,
                        "name": "{{ $business_name }}",
                        "description": "{{ $data->payment_amount }}",
                        "image": "{{ $business_logo }}",
                        "order_id": orderData.order_id,
                        "callback_url": "{{ route('razor-pay.callback') }}",
                        "handler": function (response) {
                            console.log("Payment successful!", response);
                            window.location.href = "{{ route('razor-pay.verify-payment') }}?" + new URLSearchParams({
                                payment_request_id: "{{ $data->id }}",
                                payment_id: response.razorpay_payment_id,
                                order_id: response.razorpay_order_id,
                                signature: response.razorpay_signature
                            }).toString();
                        },
                        "prefill": {
                            "name": "{{ $payer?->name ?? '' }}",
                            "email": "{{ $payer?->email ?? '' }}",
                            "contact": "{{ $payer?->phone ?? '' }}"
                        },
                        "theme": {
                            "color": "#3b82f6" // Updated to match our theme
                        },
                        "method": {
                            "netbanking": true,
                            "card": true,
                            "upi": true,
                            "wallet": true
                        },
                    });

                    rzpButton.onclick = function (e) {
                        rzp1.open();
                        e.preventDefault();
                    };
                    
                    // Trigger click logic after setup
                    rzpButton.click();
                })
                .catch(error => {
                    console.error("Error creating order:", error);
                });
            } else {
                console.error("Button with ID 'rzp-button1' not found!");
            }
        });
    </script>
</body>
</html>
