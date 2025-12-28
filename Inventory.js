// ScrapTrack Inventory Management JavaScript

// Sample inventory data (replace with database later)
let inventoryItems = [
    {
        id: 1,
        name: "Glass Bottle",
        category: "Babasagin",
        qtyType: "Piraso",
        quantity: 69,
        buyingPrice: 69.00,
        sellingPrice: 69.00,
        dateAdded: "2025-12-23"
    },
    {
        id: 2,
        name: "White",
        category: "Paper",
        qtyType: "Kilo",
        quantity: 69,
        buyingPrice: 69.00,
        sellingPrice: 69.00,
        dateAdded: "2025-12-23"
    },
    {
        id: 3,
        name: "White",
        category: "Paper",
        qtyType: "Kilo",
        quantity: 69,
        buyingPrice: 69.00,
        sellingPrice: 69.00,
        dateAdded: "2025-12-23"
    },
    {
        id: 4,
        name: "White",
        category: "Paper",
        qtyType: "Kilo",
        quantity: 69,
        buyingPrice: 69.00,
        sellingPrice: 69.00,
        dateAdded: "2025-12-23"
    },
    {
        id: 5,
        name: "White",
        category: "Paper",
        qtyType: "Kilo",
        quantity: 69,
        buyingPrice: 69.00,
        sellingPrice: 69.00,
        dateAdded: "2025-12-23"
    }
];

let currentEditId = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeInventory();
    setupEventListeners();
    updateStats();
});

// Initialize inventory display
function initializeInventory() {
    renderInventoryTable();
    updateStats();
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
            <td>${item.quantity}${item.qtyType === 'Kilo' ? ' kg' : ' pieces'}</td>
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
function updateStats() {
    // Calculate total items
    const totalItems = inventoryItems.reduce((sum, item) => sum + item.quantity, 0);
    document.getElementById('totalItems').textContent = totalItems;

    // Calculate total weight (only for Kilo items)
    const totalWeight = inventoryItems
        .filter(item => item.qtyType === 'Kilo')
        .reduce((sum, item) => sum + item.quantity, 0);
    document.getElementById('totalWeight').textContent = `${totalWeight} kg`;

    // Calculate total value
    const totalValue = inventoryItems.reduce((sum, item) => 
        sum + (item.quantity * item.sellingPrice), 0);
    document.getElementById('totalValue').textContent = `₱${totalValue.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

    // Update items count
    document.getElementById('itemsCount').textContent = `${inventoryItems.length} items found`;
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

    const formData = {
        name: document.getElementById('itemName').value,
        category: document.getElementById('itemCategory').value,
        qtyType: document.getElementById('qtyType').value,
        quantity: parseFloat(document.getElementById('quantity').value),
        buyingPrice: parseFloat(document.getElementById('buyingPrice').value),
        sellingPrice: parseFloat(document.getElementById('sellingPrice').value),
        dateAdded: new Date().toISOString().split('T')[0]
    };

    if (currentEditId) {
        // Update existing item
        const index = inventoryItems.findIndex(item => item.id === currentEditId);
        if (index !== -1) {
            inventoryItems[index] = { ...inventoryItems[index], ...formData };
            showNotification('Item updated successfully!', 'success');
        }
    } else {
        // Add new item
        const newItem = {
            id: inventoryItems.length > 0 ? Math.max(...inventoryItems.map(i => i.id)) + 1 : 1,
            ...formData
        };
        inventoryItems.push(newItem);
        showNotification('Item added successfully!', 'success');
    }

    renderInventoryTable();
    updateStats();
    closeItemModal();
}

// Edit item
function editItem(id) {
    const item = inventoryItems.find(i => i.id === id);
    if (!item) return;

    currentEditId = id;
    document.getElementById('modalTitle').textContent = 'Edit Item';
    document.getElementById('itemName').value = item.name;
    document.getElementById('itemCategory').value = item.category;
    document.getElementById('qtyType').value = item.qtyType;
    document.getElementById('quantity').value = item.quantity;
    document.getElementById('buyingPrice').value = item.buyingPrice;
    document.getElementById('sellingPrice').value = item.sellingPrice;
    
    document.getElementById('itemModal').classList.add('active');
}

// Delete item
function deleteItem(id) {
    if (!confirm('Are you sure you want to delete this item?')) return;

    const index = inventoryItems.findIndex(item => item.id === id);
    if (index !== -1) {
        inventoryItems.splice(index, 1);
        renderInventoryTable();
        updateStats();
        showNotification('Item deleted successfully!', 'success');
    }
}

// Handle search
function handleSearch(e) {
    const searchTerm = e.target.value.toLowerCase();
    
    if (searchTerm === '') {
        renderInventoryTable();
        return;
    }

    const filteredItems = inventoryItems.filter(item => 
        item.name.toLowerCase().includes(searchTerm) ||
        item.category.toLowerCase().includes(searchTerm) ||
        item.qtyType.toLowerCase().includes(searchTerm)
    );

    renderInventoryTable(filteredItems);
    document.getElementById('itemsCount').textContent = `${filteredItems.length} items found`;
}

// Handle filter
function handleFilter() {
    alert('Filter functionality coming soon!\n\nYou can filter by:\n- Category\n- Quantity Type\n- Date Range\n- Price Range');
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

console.log('ScrapTrack Inventory initialized successfully!');