import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { menuItems } from '@/config/menu'

export function useDashboardModules() {
    const auth = useAuthStore()
    const accessibleModules = computed(() => menuItems
        .flatMap(item => item.children || [])
        .filter(item => !item.access || auth.can(item.access)))
    return { accessibleModules, user: computed(() => auth.user), roles: computed(() => auth.roles) }
}
