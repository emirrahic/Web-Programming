
if (Auth.isAuthenticated()) {
    console.log('User is logged in');
    const user = Auth.getUser();
    console.log('User data:', user);
} else {
    console.log('User is not logged in');
    // Redirect to login
    window.location.hash = '#login';
}



async function loadBooks() {
    const result = await Auth.apiRequest('/books', {
        method: 'GET'
    });

    if (result.success) {
        console.log('Books:', result.data);

    } else {
        console.error('Error:', result.error);
    }
}



async function createBook(bookData) {
    if (!Auth.isLibrarian()) {
        alert('You need librarian access to create books');
        return;
    }

    const result = await Auth.apiRequest('/books', {
        method: 'POST',
        body: JSON.stringify(bookData)
    });

    if (result.success) {
        alert('Book created successfully!');
        return result.data;
    } else {
        alert('Error: ' + result.error);
        return null;
    }
}



async function borrowBook(bookId) {
    if (!Auth.isAuthenticated()) {
        alert('Please login to borrow books');
        window.location.hash = '#login';
        return;
    }

    const result = await Auth.apiRequest('/loans', {
        method: 'POST',
        body: JSON.stringify({
            book_id: bookId,
            due_date: new Date(Date.now() + 14 * 24 * 60 * 60 * 1000).toISOString().split('T')[0] // 14 days from now
        })
    });

    if (result.success) {
        alert('Book borrowed successfully!');
        return result.data;
    } else {
        alert('Error: ' + result.error);
        return null;
    }
}



async function loadMyLoans() {
    const result = await Auth.apiRequest('/loans/my-loans', {
        method: 'GET'
    });

    if (result.success) {
        console.log('My loans:', result.data);
        displayLoans(result.data);
    } else {
        console.error('Error:', result.error);
    }
}

function displayLoans(loans) {
    const container = $('#loans-container');
    container.empty();

    if (loans.length === 0) {
        container.html('<p>No active loans</p>');
        return;
    }

    loans.forEach(loan => {
        const loanCard = `
            <div class="card mb-3">
                <div class="card-body">
                    <h5>${loan.book_title}</h5>
                    <p>Due date: ${loan.due_date}</p>
                    <p>Status: ${loan.status}</p>
                </div>
            </div>
        `;
        container.append(loanCard);
    });
}



async function updateProfile(userData) {
    const user = Auth.getUser();

    if (!user) {
        alert('Please login first');
        return;
    }

    const result = await Auth.apiRequest(`/users/${user.id}`, {
        method: 'PUT',
        body: JSON.stringify(userData)
    });

    if (result.success) {

        Auth.setUser(result.data);
        alert('Profile updated successfully!');
        return result.data;
    } else {
        alert('Error: ' + result.error);
        return null;
    }
}



async function loadAllUsers() {
    if (!Auth.isAdmin()) {
        alert('Admin access required');
        return;
    }

    const result = await Auth.apiRequest('/users', {
        method: 'GET'
    });

    if (result.success) {
        console.log('All users:', result.data);
        displayUsers(result.data);
    } else {
        console.error('Error:', result.error);
    }
}

function displayUsers(users) {
    const container = $('#users-container');
    container.empty();

    users.forEach(user => {
        const userCard = `
            <div class="card mb-2">
                <div class="card-body">
                    <h6>${user.username} (${user.email})</h6>
                    <p>Role: ${user.role}</p>
                    <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})">Delete</button>
                </div>
            </div>
        `;
        container.append(userCard);
    });
}



async function deleteUser(userId) {
    if (!Auth.isAdmin()) {
        alert('Admin access required');
        return;
    }

    if (!confirm('Are you sure you want to delete this user?')) {
        return;
    }

    const result = await Auth.apiRequest(`/users/${userId}`, {
        method: 'DELETE'
    });

    if (result.success) {
        alert('User deleted successfully!');
        loadAllUsers();
    } else {
        alert('Error: ' + result.error);
    }
}



function updateUIBasedOnRole() {

    $('.member-only, .librarian-only, .admin-only').hide();

    if (Auth.isAuthenticated()) {
        $('.member-only').show();

        if (Auth.isLibrarian()) {
            $('.librarian-only').show();
        }

        if (Auth.isAdmin()) {
            $('.admin-only').show();
        }
    }
}


$(document).ready(function () {
    updateUIBasedOnRole();
});



async function searchBooks(searchTerm, categoryId = null) {
    let endpoint = `/books?search=${encodeURIComponent(searchTerm)}`;

    if (categoryId) {
        endpoint += `&category_id=${categoryId}`;
    }

    const result = await Auth.apiRequest(endpoint, {
        method: 'GET'
    });

    if (result.success) {
        console.log('Search results:', result.data);
        displayBooks(result.data);
    } else {
        console.error('Error:', result.error);
    }
}

function displayBooks(books) {
    const container = $('#books-container');
    container.empty();

    if (books.length === 0) {
        container.html('<p>No books found</p>');
        return;
    }

    books.forEach(book => {
        const bookCard = `
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">${book.title}</h5>
                        <p class="card-text">Author: ${book.author_name}</p>
                        <p class="card-text">Available: ${book.available_copies}/${book.total_copies}</p>
                        ${Auth.isAuthenticated() ? `
                            <button class="btn btn-primary" onclick="borrowBook(${book.id})">Borrow</button>
                        ` : `
                            <a href="#login" class="btn btn-primary">Login to Borrow</a>
                        `}
                        ${Auth.isLibrarian() ? `
                            <button class="btn btn-secondary" onclick="editBook(${book.id})">Edit</button>
                        ` : ''}
                        ${Auth.isAdmin() ? `
                            <button class="btn btn-danger" onclick="deleteBook(${book.id})">Delete</button>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
        container.append(bookCard);
    });
}



function checkTokenExpiration() {
    const token = Auth.getToken();

    if (!token) {
        return false;
    }

    try {

        const payload = JSON.parse(atob(token.split('.')[1]));
        const expirationTime = payload.exp * 1000;

        if (Date.now() >= expirationTime) {
            console.log('Token has expired');
            Auth.removeToken();
            window.location.hash = '#login';
            return false;
        }

        return true;
    } catch (error) {
        console.error('Error checking token:', error);
        return false;
    }
}



$(document).ready(function () {

    if (Auth.isAuthenticated()) {

        loadMyLoans();


        const user = Auth.getUser();
        $('#user-name').text(user.username);
        $('#user-email').text(user.email);
        $('#user-role').text(user.role);
    } else {

        const protectedPages = ['#dashboard', '#profile', '#loans'];
        if (protectedPages.includes(window.location.hash)) {
            window.location.hash = '#login';
        }
    }


    Auth.updateUI();
});
