<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_email = $_SESSION['email'];

/* ----------------------------------------------------------
    DATA FETCH
---------------------------------------------------------- */

// PRODUCTS
$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll();

// BATCHES
$stmt = $pdo->query("
    SELECT b.*, p.name AS product_name 
    FROM batches b 
    JOIN products p ON p.id = b.product_id 
    ORDER BY b.id DESC
");
$batches = $stmt->fetchAll();

// TRANSACTIONS
$stmt = $pdo->query("
    SELECT t.*, p.name AS product_name, b.manufacture_date 
    FROM transactions t
    JOIN products p ON p.id = t.product_id
    JOIN batches b ON b.id = t.batch_id
    ORDER BY t.id DESC
");
$transactions = $stmt->fetchAll();

// INVENTORY (dynamic: total batches - sold)
$inventory = [];
foreach ($batches as $b) {
    $pid = $b['product_id'];
    $inventory[$pid] = ($inventory[$pid] ?? 0) + (int)$b['quantity'];
}
foreach ($transactions as $t) {
    $pid = $t['product_id'];
    $inventory[$pid] = ($inventory[$pid] ?? 0) - (int)$t['quantity_sold'];
}

// --- Sales Status Analytics ---
$salesStatusData = ['Sold' => 0, 'Pending' => 0, 'Cancelled' => 0];
foreach ($batches as $b) {
    if ($b['status'] === 'Completed') {
        $salesStatusData['Sold']++;
    } elseif ($b['status'] === 'Active') {
        $salesStatusData['Pending']++;
    } elseif ($b['status'] === 'Cancelled') {
        $salesStatusData['Cancelled']++;
    }
}

// --- Summary cards ---
$total_products = count($products);
$total_batches_sold = $salesStatusData['Sold'];
$total_batches_pending = $salesStatusData['Pending'];
$total_batches_cancelled = $salesStatusData['Cancelled'];

// Live analytics for charts
$monthData = $pdo->query("
    SELECT DATE_FORMAT(manufacture_date, '%b') AS month, COUNT(*) AS count
    FROM batches WHERE manufacture_date IS NOT NULL
    GROUP BY month ORDER BY MIN(manufacture_date)
")->fetchAll(PDO::FETCH_KEY_PAIR);

$statusData = ['Upcoming'=>0, 'Active'=>0, 'Expired'=>0];
$today = date('Y-m-d');
foreach ($batches as $b) {
    if (!$b['manufacture_date'] || !$b['expiry_date']) continue;
    if ($b['expiry_date'] < $today) $statusData['Expired']++;
    elseif ($b['manufacture_date'] > $today) $statusData['Upcoming']++;
    else $statusData['Active']++;
}

/* ----------------------------------------------------------
    CRUD HANDLERS
---------------------------------------------------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Add Product
    if ($_POST['action'] === 'add_product') {
        $name = $_POST['name'];
        $category = $_POST['category'];
        $desc = $_POST['description'];
        $imagePath = null;

        if (!empty($_FILES['image']['name'])) {
            $uploadDir = 'uploads/';
            $filename = uniqid() . '_' . basename($_FILES['image']['name']);
            $targetPath = $uploadDir . $filename;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            move_uploaded_file($_FILES['image']['tmp_name'], $targetPath);
            $imagePath = $targetPath;
        }

        $stmt = $pdo->prepare("INSERT INTO products (name, category, description, imageUrl) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $category, $desc, $imagePath]);
        header("Location: dashboard.php?section=products");
        exit;
    }

    // Edit Product
    if ($_POST['action'] === 'edit_product') {
        $id = $_POST['product_id'];
        $name = $_POST['name'];
        $category = $_POST['category'];
        $desc = $_POST['description'];
        $imagePath = $_POST['current_image'];

        if (!empty($_FILES['image']['name'])) {
            $uploadDir = 'uploads/';
            $filename = uniqid() . '_' . basename($_FILES['image']['name']);
            $targetPath = $uploadDir . $filename;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            move_uploaded_file($_FILES['image']['tmp_name'], $targetPath);
            $imagePath = $targetPath;
        }

        $stmt = $pdo->prepare("UPDATE products SET name=?, category=?, description=?, imageUrl=? WHERE id=?");
        $stmt->execute([$name, $category, $desc, $imagePath, $id]);
        header("Location: dashboard.php?section=products");
        exit;
    }

    // Delete Product
    if ($_POST['action'] === 'delete_product') {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
        $stmt->execute([$_POST['product_id']]);
        header("Location: dashboard.php?section=products");
        exit;
    }

    // Add Batch
    if ($_POST['action'] === 'add_batch') {
        $stmt = $pdo->prepare("INSERT INTO batches (product_id, quantity, location, status, manufacture_date, expiry_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['product_id'], $_POST['quantity'], $_POST['location'], $_POST['status'], $_POST['manufacture_date'], $_POST['expiry_date']]);
        header("Location: dashboard.php?section=batches");
        exit;
    }

    // Edit Batch
    if ($_POST['action'] === 'edit_batch') {
        $stmt = $pdo->prepare("UPDATE batches SET product_id=?, quantity=?, location=?, status=?, manufacture_date=?, expiry_date=? WHERE id=?");
        $stmt->execute([$_POST['product_id'], $_POST['quantity'], $_POST['location'], $_POST['status'], $_POST['manufacture_date'], $_POST['expiry_date'], $_POST['batch_id']]);
        header("Location: dashboard.php?section=batches");
        exit;
    }

    // Delete Batch
    if ($_POST['action'] === 'delete_batch') {
        $stmt = $pdo->prepare("DELETE FROM batches WHERE id=?");
        $stmt->execute([$_POST['batch_id']]);
        header("Location: dashboard.php?section=batches");
        exit;
    }

    // Add Transaction
    if ($_POST['action'] === 'add_transaction') {
        $stmt = $pdo->prepare("INSERT INTO transactions (batch_id, product_id, quantity_sold, remarks) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['batch_id'], $_POST['product_id'], $_POST['quantity_sold'], $_POST['remarks']]);
        header("Location: dashboard.php?section=transactions");
        exit;
    }
    
    // Edit Transaction
    if ($_POST['action'] === 'edit_transaction') {
        $stmt = $pdo->prepare("UPDATE transactions SET product_id=?, batch_id=?, quantity_sold=?, remarks=? WHERE id=?");
        $stmt->execute([$_POST['product_id'], $_POST['batch_id'], $_POST['quantity_sold'], $_POST['remarks'], $_POST['transaction_id']]);
        header("Location: dashboard.php?section=transactions");
        exit;
    }

    // Delete Transaction
    if ($_POST['action'] === 'delete_transaction') {
        $stmt = $pdo->prepare("DELETE FROM transactions WHERE id=?");
        $stmt->execute([$_POST['transaction_id']]);
        header("Location: dashboard.php?section=transactions");
        exit;
    }
}

$currentSection = $_GET['section'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>NIPA Supply Chain Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .active-link { background:#374151; color:#d1d5db }
        .content-section { display:none }
        .content-section.active { display:block }
        .form-section { display:none }
        .form-section.active { display:block }
        .mobile-card {
            @apply bg-white rounded-lg shadow-sm p-4 mb-3 border border-gray-100;
        }
        .mobile-detail-row {
            @apply flex justify-between items-center py-2 border-b border-gray-100 last:border-0;
        }
        @media (max-width: 1023px) {
            .sidebar-hide { transform: translateX(-100%); }
            .sidebar-show { transform: translateX(0); }
        }
    </style>
</head>
<body class="bg-gray-50">

<!-- Fixed Mobile Header -->
<div class="lg:hidden fixed top-0 left-0 right-0 bg-white shadow-md z-40">
    <div class="flex items-center justify-between p-4">
        <div class="flex items-center space-x-2">
            <button id="menu-btn" class="text-gray-700 text-xl">
                <i class="fas fa-bars"></i>
            </button>
            <span class="font-bold text-lg text-gray-800">NIPA SYSTEM</span>
        </div>
        <div class="text-sm text-gray-600"><?= htmlspecialchars(explode('@', $user_email)[0]) ?></div>
    </div>
</div>

<!-- Sidebar -->
<aside id="sidebar" class="fixed top-0 left-0 h-full w-64 bg-gray-900 text-white flex flex-col z-50 transition-transform duration-300 ease-in-out sidebar-hide lg:sidebar-show">
    <div class="p-6 text-2xl font-bold border-b border-gray-800 flex items-center justify-between">
        <span class="flex items-center space-x-2">
            <i class="fas fa-tree text-green-400"></i>
            <span>NIPA SYSTEM</span>
        </span>
        <button id="close-menu-btn" class="lg:hidden text-gray-400 hover:text-white">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <nav class="flex-1 px-4 py-6 space-y-2">
        <a href="?section=dashboard" class="nav-link <?= $currentSection === 'dashboard' ? 'active-link' : '' ?> flex items-center space-x-3 w-full text-left hover:bg-gray-800 rounded-lg px-3 py-2 transition">
            <i class="fas fa-chart-line w-5"></i><span>Dashboard</span>
        </a>
        <a href="?section=products" class="nav-link <?= $currentSection === 'products' ? 'active-link' : '' ?> flex items-center space-x-3 w-full text-left hover:bg-gray-800 rounded-lg px-3 py-2 transition">
            <i class="fas fa-box-open w-5"></i><span>Products</span>
        </a>
        <a href="?section=batches" class="nav-link <?= $currentSection === 'batches' ? 'active-link' : '' ?> flex items-center space-x-3 w-full text-left hover:bg-gray-800 rounded-lg px-3 py-2 transition">
            <i class="fas fa-warehouse w-5"></i><span>Batches</span>
        </a>
        <a href="?section=transactions" class="nav-link <?= $currentSection === 'transactions' ? 'active-link' : '' ?> flex items-center space-x-3 w-full text-left hover:bg-gray-800 rounded-lg px-3 py-2 transition">
            <i class="fas fa-exchange-alt w-5"></i><span>Transactions</span>
        </a>
    </nav>

    <div class="p-4 border-t border-gray-800">
        <a href="logout.php" class="flex items-center justify-center bg-red-600 hover:bg-red-700 rounded-lg py-2 font-semibold transition">
            <i class="fas fa-sign-out-alt mr-2"></i> Log Out
        </a>
    </div>
</aside>

<!-- Main Content -->
<main class="flex-1 p-4 lg:p-6 lg:ml-64 mt-16 lg:mt-0">
    
 <section id="dashboardContent" class="content-section <?= $currentSection === 'dashboard' ? 'active' : '' ?>">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-xl lg:text-2xl font-bold text-gray-800">Supply Chain Dashboard</h1>
        
        <p class="hidden lg:block text-sm font-semibold text-gray-600">
            <?= htmlspecialchars($user_email) ?>
        </p>
    </div>


    
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <div class="bg-white p-4 rounded-lg shadow-sm">
            <i class="fas fa-cubes text-2xl text-indigo-500 mb-2"></i>
            <p class="text-xs text-gray-500">PRODUCTS</p>
            <p class="text-xl font-bold"><?= $total_products ?></p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm">
            <i class="fas fa-check-circle text-2xl text-green-600 mb-2"></i>
            <p class="text-xs text-gray-500">SOLD</p>
            <p class="text-xl font-bold text-green-600"><?= $total_batches_sold ?></p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm">
            <i class="fas fa-hourglass-half text-2xl text-yellow-600 mb-2"></i>
            <p class="text-xs text-gray-500">ACTIVE</p>
            <p class="text-xl font-bold text-yellow-600"><?= $total_batches_pending ?></p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm">
            <i class="fas fa-times-circle text-2xl text-red-600 mb-2"></i>
            <p class="text-xs text-gray-500">CANCELLED</p>
            <p class="text-xl font-bold text-red-600"><?= $total_batches_cancelled ?></p>
        </div>
    </div>
    
    <div class="space-y-4 lg:grid lg:grid-cols-3 lg:gap-4 lg:space-y-0">
        <div class="bg-white p-4 rounded-lg shadow-sm">
            <h3 class="font-bold mb-3 text-sm text-gray-700">
                <i class="fas fa-chart-pie mr-2 text-indigo-500"></i>Sales Status
            </h3>
            <canvas id="salesStatusChart"></canvas>
        </div>
        
        <div class="bg-white p-4 rounded-lg shadow-sm">
            <h3 class="font-bold mb-3 text-sm text-gray-700">
                <i class="fas fa-calendar-alt mr-2 text-indigo-500"></i>Batch Shelf Life
            </h3>
            <canvas id="statusChart"></canvas>
        </div>

        
    </div>
</section>
</section>
    
    <section id="productContent" class="content-section <?= $currentSection === 'products' ? 'active' : '' ?>">
    <div class="mb-4">
        <h2 class="text-xl lg:text-2xl font-bold text-gray-800">Product Management</h2>
    </div>

    <button onclick="toggleForm('addProductForm')" class="w-full lg:w-auto bg-indigo-600 text-white px-4 py-2 rounded-lg mb-4 hover:bg-indigo-700 transition">
        <i class="fas fa-plus-circle mr-2"></i>Add New Product
    </button>

    <div id="addProductForm" class="hidden form-section mobile-card">
        <h3 class="font-bold mb-3 text-gray-700">Add New Product</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_product">
            <input name="name" placeholder="Product Name" required class="border p-2 w-full mb-2 rounded">
            <input name="category" placeholder="Category" required class="border p-2 w-full mb-2 rounded">
            <textarea name="description" placeholder="Description" rows="3" class="border p-2 w-full mb-2 rounded"></textarea>
            <label class="block text-gray-600 text-sm mb-1">Product Image</label>
            <input name="image" type="file" accept="image/*" class="border p-2 w-full mb-4 rounded">
            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded">Save</button>
                <button type="button" onclick="toggleForm('addProductForm')" class="flex-1 px-4 py-2 bg-gray-300 rounded">Cancel</button>
            </div>
        </form>
    </div>

    <div class="lg:hidden space-y-3">
        <?php foreach ($products as $product): ?>
        <div class="mobile-card">
            <div class="flex items-start justify-between mb-3">
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900"><?= htmlspecialchars($product['name']) ?></h4>
                    <p class="text-sm text-gray-500"><?= htmlspecialchars($product['category']) ?></p>
                </div>
                <?php if($product['imageUrl']): ?>
                <img src="<?= htmlspecialchars($product['imageUrl']) ?>" alt="Product" class="w-12 h-12 rounded object-cover ml-3">
                <?php endif; ?>
            </div>
            
            <div class="mobile-detail-row">
                <span class="text-sm text-gray-500">Inventory</span>
                <span class="font-bold text-indigo-600"><?= $inventory[$product['id']] ?? 0 ?></span>
            </div>
            
            <?php if($product['description']): ?>
            <div class="mt-2 pt-2 border-t border-gray-100">
                <p class="text-sm text-gray-600"><?= htmlspecialchars(substr($product['description'], 0, 100)) ?>...</p>
            </div>
            <?php endif; ?>
            
            <div class="mt-3 pt-3 border-t border-gray-100 flex justify-end gap-2">
                <button onclick="openEditModal(<?= htmlspecialchars(json_encode($product)) ?>)" class="px-3 py-1 bg-indigo-600 text-white rounded text-sm">
                    <i class="fas fa-pen mr-1"></i>Edit
                </button>
                <form method="POST" class="inline" onsubmit="return confirm('Delete product?');">
                    <input type="hidden" name="action" value="delete_product">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded text-sm">
                        <i class="fas fa-trash mr-1"></i>Delete
                    </button>
                </form>
            </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="hidden lg:block bg-white p-6 rounded-xl shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Image</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Inventory</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($products as $product): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <img src="<?= htmlspecialchars($product['imageUrl'] ?? 'placeholder.png') ?>" alt="Product" class="h-10 w-10 rounded-full object-cover">
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900"><?= htmlspecialchars($product['name']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-gray-500"><?= htmlspecialchars($product['category']) ?></td>
                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs overflow-hidden text-ellipsis"><?= htmlspecialchars($product['description']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap font-bold text-indigo-600"><?= $inventory[$product['id']] ?? 0 ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        <button onclick="openEditModal(<?= htmlspecialchars(json_encode($product)) ?>)" class="text-indigo-600 hover:text-indigo-800 mx-1">
                            <i class="fas fa-pen"></i>
                        </button>
                        <form method="POST" class="inline" onsubmit="return confirm('Delete product?');">
                            <input type="hidden" name="action" value="delete_product">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <button type="submit" class="text-red-600 hover:text-red-800 mx-1">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="editProductModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50">
        <div class="relative mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Edit Product</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_product">
                <input type="hidden" id="editProductId" name="product_id">
                <input type="hidden" id="editCurrentImage" name="current_image">

                <div class="space-y-3">
                    <div>
                        <label for="editProductName" class="text-sm font-medium text-gray-700">Product Name</label>
                        <input id="editProductName" name="name" required class="mt-1 border p-2 w-full rounded">
                    </div>
                    <div>
                        <label for="editProductCategory" class="text-sm font-medium text-gray-700">Category</label>
                        <input id="editProductCategory" name="category" required class="mt-1 border p-2 w-full rounded">
                    </div>
                    <div>
                        <label for="editProductDescription" class="text-sm font-medium text-gray-700">Description</label>
                        <textarea id="editProductDescription" name="description" rows="3" class="mt-1 border p-2 w-full rounded"></textarea>
                    </div>
                    <div>
                        <label class="block text-gray-600 text-sm mb-1 font-medium">Current Image</label>
                        <img id="editProductImagePreview" src="" alt="Current Image" class="h-16 w-16 rounded object-cover mb-2 border">
                        <label class="block text-gray-600 text-sm mb-1">Upload New Image (optional)</label>
                        <input name="image" type="file" accept="image/*" class="border p-2 w-full rounded">
                    </div>
                </div>

                <div class="mt-4 flex gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Update Product</button>
                    <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
    const editModal = document.getElementById('editProductModal');

    /**
     * Populates the edit modal with product data and displays it.
     * @param {object} product - The product data object.
     */
    function openEditModal(product) {
        if (!editModal || !product) return;

        // Populate the form fields
        document.getElementById('editProductId').value = product.id;
        document.getElementById('editProductName').value = product.name;
        document.getElementById('editProductCategory').value = product.category;
        document.getElementById('editProductDescription').value = product.description || '';
        document.getElementById('editCurrentImage').value = product.imageUrl || '';
        
        const imagePreview = document.getElementById('editProductImagePreview');
        if (product.imageUrl) {
            imagePreview.src = product.imageUrl;
            imagePreview.classList.remove('hidden');
        } else {
            // Hide the image preview if there's no image
            imagePreview.classList.add('hidden');
        }

        // Show the modal
        editModal.classList.remove('hidden');
    }

    /**
     * Hides the edit modal.
     */
    function closeEditModal() {
        if (!editModal) return;
        editModal.classList.add('hidden');
    }

    /**
     * Toggles the visibility of a form element (like the 'Add Product' form).
     * @param {string} formId - The ID of the form element to toggle.
     */
    function toggleForm(formId) {
        const formElement = document.getElementById(formId);
        if (formElement) {
            formElement.classList.toggle('hidden');
        }
    }
</script>
    
    <section id="batchesContent" class="content-section <?= $currentSection === 'batches' ? 'active' : '' ?>">
    <div class="mb-4">
        <h2 class="text-xl lg:text-2xl font-bold text-gray-800">Batch Management</h2>
    </div>

    <button onclick="toggleForm('addBatchForm')" class="w-full lg:w-auto bg-indigo-600 text-white px-4 py-2 rounded-lg mb-4 hover:bg-indigo-700 transition">
        <i class="fas fa-plus-circle mr-2"></i>Add New Batch
    </button>

    <div id="addBatchForm" class="hidden form-section mobile-card">
        <h3 class="font-bold mb-3 text-gray-700">Add New Batch</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_batch">
            <select name="product_id" required class="border p-2 w-full mb-2 rounded">
                <option value="">Select Product</option>
                <?php foreach($products as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input name="quantity" placeholder="Quantity" type="number" required class="border p-2 w-full mb-2 rounded">
            <input name="location" placeholder="Location" class="border p-2 w-full mb-2 rounded">
            <select name="status" class="border p-2 w-full mb-2 rounded">
                <option value="Active">Active</option>
                <option value="Completed">Completed</option>
                <option value="Cancelled">Cancelled</option>
            </select>
            <label class="block text-gray-600 text-sm">Manufacture Date</label>
            <input name="manufacture_date" type="date" class="border p-2 w-full mb-2 rounded">
            <label class="block text-gray-600 text-sm">Expiry Date</label>
            <input name="expiry_date" type="date" class="border p-2 w-full mb-2 rounded">
            <div class="flex gap-2 mt-3">
                <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded">Save</button>
                <button type="button" onclick="toggleForm('addBatchForm')" class="flex-1 px-4 py-2 bg-gray-300 rounded">Cancel</button>
            </div>
        </form>
    </div>

    <div class="lg:hidden space-y-3">
        <?php foreach ($batches as $batch):
            $status_class = $batch['status'] == 'Completed' ? 'bg-green-100 text-green-800' : 
                          ($batch['status'] == 'Active' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
        ?>
        <div class="mobile-card">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <h4 class="font-semibold text-gray-900">Batch #<?= $batch['id'] ?></h4>
                    <p class="text-sm text-gray-600"><?= htmlspecialchars($batch['product_name']) ?></p>
                </div>
                <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $status_class ?>"><?= $batch['status'] ?></span>
            </div>
            
            <div class="space-y-2">
                <div class="mobile-detail-row">
                    <span class="text-sm text-gray-500">Quantity</span>
                    <span class="font-semibold"><?= $batch['quantity'] ?></span>
                </div>
                <div class="mobile-detail-row">
                    <span class="text-sm text-gray-500">Location</span>
                    <span class="text-sm"><?= htmlspecialchars($batch['location']) ?></span>
                </div>
                <?php if($batch['manufacture_date']): ?>
                <div class="mobile-detail-row">
                    <span class="text-sm text-gray-500">Manufacture</span>
                    <span class="text-sm"><?= $batch['manufacture_date'] ?></span>
                </div>
                <?php endif; ?>
                <?php if($batch['expiry_date']): ?>
                <div class="mobile-detail-row">
                    <span class="text-sm text-gray-500">Expiry</span>
                    <span class="text-sm"><?= $batch['expiry_date'] ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="mt-3 pt-3 border-t border-gray-100 flex justify-end gap-2">
                <button onclick="openEditBatchModal(<?= htmlspecialchars(json_encode($batch)) ?>)" class="px-3 py-1 bg-indigo-600 text-white rounded text-sm">
                    <i class="fas fa-pen mr-1"></i>Edit
                </button>
                <form method="POST" class="inline" onsubmit="return confirm('Delete Batch?');">
                    <input type="hidden" name="action" value="delete_batch">
                    <input type="hidden" name="batch_id" value="<?= $batch['id'] ?>">
                    <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded text-sm">
                        <i class="fas fa-trash mr-1"></i>Delete
                    </button>
                </form>
            </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="hidden lg:block bg-white p-6 rounded-xl shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($batches as $batch): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900"><?= $batch['id'] ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-gray-500"><?= htmlspecialchars($batch['product_name']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap font-semibold"><?= $batch['quantity'] ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-gray-500"><?= htmlspecialchars($batch['location']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php
                            $status_class = $batch['status'] == 'Completed' ? 'bg-green-100 text-green-800' : ($batch['status'] == 'Active' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                        ?>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $status_class ?>">
                            <?= htmlspecialchars($batch['status']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        <button onclick="openEditBatchModal(<?= htmlspecialchars(json_encode($batch)) ?>)" class="text-indigo-600 hover:text-indigo-800 mx-1">
                            <i class="fas fa-pen"></i>
                        </button>
                        <form method="POST" class="inline" onsubmit="return confirm('Delete Batch?');">
                            <input type="hidden" name="action" value="delete_batch">
                            <input type="hidden" name="batch_id" value="<?= $batch['id'] ?>">
                            <button type="submit" class="text-red-600 hover:text-red-800 mx-1">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div id="editBatchModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50">
        <div class="relative mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Edit Batch</h3>
            <form method="POST">
                <input type="hidden" name="action" value="edit_batch">
                <input type="hidden" id="editBatchId" name="batch_id">

                <div class="space-y-3">
                    <div>
                        <label for="editBatchProductId" class="text-sm font-medium text-gray-700">Product</label>
                        <select id="editBatchProductId" name="product_id" required class="mt-1 border p-2 w-full rounded">
                             <?php foreach($products as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="editBatchQuantity" class="text-sm font-medium text-gray-700">Quantity</label>
                        <input id="editBatchQuantity" name="quantity" type="number" required class="mt-1 border p-2 w-full rounded">
                    </div>
                     <div>
                        <label for="editBatchLocation" class="text-sm font-medium text-gray-700">Location</label>
                        <input id="editBatchLocation" name="location" class="mt-1 border p-2 w-full rounded">
                    </div>
                    <div>
                        <label for="editBatchStatus" class="text-sm font-medium text-gray-700">Status</label>
                        <select id="editBatchStatus" name="status" class="mt-1 border p-2 w-full rounded">
                            <option value="Active">Active</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <label for="editBatchManufactureDate" class="text-sm font-medium text-gray-700">Manufacture Date</label>
                        <input id="editBatchManufactureDate" name="manufacture_date" type="date" class="mt-1 border p-2 w-full rounded">
                    </div>
                     <div>
                        <label for="editBatchExpiryDate" class="text-sm font-medium text-gray-700">Expiry Date</label>
                        <input id="editBatchExpiryDate" name="expiry_date" type="date" class="mt-1 border p-2 w-full rounded">
                    </div>
                </div>

                <div class="mt-4 flex gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Update Batch</button>
                    <button type="button" onclick="closeEditBatchModal()" class="flex-1 px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
    const editBatchModal = document.getElementById('editBatchModal');

    /**
     * Populates the edit modal with batch data and displays it.
     * @param {object} batch - The batch data object.
     */
    function openEditBatchModal(batch) {
        if (!editBatchModal || !batch) return;

        // Populate the form fields
        document.getElementById('editBatchId').value = batch.id;
        document.getElementById('editBatchProductId').value = batch.product_id;
        document.getElementById('editBatchQuantity').value = batch.quantity;
        document.getElementById('editBatchLocation').value = batch.location || '';
        document.getElementById('editBatchStatus').value = batch.status;
        document.getElementById('editBatchManufactureDate').value = batch.manufacture_date || '';
        document.getElementById('editBatchExpiryDate').value = batch.expiry_date || '';
        
        // Show the modal
        editBatchModal.classList.remove('hidden');
    }

    /**
     * Hides the edit modal.
     */
    function closeEditBatchModal() {
        if (!editBatchModal) return;
        editBatchModal.classList.add('hidden');
    }

    /**
     * Toggles the visibility of a form element (like the 'Add Batch' form).
     * @param {string} formId - The ID of the form element to toggle.
     */
    function toggleForm(formId) {
        const formElement = document.getElementById(formId);
        if (formElement) {
            formElement.classList.toggle('hidden');
        }
    }
</script>

    <section id="transactionsContent" class="content-section <?= $currentSection === 'transactions' ? 'active' : '' ?>">
    <div class="mb-4">
        <h2 class="text-xl lg:text-2xl font-bold text-gray-800">Transaction Log</h2>
    </div>

    <button onclick="toggleForm('addTransactionForm')" class="w-full lg:w-auto bg-indigo-600 text-white px-4 py-2 rounded-lg mb-4 hover:bg-indigo-700 transition">
        <i class="fas fa-plus-circle mr-2"></i>Add New Transaction
    </button>

    <div id="addTransactionForm" class="hidden form-section mobile-card">
        <h3 class="font-bold mb-3 text-gray-700">Add New Transaction</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_transaction">
            <select name="product_id" required class="border p-2 w-full mb-2 rounded" onchange="filterBatches(this.value, 'addTransactionBatchId')">
                <option value="">Select Product</option>
                <?php foreach($products as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="batch_id" id="addTransactionBatchId" required class="border p-2 w-full mb-2 rounded">
                <option value="">Select Product First</option>
                <?php foreach($batches as $b): ?>
                    <option value="<?= $b['id'] ?>" data-product-id="<?= $b['product_id'] ?>" style="display: none;">
                        Batch #<?= $b['id'] ?> - (Qty: <?= $b['quantity'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <input name="quantity_sold" placeholder="Quantity Sold" type="number" required class="border p-2 w-full mb-2 rounded">
            <textarea name="remarks" placeholder="Remarks" rows="2" class="border p-2 w-full mb-2 rounded"></textarea>
            <div class="flex gap-2 mt-3">
                <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded">Save</button>
                <button type="button" onclick="toggleForm('addTransactionForm')" class="flex-1 px-4 py-2 bg-gray-300 rounded">Cancel</button>
            </div>
        </form>
    </div>

    <div class="lg:hidden space-y-3">
        <?php foreach ($transactions as $transaction): ?>
        <div class="mobile-card">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <h4 class="font-semibold text-gray-900">Transaction #<?= $transaction['id'] ?></h4>
                    <p class="text-sm text-gray-600"><?= htmlspecialchars($transaction['product_name']) ?></p>
                </div>
                <span class="text-red-600 font-bold">-<?= $transaction['quantity_sold'] ?></span>
            </div>
            
            <div class="space-y-2">
                <div class="mobile-detail-row">
                    <span class="text-sm text-gray-500">Batch ID</span>
                    <span class="text-indigo-600 font-semibold">#<?= $transaction['batch_id'] ?></span>
                </div>
                <div class="mobile-detail-row">
                    <span class="text-sm text-gray-500">Date</span>
                    <span class="text-sm"><?= date('M d, Y', strtotime($transaction['transaction_date'])) ?></span>
                </div>
                <?php if($transaction['remarks']): ?>
                <div class="mt-2 pt-2 border-t border-gray-100">
                    <p class="text-sm text-gray-600"><?= htmlspecialchars($transaction['remarks']) ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="mt-3 pt-3 border-t border-gray-100 flex justify-end gap-2">
                 <button onclick="openEditTransactionModal(<?= htmlspecialchars(json_encode($transaction)) ?>)" class="px-3 py-1 bg-indigo-600 text-white rounded text-sm">
                    <i class="fas fa-pen mr-1"></i>Edit
                </button>
                <form method="POST" class="inline" onsubmit="return confirm('Delete transaction?');">
                    <input type="hidden" name="action" value="delete_transaction">
                    <input type="hidden" name="transaction_id" value="<?= $transaction['id'] ?>">
                    <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded text-sm">
                        <i class="fas fa-trash mr-1"></i>Delete
                    </button>
                </form>
            </div>
             </div>
        <?php endforeach; ?>
    </div>
    
    <div class="hidden lg:block bg-white p-6 rounded-xl shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sold</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($transactions as $transaction): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900"><?= $transaction['id'] ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-indigo-600 font-semibold"><?= $transaction['batch_id'] ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-gray-500"><?= htmlspecialchars($transaction['product_name']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap font-bold text-red-600">-<?= $transaction['quantity_sold'] ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-gray-500"><?= date('Y-m-d H:i', strtotime($transaction['transaction_date'])) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        <button onclick="openEditTransactionModal(<?= htmlspecialchars(json_encode($transaction)) ?>)" class="text-indigo-600 hover:text-indigo-800 mx-1">
                            <i class="fas fa-pen"></i>
                        </button>
                        <form method="POST" class="inline" onsubmit="return confirm('Delete transaction?');">
                            <input type="hidden" name="action" value="delete_transaction">
                            <input type="hidden" name="transaction_id" value="<?= $transaction['id'] ?>">
                            <button type="submit" class="text-red-600 hover:text-red-800 mx-1">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="editTransactionModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50">
        <div class="relative mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Edit Transaction</h3>
            <form method="POST">
                <input type="hidden" name="action" value="edit_transaction">
                <input type="hidden" id="editTransactionId" name="transaction_id">

                <div class="space-y-3">
                    <div>
                        <label for="editTransactionProductId" class="text-sm font-medium text-gray-700">Product</label>
                        <select id="editTransactionProductId" name="product_id" required class="mt-1 border p-2 w-full rounded" onchange="filterBatches(this.value, 'editTransactionBatchId')">
                            <?php foreach($products as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="editTransactionBatchId" class="text-sm font-medium text-gray-700">Batch</label>
                        <select id="editTransactionBatchId" name="batch_id" required class="mt-1 border p-2 w-full rounded">
                            <option value="">Select Product First</option>
                             <?php foreach($batches as $b): ?>
                                <option value="<?= $b['id'] ?>" data-product-id="<?= $b['product_id'] ?>" style="display: none;">
                                    Batch #<?= $b['id'] ?> - (Qty: <?= $b['quantity'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="editTransactionQuantity" class="text-sm font-medium text-gray-700">Quantity Sold</label>
                        <input id="editTransactionQuantity" name="quantity_sold" type="number" required class="mt-1 border p-2 w-full rounded">
                    </div>
                     <div>
                        <label for="editTransactionRemarks" class="text-sm font-medium text-gray-700">Remarks</label>
                        <textarea id="editTransactionRemarks" name="remarks" rows="2" class="mt-1 border p-2 w-full rounded"></textarea>
                    </div>
                </div>

                <div class="mt-4 flex gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Update Transaction</button>
                    <button type="button" onclick="closeEditTransactionModal()" class="flex-1 px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
    const editTransactionModal = document.getElementById('editTransactionModal');

    /**
     * Filters the batch dropdown based on the selected product.
     * @param {string} productId - The ID of the selected product.
     * @param {string} batchSelectId - The ID of the batch select element to filter.
     */
    function filterBatches(productId, batchSelectId) {
        const batchSelect = document.getElementById(batchSelectId);
        if (!batchSelect) return;

        let hasVisibleOption = false;
        batchSelect.value = ''; // Reset selection
        
        // Loop through all batch options
        for (const option of batchSelect.options) {
            // Skip the placeholder option
            if (option.value === '') {
                option.textContent = 'Select Batch';
                continue;
            }

            if (option.dataset.productId === productId) {
                option.style.display = 'block';
                hasVisibleOption = true;
            } else {
                option.style.display = 'none';
            }
        }

        if (!hasVisibleOption) {
            batchSelect.options[0].textContent = 'No active batches for this product';
        }
    }

    /**
     * Populates the edit modal with transaction data and displays it.
     * @param {object} transaction - The transaction data object.
     */
    function openEditTransactionModal(transaction) {
        if (!editTransactionModal || !transaction) return;

        // Populate the simple form fields
        document.getElementById('editTransactionId').value = transaction.id;
        document.getElementById('editTransactionQuantity').value = transaction.quantity_sold;
        document.getElementById('editTransactionRemarks').value = transaction.remarks || '';
        
        // Set the product dropdown
        const productSelect = document.getElementById('editTransactionProductId');
        productSelect.value = transaction.product_id;

        // CRITICAL: Filter batches for the selected product *before* setting the batch value
        filterBatches(transaction.product_id, 'editTransactionBatchId');

        // Now, set the batch dropdown
        document.getElementById('editTransactionBatchId').value = transaction.batch_id;
        
        // Show the modal
        editTransactionModal.classList.remove('hidden');
    }

    /**
     * Hides the edit transaction modal.
     */
    function closeEditTransactionModal() {
        if (!editTransactionModal) return;
        editTransactionModal.classList.add('hidden');
    }

    /**
     * Toggles the visibility of a form element.
     * @param {string} formId - The ID of the form element to toggle.
     */
    function toggleForm(formId) {
        const formElement = document.getElementById(formId);
        if (formElement) {
            formElement.classList.toggle('hidden');
        }
    }
</script>
</main>

<script>
// Toggle form visibility
function toggleForm(formId) {
    const form = document.getElementById(formId);
    form.classList.toggle('active');
    // Close other forms when opening a new one
    document.querySelectorAll('.form-section').forEach(f => {
        if (f.id !== formId) f.classList.remove('active');
    });
}

// Edit Product Toggle
function toggleEditProduct(product) {
    toggleForm('editProduct' + product.id);
}

// Edit Batch Toggle
function toggleEditBatch(batch) {
    toggleForm('editBatch' + batch.id);
}

// Edit Transaction Toggle
function toggleEditTransaction(transaction) {
    toggleForm('editTransaction' + transaction.id);
    // Trigger filter for correct batch selection
    setTimeout(() => {
        filterBatches(transaction.product_id, 'edit_batch_' + transaction.id);
    }, 50);
}

// Filter batches based on product selection
function filterBatches(productId, selectId = 'transaction_batch_id') {
    const batchSelect = document.getElementById(selectId);
    if (!batchSelect) return;
    
    const options = batchSelect.options;
    for (let i = 1; i < options.length; i++) {
        const option = options[i];
        if (productId === "" || option.dataset.productId == productId) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    }
    
    // Reset selection if current selection is hidden
    if (batchSelect.value && batchSelect.options[batchSelect.selectedIndex].style.display === 'none') {
        batchSelect.value = "";
    }
}

// Mobile menu handlers
document.getElementById('menu-btn').addEventListener('click', () => {
    document.getElementById('sidebar').classList.remove('sidebar-hide');
    document.getElementById('sidebar').classList.add('sidebar-show');
});

document.getElementById('close-menu-btn').addEventListener('click', () => {
    document.getElementById('sidebar').classList.remove('sidebar-show');
    document.getElementById('sidebar').classList.add('sidebar-hide');
});

// Auto-close sidebar on mobile when clicking a link
if (window.innerWidth < 1024) {
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', () => {
            setTimeout(() => {
                document.getElementById('sidebar').classList.remove('sidebar-show');
                document.getElementById('sidebar').classList.add('sidebar-hide');
            }, 100);
        });
    });
}

// Charts initialization
window.addEventListener('DOMContentLoaded', () => {
    // Only initialize charts if we're on dashboard section
    if ('<?= $currentSection ?>' === 'dashboard') {
        // Sales Status Chart
        const salesCtx = document.getElementById('salesStatusChart');
        if (salesCtx) {
            new Chart(salesCtx, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode(array_keys($salesStatusData)) ?>,
                    datasets: [{
                        data: <?= json_encode(array_values($salesStatusData)) ?>,
                        backgroundColor: ['#10B981', '#F59E0B', '#EF4444']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 10,
                                font: { size: 11 }
                            }
                        }
                    }
                }
            });
        }

        // Shelf Life Chart
        const statusCtx = document.getElementById('statusChart');
        if (statusCtx) {
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode(array_keys($statusData)) ?>,
                    datasets: [{
                        data: <?= json_encode(array_values($statusData)) ?>,
                        backgroundColor: ['#22C55E', '#FACC15', '#EF4444']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 10,
                                font: { size: 11 }
                            }
                        }
                    }
                }
            });
        }

        // Monthly Production Chart
        const monthCtx = document.getElementById('monthChart');
        if (monthCtx) {
            new Chart(monthCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode(array_keys($monthData)) ?>,
                    datasets: [{
                        label: 'Batches',
                        data: <?= json_encode(array_values($monthData)) ?>,
                        backgroundColor: 'rgba(99, 102, 241, 0.2)',
                        borderColor: '#6366F1',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }
    }
});
</script>
</body>
</html>