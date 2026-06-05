<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MarketPlace - Buy, Sell, Rent & Find Jobs</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <header>
        <div class="top-bar">
            <div class="logo">
                <i class="fas fa-store"></i>
                <span>MarketPlace</span>
            </div>
            <div class="auth-buttons">
                <button onclick="showOrders()" class="btn-orders"><i class="fas fa-shopping-bag"></i> My Orders</button>
                <button onclick="showAdminLogin()" class="btn-admin"><i class="fas fa-user-shield"></i> Admin</button>
                <button onclick="showLogin()" class="btn-login"><i class="fas fa-sign-in-alt"></i> Sign In</button>
                <button onclick="showSignup()" class="btn-signup"><i class="fas fa-user-plus"></i> Sign Up</button>
            </div>
        </div>
        <div class="banner">
            <div class="banner-content">
                <h1>Welcome to MarketPlace</h1>
                <p>Buy, Sell, Rent Properties | Shop Products | Find Jobs & Contracts</p>
            </div>
        </div>
        <nav class="main-nav">
            <button class="nav-btn active" data-type="properties">Properties</button>
            <button class="nav-btn" data-type="products">Products</button>
            <button class="nav-btn" data-type="services">Services</button>
            <button class="post-btn" onclick="showPostForm()"><i class="fas fa-plus"></i> Post Ad</button>
        </nav>
    </header>

    <div class="container">
        <aside class="sidebar">
            <h3>Categories</h3>
            <div id="categories-list"></div>
        </aside>

        <main class="content">
            <div id="listings-container"></div>
        </main>
    </div>

    <!-- Login Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Sign In</h2>
            <form id="loginForm">
                <input type="email" placeholder="Email" required>
                <input type="password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>
        </div>
    </div>

    <!-- Signup Modal -->
    <div id="signupModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Sign Up</h2>
            <form id="signupForm">
                <input id="signupName" name="name" type="text" placeholder="Full Name" required>
                <input id="signupEmail" name="email" type="email" placeholder="Email" required>
                <input id="signupPassword" name="password" type="password" placeholder="Password" required>
                <button type="submit">Register</button>
            </form>
        </div>
    </div>

    <!-- Post Form Modal -->
    <div id="postModal" class="modal">
        <div class="modal-content small">
            <span class="close">&times;</span>
            <h2 id="postModalTitle">Post New Listing</h2>
            <form id="postForm" enctype="multipart/form-data">
                <div id="dynamicFormFields"></div>
                <button type="submit">Submit for Approval</button>
            </form>
        </div>
    </div>

    <!-- Admin Login Modal -->
    <div id="adminLoginModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Admin Login</h2>
            <form id="adminLoginForm">
                <input type="email" placeholder="Admin Email" required>
                <input type="password" placeholder="Password" required>
                <button type="submit">Login as Admin</button>
            </form>
        </div>
    </div>

    <!-- Orders Modal -->
    <div id="ordersModal" class="modal">
        <div class="modal-content large">
            <span class="close">&times;</span>
            <h2>My Orders</h2>
            <div id="ordersList">Loading...</div>
        </div>
    </div>

    <!-- Order Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content small">
            <span class="close">&times;</span>
            <h2>Place Order</h2>
            <form id="orderForm">
                <input type="hidden" id="orderListingType">
                <input type="hidden" id="orderItemId">
                <label>Item</label>
                <div id="orderItemName" class="readonly-field"></div>
                <label>Price</label>
                <div id="orderItemPrice" class="readonly-field"></div>
                <label for="orderQuantity">Quantity</label>
                <input id="orderQuantity" type="number" min="1" value="1" required>
                <div id="guestOrderFields">
                    <label for="guestName">Your Name</label>
                    <input id="guestName" type="text" placeholder="Name">
                    <label for="guestEmail">Your Email</label>
                    <input id="guestEmail" type="email" placeholder="Email">
                </div>
                <button type="submit">Submit Order</button>
            </form>
        </div>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content small">
            <span class="close">&times;</span>
            <h2>Pay for Order</h2>
            <form id="paymentForm">
                <input type="hidden" id="paymentOrderId">
                <input type="hidden" id="paymentMethodId">
                <div id="paymentOrderLabel" class="readonly-field"></div>
                <label>Choose Payment System</label>
                <div id="paymentMethodCards" class="payment-method-grid">Loading payment methods...</div>
                <div id="selectedPaymentMethodLabel" class="readonly-field" style="display:none;"></div>
                <div class="payment-summary">Select a provider and continue to the payment gateway.</div>
                <button type="submit">Continue to Payment</button>
            </form>
        </div>
    </div>

    <!-- Listing Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content large">
            <span class="close">&times;</span>
            <h2 id="detailsTitle">Listing Details</h2>
            <div id="detailsBody">
                <img id="detailsImage" class="listing-image" src="" alt="Listing image" style="max-height:260px; object-fit:cover; margin-bottom:1rem; display:none; width:100%;" />
                <div id="detailsSummary" style="margin-bottom:1rem;"></div>
                <div id="detailsDescription" style="margin-bottom:1rem; color:#555;"></div>
                <div id="detailsReviewSummary" style="margin:1rem 0; padding:1rem; background:#f7f9fb; border:1px solid #e1e5ea; border-radius:10px;"></div>
                <div id="detailsReviewHistory" class="review-history"></div>
                <button id="detailsReviewButton" class="btn-review btn-action" style="margin-top:1rem;">Leave a Review</button>
            </div>
        </div>
    </div>

    <!-- Refund/Return Modal -->
    <div id="refundModal" class="modal">
        <div class="modal-content small">
            <span class="close">&times;</span>
            <h2>Request Refund / Return</h2>
            <form id="refundForm">
                <input type="hidden" id="refundOrderItemId">
                <label for="refundType">Type</label>
                <select id="refundType">
                    <option value="refund">Refund</option>
                    <option value="return">Return</option>
                </select>
                <label for="refundReason">Reason</label>
                <textarea id="refundReason" rows="4" required placeholder="Explain the issue..."></textarea>
                <button type="submit">Submit Request</button>
            </form>
        </div>
    </div>

    <!-- Review Modal -->
    <div id="reviewModal" class="modal">
        <div class="modal-content small">
            <span class="close">&times;</span>
            <h2>Submit Review</h2>
            <form id="reviewForm">
                <input type="hidden" id="reviewListingType">
                <input type="hidden" id="reviewListingId">
                <input type="hidden" id="reviewRelatedOrderId">
                <label for="reviewRating">Rating (1-5)</label>
                <input id="reviewRating" type="number" min="1" max="5" value="5" required>
                <label for="reviewTitle">Title</label>
                <input id="reviewTitle" type="text">
                <label for="reviewComment">Comment</label>
                <textarea id="reviewComment" rows="4"></textarea>
                <button type="submit">Submit Review</button>
            </form>
            <div id="reviewHistory" class="review-history">Previous reviews will appear here once you select an item.</div>
        </div>
    </div>

    <!-- Delivery Modal -->
    <div id="deliveryModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Delivery History</h2>
            <div id="deliveryHistory">Loading...</div>
        </div>
    </div>

    <script src="js/auth.js?v=6" defer></script>
    <script src="js/main.js?v=6" defer></script>
</body>
</html>