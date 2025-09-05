<template>
  <div class="layout">
    <header class="layout__header">
      <h1 class="layout__brand">Smart Ticket Triage</h1>
      <nav class="layout__nav">
        <router-link to="/tickets">Tickets</router-link>
      </nav>
    </header>
    <main class="layout__main">
      <router-view />
    </main>
  </div>
</template>

<script>
export default { name: 'AppRoot' }
</script>
