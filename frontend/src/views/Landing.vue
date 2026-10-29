<script setup></script>

<template>
  <div class="landing">
    <section class="hero">
      <div class="hero-ball"></div>
      <h1 class="font-display hero-title">Deine Pokémon-Sammlung.<br />Endlich organisiert.</h1>
      <p class="hero-subtitle">
        PokéVault verwaltet Sammlung und Tausch-Binder, verfolgt Kartenwerte über die Zeit,
        erkennt Karten per Foto und hilft dir, Tauschpartner für genau die Karten zu finden,
        die dir noch fehlen.
      </p>
      <div class="hero-actions">
        <router-link to="/register" class="btn btn-primary">Kostenlos registrieren</router-link>
        <router-link to="/login" class="btn btn-outline">Anmelden</router-link>
      </div>
    </section>

    <section class="features">
      <h2 class="font-display section-title">Warum PokéVault?</h2>
      <div class="feature-grid">
        <div class="poke-panel feature-card">
          <span class="feature-icon">🗂️</span>
          <h3>Sammlung &amp; Tausch-Binder</h3>
          <p>Trenne deine Sammlung vom Tauschbestand, mit Zustand, Sprache und Grading (PSA, BGS, CGC, AP).</p>
        </div>
        <div class="poke-panel feature-card">
          <span class="feature-icon">📈</span>
          <h3>Wert im Blick</h3>
          <p>Täglicher Cardmarket-Preis-Sync, Gesamtwert getrennt nach Vault/Tauschbinder, Top-5-Schätze und Tagesgewinner/-verlierer.</p>
        </div>
        <div class="poke-panel feature-card">
          <span class="feature-icon">📷</span>
          <h3>Foto statt Tippen</h3>
          <p>Karte fotografieren, Name und Nummer werden automatisch erkannt - direkt mit passenden Treffern zur Auswahl.</p>
        </div>
        <div class="poke-panel feature-card">
          <span class="feature-icon">🤝</span>
          <h3>Tauschen mit Nickname</h3>
          <p>Finde Tauschpartner über deine Wunschliste, chatte zu Ablauf &amp; Versand und bestätigt den Tausch gemeinsam - immer nur per Nickname.</p>
        </div>
      </div>
    </section>

    <section class="preview">
      <h2 class="font-display section-title">So sieht's aus</h2>
      <div class="preview-grid">
        <div class="poke-panel preview-card">
          <p class="preview-label">Dashboard</p>
          <div class="mock-stats">
            <div class="mock-stat">
              <strong>1.248,50 €</strong>
              <span>Vault</span>
            </div>
            <div class="mock-stat mock-stat-accent">
              <strong>312,00 €</strong>
              <span>Tauschbinder</span>
            </div>
          </div>
          <div class="mock-progress">
            <div class="mock-progress-head"><span>Base Set</span><span>84 / 102</span></div>
            <div class="mock-progress-track"><div class="mock-progress-fill" style="width: 82%"></div></div>
          </div>
        </div>

        <div class="poke-panel preview-card">
          <p class="preview-label">Scan-Flow</p>
          <div class="mock-scan">
            <div class="mock-scan-frame">📷</div>
            <p class="mock-scan-text">Erkannt: <strong>Glurak</strong> · #4/102<br />Konfidenz: 91%</p>
            <div class="mock-scan-row">
              <span class="badge badge-trade">Near Mint</span>
              <span class="badge badge-graded">PSA 9</span>
            </div>
          </div>
        </div>

        <div class="poke-panel preview-card">
          <p class="preview-label">Tausch-Chat</p>
          <div class="mock-chat">
            <div class="mock-bubble mock-bubble-theirs">Hättest du Interesse am Tausch gegen mein Pikachu-Promo?</div>
            <div class="mock-bubble mock-bubble-mine">Gerne! Versand per Brief okay?</div>
            <div class="mock-confirm">✅ Von beiden als erfolgreich bestätigt</div>
          </div>
        </div>
      </div>
    </section>

    <section class="cta-banner poke-panel">
      <h2 class="font-display">Bereit, deine Sammlung zu digitalisieren?</h2>
      <router-link to="/register" class="btn btn-accent">Jetzt kostenlos starten</router-link>
    </section>
  </div>
</template>

<style scoped>
.landing {
  display: flex;
  flex-direction: column;
  gap: 56px;
}

.hero {
  text-align: center;
  padding: 40px 0 10px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.hero-ball {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  border: 3px solid var(--poke-ink);
  background: linear-gradient(to bottom, var(--poke-red) 0 50%, var(--poke-white) 50% 100%);
  position: relative;
  box-shadow: var(--shadow-comic);
}

.hero-ball::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 22px;
  height: 22px;
  margin: -11px 0 0 -11px;
  background: var(--poke-white);
  border: 3px solid var(--poke-ink);
  border-radius: 50%;
}

.hero-title {
  font-size: 34px;
  line-height: 1.25;
  max-width: 640px;
}

.hero-subtitle {
  max-width: 560px;
  color: var(--poke-gray);
  font-weight: 600;
  font-size: 16px;
}

.hero-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  justify-content: center;
}

.section-title {
  text-align: center;
  margin-bottom: 24px;
}

.feature-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 18px;
}

.feature-card {
  text-align: center;
}

.feature-icon {
  font-size: 32px;
}

.feature-card h3 {
  font-family: var(--font-display);
  font-size: 16px;
  margin: 8px 0 6px;
}

.feature-card p {
  font-size: 13px;
  color: var(--poke-gray);
  font-weight: 600;
}

.preview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 18px;
}

.preview-card {
  background: var(--poke-white);
}

.preview-label {
  font-family: var(--font-display);
  font-weight: 600;
  margin: 0 0 12px;
  color: var(--poke-blue);
}

.mock-stats {
  display: flex;
  gap: 10px;
  margin-bottom: 14px;
}

.mock-stat {
  flex: 1;
  border: 2px solid var(--poke-ink);
  border-radius: var(--radius-sm);
  padding: 8px;
  text-align: center;
  display: flex;
  flex-direction: column;
}

.mock-stat strong {
  font-family: var(--font-display);
  font-size: 15px;
}

.mock-stat span {
  font-size: 11px;
  color: var(--poke-gray);
}

.mock-stat-accent {
  background: var(--poke-bg);
}

.mock-progress-head {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  font-weight: 700;
  margin-bottom: 4px;
}

.mock-progress-track {
  height: 12px;
  background: #e9ecf3;
  border: 2px solid var(--poke-ink);
  border-radius: 999px;
  overflow: hidden;
}

.mock-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--poke-yellow), var(--poke-red));
}

.mock-scan {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  text-align: center;
}

.mock-scan-frame {
  width: 72px;
  height: 72px;
  border: 2px dashed var(--poke-gray);
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
}

.mock-scan-text {
  font-size: 13px;
  margin: 0;
}

.mock-scan-row {
  display: flex;
  gap: 6px;
}

.mock-chat {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.mock-bubble {
  padding: 8px 12px;
  border-radius: var(--radius-sm);
  border: 2px solid var(--poke-ink);
  font-size: 12px;
  max-width: 85%;
}

.mock-bubble-theirs {
  align-self: flex-start;
  background: var(--poke-white);
}

.mock-bubble-mine {
  align-self: flex-end;
  background: var(--poke-blue);
  color: var(--poke-white);
}

.mock-confirm {
  margin-top: 4px;
  font-size: 12px;
  font-weight: 700;
  color: #1d9a4a;
  text-align: center;
}

.cta-banner {
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
  background: var(--poke-bg);
}

.cta-banner h2 {
  margin: 0;
  font-size: 22px;
}
</style>
