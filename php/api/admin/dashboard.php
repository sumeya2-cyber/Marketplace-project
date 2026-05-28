<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../../../index.html');
    exit;
}

// ... keep your existing session_start() and includes ...

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    if (isset($input['action'])) {
        $db = (new Database())->getConnection(); // Ensure your Database class works here
        $action = $input['action'];
        $type = $input['type']; // 'properties', 'products', 'services'
        
        // Define your table mapping here
        $tables = ['properties' => 'Property_Category', 'products' => 'Product_Category', 'services' => 'Service_Category'];
        $table = $tables[$type];

        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO $table (category_name) VALUES (?)");
            $stmt->execute([$input['name']]);
        } elseif ($action === 'delete') {
            $stmt = $db->prepare("DELETE FROM $table WHERE category_id = ?");
            $stmt->execute([$input['id']]);
        }
        echo json_encode(['success' => true]);
        exit;
    }
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
        .admin-container { max-width: 1200px; margin: 2rem auto; padding: 0 20px; font-family: sans-serif; }
        .admin-header { background: #2c3e50; color: white; padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
        .tabs-header { display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
        .tab-btn { padding: 10px 20px; background: #ecf0f1; border: none; cursor: pointer; border-radius: 5px; font-weight: bold; }
        .tab-btn.active { background: #3498db; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .category-form { background: white; padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .category-form input, .category-form textarea { width: 100%; margin: 10px 0; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .category-item { padding: 15px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }
        #productTable { width: 100%; border-collapse: collapse; margin-top: 20px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        #productTable thead { background-color: #009879; color: #ffffff; }
        #productTable th, #productTable td { padding: 12px 15px; border-bottom: 1px solid #dddddd; }
        .btn-approve { background: #28a745; color: white; border: none; padding: 6px 12px; cursor: pointer; border-radius: 4px; }
        .btn-reject { background: #dc3545; color: white; border: none; padding: 6px 12px; cursor: pointer; border-radius: 4px; }
        .edit-btn { background: #27ae60; color: white; border: none; padding: 5px 12px; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>

<div class="admin-container">
    <div class="admin-header">
        <h1><i class="fas fa-user-shield"></i> Admin Dashboard</h1>
        <a href="logout.php" style="background:#e67e22; color:white; padding:8px 16px; border-radius:4px; text-decoration:none;">Logout</a>
    </div>

    <div class="tabs-header">
        <button class="tab-btn active" data-target="pending-tab">Pending Approvals</button>
        <button class="tab-btn" data-target="properties-tab">Property Categories</button>
        <button class="tab-btn" data-target="products-tab">Product Categories</button>
        <button class="tab-btn" data-target="services-tab">Service Categories</button>
    </div>

    <div id="pending-tab" class="tab-content active">
        <h2>Pending Marketplace Listings</h2>
        <table id="productTable">
            <thead><tr><th>ID</th><th>Price</th><th>Qty</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody id="productTableBody"></tbody>
        </table>
    </div>

    <div id="properties-tab" class="tab-content">
        <h2>Manage Property Categories</h2>
        <div class="category-form">
            <form id="propertyCategoryForm" onsubmit="handleCategorySubmit(event, 'property')">
                <input type="text" id="propertyid" hidden>
                <input type="text" id="propertyCatName" placeholder="Category Name" required>
                <textarea id="propertyCatDesc" placeholder="Description..."></textarea>
                <button type="submit" id="btnPropertyAdd">Add Category</button>
            </form>
        </div>
        <div id="propertyCategories" class="categories-list"></div>
    </div>

    <div id="products-tab" class="tab-content">
        <h2>Manage Product Categories</h2>
        <div class="category-form">
            <form id="productCategoryForm" onsubmit="handleCategorySubmit(event, 'product')">
                <input type="text" id="productid" hidden>
                <input type="text" id="prodCatName" placeholder="Category Name" required>
                <textarea id="prodCatDesc" placeholder="Description..."></textarea>
                <button type="submit" id="btnProductAdd">Add Category</button>
            </form>
        </div>
        <div id="productCategories" class="categories-list"></div>
    </div>

    <div id="services-tab" class="tab-content">
        <h2>Manage Service Categories</h2>
        <div class="category-form">
            <form id="serviceCategoryForm" onsubmit="handleCategorySubmit(event, 'service')">
                <input type="text" id="serviceid" hidden>
                <input type="text" id="serviceCatName" placeholder="Category Name" required>
                <textarea id="serviceCatDesc" placeholder="Description..."></textarea>
                <button type="submit" id="btnServiceAdd">Add Category</button>
            </form>
        </div>
        <div id="serviceCategories" class="categories-list"></div>
    </div>
</div>

<script>

// 1. Tab Switching Logic (Kept as is)
document.querySelectorAll('.tab-btn').forEach(button => {
    button.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        button.classList.add('active');
        document.getElementById(button.getAttribute('data-target')).classList.add('active');
    });
});

// 2. Load Categories
async function loadCategories(type) {
    // This calls the file we created in Step 1
    const res = await fetch(`../api/categories.php?type=${type}`);
    const data = await res.json();
    
    // Select the container using the type (e.g., propertyCategories)
    const container = document.getElementById(`${type}Categories`);
    
    // Display the list
    container.innerHTML = data.map(c => `
        <div class="category-item">
            <strong>${c.name}</strong>
            <p>${c.description}</p>
        </div>
    `).join('');
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    loadCategories('property');
    loadCategories('product');
    loadCategories('service');
});

// 3. Prepare Form for Edit
function prepareEdit(type, id, name, desc) {
    document.getElementById(`${type}id`).value = id;
    document.getElementById(`${type}CatName`).value = name;
    document.getElementById(`${type}CatDesc`).value = desc;
    document.getElementById(`btn${type.charAt(0).toUpperCase() + type.slice(1)}Add`).innerText = "Update Category";
}

// 4. Handle Add/Update with Pop-ups
/*async function handleCategorySubmit(event, type) {
    event.preventDefault();
    const id = document.getElementById(`${type}id`).value;
    const name = document.getElementById(`${type}CatName`).value;
    const desc = document.getElementById(`${type}CatDesc`).value;

    if (id) {
        // Update Logic
        if (confirm("Are you sure you want to update this category?")) {
            await fetch('update_category.php', { 
                method: 'POST', 
                body: JSON.stringify({id, name, description: desc}) 
            });
            alert("Category updated successfully!");
        }
    } else {
        // Add Logic
        await fetch('add_category.php', { 
            method: 'POST', 
            body: JSON.stringify({name, description: desc}) 
        });
        alert("Category added successfully!");
    }
    */
    // Reset form and reload
    event.target.reset();
    document.getElementById(`${type}id`).value = '';
    document.getElementById(`btn${type.charAt(0).toUpperCase() + type.slice(1)}Add`).innerText = "Add Category";
    loadCategories(type);


    
async function handleCategorySubmit(event, type) {
    event.preventDefault();
    const name = document.getElementById(type + 'CatName').value;
    
    await fetch('dashboard.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'add', type: type, name: name})
    });
    alert("Category added!");
    loadCategories(type); // Call your existing load function
}

async function deleteCategory(type, id) {
    if (!confirm("Are you sure?")) return;
    await fetch('dashboard.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'delete', type: type, id: id})
    });
    loadCategories(type);
}

document.addEventListener('DOMContentLoaded', () => {
    loadCategories('properties');
    loadCategories('products');
    loadCategories('services');
});
</script>
</body>
</html>