/**
 * JWT Authentication Helper
 */
const Auth = {
    // API base URL
    API_BASE: 'http://localhost/WebProgrammingProject/backend/public',

    /**
     * Store JWT token in localStorage
     */
    setToken(token) {
        localStorage.setItem('jwt_token', token);
    },

    /**
     * Get JWT token from localStorage
     */
    getToken() {
        return localStorage.getItem('jwt_token');
    },

    /**
     * Remove JWT token from localStorage
     */
    removeToken() {
        localStorage.removeItem('jwt_token');
        localStorage.removeItem('user_data');
    },

    /**
     * Store user data in localStorage
     */
    setUser(userData) {
        localStorage.setItem('user_data', JSON.stringify(userData));
    },

    /**
     * Get user data from localStorage
     */
    getUser() {
        const userData = localStorage.getItem('user_data');
        return userData ? JSON.parse(userData) : null;
    },

    /**
     * Check if user is authenticated
     */
    isAuthenticated() {
        return !!this.getToken();
    },

    /**
     * Check if user is admin
     */
    isAdmin() {
        const user = this.getUser();
        return user && user.role === 'admin';
    },

    /**
     * Check if user is librarian or admin
     */
    isLibrarian() {
        const user = this.getUser();
        return user && (user.role === 'admin' || user.role === 'librarian');
    },

    /**
     * Login user
     */
    async login(email, password) {
        try {
            const response = await fetch(`${this.API_BASE}/users/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email, password })
            });

            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON response:', text);
                return { success: false, error: 'Server error: Expected JSON response but got: ' + text.substring(0, 100) };
            }

            const data = await response.json();

            if (data.success && data.token) {
                this.setToken(data.token);
                this.setUser(data.data);
                return { success: true, user: data.data };
            } else {
                return { success: false, error: data.error || 'Login failed' };
            }
        } catch (error) {
            console.error('Login error:', error);
            return { success: false, error: 'Network error: ' + error.message };
        }
    },

    /**
     * Register new user
     */
    async register(userData) {
        try {
            const response = await fetch(`${this.API_BASE}/users/register`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(userData)
            });

            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON response:', text);
                return { success: false, error: 'Server error: Expected JSON response but got: ' + text.substring(0, 100) };
            }

            const data = await response.json();

            if (data.success && data.token) {
                this.setToken(data.token);
                this.setUser(data.data);
                return { success: true, user: data.data };
            } else {
                return { success: false, error: data.error || 'Registration failed' };
            }
        } catch (error) {
            console.error('Registration error:', error);
            return { success: false, error: 'Network error: ' + error.message };
        }
    },

    /**
     * Logout user
     */
    async logout() {
        try {
            const token = this.getToken();
            if (token) {
                await fetch(`${this.API_BASE}/users/logout`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                });
            }
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            this.removeToken();
            window.location.hash = '#home';
        }
    },

    /**
     * Make authenticated API request
     */
    async apiRequest(endpoint, options = {}) {
        const token = this.getToken();

        const headers = {
            'Content-Type': 'application/json',
            ...options.headers
        };

        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        try {
            const response = await fetch(`${this.API_BASE}${endpoint}`, {
                ...options,
                headers
            });

            const data = await response.json();

            // If unauthorized, redirect to login
            if (response.status === 401) {
                this.removeToken();
                window.location.hash = '#login';
                return { success: false, error: 'Unauthorized' };
            }

            return data;
        } catch (error) {
            console.error('API request error:', error);
            return { success: false, error: 'Network error' };
        }
    },

    /**
     * Update UI based on authentication status
     */
    updateUI() {
        const isAuth = this.isAuthenticated();
        const user = this.getUser();

        // Show/hide menu items based on authentication
        if (isAuth) {
            $('.auth-required').show();
            $('.guest-only').hide();

            // Show admin-only items
            if (this.isAdmin()) {
                $('.admin-only').show();
            } else {
                $('.admin-only').hide();
            }

            // Show librarian items
            if (this.isLibrarian()) {
                $('.librarian-only').show();
            } else {
                $('.librarian-only').hide();
            }

            // Update user info in navbar
            if (user) {
                $('#user-name').text(user.username || user.email);
            }
        } else {
            $('.auth-required').hide();
            $('.guest-only').show();
            $('.admin-only').hide();
            $('.librarian-only').hide();
        }
    }
};

// Update UI on page load and hash change
$(document).ready(function () {
    Auth.updateUI();
});

$(window).on('hashchange', function () {
    Auth.updateUI();
});
