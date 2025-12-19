<template>
  <div>
    <h2>Articles Dashboard</h2>

    <div class="row mb-4">
      <div class="col-md-4">
        <label class="form-label">Start Date</label>
        <input type="date" class="form-control" v-model="startDate">
      </div>
      <div class="col-md-4">
        <label class="form-label">End Date</label>
        <input type="date" class="form-control" v-model="endDate">
      </div>
      <div class="col-md-4 d-flex align-items-end">
        <button class="btn btn-primary me-2" @click="applyFilters">Apply Filters</button>
        <button class="btn btn-outline-secondary" @click="resetFilters">Reset</button>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h5>Articles by Category</h5>
          </div>
          <div class="card-body">
            <canvas ref="categoryChart"></canvas>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h5>Articles by Date</h5>
          </div>
          <div class="card-body">
            <canvas ref="dateChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="row mt-4">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
            <h5>Summary Statistics</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-3">
                <div class="text-center">
                  <h3>{{ totalArticles }}</h3>
                  <p>Total Articles</p>
                </div>
              </div>
              <div class="col-md-3">
                <div class="text-center">
                  <h3>{{ uniqueCategories }}</h3>
                  <p>Categories</p>
                </div>
              </div>
              <div class="col-md-3">
                <div class="text-center">
                  <h3>{{ articlesThisMonth }}</h3>
                  <p>This Month</p>
                </div>
              </div>
              <div class="col-md-3">
                <div class="text-center">
                  <h3>{{ averageArticlesPerDay }}</h3>
                  <p>Avg per Day</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
/* eslint-disable vue/multi-word-component-names */
import axios from 'axios'
import Chart from 'chart.js/auto'

export default {
  name: 'Dashboard',
  data() {
    return {
      articles: [],
      startDate: '',
      endDate: '',
      categoryChart: null,
      dateChart: null
    }
  },
  computed: {
    filteredArticles() {
      let filtered = this.articles
      if (this.startDate) {
        filtered = filtered.filter(article => new Date(article.date) >= new Date(this.startDate))
      }
      if (this.endDate) {
        filtered = filtered.filter(article => new Date(article.date) <= new Date(this.endDate))
      }
      return filtered
    },
    totalArticles() {
      return this.filteredArticles.length
    },
    uniqueCategories() {
      return new Set(this.filteredArticles.map(article => article.category)).size
    },
    articlesThisMonth() {
      const now = new Date()
      const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1)
      return this.filteredArticles.filter(article => new Date(article.date) >= startOfMonth).length
    },
    averageArticlesPerDay() {
      if (this.filteredArticles.length === 0) return 0
      const dates = this.filteredArticles.map(article => article.date.split('T')[0])
      const uniqueDates = [...new Set(dates)]
      return Math.round(this.filteredArticles.length / uniqueDates.length)
    }
  },
  mounted() {
    this.fetchArticles()
  },
  methods: {
    async fetchArticles() {
      try {
        const response = await axios.get('http://localhost:8000/api/articles')
        this.articles = response.data
        this.renderCharts()
      } catch (error) {
        console.error('Failed to fetch articles:', error)
      }
    },
    applyFilters() {
      this.renderCharts()
    },
    resetFilters() {
      this.startDate = ''
      this.endDate = ''
      this.renderCharts()
    },
    renderCharts() {
      this.renderCategoryChart()
      this.renderDateChart()
    },
    renderCategoryChart() {
      if (this.categoryChart) {
        this.categoryChart.destroy()
      }

      const categoryCounts = {}
      this.filteredArticles.forEach(article => {
        categoryCounts[article.category] = (categoryCounts[article.category] || 0) + 1
      })

      this.categoryChart = new Chart(this.$refs.categoryChart, {
        type: 'pie',
        data: {
          labels: Object.keys(categoryCounts),
          datasets: [{
            data: Object.values(categoryCounts),
            backgroundColor: [
              '#FF6384',
              '#36A2EB',
              '#FFCE56',
              '#4BC0C0',
              '#9966FF',
              '#FF9F40'
            ]
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: {
              position: 'bottom'
            }
          }
        }
      })
    },
    renderDateChart() {
      if (this.dateChart) {
        this.dateChart.destroy()
      }

      const dateCounts = {}
      this.filteredArticles.forEach(article => {
        const date = article.date.split('T')[0]
        dateCounts[date] = (dateCounts[date] || 0) + 1
      })

      const sortedDates = Object.keys(dateCounts).sort()

      this.dateChart = new Chart(this.$refs.dateChart, {
        type: 'bar',
        data: {
          labels: sortedDates,
          datasets: [{
            label: 'Articles',
            data: sortedDates.map(date => dateCounts[date]),
            backgroundColor: '#36A2EB'
          }]
        },
        options: {
          responsive: true,
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      })
    }
  }
}
</script>

<style scoped>
.card {
  margin-bottom: 20px;
}
</style>
