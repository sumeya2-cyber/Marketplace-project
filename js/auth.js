// Auth functions
function showLogin() {
    document.getElementById('loginModal').style.display = 'block';
}

function showSignup() {
    document.getElementById('signupModal').style.display = 'block';
}

function showAdminLogin() {
    document.getElementById('adminLoginModal').style.display = 'block';
}

// Close modals
document.querySelectorAll('.close').forEach(closeBtn => {
    closeBtn.onclick = function() {
        this.closest('.modal').style.display = 'none';
    }
});

// Handle login
document.getElementById('loginForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = e.target[0].value;
    const password = e.target[1].value;
    
    try {
        const response = await fetch('php/api/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
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
        alert('Error logging in. Please try again.');
    }
});

// Handle signup
document.getElementById('signupForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fullName = e.target[0].value;
    const username = e.target[1].value;
    const email = e.target[2].value;
    const password = e.target[3].value;
    const phone = e.target[4].value;
    
    try {
        const response = await fetch('php/api/register.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ fullName, username, email, password, phone })
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

// Handle admin login
document.getElementById('adminLoginForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = e.target[0].value;
    const password = e.target[1].value;
    
    try {
        const response = await fetch('php/api/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email, password, type: 'admin' })
        });
        
        const result = await response.json();
        
        if (result.success && result.user.user_type === 'admin') {
            localStorage.setItem('admin', JSON.stringify(result.user));
            window.location.href = 'php/api/admin/dashboard.php';
        } else {
            alert('Admin login failed');
        }
    } catch (error) {
        console.error('Admin login error:', error);
        alert('Error logging in as admin');
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
    location.reload();
}

// Check for existing session
const savedUser = localStorage.getItem('user');
if (savedUser) {
    currentUser = JSON.parse(savedUser);
    updateAuthUI();
}