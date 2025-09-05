<template>
  <div class="layout">
    <header class="layout__header">
      <h1 class="layout__brand">Smart Ticket Triage</h1>
      <nav class="layout__nav">
        <router-link to="/tickets">Tickets</router-link>
        <router-link to="/dashboard">Dashboard</router-link>
        <button class="btn btn--icon theme-toggle" @click="toggleTheme" :title="theme === 'dark' ? 'Switch to light' : 'Switch to dark'">
          <span v-if="theme === 'dark'">☀️</span>
          <span v-else>🌙</span>
        </button>
      </nav>
    </header>
    <main class="layout__main">
      <router-view />
    </main>
  </div>
</template>

<script>
export default {
  name: 'AppRoot',
  data() {
    return { theme: 'light' };
  },
  created() {
    const saved = localStorage.getItem('theme');
    if (saved === 'dark' || saved === 'light') this.theme = saved;
    this.applyTheme();
  },
  methods: {
    toggleTheme() {
      this.theme = this.theme === 'dark' ? 'light' : 'dark';
      localStorage.setItem('theme', this.theme);
      this.applyTheme();
    },
    applyTheme() {
      const el = document.documentElement;
      if (this.theme === 'dark') el.setAttribute('data-theme', 'dark');
      else el.removeAttribute('data-theme');
    }
  }
}
</script>
