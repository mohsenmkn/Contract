<template>
  <div class="forbidden-page">
    <h1>403</h1>
    <p>شما دسترسی لازم برای مشاهده این صفحه را ندارید.</p>
    <router-link to="/dashboard">بازگشت به داشبورد</router-link>
  </div>
</template>

<script setup>
</script>

<style scoped>
.forbidden-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
  text-align: center;
}
</style>
