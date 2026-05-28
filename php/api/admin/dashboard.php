<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../../../index.html');
    exit;
}
//“Detect all errors, but don’t display them on the screen.”
ini_set('display_errors', '0');
error_reporting(E_ALL);

if (!@include_once __DIR__ . '/../../config/Database.php') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Missing Database.php include']);
        exit;
    }
}

if (!class_exists('Database')) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database class not available']);
        exit;
    }
}

function generateCategoryId($type) {
    $prefixes = [
        'property' => 'PRC',
        'product' => 'PDC',
        'service' => 'SRC'
    ];
    $prefix = $prefixes[$type] ?? 'CAT';
    try {
        return $prefix . '-' . strtoupper(bin2hex(random_bytes(3)));
    } catch (Throwable $e) {
        return $prefix . '-' . strtoupper(substr(uniqid('', true), -8));
    }
}

function normalizeBlankCategoryIds($db, $table, $type) {
    $stmt = $db->prepare("SELECT category_name FROM $table WHERE category_id = '' OR category_id IS NULL");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $update = $db->prepare("UPDATE $table SET category_id = ? WHERE category_id = '' AND category_name = ? LIMIT 1");
    foreach ($rows as $row) {
        $newId = generateCategoryId($type);
        $update->execute([$newId, $row['category_name']]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (ob_get_level()) {
        ob_clean();
    }
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
        exit;
    }

    if (!isset($input['action'], $input['type'])) {
        echo json_encode(['success' => false, 'message' => 'Missing action or type']);
        exit;
    }

    try {
        $db = (new Database())->getConnection();
        if (!$db) {
            throw new Exception('Database connection failed');
        }

        $action = $input['action'];
        $type = $input['type'];
        $tables = [
            'property' => 'property_category',
            'product' => 'product_category',
            'service' => 'service_category'
        ];

        if (!isset($tables[$type])) {
            throw new Exception('Invalid category type');
        }

        $table = $tables[$type];

        if ($action === 'add') {
            $name = trim($input['name'] ?? '');
            $description = trim($input['description'] ?? '');
            if ($name === '') {
                throw new Exception('Category name is required');
            }
            $categoryId = generateCategoryId($type);
            $stmt = $db->prepare("INSERT INTO $table (category_id, category_name, description) VALUES (?, ?, ?)");
            $stmt->execute([$categoryId, $name, $description]);
            echo json_encode(['success' => true, 'id' => $categoryId]);
            exit;
        }

        if ($action === 'update') {
            $id = $input['id'] ?? null;
            $name = trim($input['name'] ?? '');
            $description = trim($input['description'] ?? '');
            if (!$id || $name === '') {
                throw new Exception('Category id and name are required');
            }
            $stmt = $db->prepare("UPDATE $table SET category_name = ?, description = ? WHERE category_id = ?");
            $stmt->execute([$name, $description, $id]);
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'delete') {
            $id = $input['id'] ?? null;
            if (!$id) {
                throw new Exception('Category id is required');
            }
            $stmt = $db->prepare("DELETE FROM $table WHERE category_id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'list') {
            normalizeBlankCategoryIds($db, $table, $type);
            $stmt = $db->prepare("SELECT category_id, category_name, description FROM $table ORDER BY category_name ASC");
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'categories' => $categories]);
            exit;
        }

        throw new Exception('Unknown action');
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
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
        .category-item { padding: 15px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; gap: 1rem; }
        .category-item p { margin: 0.5rem 0 0; color: #555; }
        .category-actions { display: flex; gap: 0.5rem; }
        .category-actions button { border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; }
        .edit-btn { background: #27ae60; color: white; }
        .delete-btn { background: #c0392b; color: white; }
        #productTable { width: 100%; border-collapse: collapse; margin-top: 20px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        #productTable thead { background-color: #009879; color: #ffffff; }
        #productTable th, #productTable td { padding: 12px 15px; border-bottom: 1px solid #dddddd; }
        .btn-approve { background: #28a745; color: white; border: none; padding: 6px 12px; cursor: pointer; border-radius: 4px; }
        .btn-reject { background: #dc3545; color: white; border: none; padding: 6px 12px; cursor: pointer; border-radius: 4px; }
    </style>
</head>
<body>

<div class="admin-container">
    <div class="admin-header">
        <h1><i class="fas fa-user-shield"></i> Admin Dashboard</h1>
        <a href="./logout.php" style="background:#e67e22; color:white; padding:8px 16px; border-radius:4px; text-decoration:none;">Logout</a>
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
                <input type="hidden" id="propertyId">
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
                <input type="hidden" id="productId">
                <input type="text" id="productCatName" placeholder="Category Name" required>
                <textarea id="productCatDesc" placeholder="Description..."></textarea>
                <button type="submit" id="btnProductAdd">Add Category</button>
            </form>
        </div>
        <div id="productCategories" class="categories-list"></div>
    </div>

    <div id="services-tab" class="tab-content">
        <h2>Manage Service Categories</h2>
        <div class="category-form">
            <form id="serviceCategoryForm" onsubmit="handleCategorySubmit(event, 'service')">
                <input type="hidden" id="serviceId">
                <input type="text" id="serviceCatName" placeholder="Category Name" required>
                <textarea id="serviceCatDesc" placeholder="Description..."></textarea>
                <button type="submit" id="btnServiceAdd">Add Category</button>
            </form>
        </div>
        <div id="serviceCategories" class="categories-list"></div>
    </div>
</div>

<script>

// Tab switching logic
document.querySelectorAll('.tab-btn').forEach(button => {
    button.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        button.classList.add('active');
        document.getElementById(button.getAttribute('data-target')).classList.add('active');
    });
});

function getCategoryElements(type) {
    return {
        idInput: document.getElementById(`${type}Id`),
        nameInput: document.getElementById(`${type}CatName`),
        descInput: document.getElementById(`${type}CatDesc`),
        submitBtn: document.getElementById(`btn${type.charAt(0).toUpperCase() + type.slice(1)}Add`),
        listContainer: document.getElementById(`${type}Categories`)
    };
}

async function requestCategoryAction(action, type, data = {}) {
    const response = await fetch('./dashboard.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action, type, ...data })
    });
    const text = await response.text();
    const contentType = response.headers.get('Content-Type') || '';
    if (!contentType.includes('application/json')) {
        console.error('Non-JSON response from dashboard.php:', response.status, response.url, text);
        throw new Error('Server returned a non-JSON response');
    }
    let result;
    try {
        result = JSON.parse(text);
    } catch (error) {
        console.error('Invalid JSON response from dashboard.php:', text);
        throw new Error('Invalid JSON response from server');
    }
    if (!response.ok || !result.success) {
        throw new Error(result.message || 'Category request failed');
    }
    return result;
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function renderCategoryList(type, categories) {
    const { listContainer } = getCategoryElements(type);
    if (!Array.isArray(categories) || categories.length === 0) {
        listContainer.innerHTML = '<p>No categories found.</p>';
        return;
    }

    listContainer.innerHTML = categories.map(category => {
        const name = category.category_name || '';
        const description = category.description || '';
        const categoryId = category.category_id || '';
        return `
            <div class="category-item">
                <div>
                    <strong>${escapeHtml(name)}</strong>
                    <p>${escapeHtml(description)}</p>
                </div>
                <div class="category-actions">
                    <button type="button" class="edit-btn" data-type="${type}" data-id="${escapeHtml(categoryId)}" data-name="${escapeHtml(name)}" data-desc="${escapeHtml(description)}">Edit</button>
                    <button type="button" class="delete-btn" data-type="${type}" data-id="${escapeHtml(categoryId)}">Delete</button>
                </div>
            </div>
        `;
    }).join('');

    listContainer.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', () => {
            prepareEdit(button.dataset.type, button.dataset.id, button.dataset.name, button.dataset.desc);
        });
    });

    listContainer.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', () => {
            deleteCategory(button.dataset.type, button.dataset.id);
        });
    });
}

async function loadCategories(type) {
    try {
        const result = await requestCategoryAction('list', type);
        renderCategoryList(type, result.categories);
    } catch (error) {
        console.error(error);
        const { listContainer } = getCategoryElements(type);
        listContainer.innerHTML = '<p>Error loading categories.</p>';
    }
}

function prepareEdit(type, id, name, desc) {
    const { idInput, nameInput, descInput, submitBtn } = getCategoryElements(type);
    idInput.value = id;
    nameInput.value = name;
    descInput.value = desc;
    submitBtn.innerText = 'Update Category';
}

function resetCategoryForm(type) {
    const { idInput, nameInput, descInput, submitBtn } = getCategoryElements(type);
    idInput.value = '';
    nameInput.value = '';
    descInput.value = '';
    submitBtn.innerText = 'Add Category';
}

async function handleCategorySubmit(event, type) {
    event.preventDefault();
    const { idInput, nameInput, descInput } = getCategoryElements(type);
    const name = nameInput.value.trim();
    const description = descInput.value.trim();
    const id = idInput.value;

    if (!name) {
        alert('Category Name is required');
        return;
    }

    try {
        if (id) {
            await requestCategoryAction('update', type, { id, name, description });
        } else {
            await requestCategoryAction('add', type, { name, description });
        }
        resetCategoryForm(type);
        await loadCategories(type);
    } catch (error) {
        console.error(error);
        alert(error.message || 'Unable to save category.');
    }
}

async function deleteCategory(type, id) {
    if (!confirm('Are you sure you want to delete this category?')) {
        return;
    }
    try {
        await requestCategoryAction('delete', type, { id });
        await loadCategories(type);
    } catch (error) {
        console.error(error);
        alert(error.message || 'Unable to delete category.');
    }
}

window.addEventListener('DOMContentLoaded', () => {
    ['property', 'product', 'service'].forEach(type => loadCategories(type));
});
</script>
</body>
</html>
