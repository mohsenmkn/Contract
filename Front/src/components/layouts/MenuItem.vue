<template>
  <template v-if="hasAccess(item)">

    <!-- =========================================================
         COLLAPSED - LEVEL 0
         ========================================================= -->
    <template v-if="isCollapsed && depth === 0">

      <!-- LEAF -->
      <RouterLink
          v-if="!hasChildren"
          :to="item.to"
          class="c-item"
          :class="{ active: isActive(item.to) }"
          @mouseenter="showTooltip"
          @mouseleave="hideTooltip"
          @click="handleLeafClick"
      >
        <component
            v-if="item.icon"
            :is="item.icon"
            class="c-icon"
        />

        <span
            v-if="item.badge"
            class="c-badge"
            :class="`badge-${item.badgeColor || 'blue'}`"
        >{{ item.badge }}</span>
      </RouterLink>

      <!-- PARENT -->
      <div
          v-else
          ref="parentEl"
          class="c-parent"
          :class="{
            'has-active': hasActiveChild,
            'is-open': popoverVisible
          }"
          @mouseenter="openPopover"
          @mouseleave="closePopover"
          @click.stop="togglePopover"
      >
        <component
            v-if="item.icon"
            :is="item.icon"
            class="c-icon"
        />

        <span
            v-if="item.badge"
            class="c-badge"
            :class="`badge-${item.badgeColor || 'blue'}`"
        >{{ item.badge }}</span>
      </div>

      <!-- TOOLTIP -->
      <Teleport to="body">
        <Transition name="menu-fade">
          <div
              v-if="tooltipVisible"
              class="c-tooltip-fixed"
              :style="tooltipStyle"
              role="tooltip"
          >
            {{ item.label }}
          </div>
        </Transition>
      </Teleport>

      <!-- POPOVER -->
      <Teleport to="body">
        <Transition name="pop">
          <div
              v-if="popoverVisible"
              class="c-popover"
              :style="popoverStyle"
              @mouseenter="keepPopover"
              @mouseleave="closePopover"
              @click.stop
          >
            <div class="c-popover-title">
              {{ item.label }}
            </div>

            <div class="c-popover-body">
              <MenuItem
                  v-for="child in item.children"
                  :key="child.key"
                  :item="child"
                  :depth="0"
                  :opened-groups="openedGroups"
                  :toggle-group="toggleGroup"
                  :is-active="isActive"
                  :on-navigate="onNavigate"
                  :has-access="hasAccess"
                  :is-collapsed="false"
              />
            </div>
          </div>
        </Transition>
      </Teleport>

    </template>


    <!-- =========================================================
         NORMAL / EXPANDED
         ========================================================= -->
    <template v-else>

      <!-- LEAF -->
      <RouterLink
          v-if="!hasChildren"
          :to="item.to"
          class="menu-item"
          :class="[`level-${depth}`, { active: isActive(item.to) }]"
          :style="{ paddingRight: `${depth * 18 + 14}px` }"
          @click="handleLeafClick"
      >
        <span
            v-if="depth >= 1"
            class="dot"
            :class="`dot-${depth}`"
        ></span>

        <component
            v-if="item.icon"
            :is="item.icon"
            class="item-icon"
        />

        <span class="item-label">{{ item.label }}</span>

        <span
            v-if="item.badge"
            class="item-badge"
            :class="`badge-${item.badgeColor || 'blue'}`"
        >{{ item.badge }}</span>
      </RouterLink>


      <!-- PARENT / GROUP -->
      <div
          v-else
          class="menu-group"
          :class="`group-level-${depth}`"
      >
        <button
            type="button"
            class="group-title"
            :class="[`level-${depth}`, { 'has-active-child': hasActiveChild }]"
            :style="{ paddingRight: `${depth * 18 + 14}px` }"
            @click="toggleGroup(item.key)"
            :aria-expanded="isOpen"
        >
          <div class="group-left">
            <span class="accent" :class="`accent-${depth}`"></span>

            <component
                v-if="item.icon"
                :is="item.icon"
                class="item-icon"
            />

            <span class="item-label">{{ item.label }}</span>

            <span
                v-if="item.badge"
                class="item-badge"
                :class="`badge-${item.badgeColor || 'blue'}`"
            >{{ item.badge }}</span>
          </div>

          <i
              class="pi pi-angle-down chevron"
              :class="{ rotated: isOpen }"
          ></i>
        </button>

        <Transition name="slide-down">
          <div v-if="isOpen" class="group-items">
            <div class="connector" :class="`connector-${depth}`"></div>

            <MenuItem
                v-for="child in item.children"
                :key="child.key"
                :item="child"
                :depth="depth + 1"
                :opened-groups="openedGroups"
                :toggle-group="toggleGroup"
                :is-active="isActive"
                :on-navigate="onNavigate"
                :has-access="hasAccess"
                :is-collapsed="isCollapsed"
            />
          </div>
        </Transition>
      </div>

    </template>

  </template>
</template>


<script setup>
import {
  computed,
  ref,
  onBeforeUnmount
} from 'vue';

const props = defineProps({
  item: {
    type: Object,
    required: true
  },

  depth: {
    type: Number,
    default: 0
  },

  openedGroups: {
    type: Object,
    required: true
  },

  toggleGroup: {
    type: Function,
    required: true
  },

  isActive: {
    type: Function,
    required: true
  },

  onNavigate: {
    type: Function,
    required: true
  },

  hasAccess: {
    type: Function,
    required: true
  },

  isCollapsed: {
    type: Boolean,
    default: false
  }
});


const hasChildren = computed(() => {
  return Array.isArray(props.item.children) &&
      props.item.children.length > 0;
});


const isOpen = computed(() => {
  return Boolean(
      props.openedGroups[props.item.key]
  );
});


const hasActiveChild = computed(() => {

  if (!hasChildren.value) {
    return false;
  }

  const check = (items) => {

    for (const child of items) {

      if (
          child.to &&
          props.isActive(child.to)
      ) {
        return true;
      }

      if (
          child.children &&
          child.children.length &&
          check(child.children)
      ) {
        return true;
      }
    }

    return false;
  };

  return check(props.item.children);
});


/* ============================================================
   FIXED TOOLTIP / POPOVER
   ============================================================ */

const parentEl = ref(null);

const tooltipVisible = ref(false);
const popoverVisible = ref(false);

const tooltipStyle = ref({});
const popoverStyle = ref({});

let closeTimer = null;


const clearCloseTimer = () => {

  if (closeTimer) {
    clearTimeout(closeTimer);
    closeTimer = null;
  }
};


const getRect = (event = null) => {

  if (event?.currentTarget?.getBoundingClientRect) {
    return event.currentTarget.getBoundingClientRect();
  }

  if (parentEl.value?.getBoundingClientRect) {
    return parentEl.value.getBoundingClientRect();
  }

  return null;
};


/* ============================================================
   LEAF TOOLTIP
   ============================================================ */

const showTooltip = (event) => {

  if (hasChildren.value) {
    return;
  }

  const rect = getRect(event);

  if (!rect) {
    return;
  }

  const gap = 10;

  /*
   * Tooltip در سمت چپ Sidebar قرار می‌گیرد.
   */
  const estimatedWidth = 150;

  let left = rect.left - estimatedWidth - gap;

  if (left < 8) {
    left = 8;
  }

  let top =
      rect.top +
      (rect.height / 2);

  tooltipStyle.value = {
    left: `${left}px`,
    top: `${top}px`,
    transform: 'translateY(-50%)'
  };

  tooltipVisible.value = true;
};


const hideTooltip = () => {
  tooltipVisible.value = false;
};


/* ============================================================
   PARENT POPOVER
   ============================================================ */

const calculatePopoverPosition = (event = null) => {

  const rect = getRect(event);

  if (!rect) {
    return;
  }

  const width = 280;
  const gap = 8;
  const viewportPadding = 8;

  /*
   * Sidebar در سمت راست است؛
   * Popover در سمت چپ آیکون باز می‌شود.
   */
  let left =
      rect.left -
      width -
      gap;

  if (left < viewportPadding) {
    left = viewportPadding;
  }

  /*
   * ارتفاع واقعی بعد از render مشخص می‌شود.
   * فعلاً با یک مقدار محافظه‌کارانه جایگذاری می‌کنیم.
   */
  const estimatedHeight = 360;

  let top = rect.top;

  const maxTop =
      window.innerHeight -
      estimatedHeight -
      viewportPadding;

  if (top > maxTop) {
    top = Math.max(
        viewportPadding,
        maxTop
    );
  }

  if (top < viewportPadding) {
    top = viewportPadding;
  }

  popoverStyle.value = {
    left: `${left}px`,
    top: `${top}px`,
    width: `${width}px`
  };
};


const openPopover = (event) => {

  if (!hasChildren.value) {
    return;
  }

  clearCloseTimer();

  calculatePopoverPosition(event);

  popoverVisible.value = true;
};


const keepPopover = () => {

  clearCloseTimer();

  popoverVisible.value = true;
};


const closePopover = () => {

  clearCloseTimer();

  closeTimer = setTimeout(() => {

    popoverVisible.value = false;
    closeTimer = null;

  }, 300);
};


const togglePopover = (event) => {

  if (!hasChildren.value) {
    return;
  }

  clearCloseTimer();

  if (popoverVisible.value) {
    popoverVisible.value = false;
    return;
  }

  calculatePopoverPosition(event);

  popoverVisible.value = true;
};


/* ============================================================
   NAVIGATION
   ============================================================ */

const handleLeafClick = () => {
  props.onNavigate();
};


/* ============================================================
   CLEANUP
   ============================================================ */

onBeforeUnmount(() => {
  clearCloseTimer();
});
</script>


<style scoped>
/* ============================================================
   COLLAPSED ITEMS
   ============================================================ */

.c-item,
.c-parent {
  position: relative;

  display: flex;
  align-items: center;
  justify-content: center;

  width: 46px;
  height: 46px;

  margin: 3px auto;

  border-radius: 12px;

  cursor: pointer;
  text-decoration: none;

  transition:
      background 0.2s,
      transform 0.2s,
      box-shadow 0.2s;
}


.c-item:hover,
.c-parent:hover {
  background: rgba(255, 255, 255, 0.08);
  transform: scale(1.06);
}


.c-item.active {
  background:
      linear-gradient(
          135deg,
          rgba(37, 99, 235, 0.22),
          rgba(37, 99, 235, 0.38)
      );

  box-shadow:
      inset 0 0 0 1px
      rgba(96, 165, 250, 0.30),

      0 4px 12px
      rgba(37, 99, 235, 0.25);
}


.c-parent.has-active {
  background:
      rgba(37, 99, 235, 0.12);
}


.c-parent.is-open {
  background:
      rgba(37, 99, 235, 0.20);

  box-shadow:
      inset 0 0 0 1px
      rgba(96, 165, 250, 0.22);
}


.c-icon {
  width: 22px;
  height: 22px;

  color: #cbd5e1;

  stroke-width: 1.9;

  transition:
      color 0.2s,
      transform 0.2s;
}


.c-item:hover .c-icon,
.c-parent:hover .c-icon {
  color: #f8fafc;
}


.c-item.active .c-icon {
  color: #60a5fa;
}


.c-parent.is-open .c-icon {
  color: #60a5fa;
  transform: scale(1.05);
}


/* ============================================================
   BADGE
   ============================================================ */

.c-badge {
  position: absolute;

  top: 2px;
  left: 2px;

  min-width: 17px;
  height: 17px;

  padding: 0 4px;

  display: inline-flex;
  align-items: center;
  justify-content: center;

  border-radius: 999px;

  font-size: 9.5px;
  font-weight: 700;

  color: #fff;

  box-shadow:
      0 2px 5px
      rgba(0, 0, 0, 0.3);
}


/* ============================================================
   TELEPORTED TOOLTIP
   ============================================================ */

.c-tooltip-fixed {
  position: fixed;

  z-index: 100000;

  max-width: 220px;

  padding: 7px 11px;

  border-radius: 7px;

  background:
      rgba(15, 23, 42, 0.98);

  border:
      1px solid
      rgba(255, 255, 255, 0.12);

  color: #f8fafc;

  font-size: 12px;
  line-height: 1.5;

  white-space: nowrap;

  box-shadow:
      0 8px 25px
      rgba(0, 0, 0, 0.45);

  pointer-events: none;
}


/* ============================================================
   TELEPORTED POPOVER
   ============================================================ */

.c-popover {
  position: fixed;

  z-index: 100001;

  box-sizing: border-box;

  min-width: 280px;
  max-width: 360px;

  max-height: min(520px, calc(100vh - 16px));

  overflow-y: auto;
  overflow-x: hidden;

  direction: rtl;

  background:
      linear-gradient(
          180deg,
          #1e293b,
          #0f172a
      );

  border:
      1px solid
      rgba(255, 255, 255, 0.12);

  border-radius: 12px;

  box-shadow:
      0 15px 45px
      rgba(0, 0, 0, 0.58);

  color: #f1f5f9;

  scrollbar-width: thin;

  scrollbar-color:
      rgba(148, 163, 184, 0.25)
      transparent;
}


.c-popover-title {
  position: sticky;

  top: 0;

  z-index: 2;

  padding: 11px 16px;

  border-bottom:
      1px solid
      rgba(255, 255, 255, 0.08);

  font-size: 13px;
  font-weight: 650;

  color: #f1f5f9;

  background:
      rgba(30, 41, 59, 0.96);

  backdrop-filter: blur(8px);
}


.c-popover-body {
  padding: 6px 8px;
}


/* ============================================================
   POPOVER ANIMATION
   ============================================================ */

.pop-enter-active,
.pop-leave-active {
  transition:
      opacity 0.16s ease,
      transform 0.16s ease;
}


.pop-enter-from,
.pop-leave-to {
  opacity: 0;

  transform:
      translateX(-6px);
}


.pop-enter-to,
.pop-leave-from {
  opacity: 1;

  transform:
      translateX(0);
}


.menu-fade-enter-active,
.menu-fade-leave-active {
  transition:
      opacity 0.12s ease,
      transform 0.12s ease;
}


.menu-fade-enter-from,
.menu-fade-leave-to {
  opacity: 0;

  transform:
      translateY(-50%)
      translateX(-4px);
}


.menu-fade-enter-to,
.menu-fade-leave-from {
  opacity: 1;

  transform:
      translateY(-50%)
      translateX(0);
}


/* ============================================================
   NORMAL MENU ITEM
   ============================================================ */

.menu-item {
  position: relative;

  box-sizing: border-box;

  width: 100%;
  min-height: 40px;

  border-radius: 9px;

  user-select: none;

  display: flex;
  align-items: center;

  gap: 11px;

  margin: 2px 0;

  padding: 8px 0;

  text-decoration: none;

  color: #cbd5e1;

  transition:
      background 0.18s,
      color 0.18s,
      transform 0.18s,
      box-shadow 0.18s;
}


.menu-item.level-0 {
  font-size: 14px;
  font-weight: 500;
}


.menu-item.level-1 {
  font-size: 13.5px;
  font-weight: 430;
  color: #b8c5d6;
}


.menu-item.level-2,
.menu-item.level-3 {
  font-size: 13px;
  font-weight: 400;
  color: #9fb0c5;
}


.menu-item:hover {
  background:
      rgba(255, 255, 255, 0.065);

  color: #f8fafc;

  transform:
      translateX(-2px);
}


.menu-item.level-1:hover,
.menu-item.level-2:hover {
  background:
      rgba(96, 165, 250, 0.08);
}


.menu-item.active {
  color: #fff;

  background:
      linear-gradient(
          90deg,
          rgba(37, 99, 235, 0.18),
          rgba(37, 99, 235, 0.32)
      );

  box-shadow:
      inset 0 0 0 1px
      rgba(96, 165, 250, 0.22),

      0 2px 8px
      rgba(0, 0, 0, 0.12);
}


.menu-item.active::before {
  content: "";

  position: absolute;

  right: 0;

  top: 6px;
  bottom: 6px;

  width: 3px;

  border-radius:
      3px 0 0 3px;

  background:
      linear-gradient(
          180deg,
          #60a5fa,
          #3b82f6
      );

  box-shadow:
      0 0 8px
      rgba(96, 165, 250, 0.6);
}


.menu-item.active .item-icon {
  color: #60a5fa;
  transform: scale(1.08);
}


/* ============================================================
   DOT
   ============================================================ */

.dot {
  flex: 0 0 auto;
  border-radius: 50%;
}


.dot-1 {
  width: 6px;
  height: 6px;
  background: #60a5fa;
  box-shadow:
      0 0 0 2px
      rgba(96, 165, 250, 0.15);
}


.dot-2 {
  width: 5px;
  height: 5px;
  background: #94a3b8;
}


.dot-3 {
  width: 4px;
  height: 4px;
  background: #64748b;
}


.menu-item:hover .dot,
.menu-item.active .dot {
  transform: scale(1.4);
  background: #60a5fa;
}


/* ============================================================
   ICONS
   ============================================================ */

.item-icon {
  width: 18px;
  height: 18px;

  flex: 0 0 18px;

  opacity: 0.82;

  stroke-width: 1.9;

  transition:
      opacity 0.18s,
      transform 0.18s,
      color 0.18s;
}


.level-0 .item-icon {
  width: 20px;
  height: 20px;
  flex-basis: 20px;
  opacity: 0.95;
}


.level-1 .item-icon {
  width: 17px;
  height: 17px;
  flex-basis: 17px;
  opacity: 0.75;
}


.level-2 .item-icon,
.level-3 .item-icon {
  width: 15px;
  height: 15px;
  flex-basis: 15px;
  opacity: 0.65;
}


.menu-item:hover .item-icon {
  opacity: 1;
}


/* ============================================================
   LABELS
   ============================================================ */

.item-label {
  min-width: 0;
  flex: 1;

  overflow: hidden;

  white-space: nowrap;
  text-overflow: ellipsis;

  line-height: 1.6;
}


.level-0 .item-label {
  font-size: 14.5px;
  font-weight: 550;
}


.level-1 .item-label {
  font-size: 13.5px;
  font-weight: 450;
}


.level-2 .item-label,
.level-3 .item-label {
  font-size: 13px;
  font-weight: 400;
}


/* ============================================================
   BADGE NORMAL
   ============================================================ */

.item-badge {
  flex: 0 0 auto;

  min-width: 20px;
  height: 20px;

  box-sizing: border-box;

  display: inline-flex;
  align-items: center;
  justify-content: center;

  margin-right: auto;

  padding: 0 6px;

  border-radius: 999px;

  font-size: 10.5px;
  line-height: 1;
  font-weight: 700;

  box-shadow:
      0 1px 3px
      rgba(0, 0, 0, 0.2);
}


.badge-red {
  background: #dc2626;
  color: #fff;
}


.badge-blue {
  background: #2563eb;
  color: #fff;
}


.badge-orange {
  background: #d97706;
  color: #fff;
}


.badge-green {
  background: #059669;
  color: #fff;
}


/* ============================================================
   GROUP
   ============================================================ */

.menu-group {
  position: relative;
  margin: 4px 0;
}


.group-title {
  position: relative;

  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 10px;

  width: 100%;
  min-height: 44px;

  border: 0;
  border-radius: 10px;

  padding: 10px 0;

  cursor: pointer;

  color: #cbd5e1;

  transition:
      background 0.2s,
      color 0.2s,
      box-shadow 0.2s;
}


.group-title.level-0 {
  background:
      linear-gradient(
          90deg,
          rgba(37, 99, 235, 0.10),
          rgba(37, 99, 235, 0.04)
      );

  border:
      1px solid
      rgba(96, 165, 250, 0.15);

  font-weight: 600;
}


.group-title.level-1 {
  background:
      rgba(148, 163, 184, 0.05);

  border:
      1px solid
      rgba(148, 163, 184, 0.10);

  font-weight: 550;
}


.group-title.level-2 {
  background:
      rgba(148, 163, 184, 0.03);

  border:
      1px solid
      rgba(148, 163, 184, 0.08);

  font-weight: 500;
}


.group-title:hover {
  background:
      rgba(255, 255, 255, 0.07);

  color: #f8fafc;
}


.group-title.level-0:hover {
  background:
      linear-gradient(
          90deg,
          rgba(37, 99, 235, 0.18),
          rgba(37, 99, 235, 0.08)
      );

  border-color:
      rgba(96, 165, 250, 0.30);
}


.group-title.has-active-child {
  color: #dbeafe;
}


.group-title.has-active-child.level-0 {
  background:
      linear-gradient(
          90deg,
          rgba(37, 99, 235, 0.14),
          rgba(37, 99, 235, 0.05)
      );

  border-color:
      rgba(96, 165, 250, 0.25);
}


/* ACCENT */

.accent {
  position: absolute;

  right: 0;

  top: 8px;
  bottom: 8px;

  width: 3px;

  border-radius:
      3px 0 0 3px;

  transition:
      all 0.2s;
}


.accent-0 {
  background:
      linear-gradient(
          180deg,
          #3b82f6,
          #60a5fa
      );

  box-shadow:
      0 0 6px
      rgba(59, 130, 246, 0.4);
}


.accent-1 {
  background:
      linear-gradient(
          180deg,
          #60a5fa,
          #93c5fd
      );

  opacity: 0.7;
}


.accent-2 {
  background: #94a3b8;
  opacity: 0.5;
}


.group-title:hover .accent {
  opacity: 1;
  transform: scaleY(1.1);
}


/* GROUP LEFT */

.group-left {
  min-width: 0;
  flex: 1;

  display: flex;
  align-items: center;

  gap: 11px;

  padding-right: 8px;
}


.group-title .item-icon {
  width: 19px;
  height: 19px;
  flex-basis: 19px;
  opacity: 0.9;
}


.group-title.level-0 .item-icon {
  width: 21px;
  height: 21px;
  flex-basis: 21px;
  color: #60a5fa;
}


.group-title .item-label {
  font-size: 14px;
  line-height: 1.6;
}


.group-title.level-0 .item-label {
  font-size: 14.5px;
  font-weight: 650;
  color: #f1f5f9;
}


/* CHEVRON */

.chevron {
  font-size: 14px;

  flex: 0 0 18px;

  text-align: center;

  opacity: 0.6;

  color: #94a3b8;

  transition:
      transform 0.25s
      cubic-bezier(0.4, 0, 0.2, 1),

      opacity 0.2s,
      color 0.2s;
}


.group-title:hover .chevron {
  opacity: 1;
  color: #60a5fa;
}


.chevron.rotated {
  transform: rotate(180deg);

  opacity: 1;

  color: #60a5fa;
}


/* CHILDREN */

.group-items {
  position: relative;

  margin-right: 12px;
  margin-top: 4px;

  padding: 4px 0 6px;
}


.connector {
  position: absolute;

  right: 10px;

  top: 0;
  bottom: 8px;

  width: 2px;

  border-radius: 2px;

  pointer-events: none;
}


.connector-0 {
  background:
      linear-gradient(
          180deg,
          rgba(96, 165, 250, 0.35),
          rgba(96, 165, 250, 0.10)
      );
}


.connector-1 {
  background:
      linear-gradient(
          180deg,
          rgba(148, 163, 184, 0.25),
          rgba(148, 163, 184, 0.08)
      );
}


.connector-2 {
  background:
      linear-gradient(
          180deg,
          rgba(148, 163, 184, 0.18),
          rgba(148, 163, 184, 0.05)
      );
}


.group-items > .menu-item::after,
.group-items > .menu-group > .group-title::after {
  content: "";

  position: absolute;

  right: 10px;

  top: 50%;

  width: 10px;
  height: 2px;

  background:
      rgba(148, 163, 184, 0.25);

  transform:
      translateY(-50%);

  pointer-events: none;

  border-radius: 1px;
}


.group-items > .menu-item:hover::after,
.group-items > .menu-item.active::after {
  background:
      rgba(96, 165, 250, 0.5);
}


/* ============================================================
   SLIDE
   ============================================================ */

.slide-down-enter-active {
  overflow: hidden;

  transition:
      opacity 0.25s,
      max-height 0.32s
      cubic-bezier(0.4, 0, 0.2, 1),
      transform 0.25s;
}


.slide-down-leave-active {
  overflow: hidden;

  transition:
      opacity 0.18s,
      max-height 0.22s,
      transform 0.18s;
}


.slide-down-enter-from {
  opacity: 0;
  max-height: 0;
  transform: translateY(-6px);
}


.slide-down-enter-to {
  opacity: 1;
  max-height: 1500px;
  transform: translateY(0);
}


.slide-down-leave-from {
  opacity: 1;
  max-height: 1500px;
  transform: translateY(0);
}


.slide-down-leave-to {
  opacity: 0;
  max-height: 0;
  transform: translateY(-6px);
}


/* ============================================================
   ACCESSIBILITY
   ============================================================ */

.menu-item:focus-visible,
.group-title:focus-visible,
.c-item:focus-visible,
.c-parent:focus-visible {
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
    animation: none !important;
  }

  .menu-item:hover,
  .c-item:hover,
  .c-parent:hover {
    transform: none;
  }
}
</style>
