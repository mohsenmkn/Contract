// src/menu.js

import {
    LayoutDashboard, Users, ShieldCheck, Wallet, FileText, SquareStack,
    FolderTree, Building2, Settings, UsersRound, RefreshCw, Network,
    FileSignature, Handshake, Receipt, Shield, PenTool, BarChart3,
    FilePlus2, CreditCard,Send,Briefcase,UserCheck,
} from 'lucide-vue-next'

export const menuItems = [
    // ═══════════════════════════════════════════════
    // داشبورد
    // ═══════════════════════════════════════════════
    // {
    //     key: 'dashboard',
    //     label: 'داشبورد',
    //     to: '/dashboard',
    //     icon: LayoutDashboard,
    // },

    // ═══════════════════════════════════════════════
    // سامانه مدیریت قراردادها
    // ═══════════════════════════════════════════════
    // ✅ درخواست‌های قرارداد من (برای نماینده واحد)
    {
        key: 'my-contract-requests',
        label: 'درخواست‌های من',
        to: '/contract-requests',
        access: 'contracts.create',
        icon: Send,
    },
    {
        key: 'contracts',
        label: 'سامانه قراردادها',
        icon: FileSignature,
        children: [
            {
                key: 'contract-dashboard',
                label: 'داشبورد قراردادها',
                to: '/contract-dashboard',
                access: 'dashboard.view',
                icon: BarChart3,
            },
            {
                key: 'contractor-portal',
                label: 'پورتال پیمانکار',
                to: '/contractor-portal',
                access: 'contracts.read',
                icon: Building2,
            },
            {
                key: 'parties',
                label: 'طرفین قرارداد',
                to: '/parties',
                access: 'parties.read',
                icon: Handshake,
            },
            {
                key: 'contracts-list',
                label: 'قراردادها',
                to: '/contracts',
                access: 'contracts.read',
                icon: FileText,
            },
            {
                key: 'amendments',
                label: 'الحاقیه‌ها',
                to: '/amendments',
                access: 'amendments.read',
                icon: FilePlus2,
            },
            {
                key: 'invoices',
                label: 'صورت‌وضعیت‌ها',
                to: '/invoices',
                access: 'invoices.read',
                icon: Receipt,
            },
            {
                key: 'payments',
                label: 'پرداخت‌ها',
                to: '/payments',
                access: 'payments.read',
                icon: CreditCard,
            },
            {
                key: 'guarantees',
                label: 'ضمانت‌نامه‌ها',
                to: '/guarantees',
                access: 'guarantees.read',
                icon: Shield,
            },
            {
                key: 'signatures',
                label: 'امضاهای در انتظار',
                to: '/signatures',
                access: 'signatures.read',
                icon: PenTool,
            },
        ],
    },


    // ✅ واحدهای سازمانی (جدید)
    {
        key: 'organization',
        label: 'سازمان',
        icon: Briefcase,
        children: [
            {
                key: 'units',
                label: 'واحدهای سازمانی',
                to: '/units',
                access: 'units.read',
                icon: Building2,
            },
            {
                key: 'representatives',
                label: 'نمایندگان واحدها',
                to: '/units/representatives',
                access: 'units.assign_representative',
                icon: UserCheck,
            },
        ],
    },

    // ═══════════════════════════════════════════════
    // مدیریت کاربران و دسترسی‌ها
    // ═══════════════════════════════════════════════
    {
        key: 'system',
        label: 'مدیریت سیستم',
        icon: Settings,
        children: [
            {
                key: 'users',
                label: 'کاربران',
                to: '/users',
                access: 'users.read',
                icon: Users,
            },
            {
                key: 'roles',
                label: 'نقش‌ها و دسترسی‌ها',
                to: '/roles',
                access: 'roles.read',
                icon: ShieldCheck,
            },
            {
                key: 'groups',
                label: 'گروه‌های کاربری',
                to: '/groups',
                access: 'groups.read',
                icon: UsersRound,
            },
        ],
    },
]