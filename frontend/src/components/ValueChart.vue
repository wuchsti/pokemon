<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Chart } from 'chart.js/auto'

// Line chart: Vault- vs. Tausch-Binder-Wert über die Zeit.
// Palette (validated via dataviz skill's validate_palette.js, light mode,
// categorical, 2 slots): Vault = Pokémon-Blau, Tausch-Binder = Pokéball-Rot.
// All checks pass (lightness band, chroma floor, CVD + normal-vision
// separation, contrast) - see project chat history for the run.
const COLOR_VAULT = '#3B4CCA'
const COLOR_TRADE = '#CC0000'
const COLOR_GRID = '#e9ecf3'
const COLOR_INK = '#2a2a2a'
const COLOR_SURFACE = '#ffffff'

const props = defineProps({
  series: { type: Array, required: true }, // [{ date, vault, trade_binder }]
})

const canvasEl = ref(null)
const showTable = ref(false)
let chartInstance = null

const hasAnyData = computed(() => props.series.some((d) => d.vault !== null || d.trade_binder !== null))

function formatEuro(value) {
  if (value === null || value === undefined) return '–'
  return new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(value)
}

function formatDateShort(isoDate) {
  return new Date(isoDate).toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit' })
}

// Draws a small filled, white-ringed end-dot + the value, directly on the
// line's last real data point (one per series, never one dot per point).
const endLabelPlugin = {
  id: 'endLabels',
  afterDatasetsDraw(chart) {
    const { ctx } = chart
    chart.data.datasets.forEach((dataset, datasetIndex) => {
      const meta = chart.getDatasetMeta(datasetIndex)
      if (meta.hidden) return

      let lastIndex = -1
      for (let i = dataset.data.length - 1; i >= 0; i--) {
        if (dataset.data[i] !== null && dataset.data[i] !== undefined) {
          lastIndex = i
          break
        }
      }
      if (lastIndex === -1) return

      const point = meta.data[lastIndex]
      if (!point) return

      ctx.save()
      ctx.beginPath()
      ctx.arc(point.x, point.y, 5, 0, Math.PI * 2)
      ctx.fillStyle = dataset.borderColor
      ctx.fill()
      ctx.lineWidth = 2
      ctx.strokeStyle = COLOR_SURFACE
      ctx.stroke()

      ctx.font = '700 12px Nunito, sans-serif'
      ctx.fillStyle = COLOR_INK
      ctx.textBaseline = 'middle'
      const label = formatEuro(dataset.data[lastIndex])
      const textX = point.x + 10
      const align = textX + ctx.measureText(label).width > chart.width - 4 ? 'right' : 'left'
      ctx.textAlign = align
      ctx.fillText(label, align === 'right' ? point.x - 10 : textX, point.y)
      ctx.restore()
    })
  },
}

// Vertical crosshair line under the hovered point, tying the two series'
// tooltips to a shared x-position.
const crosshairPlugin = {
  id: 'crosshair',
  afterDatasetsDraw(chart) {
    const active = chart.getActiveElements()
    if (!active.length) return
    const { ctx, chartArea } = chart
    const x = active[0].element.x
    ctx.save()
    ctx.beginPath()
    ctx.moveTo(x, chartArea.top)
    ctx.lineTo(x, chartArea.bottom)
    ctx.lineWidth = 1
    ctx.strokeStyle = COLOR_GRID
    ctx.setLineDash([4, 4])
    ctx.stroke()
    ctx.restore()
  },
}

function buildChart() {
  if (!canvasEl.value) return

  const labels = props.series.map((d) => d.date)
  const vaultData = props.series.map((d) => d.vault)
  const tradeData = props.series.map((d) => d.trade_binder)

  chartInstance = new Chart(canvasEl.value, {
    type: 'line',
    data: {
      labels,
      datasets: [
        {
          label: 'Vault',
          data: vaultData,
          borderColor: COLOR_VAULT,
          backgroundColor: 'rgba(59, 76, 202, 0.1)',
          fill: true,
          borderWidth: 2,
          pointRadius: 0,
          pointHoverRadius: 6,
          pointHoverBackgroundColor: COLOR_VAULT,
          pointHoverBorderColor: COLOR_SURFACE,
          pointHoverBorderWidth: 2,
          tension: 0.25,
          spanGaps: false,
        },
        {
          label: 'Tausch-Binder',
          data: tradeData,
          borderColor: COLOR_TRADE,
          backgroundColor: 'rgba(204, 0, 0, 0.1)',
          fill: true,
          borderWidth: 2,
          pointRadius: 0,
          pointHoverRadius: 6,
          pointHoverBackgroundColor: COLOR_TRADE,
          pointHoverBorderColor: COLOR_SURFACE,
          pointHoverBorderWidth: 2,
          tension: 0.25,
          spanGaps: false,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      layout: { padding: { right: 56 } },
      scales: {
        x: {
          grid: { display: false },
          ticks: {
            color: COLOR_INK,
            font: { family: 'Nunito', weight: '600', size: 11 },
            maxRotation: 0,
            autoSkip: true,
            callback: (value, index) => formatDateShort(labels[index]),
          },
        },
        y: {
          grid: { color: COLOR_GRID, lineWidth: 1 },
          border: { display: false },
          ticks: {
            color: COLOR_INK,
            font: { family: 'Nunito', size: 11 },
            callback: (value) => formatEuro(value),
          },
        },
      },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: COLOR_INK,
          titleFont: { family: 'Fredoka', weight: '600' },
          bodyFont: { family: 'Nunito' },
          padding: 10,
          cornerRadius: 10,
          callbacks: {
            title: (items) => formatDateShort(items[0].label),
            label: (item) => `${item.dataset.label}: ${formatEuro(item.parsed.y)}`,
          },
        },
      },
    },
    plugins: [crosshairPlugin, endLabelPlugin],
  })
}

function destroyChart() {
  chartInstance?.destroy()
  chartInstance = null
}

onMounted(() => {
  if (!showTable.value) buildChart()
})

watch(
  () => props.series,
  () => {
    destroyChart()
    if (!showTable.value) buildChart()
  }
)

watch(showTable, (value) => {
  if (value) {
    destroyChart()
  } else {
    buildChart()
  }
})

onBeforeUnmount(destroyChart)
</script>

<template>
  <div class="value-chart">
    <div class="value-chart-head">
      <div class="value-chart-legend">
        <span class="legend-item"><span class="legend-dot" style="background: #3b4cca"></span>Vault</span>
        <span class="legend-item"><span class="legend-dot" style="background: #cc0000"></span>Tausch-Binder</span>
      </div>
      <button class="btn btn-outline btn-sm" @click="showTable = !showTable">
        {{ showTable ? 'Als Diagramm anzeigen' : 'Als Tabelle anzeigen' }}
      </button>
    </div>

    <p v-if="!hasAnyData" class="value-chart-empty">
      Noch keine Preis-Historie vorhanden. Der tägliche Preis-Sync befüllt diesen Verlauf automatisch.
    </p>

    <div v-else-if="showTable" class="value-table-wrap">
      <table class="value-table">
        <thead>
          <tr>
            <th>Datum</th>
            <th>Vault</th>
            <th>Tausch-Binder</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="day in series" :key="day.date">
            <td>{{ formatDateShort(day.date) }}</td>
            <td>{{ formatEuro(day.vault) }}</td>
            <td>{{ formatEuro(day.trade_binder) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else class="value-chart-canvas-wrap">
      <canvas ref="canvasEl"></canvas>
    </div>
  </div>
</template>

<style scoped>
.value-chart-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 14px;
  flex-wrap: wrap;
  gap: 10px;
}

.value-chart-legend {
  display: flex;
  gap: 16px;
}

.legend-item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-weight: 700;
  font-size: 13px;
  color: var(--poke-ink);
}

.legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  display: inline-block;
}

.value-chart-canvas-wrap {
  height: 280px;
  position: relative;
}

.value-chart-empty {
  color: var(--poke-gray);
  font-weight: 600;
}

.value-table-wrap {
  max-height: 280px;
  overflow-y: auto;
  border: var(--border-comic);
  border-radius: var(--radius-sm);
}

.value-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.value-table th,
.value-table td {
  padding: 8px 12px;
  text-align: right;
  font-variant-numeric: tabular-nums;
  border-bottom: 1px solid var(--poke-bg);
}

.value-table th:first-child,
.value-table td:first-child {
  text-align: left;
}

.value-table thead {
  background: var(--poke-bg);
  position: sticky;
  top: 0;
}
</style>
