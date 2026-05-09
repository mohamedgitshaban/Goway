<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paymob Payout Credentials
    |--------------------------------------------------------------------------
    | Used for both Vodafone Cash and bank transfer disbursements via Paymob.
    |
    | PAYMOB_API_KEY                     — your Paymob secret API key
    | PAYMOB_VF_CASH_INTEGRATION_ID      — integration ID for VF Cash wallet
    | PAYMOB_BANK_TRANSFER_INTEGRATION_ID — integration ID for bank transfer
    */
    'paymob' => [
        'api_key'                    => env('PAYMOB_API_KEY', ''),
        'vf_cash_integration_id'     => env('PAYMOB_VF_CASH_INTEGRATION_ID', ''),
        'bank_transfer_integration_id' => env('PAYMOB_BANK_TRANSFER_INTEGRATION_ID', ''),
    ],

];
