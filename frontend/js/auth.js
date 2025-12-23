
const Auth = {

    API_BASE: './backend',

    setToken(token) {
        localStorage.setItem('jwt_token', token);
    },

    getToken() {
        return localStorage.getItem('jwt_token');
    },

    removeToken() {
        localStorage.removeItem('jwt_token');
        localStorage.removeItem('user_data');
    },

    setUser(userData) {
        localStorage.setItem('user_data', JSON.stringify(userData));
    },

    getUser() {
        const userData = localStorage.getItem('user_data');
        return userData ? JSON.parse(userData) : null;
    },

    isAuthenticated() {
        return !!this.getToken();
    },

    isAdmin() {
        const user = this.getUser();
        return user && user.role === 'admin';
    },

    isLibrarian() {
        const user = this.getUser();
        return user && (user.role === 'admin' || user.role === 'librarian');
    },

    async login(email, password) {
        try {
            const response = await fetch(`${this.API_BASE}/users/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email, password })
            });

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
                this.updateUI(); // Ensure UI updates immediately
                return { success: true, user: data.data };
            } else {
                return { success: false, error: data.error || 'Login failed' };
            }
        } catch (error) {
            console.error('Login error:', error);
            return { success: false, error: 'Network error: ' + error.message };
        }
    },

    async register(userData) {
        try {
            const response = await fetch(`${this.API_BASE}/users/register`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(userData)
            });

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
                this.updateUI(); // Ensure UI updates immediately
                return { success: true, user: data.data };
            } else {
                return { success: false, error: data.error || 'Registration failed' };
            }
        } catch (error) {
            console.error('Registration error:', error);
            return { success: false, error: 'Network error: ' + error.message };
        }
    },

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
            this.updateUI(); // Update UI after logout
        }
    },

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

            if (response.status === 401) {
                this.removeToken();
                window.location.hash = '#login';
                this.updateUI();
                return { success: false, error: 'Unauthorized' };
            }

            return data;
        } catch (error) {
            console.error('API request error:', error);
            return { success: false, error: 'Network error' };
        }
    },

    updateUI() {
        const isAuth = this.isAuthenticated();
        const user = this.getUser();
        console.log('UpdateUI called. Authenticated:', isAuth, 'User:', user);

        if (isAuth) {
            $('.auth-required').show();
            $('.guest-only').hide();

            if (this.isAdmin()) {
                $('.admin-only').show();
            } else {
                $('.admin-only').hide();
            }

            if (this.isLibrarian()) {
                $('.librarian-only').show();
            } else {
                $('.librarian-only').hide();
            }

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


$(document).ready(function () {
    Auth.updateUI();
});

$(window).on('hashchange', function () {
    Auth.updateUI();
});
