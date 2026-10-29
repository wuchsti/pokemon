<script setup>
import { APP_VERSION } from '../version'
</script>

<template>
  <footer class="site-footer">
    <div class="container footer-inner">
      <span class="footer-brand font-display">PokéVault</span>
      <nav class="footer-links">
        <router-link to="/faq">FAQ</router-link>
        <router-link to="/impressum">Impressum</router-link>
        <router-link to="/datenschutz">Datenschutz</router-link>
      </nav>
      <span class="footer-copy">PokéVault ist ein inoffizielles Fan-Projekt, nicht verbunden mit Nintendo, The Pokémon Company oder Game Freak.</span>
      <span class="footer-version">v{{ APP_VERSION }}</span>
    </div>
  </footer>
</template>

<style scoped>
.site-footer {
  border-top: var(--border-comic);
  background: var(--poke-white);
  margin-top: 40px;
  padding: 20px 0;
}

.footer-inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  text-align: center;
}

.footer-brand {
  font-size: 16px;
  color: var(--poke-ink);
}

.footer-links {
  display: flex;
  gap: 18px;
}

.footer-links a {
  font-weight: 700;
  font-size: 13px;
  color: var(--poke-blue);
}

.footer-copy {
  font-size: 11px;
  color: var(--poke-gray);
  max-width: 480px;
}

.footer-version {
  font-size: 11px;
  font-weight: 700;
  color: var(--poke-gray);
}
</style>
