import { defineStore } from 'pinia'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('auth_token') || null,
        loading: false,
        error: null,
        initialized: false
    }),
    getters: {
        isLoggedIn: (state) => !!state.user && !!state.token,
        isAdmin: (state) => state.user?.role === 'admin' || state.user?.role === 'staff',
        currentUser: (state) => state.user
    },
    actions: {
        async fetchUser() {
            if (!this.token) {
                this.initialized = true
                return
            }
            this.loading = true
            try {
                const response = await api.get('/user')
                this.user = response.data
            } catch (error) {
                if (error.response?.status === 401) {
                    this.clearAuth()
                } else {
                    console.error('Fetch user failed', error)
                }
            } finally {
                this.loading = false
                this.initialized = true
            }
        },
        async login(credentials) {
            this.loading = true
            this.error = null
            try {
                // Token-based login (no CSRF needed for cross-domain)
                const response = await api.post('/login', credentials)

                // Store token
                this.token = response.data.access_token
                localStorage.setItem('auth_token', this.token)

                // Fetch user info
                await this.fetchUser()
                return true
            } catch (error) {
                this.error = error.response?.data?.message || 'Login failed'
                throw error
            } finally {
                this.loading = false
            }
        },
        async register(userData) {
            this.loading = true
            this.error = null
            try {
                const response = await api.post('/register', userData)

                // Store token from registration
                this.token = response.data.access_token
                localStorage.setItem('auth_token', this.token)
                this.user = response.data.user

                return true
            } catch (error) {
                this.error = error.response?.data?.message || 'Registration failed'
                throw error
            } finally {
                this.loading = false
            }
        },
        async logout() {
            try {
                await api.post('/logout')
            } catch (e) {
                // Ignore logout errors
            }
            this.clearAuth()
        },
        clearAuth() {
            this.user = null
            this.token = null
            localStorage.removeItem('auth_token')
        },
        async updateProfile(data) {
            const response = await api.put('/profile', data)
            this.user = response.data.user || response.data
            return response
        },
        async refreshBalance() {
            if (!this.token || !this.user) return
            try {
                const response = await api.get('/user/wallet')
                if (this.user) {
                    this.user.balance = response.data.balance
                }
            } catch (error) {
                console.error('Refresh balance failed', error)
            }
        }
    }
})
