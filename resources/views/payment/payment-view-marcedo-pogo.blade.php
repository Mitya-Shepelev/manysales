<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ translate('Payment') }} - MercadoPago</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ dynamicAsset(path: 'public/assets/back-end/libs/bootstrap-5/bootstrap.min.css') }}">
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/jquery.js') }}"></script>
    <script src="https://sdk.mercadopago.com/js/v2"></script>
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
        }
        .payment-header {
            padding: 32px 40px 20px;
            border-bottom: 1px solid #f3f4f6;
            text-align: center;
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
        .payment-widget {
            padding: 30px 40px;
        }
        
        /* Form Styling Overrides */
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #d1d5db;
            font-size: 15px;
            margin-bottom: 15px;
            box-shadow: none;
            transition: all 0.2s;
        }
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .form-group { margin-bottom: 5px; }
        label { font-weight: 500; font-size: 14px; margin-bottom: 6px; color: #374151; display: block;}
        
        .title {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .btn--primary {
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            margin-top: 10px;
            transition: background 0.2s;
        }
        .btn--primary:hover {
            background: #2563eb;
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

    <input type="hidden" id="mercado-pago-public-key" value="{{ $config->public_key }}">

    <div class="payment-widget">
        <p class="alert alert-danger d-none" role="alert" id="error_alert" style="border-radius: 10px;"></p>
        
        <form id="form-checkout">
            <h3 class="title">{{translate('Buyer Details')}}</h3>
            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <input id="form-checkout__cardholderEmail" name="cardholderEmail" type="email" class="form-control" placeholder="Email"/>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-5">
                    <div class="form-group">
                        <select id="form-checkout__identificationType" name="identificationType" class="form-control"></select>
                    </div>
                </div>
                <div class="col-sm-7">
                    <div class="form-group">
                        <input id="form-checkout__identificationNumber" name="docNumber" type="text" class="form-control" placeholder="Doc Number"/>
                    </div>
                </div>
            </div>
            
            <h3 class="title" style="margin-top: 25px;">{{translate('Card Details')}}</h3>
            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <input id="form-checkout__cardholderName" name="cardholderName" type="text" class="form-control" placeholder="Card Holder Name"/>
                    </div>
                </div>
                
                <div class="col-12">
                    <div class="form-group">
                        <input id="form-checkout__cardNumber" name="cardNumber" type="text" class="form-control" placeholder="Card Number"/>
                    </div>
                </div>
                
                <div class="col-sm-6">
                    <div class="form-group">
                        <div class="input-group">
                            <input id="form-checkout__cardExpirationMonth" name="cardExpirationMonth" type="text" class="form-control" placeholder="MM" style="margin-right: 5px"/>
                            <input id="form-checkout__cardExpirationYear" name="cardExpirationYear" type="text" class="form-control" placeholder="YY"/>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        <input id="form-checkout__securityCode" name="securityCode" type="text" class="form-control" placeholder="CVC"/>
                    </div>
                </div>
                
                <div id="issuerInput" class="col-12 hidden">
                    <div class="form-group">
                        <select id="form-checkout__issuer" name="issuer" class="form-control"></select>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-group">
                        <select id="form-checkout__installments" name="installments" type="text" class="form-control"></select>
                    </div>
                </div>
                
                <div class="col-12">
                    <button id="form-checkout__submit" type="submit" class="btn--primary">{{translate('Pay Now')}}</button>
                    <p id="loading-message" style="text-align: center; margin-top: 10px; color: #666; display: none;">{{translate('Processing payment')}}...</p>
                </div>
            </div>
        </form>
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
            {{ translate('Secured by MercadoPago') }}
        </div>
    </div>
</div>

<script>
    'use strict';
    const publicKey = document.getElementById("mercado-pago-public-key").value;
    const mercadopago = new MercadoPago(publicKey);

    loadCardForm();
    function loadCardForm() {
        const productCost = '{{$data->payment_amount}}';

        const cardForm = mercadopago.cardForm({
            amount: productCost,
            autoMount: true,
            form: {
                id: "form-checkout",
                cardholderName: {
                    id: "form-checkout__cardholderName",
                    placeholder: "Card holder name",
                },
                cardholderEmail: {
                    id: "form-checkout__cardholderEmail",
                    placeholder: "Card holder email",
                },
                cardNumber: {
                    id: "form-checkout__cardNumber",
                    placeholder: "Card number",
                },
                cardExpirationMonth: {
                    id: "form-checkout__cardExpirationMonth",
                    placeholder: "MM",
                },
                cardExpirationYear: {
                    id: "form-checkout__cardExpirationYear",
                    placeholder: "YY",
                },
                securityCode: {
                    id: "form-checkout__securityCode",
                    placeholder: "Security code",
                },
                installments: {
                    id: "form-checkout__installments",
                    placeholder: "Installments",
                },
                identificationType: {
                    id: "form-checkout__identificationType",
                },
                identificationNumber: {
                    id: "form-checkout__identificationNumber",
                    placeholder: "Identification number",
                },
                issuer: {
                    id: "form-checkout__issuer",
                    placeholder: "Issuer",
                },
            },
            callbacks: {
                onFormMounted: error => {
                    if (error)
                        return console.warn("Form Mounted handling error: ", error);
                    console.log("Form mounted");
                },
                onSubmit: event => {
                    event.preventDefault();
                    document.getElementById("loading-message").style.display = "block";

                    const {
                        paymentMethodId,
                        issuerId,
                        cardholderEmail: email,
                        amount,
                        token,
                        installments,
                        identificationNumber,
                        identificationType,
                    } = cardForm.getCardFormData();

                    fetch("{{route('mercadopago.make_payment', ['payment_id' => $data->id])}}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": "{{csrf_token()}}"
                        },
                        body: JSON.stringify({
                            token,
                            issuerId,
                            paymentMethodId,
                            transactionAmount: Number(amount),
                            installments: Number(installments),
                            payer: {
                                email,
                                identification: {
                                    type: identificationType,
                                    number: identificationNumber,
                                },
                            },
                        }),
                    })
                        .then(response => {
                            return response.json();
                        })
                        .then(result => {
                            if (result.error) {
                                document.getElementById("loading-message").style.display = "none";
                                document.getElementById("error_alert").innerText = result.error;
                                document.getElementById("error_alert").classList.remove('d-none');
                                document.getElementById("error_alert").style.display = "block";
                                return false;
                            }
                            location.href = '{{route('payment-success')}}';
                        })
                        .catch(error => {
                            document.getElementById("loading-message").style.display = "none";
                            document.getElementById("error_alert").innerHTML = error;
                            document.getElementById("error_alert").classList.remove('d-none');
                            document.getElementById("error_alert").style.display = "block";
                        });
                },
                onFetching: (resource) => {
                    console.log("Fetching resource: ", resource);
                    const payButton = document.getElementById("form-checkout__submit");
                    payButton.setAttribute('disabled', true);
                    return () => {
                        payButton.removeAttribute("disabled");
                    };
                },
            },
        });
    }
</script>
</body>
</html>
