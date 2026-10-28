
<template>
  <div class="sidebar-wrapper">

    <!-- Mobile overlay -->
    <Transition name="fade">
      <div
          v-if="isMobile && open"
          class="overlay"
          @click="emit('close')"
      ></div>
    </Transition>

    <aside
        class="sidebar"
        :class="{
        'is-open': isMobile ? open : true,
        'is-collapsed': isCollapsed && !isMobile
      }"
    >

      <!-- =====================================================
           HEADER
           ===================================================== -->
      <div class="header">

        <div class="brand">
          <div class="logo">
            <Building2 class="logo-icon" />
          </div>

          <div class="title" v-show="!isCollapsed">
            <div class="app-name">پنل مدیریت</div>

            <div
                class="user"
                v-if="auth.user && !isCollapsed"
            >
              {{ auth.displayName }}
            </div>
          </div>
        </div>

        <!-- Desktop collapse -->
        <button
            v-if="!isMobile"
            type="button"
            class="icon-btn collapse-btn"
            @click="toggleCollapse"
            :aria-label="isCollapsed ? 'باز کردن منو' : 'بستن منو'"
        >
          <component
              :is="isCollapsed ? ChevronLeft : ChevronRight"
          />
        </button>

        <!-- Mobile close -->
        <button
            v-if="isMobile"
            type="button"
            class="icon-btn close-btn"
            @click="emit('close')"
            aria-label="بستن منو"
        >
          <X />
        </button>

      </div>


      <!-- =====================================================
           SEARCH
           فقط در حالت باز
           ===================================================== -->
      <div
          v-if="auth.loaded && !isCollapsed"
          class="search-container"
      >
        <div class="search-box">

          <Search class="search-icon" />

          <input
              v-model="searchQuery"
              type="text"
              placeholder="جستجو در منو..."
              class="search-input"
          />

          <button
              v-if="searchQuery"
              type="button"
              class="clear-btn"
              @click="searchQuery = ''"
              aria-label="پاک کردن جستجو"
          >
            <X class="clear-icon" />
          </button>

        </div>
      </div>


      <!-- =====================================================
           MENU
           ===================================================== -->
      <nav
          v-if="auth.loaded"
          class="nav"
          :key="auth.user?.id || 'guest'"
      >

        <template v-if="filteredMenu.length > 0">

          <!--
            نکته مهم:

            دیگر در اینجا برای حالت Collapsed منو را دستی
            تولید نمی‌کنیم.

            هر دو حالت باز و بسته توسط MenuItem مدیریت می‌شوند.
            بنابراین منطق Popover در حالت بسته نیز فعال است.
          -->
          <MenuItem
              v-for="item in filteredMenu"
              :key="item.key"
              :item="item"
              :depth="0"
              :opened-groups="openedGroups"
              :toggle-group="toggleGroup"
              :is-active="isActive"
              :on-navigate="onNavigate"
              :has-access="hasAccess"
              :is-collapsed="isCollapsed && !isMobile"
          />

        </template>

        <div
            v-else
            class="no-results"
        >
          <SearchX class="no-results-icon" />
          <p>نتیجه‌ای یافت نشد</p>
        </div>

      </nav>


      <!-- Loading -->
      <div
          v-else
          class="loading"
      >
        <Loader2 class="loading-icon animate-spin" />
        <p>در حال بارگذاری...</p>
      </div>


      <!-- =====================================================
           FOOTER
           ===================================================== -->
      <div
          class="footer"
          v-show="!isCollapsed"
      >
        <div class="version">
          نسخه 1.0.0
        </div>
      </div>

    </aside>
  </div>
</template>


<script setup>
import {
  computed,
  reactive,
  watch,
  ref,
  onMounted,
  onUnmounted
} from 'vue';

import { useRoute } from 'vue-router';

import { useAuthStore } from '@/stores/auth.js';

import { menuItems } from '@/config/menu';

import {
  X,
  Search,
  SearchX,
  Loader2,
  Building2,
  ChevronLeft,
  ChevronRight
} from 'lucide-vue-next';

import MenuItem from '@/components/layouts/MenuItem.vue';


// =====================================================
// PROPS / EMITS
// =====================================================

const props = defineProps({
  open: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close']);


// =====================================================
// STORES / ROUTER
// =====================================================

const auth = useAuthStore();

const route = useRoute();


// =====================================================
// SIDEBAR STATE
// =====================================================

const searchQuery = ref('');

const isCollapsed = ref(false);

const windowWidth = ref(
    typeof window !== 'undefined'
        ? window.innerWidth
        : 1920
);

const isMobile = computed(() => {
  return windowWidth.value <= 1024;
});


// =====================================================
// RESIZE
// =====================================================

const handleResize = () => {

  windowWidth.value = window.innerWidth;

  /*
   * در موبایل حالت collapsed نداریم.
   */
  if (isMobile.value) {
    isCollapsed.value = false;
  }
};


// =====================================================
// COLLAPSE
// =====================================================

const toggleCollapse = () => {

  /*
   * فقط دسکتاپ
   */
  if (isMobile.value) {
    return;
  }

  isCollapsed.value = !isCollapsed.value;

  localStorage.setItem(
      'sidebar_collapsed',
      String(isCollapsed.value)
  );
};


// =====================================================
// MOUNT / UNMOUNT
// =====================================================

onMounted(() => {

  window.addEventListener(
      'resize',
      handleResize
  );

  const saved = localStorage.getItem(
      'sidebar_collapsed'
  );

  if (
      saved === 'true' &&
      !isMobile.value
  ) {
    isCollapsed.value = true;
  }

});


onUnmounted(() => {

  window.removeEventListener(
      'resize',
      handleResize
  );

});


// =====================================================
// MENU STATE
// =====================================================

const STORAGE_KEY = 'sidebar_menu_state';

let savedState = null;

try {

  savedState = localStorage.getItem(
      STORAGE_KEY
  );

} catch (error) {

  savedState = null;

}


let parsedState = {};

if (savedState) {

  try {

    parsedState = JSON.parse(savedState);

  } catch (error) {

    parsedState = {};

  }

}

const openedGroups = reactive(
    parsedState || {}
);


// =====================================================
// SAVE MENU STATE
// =====================================================

watch(
    openedGroups,
    (value) => {

      try {

        localStorage.setItem(
            STORAGE_KEY,
            JSON.stringify(value)
        );

      } catch (error) {
        // ignore localStorage errors
      }

    },
    {
      deep: true
    }
);


// =====================================================
// ACTIVE ROUTE
// =====================================================

const allMenuPaths = [];


const collectPaths = (items) => {

  for (const item of items) {

    if (item.to) {
      allMenuPaths.push(item.to);
    }

    if (
        item.children &&
        item.children.length
    ) {
      collectPaths(item.children);
    }

  }

};


collectPaths(menuItems);


// =====================================================
// ACTIVE PATH
// =====================================================

const activePath = computed(() => {

  const path = route.path;

  const matches = allMenuPaths.filter(
      (to) => {

        if (!to) {
          return false;
        }

        return (
            path === to ||
            path.startsWith(to + '/')
        );

      }
  );

  if (!matches.length) {
    return null;
  }

  /*
   * طولانی‌ترین مسیر را انتخاب می‌کنیم
   * تا Routeهای تو در تو درست تشخیص داده شوند.
   */
  return matches.reduce(
      (a, b) =>
          a.length > b.length
              ? a
              : b
  );

});


// =====================================================
// ACTIVE CHECK
// =====================================================

const isActive = (to) => {

  if (!to) {
    return false;
  }

  return activePath.value === to;

};


// =====================================================
// ACCESS CONTROL
// =====================================================

const hasAccess = (item) => {

  return (
      !item.access ||
      auth.can(item.access)
  );

};


// =====================================================
// FILTER MENU
// =====================================================

const filterMenuItem = (
    item,
    search = ''
) => {

  /*
   * اگر کاربر دسترسی ندارد
   */
  if (!hasAccess(item)) {
    return null;
  }


  const label = String(
      item.label || ''
  );

  const normalizedSearch =
      String(search || '')
          .trim()
          .toLowerCase();

  const match =
      !normalizedSearch ||
      label
          .toLowerCase()
          .includes(normalizedSearch);


  /*
   * Parent
   */
  if (
      item.children &&
      item.children.length
  ) {

    const children = item.children
        .map((child) =>
            filterMenuItem(
                child,
                normalizedSearch
            )
        )
        .filter(Boolean);


    /*
     * اگر جستجو داریم و نه خود Parent
     * match شده و نه فرزندی دارد
     */
    if (
        normalizedSearch &&
        !match &&
        !children.length
    ) {
      return null;
    }


    /*
     * اگر هیچ فرزند قابل نمایشی ندارد
     * و خودش هم match نشده
     */
    if (
        !children.length &&
        !item.to
    ) {
      return null;
    }


    return {
      ...item,
      children
    };

  }


  /*
   * Leaf
   */
  if (
      normalizedSearch &&
      !match
  ) {
    return null;
  }


  return item;

};


// =====================================================
// FILTERED MENU
// =====================================================

const filteredMenu = computed(() => {

  if (
      !auth.loaded ||
      !auth.user
  ) {
    return [];
  }

  return menuItems
      .map((item) =>
          filterMenuItem(
              item,
              searchQuery.value
          )
      )
      .filter(Boolean);

});


// =====================================================
// GROUP TOGGLE
// =====================================================

const toggleGroup = (key) => {

  if (!key) {
    return;
  }

  openedGroups[key] =
      !openedGroups[key];

};


// =====================================================
// NAVIGATION
// =====================================================

const onNavigate = () => {

  /*
   * در موبایل بعد از Navigation
   * Sidebar بسته شود.
   */
  if (isMobile.value) {
    emit('close');
  }

};


// =====================================================
// OPEN ACTIVE GROUPS
// =====================================================

const openActiveGroups = (items) => {

  let foundActive = false;


  for (const item of items) {

    if (
        !item.children ||
        !item.children.length
    ) {

      if (item.to && isActive(item.to)) {
        foundActive = true;
      }

      continue;
    }


    let childIsActive = false;


    for (const child of item.children) {

      /*
       * خود child
       */
      if (
          child.to &&
          isActive(child.to)
      ) {
        childIsActive = true;
      }


      /*
       * فرزندان child
       */
      if (
          child.children &&
          child.children.length
      ) {

        const nestedActive =
            openActiveGroups(
                child.children
            );

        if (nestedActive) {
          childIsActive = true;
        }

      }

    }


    if (childIsActive) {

      openedGroups[item.key] = true;

      foundActive = true;

    }

  }


  return foundActive;

};


// =====================================================
// WATCH ROUTE / MENU
// =====================================================

watch(
    [
      () => route.path,
      () => filteredMenu.value
    ],
    () => {

      if (
          filteredMenu.value.length
      ) {

        openActiveGroups(
            filteredMenu.value
        );

      }

    },
    {
      immediate: true
    }
);

</script>


<style scoped>

/* =====================================================
   BASE
   ===================================================== */

.sidebar-wrapper {
  position: relative;
}


/* =====================================================
   MOBILE OVERLAY
   ===================================================== */

.overlay {
  position: fixed;
  inset: 0;

  background: rgba(
      2,
      6,
      23,
      0.62
  );

  z-index: 40;

  backdrop-filter: blur(5px);
}


.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s;
}


.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}


/* =====================================================
   SIDEBAR
   ===================================================== */

.sidebar {
  direction: rtl;

  position: fixed;

  top: 0;
  right: 0;

  z-index: 50;

  display: flex;
  flex-direction: column;

  width: 292px;

  height: 100vh;
  height: 100dvh;

  box-sizing: border-box;

  color: #e2e8f0;

  background:
      linear-gradient(
          180deg,
          #111827 0%,
          #0f172a 48%,
          #0b1220 100%
      );

  border-left:
      1px solid
      rgba(255, 255, 255, 0.07);

  box-shadow:
      -8px 0 30px
      rgba(2, 6, 23, 0.12);

  transition:
      width
      0.28s
      cubic-bezier(0.4, 0, 0.2, 1);
}


.sidebar.is-collapsed {
  width: 72px;
}


/* =====================================================
   COLLAPSED HEADER / FLOATING TOGGLE
   ===================================================== */

/*
 * وقتی Sidebar بسته است، دکمه Toggle دیگر کنار Avatar نمی‌نشیند.
 * دکمه به شکل یک کپسول کوچک روی لبه چپ Sidebar قرار می‌گیرد.
 */
.sidebar.is-collapsed .header {
  min-height: 72px;
  height: 72px;
  padding: 12px 8px;
  overflow: visible;
  justify-content: center;
}

.sidebar.is-collapsed .brand {
  width: 100%;
  justify-content: center;
  padding-left: 10px;
}

.sidebar.is-collapsed .title {
  display: none;
}

.sidebar.is-collapsed .collapse-btn {
  position: absolute;

  /* لبه چپ Sidebar؛ چون Sidebar راست صفحه است */
  left: -13px;
  top: 50%;

  width: 28px;
  height: 42px;

  min-width: 28px;
  padding: 0;

  transform: translateY(-50%);

  display: inline-flex;
  align-items: center;
  justify-content: center;

  border-radius: 12px 0 0 12px;

  border: 1px solid rgba(255, 255, 255, 0.10);
  border-right: 0;

  background: rgba(15, 23, 42, 0.96);
  color: #cbd5e1;

  box-shadow:
      -4px 0 14px rgba(0, 0, 0, 0.18),
      0 4px 14px rgba(0, 0, 0, 0.30);

  z-index: 100;

  transition:
      background 0.18s ease,
      color 0.18s ease,
      border-color 0.18s ease,
      width 0.18s ease,
      left 0.18s ease;
}

.sidebar.is-collapsed .collapse-btn svg {
  width: 15px;
  height: 15px;
}

.sidebar.is-collapsed .collapse-btn:hover {
  left: -10px;
  color: #fff;
  background: rgba(37, 99, 235, 0.92);
  border-color: rgba(96, 165, 250, 0.55);
  box-shadow:
      -5px 0 18px rgba(37, 99, 235, 0.18),
      0 5px 16px rgba(0, 0, 0, 0.32);
}

/* =====================================================
   HEADER
   ===================================================== */

.header {
  position: relative;

  flex: 0 0 auto;

  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 10px;

  min-height: 72px;

  padding: 14px 15px;

  border-bottom:
      1px solid
      rgba(255, 255, 255, 0.075);

  background:
      rgba(15, 23, 42, 0.72);
}


.brand {
  min-width: 0;

  display: flex;
  align-items: center;

  gap: 11px;
}


.logo {
  width: 42px;
  height: 42px;

  flex: 0 0 42px;

  display: grid;
  place-items: center;

  border-radius: 12px;

  background:
      linear-gradient(
          135deg,
          #2563eb,
          #4f46e5
      );

  color: #fff;

  box-shadow:
      0 5px 14px
      rgba(37, 99, 235, 0.25),

      inset 0 1px 0
      rgba(255, 255, 255, 0.16);
}


.logo-icon {
  width: 21px;
  height: 21px;

  stroke-width: 1.9;
}


.title {
  min-width: 0;

  overflow: hidden;
}


.app-name {
  color: #f8fafc;

  font-size: 14.5px;

  line-height: 1.5;

  font-weight: 750;

  white-space: nowrap;

  overflow: hidden;

  text-overflow: ellipsis;
}


.user {
  margin-top: 2px;

  color: #94a3b8;

  font-size: 11.5px;

  line-height: 1.5;

  white-space: nowrap;

  overflow: hidden;

  text-overflow: ellipsis;
}


/* =====================================================
   HEADER BUTTON
   ===================================================== */

.icon-btn {
  display: inline-flex;

  align-items: center;
  justify-content: center;

  width: 36px;
  height: 36px;

  flex: 0 0 36px;

  padding: 0;

  border:
      1px solid
      rgba(255, 255, 255, 0.08);

  border-radius: 10px;

  background:
      rgba(255, 255, 255, 0.04);

  color: #cbd5e1;

  cursor: pointer;

  transition:
      background 0.18s,
      color 0.18s,
      border-color 0.18s;
}


.icon-btn svg {
  width: 17px;
  height: 17px;
}


.icon-btn:hover {
  color: #fff;

  background:
      rgba(255, 255, 255, 0.09);

  border-color:
      rgba(255, 255, 255, 0.14);
}


.close-btn {
  display: none;
}


/* =====================================================
   SEARCH
   ===================================================== */

.search-container {
  flex: 0 0 auto;

  padding:
      12px
      14px
      11px;

  border-bottom:
      1px solid
      rgba(255, 255, 255, 0.06);
}


.search-box {
  position: relative;

  display: flex;

  align-items: center;
}


.search-icon {
  position: absolute;

  right: 11px;

  width: 17px;
  height: 17px;

  color: #94a3b8;

  opacity: 0.72;

  pointer-events: none;
}


.search-input {
  width: 100%;

  height: 40px;

  box-sizing: border-box;

  padding:
      8px
      38px
      8px
      34px;

  border:
      1px solid
      rgba(148, 163, 184, 0.13);

  border-radius: 10px;

  outline: none;

  background:
      rgba(255, 255, 255, 0.055);

  color: #f1f5f9;

  font-family: inherit;

  font-size: 13px;

  transition:
      background 0.18s,
      border-color 0.18s,
      box-shadow 0.18s;
}


.search-input::placeholder {
  color: #64748b;
}


.search-input:hover {
  background:
      rgba(255, 255, 255, 0.07);
}


.search-input:focus {
  background:
      rgba(255, 255, 255, 0.075);

  border-color:
      rgba(96, 165, 250, 0.45);

  box-shadow:
      0 0 0 3px
      rgba(37, 99, 235, 0.11);
}


.clear-btn {
  position: absolute;

  left: 7px;

  display: inline-flex;

  align-items: center;
  justify-content: center;

  width: 26px;
  height: 26px;

  padding: 0;

  border: 0;

  border-radius: 7px;

  background: transparent;

  color: #64748b;

  cursor: pointer;
}


.clear-btn:hover {
  background:
      rgba(255, 255, 255, 0.08);

  color: #e2e8f0;
}


.clear-icon {
  width: 14px;
  height: 14px;
}


/* =====================================================
   NAV
   ===================================================== */

.nav {
  flex: 1 1 auto;

  min-height: 0;

  padding:
      12px
      10px
      14px;

  /*
   * overflow-y برای اسکرول عمودی لازم است.
   *
   * overflow-x را visible می‌گذاریم تا Popover
   * آیتم‌های collapsed از Sidebar بیرون بزند.
   */
  overflow-y: auto;
  overflow-x: visible;

  scrollbar-width: thin;

  scrollbar-color:
      rgba(148, 163, 184, 0.2)
      transparent;
}


.nav::-webkit-scrollbar {
  width: 6px;
}


.nav::-webkit-scrollbar-track {
  background: transparent;
}


.nav::-webkit-scrollbar-thumb {
  background:
      rgba(148, 163, 184, 0.18);

  border-radius: 999px;
}


.nav::-webkit-scrollbar-thumb:hover {
  background:
      rgba(148, 163, 184, 0.3);
}


/* =====================================================
   EMPTY / LOADING
   ===================================================== */

.no-results {
  min-height: 190px;

  display: flex;

  flex-direction: column;

  align-items: center;

  justify-content: center;

  padding:
      25px
      20px;

  text-align: center;

  color: #94a3b8;
}


.no-results-icon {
  width: 42px;
  height: 42px;

  margin-bottom: 12px;

  opacity: 0.42;
}


.no-results p {
  margin: 0;

  font-size: 13px;
}


.loading {
  flex: 1;

  display: flex;

  flex-direction: column;

  align-items: center;

  justify-content: center;

  padding:
      40px
      20px;

  color: #94a3b8;

  text-align: center;
}


.loading-icon {
  width: 30px;
  height: 30px;

  margin-bottom: 12px;
}


.loading p {
  margin: 0;

  font-size: 13px;
}


/* =====================================================
   FOOTER
   ===================================================== */

.footer {
  flex: 0 0 auto;

  padding:
      10px
      14px
      12px;

  border-top:
      1px solid
      rgba(255, 255, 255, 0.06);

  background:
      rgba(2, 6, 23, 0.16);

  text-align: center;
}


.version {
  color: #64748b;

  font-size: 10.5px;

  line-height: 1.5;
}


/* =====================================================
   MOBILE
   ===================================================== */

@media (max-width: 1024px) {

  .sidebar {
    width: 286px;

    box-shadow:
        -14px 0 35px
        rgba(2, 6, 23, 0.38);

    transform:
        translateX(100%);
  }


  .sidebar.is-open {
    transform:
        translateX(0);
  }


  .close-btn {
    display: inline-flex;
  }


  .collapse-btn {
    display: none;
  }


  .nav {
    overflow-x: hidden;
  }

}


@media (max-width: 480px) {

  .sidebar {
    width:
        min(88vw, 300px);
  }


  .header {
    min-height: 68px;

    padding: 12px;
  }


  .search-container {
    padding:
        10px
        12px;
  }


  .nav {
    padding-left: 8px;
    padding-right: 8px;
  }

}


/* =====================================================
   ACCESSIBILITY
   ===================================================== */

.icon-btn:focus-visible,
.clear-btn:focus-visible,
.search-input:focus-visible {
  outline:
      2px solid
      rgba(96, 165, 250, 0.75);

  outline-offset: 2px;
}


@media (prefers-reduced-motion: reduce) {

  *,
  *::before,
  *::after {
    transition: none !important;
  }

}

</style>