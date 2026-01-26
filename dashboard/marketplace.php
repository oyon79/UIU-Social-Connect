<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_approved']) {
    header('Location: ../index.php');
    exit;
}

// Prevent admins from accessing user dashboard
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header('Location: ../admin/index.php');
    exit;
}

$pageTitle = 'Student Marketplace - UIU Social Connect';
require_once '../includes/header.php';
?>

<style>
    body {
        background: var(--gray-light);
    }

    .main-container {
        margin-left: 280px;
        min-height: 100vh;
        padding: 2rem;
    }

    .marketplace-header {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow-md);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .marketplace-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
    }

    .product-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: var(--shadow-md);
        transition: all 0.3s ease;
        position: relative;
    }

    .product-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-lg);
    }

    .product-image {
        width: 100%;
        height: 200px;
        object-fit: cover;
        background: linear-gradient(135deg, var(--gray-light), var(--gray-medium));
    }

    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .product-content {
        padding: 1.25rem;
    }

    .product-price {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary-orange);
        margin-bottom: 0.5rem;
    }

    .product-title {
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .product-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .product-menu {
        position: absolute;
        top: 0.75rem;
        right: 0.75rem;
    }

    .product-menu-btn {
        background: rgba(255, 255, 255, 0.9);
        border: none;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: var(--shadow-sm);
    }

    .product-menu-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border-radius: 8px;
        box-shadow: var(--shadow-lg);
        margin-top: 0.5rem;
        min-width: 150px;
        z-index: 10;
        display: none;
    }

    .product-menu-dropdown.active {
        display: block;
    }

    .product-menu-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        cursor: pointer;
        border: none;
        background: none;
        width: 100%;
        text-align: left;
        font-size: 0.875rem;
    }

    .product-menu-item:hover {
        background: var(--gray-light);
    }

    .product-menu-item.danger {
        color: var(--error);
    }

    .image-preview {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 8px;
        margin-top: 0.5rem;
    }

    .image-preview-container {
        position: relative;
    }

    .remove-image-btn {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        background: var(--error);
        color: white;
        border: none;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        cursor: pointer;
    }

    @media (max-width: 768px) {
        .main-container {
            margin-left: 0;
        }
    }
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

<!-- Create/Edit Item Modal -->
<div id="listItemModal" class="modal">
    <div class="modal-backdrop" onclick="closeListModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">List Item for Sale</h3>
            <button class="modal-close" onclick="closeListModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="listItemForm" enctype="multipart/form-data">
                <input type="hidden" id="itemId" value="">
                <div class="form-group">
                    <label class="form-label">Item Name</label>
                    <input type="text" id="itemTitle" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea id="itemDescription" class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Price (BDT)</label>
                    <input type="number" id="itemPrice" class="form-control" step="0.01" min="0" required>
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
                <div class="form-group">
                    <label class="form-label">Condition</label>
                    <select id="itemCondition" class="form-control">
                        <option value="new">New</option>
                        <option value="like-new">Like New</option>
                        <option value="good">Good</option>
                        <option value="fair">Fair</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Image</label>
                    <input type="file" id="itemImage" class="form-control" accept="image/*" onchange="previewImage(this)">
                    <div id="imagePreviewContainer" style="display: none;">
                        <div class="image-preview-container">
                            <img id="imagePreview" class="image-preview" src="" alt="Preview">
                            <button type="button" class="remove-image-btn" onclick="removeImagePreview()">×</button>
                        </div>
                    </div>
                    <div id="currentImageContainer" style="display: none;">
                        <p style="font-size: 0.875rem; color: var(--gray-dark); margin-top: 0.5rem;">Current Image:</p>
                        <div class="image-preview-container">
                            <img id="currentImage" class="image-preview" src="" alt="Current">
                            <button type="button" class="remove-image-btn" onclick="removeCurrentImage()">×</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeListModal()">Cancel</button>
            <button class="btn btn-primary" id="submitBtn" onclick="submitItem()">List Item</button>
        </div>
    </div>
</div>

<script>
    let currentUserId = <?php echo $_SESSION['user_id']; ?>;
    let editingItemId = null;

    loadItems();

    async function loadItems() {
        try {
            const response = await fetch('../api/marketplace.php?action=get_all');
            const data = await response.json();

            console.log('Marketplace API Response:', data);

            const grid = document.getElementById('marketplaceGrid');

            if (data.success && data.items && data.items.length > 0) {
                grid.innerHTML = data.items.map(item => {
                    const isOwner = item.user_id == currentUserId;
                    return `
                    <div class="product-card animate-scale-in">
                        ${isOwner ? `
                        <div class="product-menu">
                            <button class="product-menu-btn" onclick="toggleMenu(this)">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="1"></circle>
                                    <circle cx="12" cy="5" r="1"></circle>
                                    <circle cx="12" cy="19" r="1"></circle>
                                </svg>
                            </button>
                            <div class="product-menu-dropdown">
                                <button class="product-menu-item" onclick="editItem(${item.id})">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                    <span>Edit</span>
                                </button>
                                <button class="product-menu-item danger" onclick="deleteItem(${item.id})">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                    <span>Delete</span>
                                </button>
                                <button class="product-menu-item" onclick="markAsSold(${item.id})">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    <span>Mark as Sold</span>
                                </button>
                            </div>
                        </div>
                        ` : ''}
                        <div class="product-image">
                            ${item.image_url ? `<img src="../${escapeHtml(item.image_url)}" alt="${escapeHtml(item.title)}">` : ''}
                        </div>
                        <div class="product-content">
                            <div class="product-price">৳${parseFloat(item.price).toFixed(2)}</div>
                            <h3 class="product-title">${escapeHtml(item.title)}</h3>
                            <p style="color: var(--gray-dark); font-size: 0.875rem; margin-bottom: 0.5rem;">${escapeHtml(item.description)}</p>
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.8125rem; color: var(--gray-dark); margin-bottom: 1rem;">
                                <span>${escapeHtml(item.category)} • ${item.condition_status || 'good'}</span>
                                <span>By ${escapeHtml(item.seller_name)}</span>
                            </div>
                            <button class="btn btn-primary btn-block" onclick="contactSeller(${item.user_id})">
                                Contact Seller
                            </button>
                        </div>
                    </div>
                `;
                }).join('');
            } else {
                grid.innerHTML = `
                    <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 1rem; color: var(--gray-dark);">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <path d="M16 10a4 4 0 0 1-8 0"></path>
                        </svg>
                        <h3>No Items Listed</h3>
                        <p style="color: var(--gray-dark);">Be the first to list an item!</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading items:', error);
            document.getElementById('marketplaceGrid').innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                    <h3>Error Loading Items</h3>
                    <p style="color: var(--error);">${error.message}</p>
                    <button class="btn btn-primary" onclick="loadItems()" style="margin-top: 1rem;">Retry</button>
                </div>
            `;
        }
    }

    function toggleMenu(btn) {
        const dropdown = btn.nextElementSibling;
        document.querySelectorAll('.product-menu-dropdown').forEach(menu => {
            if (menu !== dropdown) menu.classList.remove('active');
        });
        dropdown.classList.toggle('active');
    }

    // Close menu when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.product-menu')) {
            document.querySelectorAll('.product-menu-dropdown').forEach(menu => {
                menu.classList.remove('active');
            });
        }
    });

    async function editItem(itemId) {
        try {
            const response = await fetch(`../api/marketplace.php?action=get_by_id&id=${itemId}`);
            const data = await response.json();

            if (data.success && data.item) {
                const item = data.item;
                editingItemId = itemId;

                document.getElementById('modalTitle').textContent = 'Edit Item';
                document.getElementById('itemId').value = item.id;
                document.getElementById('itemTitle').value = item.title;
                document.getElementById('itemDescription').value = item.description;
                document.getElementById('itemPrice').value = item.price;
                document.getElementById('itemCategory').value = item.category || 'Other';
                document.getElementById('itemCondition').value = item.condition_status || 'good';
                document.getElementById('submitBtn').textContent = 'Update Item';

                // Show current image if exists
                if (item.image_url) {
                    document.getElementById('currentImage').src = '../' + item.image_url;
                    document.getElementById('currentImageContainer').style.display = 'block';
                    document.getElementById('imagePreviewContainer').style.display = 'none';
                } else {
                    document.getElementById('currentImageContainer').style.display = 'none';
                }

                openListModal();
            } else {
                alert('Failed to load item details');
            }
        } catch (error) {
            console.error('Error loading item:', error);
            alert('Error loading item details');
        }
    }

    async function deleteItem(itemId) {
        if (!confirm('Are you sure you want to delete this item?')) return;

        try {
            const response = await fetch('../api/marketplace.php?action=delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: itemId
                })
            });

            const data = await response.json();
            if (data.success) {
                showAlert('Item deleted successfully', 'success');
                loadItems();
            } else {
                showAlert(data.message || 'Failed to delete item', 'error');
            }
        } catch (error) {
            console.error('Error deleting item:', error);
            showAlert('Connection error', 'error');
        }
    }

    async function markAsSold(itemId) {
        if (!confirm('Mark this item as sold?')) return;

        try {
            const response = await fetch('../api/marketplace.php?action=mark_sold', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: itemId
                })
            });

            const data = await response.json();
            if (data.success) {
                showAlert('Item marked as sold', 'success');
                loadItems();
            } else {
                showAlert(data.message || 'Failed to update item', 'error');
            }
        } catch (error) {
            console.error('Error marking as sold:', error);
            showAlert('Connection error', 'error');
        }
    }

    function openListModal() {
        document.getElementById('listItemModal').classList.add('active');
    }

    function closeListModal() {
        document.getElementById('listItemModal').classList.remove('active');
        document.getElementById('listItemForm').reset();
        document.getElementById('modalTitle').textContent = 'List Item for Sale';
        document.getElementById('submitBtn').textContent = 'List Item';
        document.getElementById('itemId').value = '';
        editingItemId = null;
        document.getElementById('imagePreviewContainer').style.display = 'none';
        document.getElementById('currentImageContainer').style.display = 'none';
        document.getElementById('itemImage').value = '';
    }

    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imagePreview').src = e.target.result;
                document.getElementById('imagePreviewContainer').style.display = 'block';
                document.getElementById('currentImageContainer').style.display = 'none';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function removeImagePreview() {
        document.getElementById('itemImage').value = '';
        document.getElementById('imagePreviewContainer').style.display = 'none';
    }

    function removeCurrentImage() {
        if (confirm('Remove current image?')) {
            document.getElementById('currentImageContainer').style.display = 'none';
            // Set a flag to remove image on update
            document.getElementById('itemId').setAttribute('data-remove-image', '1');
        }
    }

    async function submitItem() {
        const title = document.getElementById('itemTitle').value.trim();
        const description = document.getElementById('itemDescription').value.trim();
        const price = document.getElementById('itemPrice').value;
        const category = document.getElementById('itemCategory').value;
        const condition = document.getElementById('itemCondition').value;
        const itemId = document.getElementById('itemId').value;
        const removeImage = document.getElementById('itemId').getAttribute('data-remove-image') === '1';

        if (!title || !description || !price || parseFloat(price) <= 0) {
            alert('Please fill all fields with valid values');
            return;
        }

        const formData = new FormData();
        formData.append('title', title);
        formData.append('description', description);
        formData.append('price', price);
        formData.append('category', category);
        formData.append('condition_status', condition);

        if (itemId) {
            formData.append('id', itemId);
            if (removeImage) {
                formData.append('remove_image', '1');
            }
        }

        const imageFile = document.getElementById('itemImage').files[0];
        if (imageFile) {
            formData.append('image', imageFile);
        }

        try {
            const action = itemId ? 'update' : 'create';
            const response = await fetch(`../api/marketplace.php?action=${action}`, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                closeListModal();
                showAlert(data.message || (itemId ? 'Item updated successfully' : 'Item listed! Waiting for admin approval.'), 'success');
                loadItems();
            } else {
                showAlert(data.message || 'Failed to save item', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showAlert('Connection error', 'error');
        }
    }

    function contactSeller(sellerId) {
        window.location.href = `messages.php?user=${sellerId}`;
    }

    function showAlert(message, type) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} animate-slide-down`;
        alert.style.position = 'fixed';
        alert.style.top = '20px';
        alert.style.right = '20px';
        alert.style.zIndex = '9999';
        alert.innerHTML = `<span>${message}</span>`;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 3000);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>

</body>

</html>