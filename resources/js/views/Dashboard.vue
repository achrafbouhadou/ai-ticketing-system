<template>
  <section class="dashboard">
    <h2 class="dashboard__title">Overview</h2>

    <div v-if="loading" class="dashboard__loading">Loading…</div>

    <div v-else>
      <div class="dashboard__cards">
        <div class="dashboard__card card">
          <h3 class="dashboard__card-title">By Status</h3>
          <ul class="dashboard__list">
            <li v-for="(v,k) in stats.status_counts" :key="'s_'+k">
              <span class="dashboard__key">{{ labelStatus(k) }}</span>
              <span class="dashboard__value">{{ v }}</span>
            </li>
          </ul>
        </div>

        <div class="dashboard__card card">
          <h3 class="dashboard__card-title">By Category</h3>
          <ul class="dashboard__list">
            <li v-for="(v,k) in stats.category_counts" :key="'c_'+k">
              <span class="dashboard__key">{{ labelCategory(k) }}</span>
              <span class="dashboard__value">{{ v }}</span>
            </li>
          </ul>
        </div>
      </div>

      <div class="dashboard__chart card">
        <canvas ref="chartEl" aria-label="Tickets by category bar chart"></canvas>
      </div>
    </div>
  </section>
</template>

<script>
import api from '../services/api';
import {
  Chart, BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend,
} from 'chart.js';

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend);

export default {
  name: 'Dashboard',
  data() {
    return {
      loading: true,
      stats: { status_counts: {}, category_counts: {} },
      chart: null,
    };
  },
  async created() {
    await this.load();
  },
  beforeUnmount() {
    if (this.chart) this.chart.destroy();
  },
  methods: {
    async load() {
      this.loading = true;
      try {
        const { data } = await api.get('/stats');
        this.stats = data;
      } finally {
        this.loading = false;
        this.$nextTick(() => this.renderChart());
      }
    },
    renderChart() {
      if (this.chart) this.chart.destroy();

      if (!this.$refs.chartEl) {
        console.warn('Canvas element not found');
        return;
      }

      const ctx = this.$refs.chartEl.getContext('2d');
      const entries = Object.entries(this.stats.category_counts || {});
      const labels = entries.map(([k]) => this.labelCategory(k));
      const values = entries.map(([, v]) => v);

      const css = getComputedStyle(document.documentElement);
      const textColor = (css.getPropertyValue('--fg') || '#111827').trim();
      const gridColor = (css.getPropertyValue('--line') || '#e5e7eb').trim();
      const brand = (css.getPropertyValue('--brand') || '#4f46e5').trim();

      this.chart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels,
          datasets: [
            { label: 'Tickets by Category', data: values, backgroundColor: brand, borderColor: brand },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: true, labels: { color: textColor } } },
          scales: {
            x: { ticks: { color: textColor }, grid: { color: gridColor } },
            y: { beginAtZero: true, ticks: { precision: 0, color: textColor }, grid: { color: gridColor } },
          },
        },
      });
    },
    labelStatus(k) {
      const map = { open: 'Open', in_progress: 'In progress', resolved: 'Resolved' };
      return map[k] ?? k;
    },
    labelCategory(k) {
      const map = {
        billing: 'Billing',
        technical: 'Technical',
        account: 'Account',
        other: 'Other',
        unclassified: 'Unclassified',
      };
      return map[k] ?? k;
    },
  },
};
</script>

<style>
.dashboard__title{margin:0 0 12px 0}
.dashboard__loading{color:var(--muted)}

.dashboard__cards{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px}
.dashboard__card-title{margin:0 0 8px 0}

.dashboard__list{list-style:none;margin:0;padding:0}
.dashboard__list li{display:flex;justify-content:space-between;padding:6px 0;border-top:1px solid var(--line)}
.dashboard__list li:first-child{border-top:none}
.dashboard__key{color:var(--muted)}
.dashboard__value{font-weight:700}

.dashboard__chart{height:320px;padding:12px}
</style>
