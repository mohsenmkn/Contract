// src/router/index.js

import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import LoginView from '@/views/Auth/LoginView.vue';
import ForgotPasswordView from '@/views/Auth/ForgotPasswordView.vue';
import DashboardLayout from '@/components/layouts/DashboardLayout.vue';
import UsersView from '@/views/User/UsersView.vue';
import ForbiddenPage from '@/views/ForbiddenPage.vue';
import RoleList from '@/views/User/RoleList.vue';
import GroupsView from '@/views/Acl/GroupsView.vue';

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: (to) => {
        const auth = useAuthStore()
        // ✅ هدایت هوشمند بر اساس نقش
        if (auth.isContractor) {
          return { name: 'contractor.portal' }
        }
        return { name: 'Dashboard' }
      }
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { requiresGuest: true }
    },
    {
      path: '/forgot-password',
      name: 'forgot-password',
      component: ForgotPasswordView,
      meta: { requiresGuest: true }
    },
    {
      path: '/',
      component: DashboardLayout,
      meta: { requiresAuth: true },
      children: [
        // ═══════════════════════════════════════════════
        // داشبورد مدیریتی (فقط برای مدیران)
        // ═══════════════════════════════════════════════
        {
          path: '/dashboard',
          name: 'Dashboard',
          component: () => import('@/views/Dashboard/ContractDashboard.vue'),
          meta: {
            title: 'داشبورد مدیریتی',
            requiresAuth: true,
            roles: ['super_admin', 'contract_manager', 'financial_expert', 'legal_expert']
          }
        },

        // ═══════════════════════════════════════════════
        // پورتال پیمانکار (فقط برای پیمانکاران)
        // ═══════════════════════════════════════════════
        {
          path: '/contractor-portal',
          name: 'contractor.portal',
          component: () => import('@/views/Contract/ContractorPortal.vue'),
          meta: {
            title: 'پورتال پیمانکار',
            requiresAuth: true,
            roles: ['contractor']
          }
        },

        // ═══════════════════════════════════════════════
        // داشبورد قراردادها (برای مدیران)
        // ═══════════════════════════════════════════════
        {
          path: 'contract-dashboard',
          name: 'contract.dashboard',
          component: () => import('@/views/Dashboard/ContractDashboard.vue'),
          meta: {
            title: 'داشبورد قراردادها',
            permission: 'dashboard.view',
            roles: ['super_admin', 'contract_manager', 'financial_expert']
          }
        },

        // ═══════════════════════════════════════════════
        // مدیریت کاربران و دسترسی‌ها
        // ═══════════════════════════════════════════════
        {
          path: 'users',
          name: 'users.index',
          component: UsersView,
          meta: {
            title: 'کاربران',
            permission: 'users.read'
          }
        },
        {
          path: 'roles',
          name: 'roles.index',
          component: RoleList,
          meta: {
            title: 'نقش‌ها',
            permission: 'roles.read'
          }
        },
        {
          path: 'groups',
          name: 'groups.index',
          component: GroupsView,
          meta: {
            title: 'گروه‌های کاربری',
            permission: 'groups.read'
          }
        },
        {
          path: '/profile',
          name: 'profile',
          component: () => import('@/views/User/Profile.vue'),
          meta: {
            title: 'پروفایل من',
            requiresAuth: true
          }
        },
        {
          path: '/settings',
          name: 'settings',
          component: () => import('@/views/User/Settings.vue'),
          meta: {
            title: 'تنظیمات',
            requiresAuth: true
          }
        },

        // ═══════════════════════════════════════════════
        // سامانه مدیریت قراردادها
        // ═══════════════════════════════════════════════
        {
          path: 'parties',
          name: 'parties.index',
          component: () => import('@/views/Party/PartiesList.vue'),
          meta: {
            title: 'طرفین قرارداد',
            permission: 'parties.read'
          }
        },
        {
          path: 'parties/create',
          name: 'parties.create',
          component: () => import('@/views/Party/PartyForm.vue'),
          meta: {
            title: 'ایجاد طرف قرارداد',
            permission: 'parties.create',
            parent: 'parties.index'
          }
        },
        {
          path: 'parties/:id/edit',
          name: 'parties.edit',
          component: () => import('@/views/Party/PartyForm.vue'),
          meta: {
            title: 'ویرایش طرف قرارداد',
            permission: 'parties.update',
            parent: 'parties.index'
          }
        },
        {
          path: 'contracts',
          name: 'contracts.index',
          component: () => import('@/views/Contract/ContractsList.vue'),
          meta: {
            title: 'قراردادها',
            permission: 'contracts.read'
          }
        },
        {
          path: 'contracts/create',
          name: 'contracts.create',
          component: () => import('@/views/Contract/ContractCreate.vue'),
          meta: {
            title: 'ایجاد قرارداد',
            permission: 'contracts.create',
            parent: 'contracts.index'
          }
        },
        {
          path: 'contracts/:id',
          name: 'contracts.show',
          component: () => import('@/views/Contract/ContractShow.vue'),
          meta: {
            title: 'جزئیات قرارداد',
            permission: 'contracts.read',
            parent: 'contracts.index'
          }
        },
        {
          path: 'contracts/:id/edit',
          name: 'contracts.edit',
          component: () => import('@/views/Contract/ContractCreate.vue'),
          meta: {
            title: 'ویرایش قرارداد',
            permission: 'contracts.update',
            parent: 'contracts.index'
          }
        },
        {
          path: 'amendments',
          name: 'amendments.index',
          component: () => import('@/views/Amendment/AmendmentsList.vue'),
          meta: {
            title: 'الحاقیه‌ها',
            permission: 'amendments.read'
          }
        },
        {
          path: 'contracts/:contractId/amendments/create',
          name: 'amendments.create',
          component: () => import('@/views/Amendment/AmendmentCreate.vue'),
          meta: {
            title: 'ایجاد الحاقیه',
            permission: 'amendments.create',
            parent: 'contracts.show'
          }
        },
        {
          path: 'invoices',
          name: 'invoices.index',
          component: () => import('@/views/Invoice/InvoicesList.vue'),
          meta: {
            title: 'صورت‌وضعیت‌ها',
            permission: 'invoices.read'
          }
        },
        {
          path: 'contracts/:contractId/invoices/create',
          name: 'invoices.create',
          component: () => import('@/views/Invoice/InvoiceCreate.vue'),
          meta: {
            title: 'ایجاد صورت‌وضعیت',
            permission: 'invoices.create',
            parent: 'contracts.show'
          }
        },
        {
          path: 'invoices/:id',
          name: 'invoices.show',
          component: () => import('@/views/Invoice/InvoiceShow.vue'),
          meta: {
            title: 'جزئیات صورت‌وضعیت',
            permission: 'invoices.read',
            parent: 'invoices.index'
          }
        },
        {
          path: 'payments',
          name: 'payments.index',
          component: () => import('@/views/Payment/PaymentsList.vue'),
          meta: {
            title: 'پرداخت‌ها',
            permission: 'payments.read'
          }
        },
        {
          path: 'invoices/:invoiceId/payments/create',
          name: 'payments.create',
          component: () => import('@/views/Payment/PaymentCreate.vue'),
          meta: {
            title: 'ثبت پرداخت',
            permission: 'payments.create',
            parent: 'invoices.show'
          }
        },
        {
          path: 'guarantees',
          name: 'guarantees.index',
          component: () => import('@/views/Guarantee/GuaranteesList.vue'),
          meta: {
            title: 'ضمانت‌نامه‌ها',
            permission: 'guarantees.read'
          }
        },
        {
          path: 'contracts/:contractId/guarantees/create',
          name: 'guarantees.create',
          component: () => import('@/views/Guarantee/GuaranteeCreate.vue'),
          meta: {
            title: 'ثبت ضمانت‌نامه',
            permission: 'guarantees.create',
            parent: 'contracts.show'
          }
        },
        {
          path: 'signatures',
          name: 'signatures.index',
          component: () => import('@/views/Signature/PendingSignatures.vue'),
          meta: {
            title: 'امضاهای در انتظار',
            permission: 'signatures.read'
          }
        },
        {
          path: 'signatures/:id',
          name: 'signatures.detail',
          component: () => import('@/views/Signature/SignatureDetail.vue'),
          meta: {
            title: 'امضای سند',
            permission: 'signatures.sign',
            parent: 'signatures.index'
          }
        },
        // واحدهای سازمانی
        {
          path: 'units',
          name: 'units.index',
          component: () => import('@/views/Organization/UnitsList.vue'),
          meta: {
            title: 'واحدهای سازمانی',
            permission: 'units.read'
          }
        },
        {
          path: 'units/create',
          name: 'units.create',
          component: () => import('@/views/Organization/UnitForm.vue'),
          meta: {
            title: 'ایجاد واحد',
            permission: 'units.create',
            parent: 'units.index'
          }
        },

        // 403
        {
          path: '403',
          name: 'forbidden',
          component: ForbiddenPage
        }
      ]
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/NotFoundPage.vue')
    }
  ]
});

// ═══════════════════════════════════════════════
// Router Guards
// ═══════════════════════════════════════════════
// src/router/index.js (بخش guard)

router.beforeEach(async (to) => {
  const auth = useAuthStore();

  // ✅ اطمینان از لود شدن auth
  if (!auth.loaded) {
    await auth.initAuth();
  }

  // ✅ اگر کاربر لاگین کرده و به صفحه مهمان می‌رود
  if (to.meta.requiresGuest && auth.token) {
    return { name: auth.defaultDashboard };
  }

  // ✅ اگر نیاز به احراز هویت دارد و کاربر لاگین نکرده
  if (to.matched.some(record => record.meta.requiresAuth) && !auth.token) {
    return {
      name: 'login',
      query: { redirect: to.fullPath }
    };
  }

  // ✅ بررسی نقش خاص (roles)
  const requiredRoles = to.matched
      .map(record => record.meta.roles)
      .filter(Boolean)
      .flat();

  if (requiredRoles.length > 0 && !auth.hasAnyRole(requiredRoles)) {
    return { name: 'forbidden' };
  }

  // ✅ بررسی دسترسی (permission)
  const permissions = to.matched
      .map(record => record.meta.permission)
      .filter(Boolean);

  if (permissions.some(permission => !auth.can(permission))) {
    return { name: 'forbidden' };
  }

  return true;
});

export default router;