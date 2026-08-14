<?php

/**
 * Konfigurasi starter kit.
 *
 * FASE STATIS: seluruh data di bawah masih contoh (dummy) sehingga tampilan
 * dapat ditinjau lebih dulu sebelum lapisan basis data & autentikasi dipasang.
 */
return [

    'branding' => [
        'app_name' => 'AdminKit',
        'company' => 'Admin Panel Starter Kit',
        'meta_description' => 'Laravel 12 · Inertia · Vue 3 · TailwindCSS. Compact, monokrom, token-first.',
        'support_email' => 'dukungan@adminkit.test',
        'footer_text' => '© '.date('Y').' AdminKit',
    ],

    'demo_user' => [
        'name' => 'Zulfadli Rizal',
        'email' => 'admin@adminkit.test',
        'role' => 'Super Admin',
        'phone' => '082320099971',
        'office' => 'Pamanukan',
        'avatar' => '',
        'is_admin' => true,
    ],

];
