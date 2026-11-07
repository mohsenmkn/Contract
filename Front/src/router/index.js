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

const unitContractRoles = ['unit_contract_staff', 'unit_head', 'unit_manager', 'unit_deputy', 'unit_representative'];

const contractCreateComponent = () => {
  const auth = useAuthStore();
  return  import('@/components/contract/ContractRequestForm.vue');
};

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior(to) {
    if (to.hash) return { el: to.hash, behavior: 'smooth' }
    return { top: 0 }
  },
  routes: [
    {
      path: '/',
      redirect: (to) => {
        const auth = useAuthStore()
        return { name: auth.defaultDashboard }
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
            roles: ['super-admin', 'contract_manager', 'financial_expert', 'legal_expert']
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

        {
          path: '/executive-dashboard',
          name: 'executive.dashboard',
          component: () => import('@/views/Dashboard/ExecutiveDashboard.vue'),
          meta: {
            title: 'داشبورد امضاهای مدیریتی',
            requiresAuth: true,
            roles: ['ceo', 'board_member', 'deputy_ceo']
          }
        },
        {
          path: '/unit-dashboard',
          name: 'unit.dashboard',
          component: () => import('@/views/Dashboard/UnitDashboard.vue'),
          meta: {
            title: 'داشبورد واحد',
            requiresAuth: true,
            roles: ['unit_contract_staff', 'unit_head', 'unit_manager', 'unit_deputy', 'unit_representative','کاربران واحد'],
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
            roles: ['super-admin', 'contract_manager', 'financial_expert']
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
          component: contractCreateComponent,
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
          component: () => import('@/components/contract/ContractRequestForm.vue'),
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
          path: 'guarantees/:id',
          name: 'guarantees.show',
          component: () => import('@/views/Guarantee/GuaranteeShow.vue'),
          meta: {
            title: 'جزئیات ضمانت‌نامه',
            permission: 'guarantees.read',
            parent: 'guarantees.index'
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
          path: 'guarantee-desk',
          name: 'guarantee.desk',
          component: () => import('@/views/Guarantee/GuaranteeDesk.vue'),
          meta: {
            title: 'میز دریافت ضمانت',
            permission: 'guarantees.manage'
          }
        },
        {
          path: 'contracts/:contractId/guarantees/create',
          name: 'guarantees.create',
          component: () => import('@/views/Guarantee/GuaranteeCreate.vue'),
          meta: {
            title: 'ثبت ضمانت‌نامه',
            permission: 'guarantees.manage',
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
          path: 'signature-assets',
          name: 'signatures.assets',
          component: () => import('@/views/Signature/SignatureAssets.vue'),
          meta: {
            title: 'مدیریت امضای کاربران',
            roles: ['super-admin']
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
          path: 'contract-system-settings',
          name: 'contract.system-settings',
          component: () => import('@/views/Contract/ContractSystemSettings.vue'),
          meta: {
            title: 'تنظیمات سامانه قراردادها',
            roles: ['super-admin']
          }
        },
        {
          path: 'contract-settings',
          name: 'contract.settings',
          component: () => import('@/views/Contract/ContractSettings.vue'),
          meta: {
            title: 'تنظیمات گردش‌کار قرارداد',
            permission: 'contract-settings.manage'
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
        {
          path: 'contract-requests',
          redirect: { name: 'contracts.index' }
        },
        {
          path: 'contract-requests/create',
          redirect: { name: 'contracts.create' }
        },
        {
          path: 'contract-requests/:id/edit',
          redirect: to => ({ name: 'contracts.edit', params: { id: to.params.id } })
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

  // The root redirect can run before persisted auth finishes loading. Correct
  // its fallback destination once the user's role is known.
  if (auth.token && to.redirectedFrom?.path === '/' && to.name !== auth.defaultDashboard) {
    return { name: auth.defaultDashboard };
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
