<template>
  <nav class="admin-nav">
    <router-link to="/admin">Übersicht</router-link>
    <router-link to="/admin/users">Nutzer</router-link>
    <router-link to="/admin/content">Impressum &amp; Datenschutz</router-link>
    <router-link to="/admin/logs">Logs</router-link>
  </nav>
</template>

<style scoped>
.admin-nav {
  display: flex;
  gap: 16px;
  margin-bottom: 24px;
  border-bottom: 2px solid var(--poke-ink);
  padding-bottom: 10px;
}

.admin-nav a {
  font-weight: 700;
  color: var(--poke-gray);
  font-size: 14px;
}

.admin-nav a.router-link-active {
  color: var(--poke-red);
}
</style>
