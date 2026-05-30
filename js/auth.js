// Auth state
let currentUser = null;

// Modal triggers
function showLogin() { document.getElementById('loginModal').classList.add('open'); }
function showSignup() { document.getElementById('signupModal').classList.add('open'); }
function showAdminLogin() { document.getElementById('adminLoginModal').classList.add('open'); }

// Close modals when clicking the close button
document.querySelectorAll('.close').forEach(closeBtn => {
    closeBtn.onclick = function() {
        this.closest('.modal').classList.remove('open');
    }
});

// Handle User Login
document.getElementById('loginForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = e.target[0].value.trim();
    const password = e.target[1].value;

    if (!validateEmail(email) || password.length === 0) {
        alert('Please enter a valid email and password.');
        return;
    }

    try {
        const response = await fetch('php/api/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password, type: 'user' }),
            credentials: 'same-origin'
        });

        const result = await response.json();

        if (result.success) {
            currentUser = result.user;
            localStorage.setItem('user', JSON.stringify(result.user));
            alert('Login successful!');
            document.getElementById('loginModal').classList.remove('open');
            updateAuthUI();
        } else {
            alert('Login failed: ' + result.message);
        }
    } catch (error) {
        console.error('Login error:', error);
        alert('Error connecting to login server.');
    }
});

// Handle Admin Login
document.getElementById('adminLoginForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = e.target[0].value.trim();
    const password = e.target[1].value;

    if (!validateEmail(email) || password.length === 0) {
        alert('Please enter a valid email and password.');
        return;
    }

    try {
        const response = await fetch('php/api/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ email, password, type: 'admin' }),
              credentials: 'same-origin'
        });

        const result = await response.json();

        if (result.success && result.user && result.user.user_type === 'admin') {
            localStorage.setItem('admin', JSON.stringify(result.user));
            window.location.href = 'php/api/admin/dashboard.php';
        } else {
            alert('Admin login failed: ' + (result.message || 'Invalid credentials'));
        }
    } catch (error) {
        console.error('Admin login error:', error);
        alert('Error connecting to admin login server.');
    }
});

// Handle signup
document.getElementById('signupForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const name = document.getElementById('signupName')?.value.trim();
    const email = document.getElementById('signupEmail')?.value.trim();
    const password = document.getElementById('signupPassword')?.value;

    if (!name || !validateEmail(email) || password.length < 6) {
        alert('Please enter your name, a valid email, and a password with at least 6 characters.');
        return;
    }

    try {
        const response = await fetch('php/api/signup.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, email, password }),
                credentials: 'same-origin'
        });

        const result = await response.json();

        if (result.success) {
            alert(result.message || 'Registration successful. Please login.');
            document.getElementById('signupModal').classList.remove('open');
            showLogin();
        } else {
            alert('Registration failed: ' + result.message);
        }
    } catch (error) {
        console.error('Signup error:', error);
        alert('Error registering. Please try again.');
    }
});

function validateEmail(email) {
    return /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email);
}

// Update UI based on auth state
function updateAuthUI() {
    const authButtons = document.querySelector('.auth-buttons');
    if (!authButtons) return;
    if (currentUser) {
        authButtons.innerHTML = `
            <button onclick="showOrders()" class="btn-orders"><i class="fas fa-shopping-bag"></i> My Orders</button>
            <span>Welcome, ${escapeHtml(currentUser.username)}</span>
            <button onclick="logout()" class="btn-logout">Logout</button>
        `;
    }
}

// Logout function
function logout() {
    currentUser = null;
    localStorage.removeItem('user');
    localStorage.removeItem('admin');
    location.reload();
}

// Check for existing session on page load
const savedUser = localStorage.getItem('user');
if (savedUser) {
    currentUser = JSON.parse(savedUser);
    updateAuthUI();
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
