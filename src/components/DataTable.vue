<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2>Articles Management</h2>
      <div>
        <SyncButton @articles-synced="fetchArticles" />
        <button class="btn btn-success ms-2" @click="openModal()">Add Article</button>
      </div>
    </div>

    <div class="row mb-3">
      <div class="col-md-4">
        <input
          type="text"
          class="form-control"
          placeholder="Search articles..."
          v-model="searchQuery"
        >
      </div>
      <div class="col-md-3">
        <select class="form-select" v-model="selectedCategory">
          <option value="">All Categories</option>
          <option v-for="category in categories" :key="category" :value="category">
            {{ category }}
          </option>
        </select>
      </div>
      <div class="col-md-3">
        <select class="form-select" v-model="sortBy">
          <option value="title">Sort by Title</option>
          <option value="category">Sort by Category</option>
          <option value="date">Sort by Date</option>
        </select>
      </div>
      <div class="col-md-2">
        <select class="form-select" v-model="sortOrder">
          <option value="asc">Ascending</option>
          <option value="desc">Descending</option>
        </select>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-striped table-hover">
        <thead class="table-dark">
          <tr>
            <th>Title</th>
            <th>Content</th>
            <th>Category</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="article in paginatedArticles" :key="article.id">
            <td>{{ article.title }}</td>
            <td>{{ article.content.substring(0, 100) }}...</td>
            <td>
              <span class="badge bg-primary">{{ article.category }}</span>
            </td>
            <td>{{ formatDate(article.date) }}</td>
            <td>
              <button class="btn btn-sm btn-outline-primary me-1" @click="openModal(article)">Edit</button>
              <button class="btn btn-sm btn-outline-danger" @click="deleteArticle(article.id)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <nav aria-label="Page navigation" v-if="totalPages > 1">
      <ul class="pagination justify-content-center">
        <li class="page-item" :class="{ disabled: currentPage === 1 }">
          <a class="page-link" href="#" @click.prevent="currentPage--" v-if="currentPage > 1">Previous</a>
        </li>
        <li class="page-item" v-for="page in visiblePages" :key="page" :class="{ active: page === currentPage }">
          <a class="page-link" href="#" @click.prevent="currentPage = page">{{ page }}</a>
        </li>
        <li class="page-item" :class="{ disabled: currentPage === totalPages }">
          <a class="page-link" href="#" @click.prevent="currentPage++" v-if="currentPage < totalPages">Next</a>
        </li>
      </ul>
    </nav>

    <!-- Modal for Add/Edit Article -->
    <div class="modal fade" id="articleModal" tabindex="-1" aria-labelledby="articleModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="articleModalLabel">{{ isEditing ? 'Edit Article' : 'Add Article' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveArticle">
              <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" class="form-control" v-model="articleForm.title" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Content</label>
                <textarea class="form-control" rows="5" v-model="articleForm.content" required></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label">Category</label>
                <input type="text" class="form-control" v-model="articleForm.category" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Date</label>
                <input type="date" class="form-control" v-model="articleForm.date" required>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">{{ isEditing ? 'Update' : 'Create' }}</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
/* eslint-disable no-undef */
import axios from 'axios'
import SyncButton from './SyncButton.vue'
import { Modal } from 'bootstrap'

export default {
  name: 'DataTable',
  components: {
    SyncButton
  },
  data() {
    return {
      articles: [],
      searchQuery: '',
      selectedCategory: '',
      sortBy: 'title',
      sortOrder: 'asc',
      currentPage: 1,
      itemsPerPage: 10,
      articleForm: {
        title: '',
        content: '',
        category: '',
        date: ''
      },
      isEditing: false,
      editingId: null
    }
  },
  computed: {
    filteredArticles() {
      let filtered = this.articles.filter(article => {
        const matchesSearch = article.title.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                             article.content.toLowerCase().includes(this.searchQuery.toLowerCase())
        const matchesCategory = !this.selectedCategory || article.category === this.selectedCategory
        return matchesSearch && matchesCategory
      })

      filtered.sort((a, b) => {
        let aValue = a[this.sortBy]
        let bValue = b[this.sortBy]

        if (this.sortBy === 'date') {
          aValue = new Date(aValue)
          bValue = new Date(bValue)
        } else {
          aValue = aValue.toLowerCase()
          bValue = bValue.toLowerCase()
        }

        if (this.sortOrder === 'asc') {
          return aValue > bValue ? 1 : -1
        } else {
          return aValue < bValue ? 1 : -1
        }
      })

      return filtered
    },
    paginatedArticles() {
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.filteredArticles.slice(start, end)
    },
    totalPages() {
      return Math.ceil(this.filteredArticles.length / this.itemsPerPage)
    },
    visiblePages() {
      const pages = []
      const start = Math.max(1, this.currentPage - 2)
      const end = Math.min(this.totalPages, this.currentPage + 2)
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
      return pages
    },
    categories() {
      return [...new Set(this.articles.map(article => article.category))]
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
      } catch (error) {
        console.error('Failed to fetch articles:', error)
      }
    },
    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString()
    },
    openModal(article = null) {
      if (article) {
        this.isEditing = true
        this.editingId = article.id
        this.articleForm = { ...article }
        this.articleForm.date = article.date.split('T')[0] // Format for date input
      } else {
        this.isEditing = false
        this.editingId = null
        this.articleForm = {
          title: '',
          content: '',
          category: '',
          date: ''
        }
      }
      // Show modal using Bootstrap
      const modal = new Modal(document.getElementById('articleModal'))
      modal.show()
    },
    async saveArticle() {
      try {
        if (this.isEditing) {
          await axios.put(`http://localhost:8000/api/articles/${this.editingId}`, this.articleForm)
        } else {
          await axios.post('http://localhost:8000/api/articles', this.articleForm)
        }
        this.fetchArticles()
        // Hide modal
        const modal = Modal.getInstance(document.getElementById('articleModal'))
        modal.hide()
      } catch (error) {
        console.error('Failed to save article:', error)
        alert('Failed to save article. Please try again.')
      }
    },
    async deleteArticle(id) {
      if (confirm('Are you sure you want to delete this article?')) {
        try {
          await axios.delete(`http://localhost:8000/api/articles/${id}`)
          this.fetchArticles()
        } catch (error) {
          console.error('Failed to delete article:', error)
          alert('Failed to delete article. Please try again.')
        }
      }
    }
  }
}
</script>

<style scoped>
.table-responsive {
  max-height: 600px;
  overflow-y: auto;
}

.badge {
  font-size: 0.8em;
}

.pagination .page-link {
  color: #007bff;
}

.pagination .page-item.active .page-link {
  background-color: #007bff;
  border-color: #007bff;
}
</style>
