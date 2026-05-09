let currentUser = null;
let currentListingType = 'properties';
let currentCategory = null;

// Initialize page
document.addEventListener('DOMContentLoaded', () => {
    loadCategories('properties');
    loadListings('properties');
    
    // Setup navigation
    document.querySelectorAll('.nav-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentListingType = btn.dataset.type;
            loadCategories(currentListingType);
            loadListings(currentListingType);
        });
    });
});

// Load categories based on type
async function loadCategories(type) {
    try {
        const response = await fetch(`php/api/get_categories.php?type=${type}`);
        const categories = await response.json();
        
        const container = document.getElementById('categories-list');
        container.innerHTML = '<ul>' + categories.map(cat => 
            `<li onclick="filterByCategory(${cat.id})">${cat.name}</li>`
        ).join('') + '</ul>';
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

// Load listings
async function loadListings(type, categoryId = null) {
    const container = document.getElementById('listings-container');
    container.innerHTML = '<div class="loading"><i class="fas fa-spinner"></i> Loading...</div>';
    
    try {
        let url = `php/api/get_listings.php?type=${type}`;
        if (categoryId) url += `&category=${categoryId}`;
        
        const response = await fetch(url);
        const listings = await response.json();
        
        if (listings.length === 0) {
            container.innerHTML = '<p>No listings found in this category.</p>';
            return;
        }
        
        container.innerHTML = listings.map(listing => createListingCard(listing, type)).join('');
    } catch (error) {
        console.error('Error loading listings:', error);
        container.innerHTML = '<p>Error loading listings. Please try again.</p>';
    }
}

// Create listing card HTML
function createListingCard(listing, type) {
    let additionalInfo = '';
    
    if (type === 'properties') {
        additionalInfo = `
            <div>Type: ${listing.listing_type}</div>
            <div>${listing.bedrooms} beds | ${listing.bathrooms} baths</div>
            <div>${listing.area_sqft} sq ft</div>
        `;
    } else if (type === 'products') {
        additionalInfo = `
            <div>Condition: ${listing.productcondition}</div>
            <div>Quantity: ${listing.quantity}</div>
        `;
    } else if (type === 'contracts') {
        additionalInfo = `
            <div>Duration: ${listing.duration}</div>
            <div>Experience: ${listing.experience_level}</div>
            ${currentUser ? `<button onclick="placeBid(${listing.id})" class="bid-btn">Place Bid</button>` : ''}
        `;
    }
    
    return `
        <div class="listing-card">
        
            ${listing.image_path ? `<img src="${listing.image_path}" class="listing-image" alt="${listing.title}">` : 
              '<div class="listing-image" style="background: #ddd; display: flex; align-items: center; justify-content: center;"><i class="fas fa-image" style="font-size: 3rem; color: #999;"></i></div>'}
            <div class="listing-info">
                <div class="listing-title">${listing.title}</div>
                <div class="listing-price">$${listing.price || listing.budget}</div>
                <div class="listing-description">${listing.itemdescription.substring(0, 100)}...</div>
                ${additionalInfo}
                <small>Posted by: ${listing.username} | ${new Date(listing.created_at).toLocaleDateString()}</small>
            </div>
        </div>
    `;
}

// Filter by category
function filterByCategory(categoryId) {
    currentCategory = categoryId;
    loadListings(currentListingType, categoryId);
}

// Show post form
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
    
    // Generate form based on type
    let fieldsHtml = `
        <input type="hidden" id="listingType" value="${currentListingType}">
        <input type="text" id="title" placeholder="Title" required>
        <textarea id="description" placeholder="Description" rows="4" required></textarea>
        <input type="number" id="price" placeholder="${currentListingType === 'contracts' ? 'Budget' : 'Price'}" required>
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
            <input type="number" id="quantity" placeholder="Quantity" value="1">
        `;
    } else if (currentListingType === 'contracts') {
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
    
    // Load categories for dropdown
    loadCategoryDropdown(currentListingType);
    
    modal.style.display = 'block';
    
    // Setup form submission
    const form = document.getElementById('postForm');
    form.onsubmit = (e) => {
        e.preventDefault();
        submitPost();
    };
}

// Load categories for dropdown
async function loadCategoryDropdown(type) {
    const response = await fetch(`php/api/get_categories.php?type=${type}`);
    const categories = await response.json();
    const select = document.getElementById('category');
    select.innerHTML = '<option value="">Select Category</option>' + 
        categories.map(cat => `<option value="${cat.id}">${cat.name}</option>`).join('');
}

// Submit post
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
        formData.append('quantity', document.getElementById('quantity').value);
    } else if (type === 'contracts') {
        formData.append('duration', document.getElementById('duration').value);
        formData.append('location', document.getElementById('location').value);
        formData.append('experience_level', document.getElementById('experience').value);
    }
    
    try {
        const response = await fetch(`php/api/post_${type}.php`, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Listing submitted successfully! Waiting for admin approval.');
            document.getElementById('postModal').style.display = 'none';
            loadListings(currentListingType);
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error submitting post:', error);
        alert('Error submitting post. Please try again.');
    }
}

// Place bid on contract
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
            alert('Bid placed successfully!');
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error placing bid:', error);
        alert('Error placing bid. Please try again.');
    }
}