let currentListingType = 'properties';
let currentCategoryId = null;

// Initialize page
document.addEventListener('DOMContentLoaded', () => {
    setupNavigation();
    switchTab('properties');
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

