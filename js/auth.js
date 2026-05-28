// Auth state
let currentUser = null;

// Modal triggers
function showLogin() { document.getElementById('loginModal').style.display = 'block'; }
function showSignup() { document.getElementById('signupModal').style.display = 'block'; }
function showAdminLogin() { document.getElementById('adminLoginModal').style.display = 'block'; }

// Close modals when clicking the close button
document.querySelectorAll('.close').forEach(closeBtn => {
    closeBtn.onclick = function() {
        this.closest('.modal').style.display = 'none';
    }
});

// Handle User Login
document.getElementById('loginForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = e.target[0].value;
    const password = e.target[1].value;
    
    try {
        const response = await fetch('php/api/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password, type: 'user' })
        });
        
        const result = await response.json();
        
        if (result.success) {
            currentUser = result.user;
            localStorage.setItem('user', JSON.stringify(result.user));
            alert('Login successful!');
            document.getElementById('loginModal').style.display = 'none';
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
    const email = e.target[0].value;
    const password = e.target[1].value;
    
    try {
        const response = await fetch('php/api/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password, type: 'admin' })
        });
        
        const result = await response.json();
        
        // Verifies the success status and ensures the user object exists
        if (result.success && result.user && result.user.user_type === 'admin') {
            localStorage.setItem('admin', JSON.stringify(result.user));
            // Redirects to the verified admin path
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
    const formData = {
        fullName: e.target[0].value,
        username: e.target[1].value,
        email: e.target[2].value,
        password: e.target[3].value,
        phone: e.target[4].value
    };
    
    try {
        const response = await fetch('php/api/register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Registration successful! Please login.');
            document.getElementById('signupModal').style.display = 'none';
            showLogin();
        } else {
            alert('Registration failed: ' + result.message);
        }
    } catch (error) {
        console.error('Signup error:', error);
        alert('Error registering. Please try again.');
    }
});

// Update UI based on auth state
function updateAuthUI() {
    const authButtons = document.querySelector('.auth-buttons');
    if (currentUser) {
        authButtons.innerHTML = `
            <span>Welcome, ${currentUser.username}</span>
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