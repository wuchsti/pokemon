<script setup>
import { computed } from 'vue'

const props = defineProps({
  setName: { type: String, required: true },
  owned: { type: Number, required: true },
  total: { type: Number, required: true },
})

const percent = computed(() => {
  if (!props.total) return 0
  return Math.min(100, Math.round((props.owned / props.total) * 100))
})
</script>

<template>
  <div class="set-progress">
    <div class="set-progress-head">
      <span class="font-display set-progress-name">{{ setName }}</span>
      <span class="set-progress-count">{{ owned }} / {{ total }} Karten</span>
    </div>
    <div class="set-progress-track">
      <div class="set-progress-fill" :style="{ width: percent + '%' }">
        <span class="set-progress-ball" v-if="percent > 0 && percent < 100"></span>
      </div>
    </div>
    <div class="set-progress-percent">{{ percent }}%</div>
  </div>
</template>

<style scoped>
.set-progress {
  margin-bottom: 18px;
}

.set-progress-head {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  margin-bottom: 6px;
}

.set-progress-name {
  font-size: 16px;
}

.set-progress-count {
  font-size: 13px;
  font-weight: 700;
  color: var(--poke-gray);
}

.set-progress-track {
  height: 16px;
  background: #e9ecf3;
  border: 2px solid var(--poke-ink);
  border-radius: 999px;
  overflow: hidden;
  position: relative;
}

.set-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--poke-yellow), var(--poke-red));
  border-radius: 999px;
  position: relative;
  transition: width 0.4s ease;
}

.set-progress-ball {
  position: absolute;
  right: -2px;
  top: 50%;
  width: 14px;
  height: 14px;
  transform: translateY(-50%);
  background: var(--poke-white);
  border: 2px solid var(--poke-ink);
  border-radius: 50%;
}

.set-progress-percent {
  margin-top: 4px;
  font-size: 12px;
  font-weight: 700;
  color: var(--poke-blue);
  text-align: right;
}
</style>
