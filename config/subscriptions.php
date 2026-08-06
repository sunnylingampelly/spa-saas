<?php

return [

    // A new spa gets this many days of full access before a plan is required.
    'trial_days' => 14,

    // How many days before an active plan expires that renewing the *same* plan is
    // allowed. Outside this window, paying again for a plan you already have active
    // is blocked — it would either double-charge for nothing (Lifetime) or silently
    // discard the time already paid for (Monthly), so it's refused up front instead.
    'renewal_window_days' => 7,

    // Monthly billing is disabled for now — a single one-time Lifetime purchase is the
    // only plan on offer. Left commented out (rather than deleted) so it's a one-line
    // revert if monthly billing comes back; existing 'monthly' subscribers are untouched
    // (SuperAdmin's plan editor keeps its own separate list and can still manage them).
    'plans' => [
        // 'monthly' => [
        //     'label' => 'Monthly',
        //     'price' => 1499,
        //     'cycle' => 'monthly',
        // ],
        'lifetime' => [
            'label' => 'Lifetime',
            'price' => 10000,
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
