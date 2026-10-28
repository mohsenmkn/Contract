import { defineStore } from 'pinia'

export const useUiStore = defineStore('ui', {
    state: () => ({
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === '1',
        openGroups: JSON.parse(localStorage.getItem('openGroups') || '{}'), // { [key]: true/false }
    }),

    actions: {
        toggleSidebar() {
            this.sidebarCollapsed = !this.sidebarCollapsed
            localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed ? '1' : '0')
        },

        toggleGroup(key) {
            this.openGroups[key] = !this.openGroups[key]
            localStorage.setItem('openGroups', JSON.stringify(this.openGroups))
        },

        closeAllGroups() {
            this.openGroups = {}
            localStorage.setItem('openGroups', JSON.stringify(this.openGroups))
        },
    },
})
