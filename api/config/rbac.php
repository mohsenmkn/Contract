<?php

return [
    'guard' => 'web',

    'modules' => [
        // ماژول‌های موجود
        'dashboard' => ['view'],
        'auth' => ['manage', 'view_users'],
        'users' => ['read', 'create', 'update', 'delete'],
        'roles' => ['read', 'create', 'update', 'delete'],
        'permissions' => ['read', 'create', 'update', 'delete'],
        'groups' => ['read', 'create', 'update', 'delete', 'assign_users'],

        // ✅ ماژول‌های جدید: واحدهای سازمانی
        'units' => array('read', 'create', 'update', 'delete', 'assign_representative'),

        // ✅ ماژول‌های جدید: قراردادهای workflow جدید
        'contracts' => array(
            'read', 'create', 'update', 'delete',
            'submit_to_legal', 'submit_to_contract_unit', 'review', 'draft', 'unit_sign', 'legal_sign',
            'send_to_contractor', 'send_to_ceo', 'notify',
            'sign', 'terminate'
        ),
        'contract-settings' => ['manage'],

        // ماژول‌های جدید - سامانه مدیریت قراردادها
        'parties' => [
            'read',
            'create',
            'update',
            'delete',
        ],


        'amendments' => [
            'read',
            'create',
            'approve',
        ],

        'invoices' => [
            'read',
            'create',
            'update',
            'delete',
            'submit',      // ارسال صورت‌وضعیت
            'approve',     // تایید صورت‌وضعیت
        ],

        'payments' => [
            'read',
            'create',
            'update',
            'delete',
        ],

        'guarantees' => [
            'read',
            'create',
            'manage',
            'update',
            'release',     // آزادسازی ضمانت‌نامه
        ],

        'signatures' => [
            'read',
            'sign',        // امضای اسناد
        ],

        'alerts' => [
            'read',
            'manage',      // مدیریت تنظیمات هشدارها
        ],

        'documents' => [
            'read',
            'upload',
            'delete',
        ],

        'reports' => [
            'view',        // مشاهده گزارش‌ها
            'export',      // خروجی گرفتن
        ],
    ],

    'labels' => [
        'module_names' => array (
            'dashboard' => 'داشبورد',
            'auth' => 'احراز هویت',
            'permissions' => 'دسترسی ها',
            'users' => 'مدیریت کاربران',
            'roles' => 'نقش‌ها و دسترسی‌ها',
            'groups' => 'گروه‌های کاربری',
            'units' => 'واحدهای سازمانی',
            'contracts' => 'قراردادها',
            'parties' => 'طرفین قرارداد',
            'amendments' => 'الحاقیه‌ها',
            'invoices' => 'صورت‌وضعیت‌ها',
            'payments' => 'پرداخت‌ها',
            'guarantees' => 'ضمانت‌نامه‌ها',
            'signatures' => 'امضاها',
            'alerts' => 'هشدارها',
            'documents' => 'اسناد',
            'reports' => 'گزارش‌ها',
        ),

        'actions' => array (
            'read' => 'مشاهده',
            'create' => 'ایجاد',
            'update' => 'ویرایش',
            'delete' => 'حذف',
            'view' => 'نمایش',
            'manage' => 'مدیریت کل',
            'view_users' => 'مشاهده کاربران',
            'assign_users' => 'اختصاص کاربران',
            'assign_representative' => 'تعیین نماینده',
            'submit_to_legal' => 'ارسال به حقوقی',
            'submit_to_contract_unit' => 'ارسال به واحد قراردادها',
            'review' => 'بررسی حقوقی',
            'draft' => 'تنظیم پیش‌نویس',
            'send_to_contractor' => 'ارسال به پیمانکار',
            'send_to_ceo' => 'ارسال به مدیرعامل',
            'notify' => 'ابلاغ',
            'sign' => 'امضا',
            'terminate' => 'فسخ',
            'submit' => 'ارسال برای تایید',
            'approve' => 'تایید',
            'release' => 'آزادسازی',
            'upload' => 'آپلود',
            'export' => 'خروجی',
        ),
    ],

    // تعریف نقش‌های پیش‌فرض و دسترسی‌های آن‌ها
    'default_roles' => [
        'super-admin' => [
            'name' => 'مدیر ارشد سیستم',
            'description' => 'دسترسی کامل به تمام بخش‌های سیستم',
            'permissions' => '*', // تمام دسترسی‌ها
        ],

        'contract_manager' => [
            'name' => 'مدیر قراردادها',
            'description' => 'مدیریت کامل قراردادها، الحاقیه‌ها و طرفین',
            'permissions' => [
                'dashboard.view',
                'parties.read', 'parties.create', 'parties.update',
                'contracts.read', 'contracts.create', 'contracts.update', 'contracts.submit_to_legal',
                'contracts.review', 'contracts.draft', 'contracts.send_to_contractor', 'contracts.notify',
                'contract-settings.manage',
                'amendments.read', 'amendments.create',
                'invoices.read',
                'guarantees.read', 'guarantees.create',
                'documents.read', 'documents.upload',
                'reports.view',
            ],
        ],

        'legal_expert' => [
            'name' => 'کارشناس حقوقی',
            'description' => 'بررسی و تایید متون قراردادها و الحاقیه‌ها',
            'permissions' => [
                'dashboard.view',
                'parties.read',
                'contracts.read', 'contracts.create', 'contracts.review', 'contracts.draft', 'contracts.send_to_contractor',
                'contracts.legal_sign', 'contracts.notify', 'signatures.read', 'signatures.sign',
                'contract-settings.manage',
                'amendments.read', 'amendments.approve',
                'invoices.read',
                'guarantees.read',
                'signatures.read', 'signatures.sign',
                'documents.read',
            ],
        ],

        'financial_expert' => [
            'name' => 'کارشناس مالی',
            'description' => 'مدیریت صورت‌وضعیت‌ها و پرداخت‌ها',
            'permissions' => [
                'dashboard.view',
                'contracts.read',
                'invoices.read', 'invoices.create', 'invoices.submit', 'invoices.approve',
                'payments.read', 'payments.create', 'payments.update',
                'guarantees.read', 'guarantees.release',
                'documents.read', 'documents.upload',
                'reports.view', 'reports.export',
            ],
        ],

        'contractor' => [
            'name' => 'پیمانکار',
            'description' => 'دسترسی محدود برای مشاهده و امضای قراردادها',
            'permissions' => [
                'dashboard.view',
                'contracts.read', // فقط قراردادهای خود
                'invoices.read',
                'signatures.read', 'signatures.sign',
                'documents.read',
            ],
        ],

        'unit_head' => [
            'name' => 'رئیس واحد',
            'description' => 'امضای قراردادهای واحد متبوع',
            'permissions' => ['contracts.read', 'contracts.create', 'contracts.unit_sign', 'contracts.submit_to_contract_unit', 'signatures.read', 'signatures.sign'],
        ],
        'unit_contract_staff' => [
            'name' => 'کارشناس قرارداد واحد',
            'description' => 'ثبت درخواست قرارداد برای واحد سازمانی خود',
            'permissions' => [
                'dashboard.view',
                'contracts.read',
                'contracts.create',
                'contracts.update',
                'contracts.submit_to_legal',
                'contracts.submit_to_contract_unit',
            ],
        ],
        'unit_manager' => [
            'name' => 'مدیر واحد',
            'description' => 'امضای قراردادهای واحد متبوع',
            'permissions' => ['contracts.read', 'contracts.create', 'contracts.unit_sign', 'contracts.submit_to_contract_unit', 'signatures.read', 'signatures.sign'],
        ],
        'unit_deputy' => [
            'name' => 'معاون واحد',
            'description' => 'امضای قراردادهای واحد متبوع',
            'permissions' => ['contracts.read', 'contracts.create', 'contracts.unit_sign', 'contracts.submit_to_contract_unit', 'signatures.read', 'signatures.sign'],
        ],
        'deputy_ceo' => [
            'name' => 'معاون مالی',
            'description' => 'امضای قراردادهای تا سقف تعیین‌شده',
            'permissions' => ['contracts.read', 'signatures.read', 'signatures.sign'],
        ],
        'ceo' => [
            'name' => 'مدیرعامل',
            'description' => 'امضای قراردادهای بالاتر از سقف تعیین‌شده',
            'permissions' => ['contracts.read', 'signatures.read', 'signatures.sign'],
        ],
        'board_member' => [
            'name' => 'عضو هیئت‌مدیره',
            'description' => 'امضای قراردادهای بالاتر از سقف تعیین‌شده',
            'permissions' => ['contracts.read', 'signatures.read', 'signatures.sign'],
        ],
        'guarantee_officer' => [
            'name' => 'کارشناس دریافت ضمانت',
            'description' => 'ثبت و تأیید ضمانت‌های دریافتی',
            'permissions' => ['guarantees.manage'],
        ],
    ],
];
