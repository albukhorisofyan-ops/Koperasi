<script setup>
import { RouterView, RouterLink } from 'vue-router'
</script>

<template>
  <div class="app-layout">
    <header class="app-header">
      <div class="header-content">
        <div class="brand">
          <span class="brand-icon">🏪</span>
          <span class="brand-name">Koperasi POS</span>
        </div>
        <nav class="nav-links">
          <RouterLink to="/" class="nav-link" active-class="active">Kasir</RouterLink>
        </nav>
      </div>
    </header>

    <main class="app-main">
      <RouterView />
    </main>
  </div>
</template>

<style>
/* Global reset & typography */
body {
  margin: 0;
  padding: 0;
  background-color: #f8fafc;
  color: #334155;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
}
</style>

<style scoped>
.app-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.app-header {
  background-color: #ffffff;
  border-bottom: 1px solid #e2e8f0;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  position: sticky;
  top: 0;
  z-index: 10;
}

.header-content {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 16px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.brand {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 1.25rem;
  font-weight: 700;
  color: #0f172a;
}

.brand-icon {
  font-size: 1.5rem;
}

.nav-links {
  display: flex;
  gap: 16px;
}

.nav-link {
  text-decoration: none;
  color: #64748b;
  font-size: 0.95rem;
  font-weight: 500;
  padding: 6px 12px;
  border-radius: 6px;
  transition: all 0.2s;
}

.nav-link:hover {
  color: #0f172a;
  background-color: #f1f5f9;
}

.nav-link.active {
  color: #2563eb;
  background-color: #eff6ff;
  font-weight: 600;
}

.app-main {
  flex: 1;
}
</style>
