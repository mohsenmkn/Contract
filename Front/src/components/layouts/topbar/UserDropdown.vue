
<template>
  <div ref="root" class="user-wrapper">

    <!-- =====================================================
         USER TRIGGER
         ===================================================== -->

    <button
        type="button"
        class="user-trigger"
        :class="{ 'is-open': isOpen }"
        @click.stop="toggle"
    >

      <div class="avatar">
        {{ userInitial }}
      </div>

      <div class="user-info">

        <div class="user-name">
          {{ userName }}
        </div>

        <div class="user-role">
          {{ userRole }}
        </div>

      </div>

      <i class="pi pi-angle-down user-chevron"></i>

    </button>


    <!-- =====================================================
         DROPDOWN
         ===================================================== -->

    <Transition name="dropdown">

      <div
          v-if="isOpen"
          class="user-dropdown"
          @click.stop
      >

        <!-- Profile -->
        <div class="dropdown-profile">

          <div class="profile-avatar">
            {{ userInitial }}
          </div>

          <div class="profile-info">

            <div class="profile-name">
              {{ userName }}
            </div>

            <div class="profile-role">
              {{ userRole }}
            </div>

          </div>

        </div>


        <div class="dropdown-divider"></div>


        <!-- Items -->

        <button
            type="button"
            class="dropdown-item"
            @click="handleProfile"
        >
          <span class="item-icon blue">
            <i class="pi pi-user"></i>
          </span>

          <span>
            پروفایل کاربری
          </span>

          <i class="pi pi-angle-left arrow"></i>
        </button>


        <button
            type="button"
            class="dropdown-item"
            @click="handleSettings"
        >
          <span class="item-icon gray">
            <i class="pi pi-cog"></i>
          </span>

          <span>
            تنظیمات
          </span>

          <i class="pi pi-angle-left arrow"></i>
        </button>


        <div class="dropdown-divider"></div>


        <!-- Logout -->

        <button
            type="button"
            class="dropdown-item logout-item"
            @click="handleLogout"
        >
          <span class="item-icon red">
            <i class="pi pi-sign-out"></i>
          </span>

          <span>
            خروج از حساب کاربری
          </span>

        </button>

      </div>

    </Transition>

  </div>
</template>


<script setup>
import {
  computed,
  onBeforeUnmount,
  onMounted,
  ref
} from 'vue'

import { useAuthStore } from '@/stores/auth.js'

const emit = defineEmits([
  'profile',
  'settings',
  'logout'
])

const auth = useAuthStore()

const isOpen = ref(false)


// =====================================================
// USER
// =====================================================

const userName = computed(() => {

  if (auth.displayName) {
    return auth.displayName
  }

  if (auth.user?.name) {
    return auth.user.name
  }

  if (auth.user?.username) {
    return auth.user.username
  }

  return 'کاربر سیستم'

})


const userRole = computed(() => {

  return (
      auth.user?.role ||
      auth.user?.roleName ||
      'مدیر سیستم'
  )

})


const userInitial = computed(() => {

  const name = userName.value?.trim()

  if (!name) {
    return 'ک'
  }

  return name.charAt(0)

})


// =====================================================
// TOGGLE
// =====================================================

const toggle = () => {

  isOpen.value = !isOpen.value

}


// =====================================================
// ACTIONS
// =====================================================

const handleProfile = () => {

  emit('profile')

  isOpen.value = false

}


const handleSettings = () => {

  emit('settings')

  isOpen.value = false

}


const handleLogout = () => {

  emit('logout')

  isOpen.value = false

}


// =====================================================
// OUTSIDE CLICK
// =====================================================

const root = ref(null)

const handleOutsideClick = (event) => {

  if (
      root.value &&
      !root.value.contains(event.target)
  ) {
    isOpen.value = false
  }

}


const handleKeydown = (event) => {

  if (event.key === 'Escape') {
    isOpen.value = false
  }

}


onMounted(() => {

  document.addEventListener(
      'click',
      handleOutsideClick
  )

  document.addEventListener(
      'keydown',
      handleKeydown
  )

})


onBeforeUnmount(() => {

  document.removeEventListener(
      'click',
      handleOutsideClick
  )

  document.removeEventListener(
      'keydown',
      handleKeydown
  )

})

</script>


<style scoped>

.user-wrapper {

  position: relative;

}


/* =====================================================
   TRIGGER
   ===================================================== */

.user-trigger {

  display: flex;

  align-items: center;

  gap: 9px;

  min-height: 48px;

  padding:
      4px 8px;

  border: 1px solid transparent;

  border-radius: 13px;

  background: transparent;

  font-family: inherit;

  cursor: pointer;

  transition:
      background 0.18s ease,
      border-color 0.18s ease;

}


.user-trigger:hover,
.user-trigger.is-open {

  background:
      #f8fafc;

  border-color:
      rgba(148, 163, 184, 0.10);

}


/* =====================================================
   AVATAR
   ===================================================== */

.avatar,
.profile-avatar {

  display: flex;

  align-items: center;
  justify-content: center;

  border-radius: 11px;

  background:
      linear-gradient(
          135deg,
          #2563eb,
          #4f46e5
      );

  color: white;

  font-weight: 750;

  box-shadow:
      0 5px 13px
      rgba(37, 99, 235, 0.20);

}


.avatar {

  width: 39px;
  height: 39px;

  flex: 0 0 39px;

  font-size: 14px;

}


.user-info {

  min-width: 85px;

  display: flex;

  flex-direction: column;

  align-items: flex-start;

}


.user-name {

  max-width: 145px;

  overflow: hidden;

  white-space: nowrap;

  text-overflow: ellipsis;

  color: #1e293b;

  font-size: 12px;

  font-weight: 700;

}


.user-role {

  margin-top: 2px;

  color: #94a3b8;

  font-size: 9.5px;

}


.user-chevron {

  color: #94a3b8;

  font-size: 10px;

}


/* =====================================================
   DROPDOWN
   ===================================================== */

.user-dropdown {

  position: absolute;

  top: calc(100% + 9px);

  left: 0;

  width: 255px;

  overflow: hidden;

  border:
      1px solid
      rgba(148, 163, 184, 0.17);

  border-radius: 15px;

  background:
      rgba(255, 255, 255, 0.98);

  box-shadow:
      0 20px 50px
      rgba(15, 23, 42, 0.15);

  backdrop-filter: blur(18px);

  z-index: 200;

}


/* =====================================================
   PROFILE
   ===================================================== */

.dropdown-profile {

  display: flex;

  align-items: center;

  gap: 10px;

  padding:
      15px;

}


.profile-avatar {

  width: 42px;
  height: 42px;

  flex: 0 0 42px;

  font-size: 15px;

}


.profile-info {

  min-width: 0;

}


.profile-name {

  color: #0f172a;

  font-size: 12.5px;

  font-weight: 750;

  white-space: nowrap;

  overflow: hidden;

  text-overflow: ellipsis;

}


.profile-role {

  margin-top: 2px;

  color: #94a3b8;

  font-size: 10px;

}


/* =====================================================
   DIVIDER
   ===================================================== */

.dropdown-divider {

  height: 1px;

  background:
      rgba(148, 163, 184, 0.12);

}


/* =====================================================
   ITEMS
   ===================================================== */

.dropdown-item {

  width: 100%;

  height: 44px;

  display: flex;

  align-items: center;

  gap: 9px;

  padding:
      0 13px;

  border: 0;

  background: transparent;

  color: #475569;

  font-family: inherit;

  font-size: 11.5px;

  font-weight: 550;

  text-align: right;

  cursor: pointer;

  transition:
      background 0.16s ease,
      color 0.16s ease;

}


.dropdown-item:hover {

  background:
      #f8fafc;

  color: #1e293b;

}


.item-icon {

  width: 29px;
  height: 29px;

  flex: 0 0 29px;

  display: flex;

  align-items: center;
  justify-content: center;

  border-radius: 8px;

}


.item-icon i {

  font-size: 12px;

}


.item-icon.blue {

  background: #eff6ff;

  color: #2563eb;

}


.item-icon.gray {

  background: #f1f5f9;

  color: #64748b;

}


.item-icon.red {

  background: #fef2f2;

  color: #dc2626;

}


.arrow {

  margin-right: auto;

  color: #cbd5e1;

  font-size: 9px;

}


.dropdown-item:hover .arrow {

  color: #94a3b8;

}


.logout-item {

  color: #dc2626;

}


.logout-item:hover {

  color: #b91c1c;

  background:
      #fef2f2;

}


/* =====================================================
   ANIMATION
   ===================================================== */

.dropdown-enter-active,
.dropdown-leave-active {

  transition:
      opacity 0.18s ease,
      transform 0.18s ease;

}


.dropdown-enter-from,
.dropdown-leave-to {

  opacity: 0;

  transform:
      translateY(-6px)
      scale(0.98);

}


.dropdown-enter-to,
.dropdown-leave-from {

  opacity: 1;

  transform:
      translateY(0)
      scale(1);

}


/* =====================================================
   MOBILE
   ===================================================== */

@media (max-width: 1024px) {

  .user-info,
  .user-chevron {

    display: none;

  }

  .user-trigger {

    padding: 3px;

  }

}


@media (max-width: 576px) {

  .user-dropdown {

    position: fixed;

    top: 68px;

    left: 10px;

    width: calc(100vw - 20px);

  }

}

</style>
