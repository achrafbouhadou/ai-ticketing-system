<template>
  <section class="dashboard">
    <h2 class="dashboard__title">Overview</h2>

    <div v-if="loading" class="dashboard__loading">Loading…</div>

    <div v-else>
      <div class="dashboard__cards">
        <div class="dashboard__card">
          <h3 class="dashboard__card-title">By Status</h3>
          <ul class="dashboard__list">
            <li v-for="(v,k) in stats.status_counts" :key="'s_'+k">
              <span class="dashboard__key">{{ labelStatus(k) }}</span>
              <span class="dashboard__value">{{ v }}</span>
            </li>
          </ul>
        </div>

        <div class="dashboard__card">
          <h3 class="dashboard__card-title">By Category</h3>
          <ul class="dashboard__list">
            <li v-for="(v,k) in stats.category_counts" :key="'c_'+k">
              <span class="dashboard__key">{{ labelCategory(k) }}</span>
              <span class="dashboard__value">{{ v }}</span>
            </li>
          </ul>
        </div>
      </div>

      <div class="dashboard__chart">
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
        this.$nextTick(() => this.renderChart());
      } finally {
        this.loading = false;
      }
    },
    renderChart() {
      if (this.chart) this.chart.destroy();

      const ctx = this.$refs.chartEl.getContext('2d');
      const entries = Object.entries(this.stats.category_counts || {});
      const labels = entries.map(([k]) => this.labelCategory(k));
      const values = entries.map(([, v]) => v);

      this.chart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels,
          datasets: [
            { label: 'Tickets by Category', data: values },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: true } },
          scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
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
.dashboard__loading{color:#666}

.dashboard__cards{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px}
.dashboard__card{border:1px solid #eee;border-radius:12px;padding:12px;background:#fafafa}
.dashboard__card-title{margin:0 0 8px 0}

.dashboard__list{list-style:none;margin:0;padding:0}
.dashboard__list li{display:flex;justify-content:space-between;padding:6px 0;border-top:1px solid #f0f0f0}
.dashboard__list li:first-child{border-top:none}
.dashboard__key{color:#444}
.dashboard__value{font-weight:700}

.dashboard__chart{height:320px;border:1px solid #eee;border-radius:12px;padding:12px;background:#fff}
</style>
