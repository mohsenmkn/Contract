import test from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync, existsSync } from 'node:fs'
import vm from 'node:vm'
import { createRouter, createMemoryHistory } from 'vue-router'
import * as icons from 'lucide-vue-next'

const source = path => readFileSync(new URL(`../src/${path}`, import.meta.url), 'utf8')
const menu = vm.runInNewContext(source('config/menu.js')
  .replace(/import[\s\S]*?from 'lucide-vue-next'/, '')
  .replace('export const menuItems', 'const menuItems') + '\nmenuItems', icons)

function fixture(permissions = [], token = 'test-token') {
  let guard
  const auth = { loaded: true, token, can: permission => permissions.includes(permission) }
  const code = source('router/index.js')
    .replace(/^import .*$/gm, '')
    .replace('import.meta.env.BASE_URL', "'/'")
    .replace('export default router', 'router')
  const component = { render: () => null }
  const context = {
    createWebHistory: createMemoryHistory,
    createRouter: options => {
      const router = createRouter(options)
      router.beforeEach = callback => { guard = callback }
      return router
    },
    useAuthStore: () => auth,
    ...Object.fromEntries(['LoginView', 'ForgotPasswordView', 'DashboardLayout', 'UsersView',
      'ForbiddenPage', 'RoleList', 'PayslipShow', 'AdminPayslips', 'GroupsView'].map(name => [name, component])),
  }
  return { router: vm.runInNewContext(code, context), guard: to => guard(to) }
}

test('every menu destination resolves to an implemented route with matching permission', () => {
  const { router } = fixture()
  for (const item of menu.flatMap(item => item.children || [item])) {
    const route = router.resolve(item.to)
    assert.notEqual(route.name, 'not-found', item.to)
    assert.equal(route.meta.permission, item.access, item.to)
  }
})

test('every route component exists and deleted modules resolve to not-found', () => {
  for (const match of source('router/index.js').matchAll(/['"](@\/[^'"]+\.vue)['"]/g)) {
    assert.ok(existsSync(new URL(`../src/${match[1].slice(2)}`, import.meta.url)), match[1])
  }
  const { router } = fixture()
  for (const path of ['/contracts', '/inventory', '/assessment', '/complaints', '/virtual-secretariat', '/library']) {
    assert.equal(router.resolve(path).name, 'not-found', path)
  }
})

test('guests are redirected to login and unauthorized users to forbidden', async () => {
  const guest = fixture([], null)
  assert.equal((await guest.guard(guest.router.resolve('/hr/employees'))).name, 'login')
  const user = fixture()
  assert.equal((await user.guard(user.router.resolve('/hr/employees'))).name, 'forbidden')
  assert.equal(await user.guard(user.router.resolve('/profile')), true)
})

test('admin payroll access does not require personal payroll access', async () => {
  const admin = fixture(['AdminPayroll.view'])
  assert.equal(await admin.guard(admin.router.resolve('/payroll/admin')), true)
  assert.equal((await admin.guard(admin.router.resolve('/payroll/payslip'))).name, 'forbidden')
})

test('navigation labels retain Persian text', () => {
  for (const item of menu.flatMap(item => [item, ...(item.children || [])])) {
    assert.match(item.label, /[\u0600-\u06ff]/)
    assert.doesNotMatch(item.label, /\?{3}/)
  }
})
