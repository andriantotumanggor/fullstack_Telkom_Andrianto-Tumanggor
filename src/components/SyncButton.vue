<template>
  <button
    class="btn btn-primary"
    @click="syncArticles"
    :disabled="isSyncing"
  >
    <span v-if="isSyncing" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
    {{ isSyncing ? 'Syncing...' : 'Sync Articles' }}
  </button>
</template>

<script>
import axios from 'axios'

export default {
  name: 'SyncButton',
  data() {
    return {
      isSyncing: false
    }
  },
  methods: {
    async syncArticles() {
      this.isSyncing = true
      try {
        const response = await axios.post('http://localhost:8000/api/articles/sync')
        alert(`Synced ${response.data.synced_count} articles`)
        this.$emit('articles-synced')
      } catch (error) {
        console.error('Sync failed:', error)
        alert('Sync failed. Please try again.')
      } finally {
        this.isSyncing = false
      }
    }
  }
}
</script>

<style scoped>
</style>
