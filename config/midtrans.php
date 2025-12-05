<?php

return [
    // use 'sandbox' or 'production'
    'env' => env('MIDTRANS_ENV', 'sandbox'),
    'server_key' => env('MIDTRANS_SERVER_KEY', ''),
    'client_key' => env('MIDTRANS_CLIENT_KEY', ''),
    'merchant_id' => env('MIDTRANS_MERCHANT_ID', ''),
    'base_url' => env('MIDTRANS_ENV', 'sandbox') === 'production' ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com',
];
