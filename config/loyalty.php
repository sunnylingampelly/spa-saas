<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reward Points Rate
    |--------------------------------------------------------------------------
    |
    | How many rupees a customer must spend (on a fully-paid invoice) to earn
    | one reward point. This is a business default the spa owner can tune —
    | not an industry-standard rate.
    |
    */

    'rupees_per_point' => env('LOYALTY_RUPEES_PER_POINT', 100),

];
