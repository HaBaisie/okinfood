<?php
// ===== Okin Food store settings. Edit these values. =====
return [
    'store_name'   => 'Okin Food Supermarket',
    'tagline'      => 'Fresh groceries delivered to your door',
    'phone'        => '',                 // e.g. 2348012345678 (digits only, used for WhatsApp link)
    'address'      => '',                 // shop address shown in footer
    'orders_email' => 'info@okinfood.com.ng', // every new order is emailed here
    'from_email'   => 'info@okinfood.com.ng',

    // Outgoing mail. Leave host empty to use PHP mail() (works on most cPanel hosts).
    // For best delivery, create the mailbox in cPanel and fill these in.
    'smtp' => [
        'host'   => '',                   // e.g. mail.okinfood.com.ng
        'port'   => 465,
        'secure' => 'ssl',                // 'ssl' (465) or 'tls' (587)
        'user'   => 'info@okinfood.com.ng',
        'pass'   => '',
    ],

    // Admin panel: https://yourdomain/admin/  (CHANGE THIS PASSWORD)
    'admin_password' => 'CHANGE-ME',

    'delivery_fee'       => 1500,   // flat fee in Naira
    'free_delivery_over' => 50000,  // free delivery above this subtotal

    // Shown to customers who choose "Bank transfer"
    'bank' => [
        'name'    => '',
        'account' => '',
        'holder'  => 'Okin Food',
    ],
];
