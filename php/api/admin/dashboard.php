<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../../../index.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../../../css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
        }
        
        .admin-header {
            background: #2c3e50;
            color: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .tab-btn {
            padding: 10px 20px;
            background: #ecf0f1;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }
        
        .tab-btn.active {
            background: #3498db;
            color: white;
        }
        
        .tab-content {
            display: none;
        }
         .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
          .product-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
         .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f5f5f5;
        }
        
        .product-info {
            padding: 1rem;
        }
        .product-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }
        
        .product-price {
            font-size: 1.3rem;
            color: #e44d3a;
            font-weight: bold;
            margin: 0.5rem 0;
        }
         .product-details {
            color: #666;
            font-size: 0.9rem;
            margin: 0.5rem 0;
        }
        
        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 0.5rem;
            border-top: 1px solid #eee;
        }
        
        .product-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .pending-listings {
            display: grid;
            gap: 1rem;
        }
        
        .listing-item {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .listing-info h4 {
            margin-bottom: 0.5rem;
        }
        
        .listing-actions button {
            padding: 5px 15px;
            margin-left: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .approve-btn {
            background: #27ae60;
            color: white;
        }
        
        .reject-btn {
            background: #e74c3c;
            color: white;
        }
        
        .category-form {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .category-form input, .category-form textarea {
            width: 100%;
            margin: 10px 0;
            padding: 10px;
        }
        
        .categories-list {
            background: white;
            padding: 1rem;
            border-radius: 10px;
        }
        
        .category-item {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1><i class="fas fa-user-shield"></i> Admin Dashboard</h1>
            <p>Welcome, <?php echo $_SESSION['username']; ?></p>
            <button onclick="logout()" class="btn-logout">Logout</button>
        </div>
        
        <div class="tabs">
            <nav>
            <button class="tab-btn active" onclick="showTab('pending')">Pending Approvals</button>
            <button class="tab-btn " onclick="showTab('properties')">Property Categories</button>
            <button class="tab-btn " onclick="showTab('products')">Product Categories</button>
            <button class="tab-btn  " onclick="showTab('contracts')">Contract Categories</button></nav>
        </div>
        
        <div id="pending-tab" class="tab-content active">
            <h2>Pending Listings</h2>
            <div id="pending-listings" class="pending-listings"></div>
        </div>

        
        
        <div id="properties-tab" class="tab-content">
            <h2>Manage Property Categories</h2>
            <div class="category-form">
                <h3>Add New Category</h3>
                <form id="propertyCategoryForm">
                    <input type="text" id="propid" hidden value="">
                    <input type="text" id="propCatName" placeholder="Category Name" required>
                    <textarea id="propCatDesc" placeholder="Description"></textarea>
                    <button  id="btnPropAdd" type="submit">Add Category</button>
                </form>
                 <div id="propertiesCategories"   ></div>
            </div>
            <div id="propertyCategories" class="categories-list"></div>
        </div>
        
        <div id="products-tab" class="tab-content">
            <h2>Manage Product Categories</h2>
            <div class="category-form">
               
                <h3>Add New Category</h3>
                <form id="productCategoryForm">
                    <input type="text" id="productid" hidden value="">
                    <input type="text" id="prodCatName" placeholder="Category Name" required>
                    <textarea id="prodCatDesc" placeholder="Description"></textarea>
                    <button type="submit" id="btnProductAdd">Add Category</button>
                </form>

                 <div id="productsCategories" ></div>
            </div>
            <div id="productCategories" class="categories-list"></div>
        </div>
        
        <div id="contracts-tab" class="tab-content">
            <h2>Manage Contract Categories</h2>
            <div class="category-form">
                <h3>Add New Category</h3>
                <form id="contractCategoryForm">
                    <input type="text" id="contid" hidden value="">
                    <input type="text" id="contCatName" placeholder="Category Name" required>
                    <textarea id="contCatDesc" placeholder="Description"></textarea>
                    <button id="btnContAdd" type="submit">Add Category</button>
                </form>
                <div id="contractsCategories"></div>
            </div>
            <div id="contractCategories" class="categories-list"></div>
        </div>
    </div>
     <script src="../../../js/main.js"></script>
    <script >
        // Load pending listings
        async function loadPendingListings() {
            const response = await fetch('get_pending_listings.php');
            const listings = await response.json();
            
            const container = document.getElementById('pending-listings');
            container.innerHTML = listings.map(listing => `
                <div class="listing-item">
                    <div class="listing-info">
                        <h4>${listing.title}</h4>
                        <p>Type: ${listing.type} | Posted by: ${listing.username}</p>
                        <small>${new Date(listing.created_at).toLocaleDateString()}</small>
                    </div>
                    
                      
              <div class="listing-image" style="background: #ddd; display: flex; align-items: center; justify-content: center;"><img src="../../../${listing.image_path}" class="listing-image" alt="${listing.image_path}"></div>
                    <div class="listing-actions">
                        <button onclick="approveListing(${listing.id}, '${listing.type}')" class="approve-btn">Approve</button>
                        <button onclick="rejectListing(${listing.id}, '${listing.type}')" class="reject-btn">Reject</button>
                    </div>
                </div>
            `).join('');
        }
        
        // Approve listing
        async function approveListing(id, type) {
            const response = await fetch('approve_listing.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id, type, action: 'approve'})
            });
            const result = await response.json();
            if (result.success) {
                alert('Listing approved!');
                loadPendingListings();
            }
        }
        
        // Reject listing
        async function rejectListing(id, type) {
            const response = await fetch('approve_listing.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id, type, action: 'reject'})
            });
            const result = await response.json();
            if (result.success) {
                alert('Listing rejected!');
                loadPendingListings();
            }
        }
        
        // Load categories
        async function loadCategories(type) {
            const response = await fetch(`../get_categories.php?type=${type}`);
            const categories = await response.json();
            const container = document.getElementById(`${type}Categories`);
            container.innerHTML = categories.map(cat => `
                <div class="category-item">
                    <div>
                        <strong>${cat.name}</strong>
                        <p>${cat.description || ''}</p>
                    </div>
                    <div>
                <button onclick="fillEditForm(${cat.id}, '${cat.name}', '${(cat.description || '').replace(/'/g, "\\'")}','${cat.type}')">Edit</button>
                <button onclick="deleteCategory(${cat.id},'${type}')">Delete</button>
            </div>
                </div>
            `).join('');
        }
        

        //Edit form

function fillEditForm(id, name, desc , type) {
    document.getElementById('productid').value = id;
    document.getElementById('prodCatName').value = name;
    document.getElementById('prodCatDesc').value = desc;
 document.getElementById('propid').value = id;
    document.getElementById('propCatName').value = name;
    document.getElementById('propCatDesc').value = desc;
     document.getElementById('contid').value = id;
    document.getElementById('contCatName').value = name;
    document.getElementById('contCatDesc').value = desc;
    document.getElementById('btnProductAdd').innerHTML = "Update";
    document.getElementById('btnPropAdd').innerHTML = "Update";
document.getElementById('btnContAdd').innerHTML = "Update";


    
}


// Delete cat
async function deleteCategory(id, type) {
    console.log(`Deleting ID: ${id}, Type: ${type}`);
    
    if(confirm('Are you sure you want to delete this category?')) {
        try {
            const response = await fetch('manage_categories.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id, type, action: 'delete'})
            });
            
            // Use .json() directly - don't use .text() first
            const result = await response.json();
            
            console.log('Server response:', result);
            
            if (result.success) {
                alert('Category deleted!');
                loadCategories(type);
            } else {
                alert('Failed to delete category: ' + (result.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while deleting');
        }
    }
}




        // Add category

        async function addCategory(type, name, description,id) {
            
            if (id==""){
            const response = await fetch('manage_categories.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({type, name, description,id, action: 'add'})
            });
            const result = await response.json();
            if (result.success) {
                alert('Category added!');
                loadCategories(type);
                 reset(type, name, description, id);
            }} else{
            
                updateCategory(type, name, description,id);
            }
        }
        //update category
        async function updateCategory(type, name, description, id) {
            const response = await fetch('manage_categories.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({type, name, description,id, action: 'update'})
            });
            const result = await response.json();
            if (result.success) {
                alert('Category updated!');
                loadCategories(type);
                reset(type, name, description, id);   
            }}
        //reset the form
        function reset(type, name, description, id){
             document.getElementById('productid').value = '';
    document.getElementById('prodCatName').value = '';
    document.getElementById('prodCatDesc').value = '';
    
    document.getElementById('propCatName').value = '';
    document.getElementById('propCatDesc').value = '';
     document.getElementById('contid').value = '';
    document.getElementById('contCatName').value = '';
    document.getElementById('contCatDesc').value = '';
    
    const btn = document.getElementById('btnProductAdd');
    btn.innerHTML = "Add Category";

        }
        // Show tab
        function showTab(tab) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById(`${tab}-tab`).classList.add('active');
            event.target.classList.add('active');
            
            if (tab === 'pending') {
                loadPendingListings();
            } else {
                loadCategories(tab);
            }
        }
        
        // Form submissions
        document.getElementById('propertyCategoryForm').addEventListener('submit', (e) => {
            e.preventDefault();
            addCategory('properties', 
                document.getElementById('propCatName').value,
                document.getElementById('propCatDesc').value,
                document.getElementById('propid').value
            );
        });
        
        document.getElementById('productCategoryForm').addEventListener('submit', (e) => {
            e.preventDefault();
            addCategory('products',
                document.getElementById('prodCatName').value,
                document.getElementById('prodCatDesc').value,
                document.getElementById('productid').value
            );
        });
        
        document.getElementById('contractCategoryForm').addEventListener('submit', (e) => {
            e.preventDefault();
            addCategory('contracts',
                document.getElementById('contCatName').value,
                document.getElementById('contCatDesc').value,
         document.getElementById('contid' ).value  );
        });
        
        // Initial load
        loadPendingListings();
        
        function logout() {
            window.location.href = '../../../index.html';
        }
    </script>
</body>
</html>
