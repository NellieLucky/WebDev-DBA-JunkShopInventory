// ScrapTrack Inventory Management JavaScript

let inventoryItems = [];
let currentEditId = null;
let currentInventoryFilter = 0; // Track current filter index (0 = All Items)
let currentSelectedCategory = null; // Track currently selected category filter
let currentSelectedQtyType = null; // Track currently selected quantity type filter

// Fetch inventory from PHP and update the table
function loadInventoryFromPHP() {
    console.log('loadInventoryFromPHP() CALLED');

    fetch('./inventory_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'get_all' })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && Array.isArray(data.data)) {
            // Map PHP response to JS format
            inventoryItems = data.data.map(item => ({
                id: item.id,
                name: item.name,
                categoryId: item.category_id,
                category: item.category,
                qtyType: item.qty_type,
                quantity: item.quantity,
                buyingPrice: parseFloat(item.buying_price),
                sellingPrice: parseFloat(item.selling_price),
                dateAdded: item.date_added ? item.date_added : new Date().toISOString().split('T')[0]
            }));
            renderInventoryTable(inventoryItems);
            updateStats();
        } else {
            console.error('Failed to load inventory:', data);
            renderInventoryTable([]);
        }
    })
    .catch(err => {
        console.error('Error fetching inventory:', err);
        renderInventoryTable([]);
    });
}

// Load categories and populate select
function loadCategories() {
    const select = document.getElementById('itemCategory');
    
    // Only load if not already populated
    if (select.options.length > 1) {
        return; // Already has options from PHP
    }
    
    fetch('./Inventory.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'get_categories' })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && Array.isArray(data.data)) {
            select.innerHTML = '<option value="">Select Category</option>';
            data.data.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.CategoryID;
                option.textContent = cat.Category_Name;
                select.appendChild(option);
            });
        }
    })
    .catch(err => console.error('Error loading categories:', err));
}

// Setup all event listeners
function setupEventListeners() {
    // Add Item Button
    const addItemBtn = document.getElementById('addItemBtn');
    addItemBtn.addEventListener('click', openAddModal);

    // Close Modal
    const closeModal = document.getElementById('closeModal');
    closeModal.addEventListener('click', closeItemModal);

    // Cancel Button
    const cancelBtn = document.getElementById('cancelBtn');
    cancelBtn.addEventListener('click', closeItemModal);

    // Form Submit
    const itemForm = document.getElementById('itemForm');
    itemForm.addEventListener('submit', handleFormSubmit);

    // Search Input
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', handleSearch);

    // Filter Button
    const filterBtn = document.getElementById('filterBtn');
    filterBtn.addEventListener('click', handleFilter);

    // Close modal when clicking outside
    const modal = document.getElementById('itemModal');
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeItemModal();
        }
    });

    loadCategories();
}

// Render inventory table
function renderInventoryTable(items = inventoryItems) {
    const tbody = document.getElementById('inventoryTableBody');
    
    if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: #a0aec0;">No items found</td></tr>';
        return;
    }

    tbody.innerHTML = items.map(item => `
        <tr>
            <td>${item.name}</td>
            <td>${item.category}</td>
            <td>${item.qtyType}</td>
            <td>${item.quantity}${item.qtyType === 'by kilo' ? ' kg' : ' pieces'}</td>
            <td style="text-align: left;">₱${item.buyingPrice.toFixed(2)}</td>
            <td style="text-align: left;">₱${item.sellingPrice.toFixed(2)}</td>
            <td>${formatDate(item.dateAdded)}</td>
            <td>
                <div class="action-buttons">
                    <button class="action-btn edit-btn" onclick="editItem(${item.id})" title="Edit">
                        <span>✏️</span>
                    </button>
                    <button class="action-btn delete-btn" onclick="deleteItem(${item.id})" title="Delete">
                        <span>🗑️</span>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Format date to MM-DD-YYYY
function formatDate(dateString) {
    const date = new Date(dateString);
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');
    const year = date.getFullYear();
    return `${month}-${day}-${year}`;
}

// Update statistics
function updateStats(items = inventoryItems) {
    // Calculate total items
    const TotalItems = items
        .filter(item => item.qtyType === 'by piece')
        .reduce((sum, item) => sum + item.quantity, 0);
    document.getElementById('TotalItems').textContent = TotalItems;

    // Calculate total weight (only for Kilo items)
    const TotalWeight = items
        .filter(item => item.qtyType === 'by kilo')
        .reduce((sum, item) => sum + item.quantity, 0);
    document.getElementById('TotalWeight').textContent = `${TotalWeight} kg`;

    // Calculate selling price
    const TotalSellingPrice = items.reduce((sum, item) => 
        sum + (item.quantity * item.sellingPrice), 0);
    document.getElementById('TotalSellingPrice').textContent = `₱${TotalSellingPrice.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

    // Update items count
    document.getElementById('itemsCount').textContent = `${items.length} items found`;
}

// Open add modal
function openAddModal() {
    currentEditId = null;
    document.getElementById('modalTitle').textContent = 'Add New Item';
    document.getElementById('itemForm').reset();
    document.getElementById('itemModal').classList.add('active');
}

// Close modal
function closeItemModal() {
    document.getElementById('itemModal').classList.remove('active');
    document.getElementById('itemForm').reset();
    currentEditId = null;
}

// Handle form submit
function handleFormSubmit(e) {
    e.preventDefault();

    const formData = new FormData(document.getElementById('itemForm'));
    formData.append('action', currentEditId ? 'update' : 'add');
    if (currentEditId) formData.append('id', currentEditId);

    fetch('./inventory_api.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadInventoryFromPHP();
                closeItemModal();
                showNotification(data.message || (currentEditId ? 'Item updated' : 'Item added'), 'success');
            } else {
                showNotification(data.message || 'Error', 'error');
            }
        })
        .catch(err => {
            console.error('Error submitting form:', err);
            showNotification('Error submitting form', 'error');
        });
}

// Edit item
function editItem(id) {
    const item = inventoryItems.find(i => i.id === id);
    if (!item) return;

    currentEditId = id;
    document.getElementById('modalTitle').textContent = 'Edit Item';
    document.getElementById('itemName').value = item.name;
    document.getElementById('itemCategory').value = item.categoryId;
    document.getElementById('qtyType').value = item.qtyType;
    document.getElementById('quantity').value = item.quantity;
    document.getElementById('buyingPrice').value = item.buyingPrice;
    document.getElementById('sellingPrice').value = item.sellingPrice;
    
    document.getElementById('itemModal').classList.add('active');
}

// Delete item
function deleteItem(id) {
    if (!confirm('Are you sure you want to delete this item?')) return;

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);

    fetch('./inventory_api.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadInventoryFromPHP();
                showNotification(data.message || 'Item deleted', 'success');
            } else {
                showNotification(data.message || 'Error deleting', 'error');
            }
        })
        .catch(err => {
            console.error('Error deleting item:', err);
            showNotification('Error deleting item', 'error');
        });
}

// Handle search
function handleSearch(e) {
    const searchTerm = e.target.value.toLowerCase();
    
    if (searchTerm === '') {
        renderInventoryTable(inventoryItems);
        updateStats(inventoryItems);
        return;
    }

    const filteredItems = inventoryItems.filter(item => 
        (item.name && item.name.toLowerCase().includes(searchTerm)) ||
        (item.category && item.category.toLowerCase().includes(searchTerm)) ||
        (item.qtyType && item.qtyType.toLowerCase().includes(searchTerm)) ||
        (item.quantity && item.quantity.toString().includes(searchTerm)) ||
        (item.buyingPrice && item.buyingPrice.toString().includes(searchTerm)) ||
        (item.sellingPrice && item.sellingPrice.toString().includes(searchTerm)) ||
        (item.dateAdded && item.dateAdded.toLowerCase().includes(searchTerm))
    );

    renderInventoryTable(filteredItems);
    updateStats(filteredItems);
    document.getElementById('itemsCount').textContent = `${filteredItems.length} items found`;
}

// Handle filter
function handleFilter() {
    const filterOptions = [
        'All Items',
        'By Category',
        'By Quantity Type',
        'Recently Added'
    ];
    
    showFilterModal('Filter Inventory', filterOptions, (index) => {
        currentInventoryFilter = index; // Track current filter
        if (index === 0) { // All Items
            currentSelectedCategory = null;
            currentSelectedQtyType = null;
            renderInventoryTable(inventoryItems);
            updateStats(inventoryItems);
            showNotification('Showing all inventory items', 'info');
        } else if (index === 1) { // By Category
            const categories = [...new Set(inventoryItems.map(item => item.category))].filter(Boolean);
            showCategoryFilterModal(categories);
        } else if (index === 2) { // By Quantity Type
            const qtyTypes = [...new Set(inventoryItems.map(item => item.qtyType))].filter(Boolean);
            showQtyTypeFilterModal(qtyTypes);
        } else if (index === 3) { // Recently Added
            currentSelectedCategory = null;
            currentSelectedQtyType = null;
            const sorted = [...inventoryItems].sort((a, b) => new Date(b.dateAdded) - new Date(a.dateAdded));
            renderInventoryTable(sorted);
            updateStats(sorted);
            showNotification('Showing recently added items first', 'info');
        }
    }, currentInventoryFilter);
}

function showCategoryFilterModal(categories) {
    // Find the index of the currently selected category
    const currentCategoryIndex = currentSelectedCategory ? categories.indexOf(currentSelectedCategory) : -1;
    
    showFilterModal('Select Category', categories, (index) => {
        const selectedCategory = categories[index];
        currentSelectedCategory = selectedCategory; // Track selected category
        currentInventoryFilter = 1; // Mark as "By Category" filter
        const filtered = inventoryItems.filter(item => item.category === selectedCategory);
        renderInventoryTable(filtered);
        updateStats(filtered);
        showNotification(`Showing ${filtered.length} items in ${selectedCategory}`, 'info');
    }, currentCategoryIndex);
}

function showQtyTypeFilterModal(qtyTypes) {
    // Find the index of the currently selected quantity type
    const currentQtyTypeIndex = currentSelectedQtyType ? qtyTypes.indexOf(currentSelectedQtyType) : -1;
    
    showFilterModal('Select Quantity Type', qtyTypes, (index) => {
        const selectedType = qtyTypes[index];
        currentSelectedQtyType = selectedType; // Track selected quantity type
        currentInventoryFilter = 2; // Mark as "By Quantity Type" filter
        const filtered = inventoryItems.filter(item => item.qtyType === selectedType);
        renderInventoryTable(filtered);
        updateStats(filtered);
        showNotification(`Showing ${filtered.length} items measured in ${selectedType}`, 'info');
    }, currentQtyTypeIndex);
}

// Generic filter modal function
function showFilterModal(title, options, callback, currentFilterIndex = -1) {
    const modal = document.createElement('div');
    modal.className = 'filter-modal-overlay';
    modal.innerHTML = `
        <div class="filter-modal">
            <div class="filter-modal-header">
                <h2>${title}</h2>
                <button class="filter-modal-close">&times;</button>
            </div>
            <div class="filter-modal-body">
                <div class="filter-options">
                    ${options.map((opt, i) => `
                        <button class="filter-option ${i === currentFilterIndex ? 'active' : ''}" data-index="${i}">
                            <span class="filter-number">${i + 1}</span>
                            <span class="filter-text">${opt}</span>
                        </button>
                    `).join('')}
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    modal.querySelector('.filter-modal-close').addEventListener('click', () => modal.remove());
    modal.addEventListener('click', (e) => {
        if (e.target === modal) modal.remove();
    });
    
    modal.querySelectorAll('.filter-option').forEach(btn => {
        btn.addEventListener('click', () => {
            const index = parseInt(btn.getAttribute('data-index'));
            callback(index);
            modal.remove();
        });
    });
}

// Show notification
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 24px;
        background: ${type === 'success' ? '#48bb78' : type === 'error' ? '#f56565' : '#4299e1'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 10000;
        animation: slideIn 0.3s ease-out;
    `;
    notification.textContent = message;

    document.body.appendChild(notification);

    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Manual initialization
setupEventListeners();
loadInventoryFromPHP();


console.log('ScrapTrack Inventory initialized successfully!');