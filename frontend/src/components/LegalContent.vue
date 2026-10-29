<script setup>
import { computed } from 'vue'

// Renders admin-editable plain text with a tiny convention: a line
// starting with "## " becomes a heading, blank lines separate paragraphs,
// single newlines within a paragraph become <br>. Deliberately not HTML/
// Markdown so the admin content editor can stay a plain textarea.
const props = defineProps({
  text: { type: String, required: true },
})

const blocks = computed(() => {
  return props.text
    .split(/\n\s*\n/)
    .map((block) => block.trim())
    .filter(Boolean)
    .map((block) => {
      if (block.startsWith('## ')) {
        return { type: 'heading', text: block.slice(3).trim() }
      }
      return { type: 'paragraph', lines: block.split('\n') }
    })
})
</script>

<template>
  <div class="legal-content">
    <template v-for="(block, index) in blocks" :key="index">
      <h2 v-if="block.type === 'heading'" class="font-display">{{ block.text }}</h2>
      <p v-else>
        <template v-for="(line, lineIndex) in block.lines" :key="lineIndex">
          {{ line }}<br v-if="lineIndex < block.lines.length - 1" />
        </template>
      </p>
    </template>
  </div>
</template>

<style scoped>
.legal-content h2 {
  font-size: 16px;
  margin: 20px 0 8px;
}

.legal-content h2:first-child {
  margin-top: 0;
}

.legal-content p {
  color: var(--poke-ink);
  font-weight: 600;
  font-size: 14px;
  line-height: 1.6;
  margin: 0 0 12px;
}
</style>
