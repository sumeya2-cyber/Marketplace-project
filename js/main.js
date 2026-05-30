let currentListingType = 'properties';
let currentCategoryId = null;
let guestToken = getGuestToken();

// Initialize page
document.addEventListener('DOMContentLoaded', () => {
    setupNavigation();
    switchTab('properties');
    document.getElementById('refundForm')?.addEventListener('submit', handleRefundForm);
    document.getElementById('reviewForm')?.addEventListener('submit', handleReviewForm);
    document.getElementById('orderForm')?.addEventListener('submit', handleOrderForm);
});

function setupNavigation() {
    document.querySelectorAll('.nav-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const selectedType = btn.dataset.type;
            switchTab(selectedType);
        });
    });
}

function switchTab(tabName) {
    const validTypes = ['properties', 'products', 'services'];
    if (!validTypes.includes(tabName)) {
        console.warn('Unknown tab type:', tabName);
        return;
    }

    currentListingType = tabName;
    currentCategoryId = null;

    document.querySelectorAll('.nav-btn').forEach(b => b.classList.toggle('active', b.dataset.type === tabName));
    document.getElementById('categories-list').innerHTML = '<div class="loading">Loading categories...</div>';
    document.getElementById('listings-container').innerHTML = '<div class="loading">Loading content...</div>';

    loadCategories(tabName);
    loadCategoryItems(tabName, null);
}

async function loadCategories(type) {
    try {
        const response = await fetch(`php/api/fetch_categories.php?type=${encodeURIComponent(type)}`);
        if (!response.ok) throw new Error('Network response was not ok');
        const categories = await response.json();

        const container = document.getElementById('categories-list');
        if (!Array.isArray(categories) || categories.length === 0) {
            container.innerHTML = '<p>No categories found.</p>';
            return;
        }

        // render a vertical unordered list for accessibility and styling
        container.innerHTML = '<ul class="category-list">' + categories.map(cat =>
            `<li class="category-list-item" data-id="${cat.id}">${escapeHtml(cat.name)}</li>`
        ).join('') + '</ul>';

        container.querySelectorAll('.category-list-item').forEach(li => {
            li.addEventListener('click', () => {
                const categoryId = li.dataset.id;
                currentCategoryId = categoryId;
                container.querySelectorAll('.category-list-item').forEach(item => item.classList.toggle('active', item === li));
                loadCategoryItems(type, categoryId);
            });
        });
    } catch (error) {
        console.error('Error loading categories:', error);
        document.getElementById('categories-list').innerHTML = '<p>Error loading categories.</p>';
    }
}

// Admin actions: approve/reject — used by admin dashboard scripts
async function approveItem(listingId) {
    if (!confirm('Approve this listing?')) return;
    try {
        const res = await fetch('php/api/approve_item.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ listing_id: listingId })
        });
        const result = await res.json();
        if (result.success) {
            alert('Listing approved. It will appear on the homepage.');
            // refresh both pending table and current listing view
            if (typeof refreshPendingTable === 'function') refreshPendingTable();
            loadCategoryItems(currentListingType, currentCategoryId);
        } else {
            alert('Error: ' + result.message);
        }
    } catch (err) {
        console.error(err);
        alert('Failed to approve listing');
    }
}

async function rejectItem(listingId) {
    if (!confirm('Reject and delete this listing? This cannot be undone.')) return;
    try {
        const res = await fetch('php/api/reject_item.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ listing_id: listingId })
        });
        const result = await res.json();
        if (result.success) {
            alert('Listing rejected and deleted.');
            if (typeof refreshPendingTable === 'function') refreshPendingTable();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (err) {
        console.error(err);
        alert('Failed to reject listing');
    }
}

async function loadCategoryItems(type, categoryId = null) {
    const container = document.getElementById('listings-container');
    container.innerHTML = '<div class="loading"><i class="fas fa-spinner"></i> Loading...</div>';

    try {
        let url = `php/api/fetch_items.php?type=${encodeURIComponent(type)}`;
        if (categoryId) {
            url += `&category=${encodeURIComponent(categoryId)}`;
        }

        const response = await fetch(url);
        if (!response.ok) throw new Error('Network response was not ok');
        const items = await response.json();

        if (!Array.isArray(items) || items.length === 0) {
            const message = categoryId ? 'No listings found in this category.' : 'No listings available yet.';
            container.innerHTML = `<p>${message}</p>`;
            return;
        }

        container.innerHTML = items.map(item => createListingCard(item, type)).join('');
        container.querySelectorAll('.btn-order').forEach(button => {
            button.addEventListener('click', () => {
                let itemType = button.dataset.type;
                const itemId = button.dataset.id;
                const itemName = button.dataset.title;
                const itemPrice = button.dataset.price;
                if (itemType === 'properties') itemType = 'property';
                if (itemType === 'products') itemType = 'product';
                if (itemType === 'services') {
                    alert('Service ordering is not supported yet. Please contact the service provider.');
                    return;
                }
                showOrderModal(itemType, itemId, itemName, itemPrice);
            });
        });
    } catch (error) {
        console.error('Error loading items:', error);
        container.innerHTML = '<p>Error loading listings. Please try again later.</p>';
    }
}

function createListingCard(listing, type) {
    const title = listing.title || listing.product_name || listing.address || listing.category_name || 'Listing';
    const price = listing.price ?? listing.budget ?? 'N/A';
    const location = listing.location || listing.address || listing.city || 'Not specified';
    const description = listing.description || listing.itemdescription || '';
    const brand = listing.brand || listing.brand_name || 'N/A';
    const imagePath = listing.image_path || listing.image || '';
    const itemId = listing.product_id || listing.property_id || listing.id || '';
    const orderButton = `<button class="btn-order" data-type="${escapeHtml(type)}" data-id="${escapeHtml(String(itemId))}" data-title="${escapeHtml(title)}" data-price="${escapeHtml(String(price))}">Order Now</button>`;

    return `
        <div class="listing-card">
            ${imagePath ? `<img src="${escapeHtml(imagePath)}" class="listing-image" alt="${escapeHtml(title)}">` : 
            '<div class="listing-image placeholder"><i class="fas fa-image"></i></div>'}
            <div class="listing-info">
                <div class="listing-title">${escapeHtml(title)}</div>
                <div class="listing-price">${price !== 'N/A' ? '$' + escapeHtml(String(price)) : 'Price not available'}</div>
                <div class="listing-detail"><strong>Location:</strong> ${escapeHtml(location)}</div>
                <div class="listing-detail"><strong>Brand:</strong> ${escapeHtml(brand)}</div>
                <div class="listing-description">${escapeHtml(description).substring(0, 160)}</div>
                <div class="listing-actions">${orderButton}</div>
            </div>
        </div>
    `;
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getGuestToken() {
    let token = localStorage.getItem('marketplaceGuestToken');
    if (!token) {
        token = 'GUEST-' + Math.random().toString(36).substring(2, 12) + '-' + Date.now();
        localStorage.setItem('marketplaceGuestToken', token);
    }
    return token;
}

function showOrderModal(type, itemId, itemName, itemPrice) {
    document.getElementById('orderListingType').value = type;
    document.getElementById('orderItemId').value = itemId;
    document.getElementById('orderItemName').textContent = itemName;
    document.getElementById('orderItemPrice').textContent = itemPrice !== 'N/A' ? '$' + itemPrice : 'N/A';
    document.getElementById('orderQuantity').value = 1;
    document.getElementById('guestName').value = '';
    document.getElementById('guestEmail').value = '';
    document.getElementById('guestOrderFields').style.display = currentUser ? 'none' : 'block';
    document.getElementById('orderModal').classList.add('open');
}

async function handleOrderForm(e) {
    e.preventDefault();
    const listingType = document.getElementById('orderListingType').value;
    const itemId = document.getElementById('orderItemId').value;
    const quantity = parseInt(document.getElementById('orderQuantity').value, 10) || 1;
    const guestName = document.getElementById('guestName').value.trim();
    const guestEmail = document.getElementById('guestEmail').value.trim();

    if (!itemId || !listingType) {
        return alert('Invalid item selected.');
    }
    if (!currentUser && (!guestName || !guestEmail)) {
        return alert('Please enter your name and email.');
    }

    try {
        const response = await fetch('php/api/create_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                listing_type: listingType,
                item_id: itemId,
                quantity,
                guest_name: guestName,
                guest_email: guestEmail,
                guest_token: guestToken
            }),
            credentials: 'same-origin'
        });
        const result = await response.json();
        if (result.success) {
            alert('Order created successfully. Order ID: ' + (result.order_id || 'N/A'));
            document.getElementById('orderModal').classList.remove('open');
            showOrders();
        } else {
            alert('Order failed: ' + result.message);
        }
    } catch (error) {
        console.error('Order error:', error);
        alert('Failed to place order. Please try again.');
    }
}

// Keep compatibility with older code
function filterByCategory(categoryId) {
    currentCategoryId = categoryId;
    loadCategoryItems(currentListingType, categoryId);
}

// Existing posting functionality remains unchanged
function showPostForm() {
    if (!currentUser) {
        alert('Please login first to post a listing');
        showLogin();
        return;
    }

    const modal = document.getElementById('postModal');
    const title = document.getElementById('postModalTitle');
    const formFields = document.getElementById('dynamicFormFields');

    title.textContent = `Post New ${currentListingType.charAt(0).toUpperCase() + currentListingType.slice(1)}`;

    let fieldsHtml = `
        <input type="hidden" id="listingType" value="${currentListingType}">
        <input type="text" id="title" placeholder="Title" required>
        <textarea id="description" placeholder="Description" rows="4" required></textarea>
        <input type="number" id="price" placeholder="${currentListingType === 'services' ? 'Budget' : 'Price'}" required>
        <input type="file" id="image" accept="image/*">
    `;

    if (currentListingType === 'properties') {
        fieldsHtml += `
            <select id="listingTypeSelect">
                <option value="sell">For Sale</option>
                <option value="rent">For Rent</option>
            </select>
            <input type="text" id="location" placeholder="Location">
            <input type="number" id="bedrooms" placeholder="Bedrooms">
            <input type="number" id="bathrooms" placeholder="Bathrooms">
            <input type="number" id="area" placeholder="Area (sq ft)" step="0.01">
        `;
    } else if (currentListingType === 'products') {
        fieldsHtml += `
            <select id="condition">
                <option value="new">New</option>
                <option value="like_new">Like New</option>
                <option value="good">Good</option>
                <option value="fair">Fair</option>
            </select>
            <input type="text" id="brand" placeholder="Brand (optional)">
            <input type="text" id="location" placeholder="Location (optional)">
            <input type="number" id="quantity" placeholder="Quantity" value="1">
        `;
    } else if (currentListingType === 'services') {
        fieldsHtml += `
            <input type="text" id="duration" placeholder="Duration (e.g., 2 weeks)">
            <input type="text" id="location" placeholder="Location">
            <select id="experience">
                <option value="entry">Entry Level</option>
                <option value="intermediate">Intermediate</option>
                <option value="expert">Expert</option>
            </select>
        `;
    }

    fieldsHtml += `<select id="category" required><option value="">Select Category</option></select>`;
    formFields.innerHTML = fieldsHtml;
    loadCategoryDropdown(currentListingType);
    modal.classList.add('open');

    document.getElementById('postForm').onsubmit = (e) => {
        e.preventDefault();
        submitPost();
    };
}

async function loadCategoryDropdown(type) {
    const response = await fetch(`php/api/fetch_categories.php?type=${encodeURIComponent(type)}`);
    const categories = await response.json();
    const select = document.getElementById('category');
    select.innerHTML = '<option value="">Select Category</option>' + categories.map(cat => `<option value="${cat.id}">${escapeHtml(cat.name)}</option>`).join('');
}

async function submitPost() {
    const formData = new FormData();
    const type = document.getElementById('listingType').value;

    formData.append('type', type);
    formData.append('title', document.getElementById('title').value);
    formData.append('itemdescription', document.getElementById('description').value);
    formData.append('price', document.getElementById('price').value);
    formData.append('category_id', document.getElementById('category').value);

    const imageFile = document.getElementById('image').files[0];
    if (imageFile) formData.append('image', imageFile);

    if (type === 'properties') {
        formData.append('listing_type', document.getElementById('listingTypeSelect').value);
        formData.append('location', document.getElementById('location').value);
        formData.append('bedrooms', document.getElementById('bedrooms').value);
        formData.append('bathrooms', document.getElementById('bathrooms').value);
        formData.append('area_sqft', document.getElementById('area').value);
    } else if (type === 'products') {
        formData.append('productcondition', document.getElementById('condition').value);
        formData.append('brand', document.getElementById('brand') ? document.getElementById('brand').value : '');
        formData.append('location', document.getElementById('location') ? document.getElementById('location').value : '');
        formData.append('quantity', document.getElementById('quantity').value);
    } else if (type === 'services') {
        formData.append('duration', document.getElementById('duration').value);
        formData.append('location', document.getElementById('location').value);
        formData.append('experience_level', document.getElementById('experience').value);
    }

    console.log('Form type:', type, 'Form data keys:', Array.from(formData.keys()));

    try {
        // route product posts to our add_item endpoint to make listings 'Pending'
        const postUrl = (type === 'products') ? 'php/api/add_item.php' : `php/api/post_${type}.php`;
        console.log('Submitting to:', postUrl);
        const response = await fetch(postUrl, {
            method: 'POST',
            body: formData
        });

        console.log('Response status:', response.status);
        const result = await response.json();
        console.log('Response result:', result);
        if (result.success) {
            alert('Listing submitted successfully! Waiting for admin approval.');
            document.getElementById('postModal').classList.remove('open');
            loadCategoryItems(currentListingType, currentCategoryId);
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error submitting post:', error);
        alert('Error submitting post. Please try again.');
    }
}

async function placeBid(contractId) {
    const amount = prompt('Enter your bid amount:');
    if (!amount) return;

    const proposal = prompt('Write your proposal:');
    if (!proposal) return;

    const days = prompt('Estimated days to complete:');

    try {
        const response = await fetch('php/api/place_bid.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                contract_id: contractId,
                bid_amount: amount,
                proposal: proposal,
                estimated_days: days
            })
        });

        const result = await response.json();
        if (result.success) {
            alert('Bid placed successfully.');
        } else {
            alert('Could not place bid: ' + (result.message || 'Unknown error.'));
        }
    } catch (error) {
        console.error('Bid error:', error);
        alert('Error placing bid. Please try again.');
    }
}

// Orders / Refunds / Reviews / Delivery UI
async function showOrders() {
    document.getElementById('ordersModal').classList.add('open');
    const listEl = document.getElementById('ordersList');
    listEl.innerHTML = 'Loading...';
    try {
        const url = 'php/api/user_orders.php?guest_token=' + encodeURIComponent(guestToken);
        const res = await fetch(url, { credentials: 'same-origin' });
        const text = await res.text();
        let json;
        try {
            json = JSON.parse(text);
        } catch (parseErr) {
            console.error('Failed to parse JSON from user_orders.php:', parseErr, 'RAW RESPONSE:', text);
            listEl.innerHTML = `<pre style="white-space:pre-wrap;">Error parsing server response:\n${escapeHtml(text)}</pre>`;
            return;
        }
        if (!json.success) return listEl.innerHTML = `<p>Error loading orders: ${escapeHtml(json.message || json.raw_output || 'Unknown')}</p>`;
        const rows = json.orders || json.data || [];
        listEl.innerHTML = renderOrders(rows);
        // attach buttons
        listEl.querySelectorAll('.btn-refund').forEach(b => b.addEventListener('click', () => openRefundModal(b.dataset.item)));
        listEl.querySelectorAll('.btn-review').forEach(b => b.addEventListener('click', () => openReviewModal(b.dataset.type, b.dataset.listing, b.dataset.order)));
        listEl.querySelectorAll('.btn-track').forEach(b => b.addEventListener('click', () => viewDeliveryHistory(b.dataset.order)));
    } catch (err) {
        console.error(err);
        listEl.innerHTML = '<p>Error loading orders.</p>';
    }
}

function renderOrders(rows) {
    if (!Array.isArray(rows) || rows.length === 0) return '<p>No orders found.</p>';
    const byOrder = {};
    rows.forEach(r => {
        const oid = r.order_id || r.id || r.orderId || r.order_id_fk || 'unknown';
        byOrder[oid] = byOrder[oid] || [];
        byOrder[oid].push(r);
    });

    return Object.keys(byOrder).map(oid => {
        const items = byOrder[oid];
        const header = `<div class="order-header"><strong>Order #${escapeHtml(String(oid))}</strong> - ${escapeHtml(items[0].created_at || items[0].order_date || '')} - Tracking: ${escapeHtml(items[0].tracking_number||'N/A')}</div>`;
        const list = items.map(it => {
            const itemId = it.order_item_id || it.item_id || it.id || '';
            const listingType = it.product_id ? 'product' : (it.property_id ? 'property' : (it.service_contract_id ? 'service' : 'product'));
            const listingId = it.product_id || it.property_id || it.service_contract_id || '';
            const title = it.product_name || it.title || it.address || it.name || it.listing_title || 'Item';
            const qty = it.quantity || it.qty || 1;
            const price = it.unit_price || it.price || it.amount || '';
            return `<div class="order-item">
                        <div class="order-item-title">${escapeHtml(title)}</div>
                        <div class="order-item-meta">Qty: ${escapeHtml(String(qty))} — ${price ? ('$' + escapeHtml(String(price))) : ''}</div>
                        <div class="order-item-actions">
                            <button class="btn-track" data-order="${escapeHtml(String(oid))}">Track</button>
                            <button class="btn-refund" data-item="${escapeHtml(String(itemId))}">Refund/Return</button>
                            <button class="btn-review" data-type="${escapeHtml(listingType)}" data-listing="${escapeHtml(String(listingId))}" data-order="${escapeHtml(String(oid))}">Review</button>
                        </div>
                    </div>`;
        }).join('');
        return `<div class="order-block">${header}${list}</div>`;
    }).join('');
}

function openRefundModal(orderItemId) {
    document.getElementById('refundOrderItemId').value = orderItemId;
    document.getElementById('refundReason').value = '';
    document.getElementById('refundModal').classList.add('open');
}

async function handleRefundForm(e) {
    e.preventDefault();
    const itemId = document.getElementById('refundOrderItemId').value;
    const reason = document.getElementById('refundReason').value.trim();
    const type = document.getElementById('refundType').value || 'refund';
    if (!itemId || !reason) return alert('Please provide a reason.');
    try {
        const payload = { order_item_id: itemId, reason, request_type: type };
        if (!currentUser) {
            payload.guest_token = guestToken;
        }
        const res = await fetch('php/api/request_refund.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
            credentials: 'same-origin'
        });
        const json = await res.json();
        if (json.success) {
            alert('Refund/Return request submitted.');
            document.getElementById('refundModal').classList.remove('open');
            showOrders();
        } else {
            alert('Error: ' + (json.message || 'Could not submit request'));
        }
    } catch (err) {
        console.error(err);
        alert('Error submitting request.');
    }
}

function openReviewModal(listingType, listingId, relatedOrderId) {
    document.getElementById('reviewListingType').value = listingType;
    document.getElementById('reviewListingId').value = listingId;
    document.getElementById('reviewRelatedOrderId').value = relatedOrderId;
    document.getElementById('reviewRating').value = 5;
    document.getElementById('reviewTitle').value = '';
    document.getElementById('reviewComment').value = '';
    document.getElementById('reviewModal').classList.add('open');
}

async function handleReviewForm(e) {
    e.preventDefault();
    const type = document.getElementById('reviewListingType').value;
    const listingId = document.getElementById('reviewListingId').value;
    const relatedOrderId = document.getElementById('reviewRelatedOrderId').value;
    const rating = Number(document.getElementById('reviewRating').value);
    const title = document.getElementById('reviewTitle').value.trim();
    const comment = document.getElementById('reviewComment').value.trim();
    if (!listingId || !rating) return alert('Please provide a rating.');
    try {
        const payload = { listing_type: type, listing_id: listingId, related_order_id: relatedOrderId, rating, title, comment };
        if (!currentUser) {
            payload.guest_token = guestToken;
        }
        const res = await fetch('php/api/post_review.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
            credentials: 'same-origin'
        });
        const json = await res.json();
        if (json.success) {
            alert('Review submitted.');
            document.getElementById('reviewModal').classList.remove('open');
        } else {
            alert('Error: ' + (json.message || 'Could not submit review'));
        }
    } catch (err) {
        console.error(err);
        alert('Error submitting review.');
    }
}

async function viewDeliveryHistory(orderId) {
    document.getElementById('deliveryModal').classList.add('open');
    const el = document.getElementById('deliveryHistory');
    el.innerHTML = 'Loading...';
    try {
        const res = await fetch(`php/api/delivery_history.php?order_id=${encodeURIComponent(orderId)}`, { credentials: 'same-origin' });
        const json = await res.json();
        if (!json.success) return el.innerHTML = '<p>No history found.</p>';
        const rows = json.history || json.data || [];
        if (!Array.isArray(rows) || rows.length === 0) return el.innerHTML = '<p>No history available.</p>';
        el.innerHTML = rows.map(r => `<div class="history-row">${escapeHtml(r.status)} — ${escapeHtml(r.updated_at || r.created_at || '')}${r.note?(' — '+escapeHtml(r.note)):''}</div>`).join('');
    } catch (err) {
        console.error(err);
        el.innerHTML = '<p>Error loading history.</p>';
    }
}

