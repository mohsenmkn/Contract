<?php

/*
 |--------------------------------------------------------------------------
 | تنظیمات امضای قراردادها
 |--------------------------------------------------------------------------
 | - مبلغ قرارداد در دیتابیس «ریال» ذخیره می‌شود؛ آستانه به «تومان» است.
 | - مقدار واقعی آستانه از جدول contract_settings خوانده می‌شود (قابل تغییر توسط
 |   مدیر سیستم)؛ مقدار این فایل فقط پیش‌فرض است.
 | - ترتیب کلیدهای `phases` همان ترتیب امضاست.
 | - هر فاز با ورود قرارداد به `status` آن شروع می‌شود و وقتی همه‌ی اسلات‌های
 |   گروه‌هایش امضا شدند، قرارداد به `next_status` می‌رود.
 | - mode = any  → امضای «یکی» از نقش‌های مجاز کافی است (یک اسلات)
 |   mode = all  → «همه‌ی» نقش‌ها باید امضا کنند (برای هر نقش یک اسلات)
 |
 | جریان: پیمانکار → واحد متقاضی → حقوقی → مدیرعامل/هیئت‌مدیره → ابلاغ
 | ضمانت، در صورت نیاز، باید پیش از ابلاغ توسط واحد مالی ثبت شود و مانع امضاها نیست.
 */
return [

    // ورود قرارداد به این وضعیت‌ها همه‌ی امضاها را باطل می‌کند
    'reset_all_statuses' => ['draft', 'pending_legal_review', 'legal_draft'],

    'phases' => [
        'contractor' => [
            'status' => 'pending_contractor_sign',
            'next_status' => 'pending_unit_sign',
            'groups' => ['contractor'],
        ],
        'unit' => [
            'status' => 'pending_unit_sign',
            'next_status' => 'pending_legal_sign',
            'groups' => ['unit'],
        ],
        'legal' => [
            'status' => 'pending_legal_sign',
            'next_status' => 'pending_executive_sign',
            'groups' => ['legal'],
        ],
        'executive' => [
            'status' => 'pending_executive_sign',
            'next_status' => 'pending_notification',
            'groups' => ['executive'],
        ],
    ],

    'groups' => [
        'contractor' => [
            'mode' => 'any',
            'roles' => ['contractor'],
            'same_party' => true,      // امضاکننده باید از طرف قرارداد باشد
        ],
        'unit' => [
            'mode' => 'any',
            'roles' => ['unit_head', 'unit_manager', 'unit_deputy'],
            'same_unit' => true,       // امضاکننده باید عضو واحد متقاضی باشد
        ],
        'legal' => [
            'mode' => 'any',
            'roles' => ['legal_expert'],
        ],
        'executive' => [
            'mode' => 'all',
            'roles' => ['ceo'],
        ],
    ],

    'role_labels' => [
        'unit_head' => 'رئیس واحد',
        'unit_manager' => 'مدیر واحد',
        'unit_deputy' => 'معاون واحد',
        'legal_expert' => 'کارشناس حقوقی',
        'contractor' => 'پیمانکار',
        'deputy_ceo' => 'معاون مالی',
        'ceo' => 'مدیرعامل',
        'board_member' => 'عضو هیئت مدیره',
    ],
];
