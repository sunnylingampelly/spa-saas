<?php

return [

    // A new spa gets this many days of full access before a plan is required.
    'trial_days' => 14,

    // Two fixed payment options — not a DB-editable catalog, mirrors config/loyalty.php's
    // "explicit, ownable business constant" convention.
    'plans' => [
        'monthly' => [
            'label' => 'Monthly',
            'price' => 1499,
            'cycle' => 'monthly',
        ],
        'lifetime' => [
            'label' => 'Lifetime',
            'price' => 24999,
            'cycle' => 'one_time',
        ],
    ],

    // Shown on the Subscription page for the manual UPI/bank-transfer payment path.
    'payout' => [
        'upi_id' => env('SUBSCRIPTION_UPI_ID', 'yourspa@upi'),
        'account_name' => env('SUBSCRIPTION_ACCOUNT_NAME', 'Your Company Pvt Ltd'),
        'account_number' => env('SUBSCRIPTION_ACCOUNT_NUMBER'),
        'ifsc' => env('SUBSCRIPTION_IFSC'),
    ],

];
