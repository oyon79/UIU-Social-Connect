<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_approved']) {
    header('Location: ../index.php');
    exit;
}

$pageTitle = 'Student Marketplace - UIU Social Connect';
require_once '../includes/header.php';
?>

<style>
    body { background: var(--gray-light); }
    .main-container { margin-left: 280px; min-height: 100vh; padding: 2rem; }
    .marketplace-header { background: white; border-radius: 20px; padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow-md); display: flex; justify-content: space-between; align-items: center; }
    .marketplace-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
    .product-card { background: white; border-radius: 16px; overflow: hidden; box-shadow: var(--shadow-md); transition: all 0.3s ease; }
    .product-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); }
    .product-image { width: 100%; height: 200px; object-fit: cover; background: linear-gradient(135deg, var(--gray-light), var(--gray-medium)); }
    .product-content { padding: 1.25rem; }
    .product-price { font-size: 1.5rem; font-weight: 700; color: var(--primary-orange); margin-bottom: 0.5rem; }
    .product-title { font-weight: 600; margin-bottom: 0.5rem; }
    @media (max-width: 768px) { .main-container { margin-left: 0; } }
</style>

<?php include '../includes/sidebar.php'; ?>

<div class="main-container">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="marketplace-header animate-fade-in">
        <div>
            <h1>🛒 Student Marketplace</h1>
            <p style="color: var(--gray-dark);">Buy and sell items within the community</p>
        </div>
        <button class="btn btn-primary" onclick="openListModal()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            List Item
        </button>
    </div>

    <div class="marketplace-grid" id="marketplaceGrid">
        <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
            <div class="spinner"></div>
            <p style="margin-top: 1rem; color: var(--gray-dark);">Loading items...</p>
        </div>
    </div>
</div>

<div id="listItemModal" class="modal">
    <div class="modal-backdrop" onclick="closeListModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">List Item for Sale</h3>
            <button class="modal-close" onclick="closeListModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="listItemForm">
                <div class="form-group">
                    <label class="form-label">Item Name</label>
                    <input type="text" id="itemName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea id="itemDescription" class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Price (BDT)</label>
                    <input type="number" id="itemPrice" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select id="itemCategory" class="form-control">
                        <option value="Books">Books</option>
                        <option value="Electronics">Electronics</option>
                        <option value="Furniture">Furniture</option>
                        <option value="Clothing">Clothing</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeListModal()">Cancel</button>
            <button class="btn btn-primary" onclick="listItem()">List Item</button>
        </div>
    </div>
</div>

<script>
    loadItems();

    async function loadItems() {
        try {
            const response = await fetch('../api/marketplace.php?action=get_all');
            const data = await response.json();
            
            const grid = document.getElementById('marketplaceGrid');
            
            if (data.success && data.items && data.items.length > 0) {
                grid.innerHTML = data.items.map(item => `
                    <div class="product-card animate-scale-in">
                        <div class="product-image"></div>
                        <div class="product-content">
                            <div class="product-price">৳${item.price}</div>
                            <h3 class="product-title">${escapeHtml(item.name)}</h3>
                            <p style="color: var(--gray-dark); font-size: 0.875rem; margin-bottom: 1rem;">${escapeHtml(item.description)}</p>
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.8125rem; color: var(--gray-dark); margin-bottom: 1rem;">
                                <span>${escapeHtml(item.category)}</span>
                                <span>By ${escapeHtml(item.seller_name)}</span>
                            </div>
                            <button class="btn btn-primary btn-block" onclick="contactSeller(${item.user_id})">
                                Contact Seller
                            </button>
                        </div>
                    </div>
                `).join('');
            } else {
                grid.innerHTML = `
                    <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                        <h3>No Items Listed</h3>
                        <p style="color: var(--gray-dark);">Be the first to list an item!</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    function openListModal() {
        document.getElementById('listItemModal').classList.add('active');
    }

    function closeListModal() {
        document.getElementById('listItemModal').classList.remove('active');
    }

    async function listItem() {
        const name = document.getElementById('itemName').value.trim();
        const description = document.getElementById('itemDescription').value.trim();
        const price = document.getElementById('itemPrice').value;
        const category = document.getElementById('itemCategory').value;

        if (!name || !description || !price) {
            alert('Please fill all fields');
            return;
        }

        try {
            const response = await fetch('../api/marketplace.php?action=create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, description, price, category })
            });

            const data = await response.json();
            if (data.success) {
                closeListModal();
                alert('Item listed! Waiting for admin approval.');
                document.getElementById('listItemForm').reset();
                loadItems();
            }
        } catch (error) {
            alert('Connection error');
        }
    }

    function contactSeller(sellerId) {
        window.location.href = `messages.php?user=${sellerId}`;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>

</body>
</html>
