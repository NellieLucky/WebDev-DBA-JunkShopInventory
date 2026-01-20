// ScrapTrack Transaction Management JavaScript

// Transaction data storage
let transactionItems = [];
let currentEditId = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    updateSummary();
    updateStockInfo();
    loadInventoryItems();
    loadCustomers();
});

// Setup all event listeners
function setupEventListeners() {
    // Operation type change
    document.getElementById('operationType').addEventListener('change', function() {
        updateOperationTitle();
    });

    // Item select change
    document.getElementById('itemSelect').addEventListener('change', updateStockInfo);

    // Add button
    document.getElementById('addItemBtn').addEventListener('click', addTransactionItem);

    // Save button
    document.getElementById('saveBtn').addEventListener('click', saveTransaction);

    // Clear button
    document.getElementById('clearBtn').addEventListener('click', clearTransaction);

    // Quantity input enter key
    document.getElementById('quantity').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            addTransactionItem();
        }
    });
}

// Load inventory items from database
function loadInventoryItems() {
    fetch('transaction_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_inventory'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            populateItemSelect(data.data);
        } else {
            showNotification('Failed to load inventory items', 'error');
        }
    })
    .catch(error => {
        console.error('Error loading inventory:', error);
        showNotification('Error loading inventory items', 'error');
    });
}

// Load customers from database
function loadCustomers() {
    fetch('transaction_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_customers'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            populateCustomerSelect(data.data);
        } else {
            showNotification('Failed to load customers', 'error');
        }
    })
    .catch(error => {
        console.error('Error loading customers:', error);
        showNotification('Error loading customers', 'error');
    });
}

function populateCustomerSelect(customers) {
    const select = document.getElementById('customerSelect');
    if (!select) return;
    const current = select.value;
    select.innerHTML = '<option value="">-- Select a Customer --</option>';
    customers.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = c.name;
        select.appendChild(opt);
    });

    // Auto-select last added by name if available
    if (window.lastAddedCustomerName) {
        const options = Array.from(select.options);
        const match = options.find(o => o.textContent && o.textContent.toLowerCase() === String(window.lastAddedCustomerName).toLowerCase());
        if (match) {
            select.value = match.value;
            showNotification('Selected newly added customer', 'info');
        }
    } else if (current) {
        select.value = current;
    }
}

// Populate item select dropdown
function populateItemSelect(items) {
    const select = document.getElementById('itemSelect');
    // Clear existing options except the first one
    select.innerHTML = '<option value="">-- Select an Item --</option>';

    items.forEach(item => {
        const option = document.createElement('option');
        option.value = item.id;
        option.setAttribute('data-name', item.name);
        option.setAttribute('data-category', item.category);
        option.setAttribute('data-stock', item.quantity);
        option.setAttribute('data-buying-price', item.buying_price);
        option.setAttribute('data-selling-price', item.selling_price);
        option.setAttribute('data-type', determineQtyType(item.category));
        option.textContent = item.name;
        select.appendChild(option);
    });
}

// Setup all event listeners
function setupEventListeners() {
    // Operation type change
    document.getElementById('operationType').addEventListener('change', function() {
        updateOperationTitle();
    });

    // Item select change
    document.getElementById('itemSelect').addEventListener('change', updateStockInfo);

    // Add button
    document.getElementById('addItemBtn').addEventListener('click', addTransactionItem);

    // Save button
    document.getElementById('saveBtn').addEventListener('click', saveTransaction);

    // Clear button
    document.getElementById('clearBtn').addEventListener('click', clearTransaction);

    // Quantity input enter key
    document.getElementById('quantity').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            addTransactionItem();
        }
    });
}

// Determine quantity type based on category
function determineQtyType(category) {
    // You can customize this logic based on your categories
    const weightCategories = ['Paper', 'Metals', 'Plastics'];
    return weightCategories.includes(category) ? 'Kilo' : 'Piraso';
}

// Update operation title
function updateOperationTitle() {
    const operationType = document.getElementById('operationType').value;
    const title = document.getElementById('operationTitle');
    
    if (operationType === 'receiving') {
        title.textContent = 'Receiving';
    } else if (operationType === 'dispatching') {
        title.textContent = 'Dispatching';
    } else {
        title.textContent = 'Receiving / Dispatching';
    }
}

// Update stock info
function updateStockInfo() {
    const select = document.getElementById('itemSelect');
    const stockInfo = document.getElementById('stockInfo');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption.value) {
        const stock = selectedOption.getAttribute('data-stock');
        const qtyType = selectedOption.getAttribute('data-type');
        const unit = qtyType === 'Kilo' ? 'kg' : 'pieces';
        stockInfo.textContent = `(There is ${stock}${unit} current stock)`;
    } else {
        stockInfo.textContent = '(Select an item to see stock)';
    }
}

// Add transaction item
function addTransactionItem() {
    const operationType = document.getElementById('operationType').value;
    const itemSelect = document.getElementById('itemSelect');
    const quantity = document.getElementById('quantity').value;

    // Validation
    if (!operationType) {
        showNotification('Please select an operation type', 'error');
        return;
    }

    if (!itemSelect.value) {
        showNotification('Please select an item', 'error');
        return;
    }

    if (!quantity || quantity <= 0) {
        showNotification('Please enter a valid quantity', 'error');
        return;
    }

    // Get item data
    const selectedOption = itemSelect.options[itemSelect.selectedIndex];
    const itemData = {
        id: Date.now(), // Temporary ID for UI
        itemId: itemSelect.value,
        name: selectedOption.getAttribute('data-name'),
        category: selectedOption.getAttribute('data-category'),
        qtyType: determineQtyType(selectedOption.getAttribute('data-category')),
        currentQty: parseInt(selectedOption.getAttribute('data-stock')),
        exchangeQty: parseInt(quantity),
        price: parseFloat(selectedOption.getAttribute('data-' + (operationType === 'receiving' ? 'buying' : 'selling') + '-price')),
        amount: parseFloat(selectedOption.getAttribute('data-' + (operationType === 'receiving' ? 'buying' : 'selling') + '-price')) * parseInt(quantity)
    };

    // Add to local array for UI
    transactionItems.push(itemData);

    // Update UI
    renderTransactionTable();
    updateSummary();

    // Clear quantity input
    document.getElementById('quantity').value = '';

    showNotification('Item added successfully!', 'success');
}

// Render transaction table
function renderTransactionTable() {
    const tbody = document.getElementById('transactionTableBody');
    
    if (transactionItems.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: #a0aec0;">No items added yet</td></tr>';
        return;
    }
    
    tbody.innerHTML = transactionItems.map(item => {
        const unit = item.qtyType === 'Kilo' ? 'kg' : ' pieces';
        return `
            <tr data-id="${item.id}">
                <td>${item.name}</td>
                <td>${item.category}</td>
                <td>${item.qtyType}</td>
                <td>${item.currentQty}${unit}</td>
                <td>${item.exchangeQty}${unit}</td>
                <td>₱${item.price.toFixed(2)}</td>
                <td>₱${item.amount.toFixed(2)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn edit-btn" onclick="editTransaction(${item.id})" title="Edit">✏️</button>
                        <button class="action-btn delete-btn" onclick="deleteTransaction(${item.id})" title="Delete">🗑️</button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// Update summary
function updateSummary() {
    // Calculate total items
    const totalItems = transactionItems.reduce((sum, item) => sum + item.exchangeQty, 0);
    document.getElementById('totalItems').textContent = totalItems;
    
    // Calculate total value
    const totalValue = transactionItems.reduce((sum, item) => sum + item.amount, 0);
    document.getElementById('totalValue').textContent = `₱${totalValue.toFixed(2)}`;
}

// Edit transaction
function editTransaction(id) {
    const item = transactionItems.find(i => i.id === id);
    if (!item) return;
    
    const newQty = prompt(`Edit quantity for ${item.name}\nCurrent: ${item.exchangeQty}`, item.exchangeQty);
    
    if (newQty && newQty > 0) {
        item.exchangeQty = parseInt(newQty);
        item.amount = item.price * item.exchangeQty;
        
        renderTransactionTable();
        updateSummary();
        showNotification('Item updated successfully!', 'success');
    }
}

// Delete transaction
function deleteTransaction(id) {
    if (!confirm('Are you sure you want to remove this item from the transaction?')) return;
    
    const index = transactionItems.findIndex(item => item.id === id);
    if (index !== -1) {
        transactionItems.splice(index, 1);
        renderTransactionTable();
        updateSummary();
        showNotification('Item removed successfully!', 'success');
    }
}

// Save transaction
function saveTransaction() {
    const operationType = document.getElementById('operationType').value;
    const customerSelect = document.getElementById('customerSelect');
    const customerId = customerSelect ? customerSelect.value : '';
    const customerName = customerSelect && customerSelect.options[customerSelect.selectedIndex] ? customerSelect.options[customerSelect.selectedIndex].text : '';

    // Validation
    if (!operationType) {
        showNotification('Please select an operation type', 'error');
        return;
    }

    if (!customerId) {
        showNotification('Please select a registered customer', 'error');
        return;
    }

    if (transactionItems.length === 0) {
        showNotification('Please add at least one item', 'error');
        return;
    }

    // Create transaction and add all items
    createAndCompleteTransaction(operationType, customerId, customerName);
}

// Create transaction and add all items
function createAndCompleteTransaction(operationType, customerId, customerName) {
    // First create the transaction
    const formData = new FormData();
    formData.append('action', 'create_transaction');
    formData.append('customer_id', customerId);
    formData.append('operation_type', operationType);

    fetch('transaction_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const transactionId = data.transaction_id;
            // Now add all items to the transaction
            addAllItemsToTransaction(transactionId, operationType, customerName);
        } else {
            showNotification('Failed to create transaction: ' + (data.error || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Error creating transaction:', error);
        showNotification('Error creating transaction', 'error');
    });
}

// Add all items to the transaction
function addAllItemsToTransaction(transactionId, operationType, customerName) {
    let completedItems = 0;
    const totalItems = transactionItems.length;

    transactionItems.forEach(item => {
        const formData = new FormData();
        formData.append('action', 'add_transaction_item');
        formData.append('transaction_id', transactionId);
        formData.append('item_id', item.itemId);
        formData.append('quantity', item.exchangeQty);
        formData.append('operation_type', operationType);

        fetch('transaction_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            completedItems++;
            if (!data.success) {
                showNotification('Failed to add item "' + item.name + '": ' + (data.error || 'Unknown error'), 'error');
                return;
            }

            // If all items are added, complete the transaction
            if (completedItems === totalItems) {
                completeTransaction(transactionId, operationType, customerName);
            }
        })
        .catch(error => {
            console.error('Error adding item:', error);
            showNotification('Error adding item "' + item.name + '"', 'error');
        });
    });
}

// Complete transaction in database
function completeTransaction(transactionId, operationType, customerName) {
    const formData = new FormData();
    formData.append('action', 'complete_transaction');
    formData.append('transaction_id', transactionId);

    fetch('transaction_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Generate and show invoice
            generateInvoice(operationType, customerName, transactionId);
            showNotification('Transaction completed successfully!', 'success');

            // Reset transaction UI without confirmation
            resetTransactionUI();
        } else {
            showNotification('Failed to complete transaction: ' + (data.error || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Error completing transaction:', error);
        showNotification('Error completing transaction', 'error');
    });
}

// Clear transaction
function clearTransaction() {
    if (transactionItems.length === 0) {
        showNotification('Transaction is already empty', 'info');
        return;
    }

    if (!confirm('Are you sure you want to clear all items?')) return;

    resetTransactionUI();
    showNotification('Transaction cleared!', 'info');
}

// Reset transaction UI (used after successful save)
function resetTransactionUI() {
    // Reset UI
    transactionItems = [];
    const cs = document.getElementById('customerSelect');
    if (cs) cs.value = '';
    document.getElementById('operationType').value = '';
    document.getElementById('itemSelect').value = '';
    document.getElementById('quantity').value = '';

    renderTransactionTable();
    updateSummary();
    updateOperationTitle();
    updateStockInfo();
}

// Generate invoice
function generateInvoice(operationType, customerName, transactionId) {
    // Set invoice data
    const today = new Date();
    const dateStr = `${today.getMonth() + 1} / ${today.getDate()} / ${today.getFullYear()}`;

    document.getElementById('invoiceDate').textContent = dateStr;
    document.getElementById('invoiceId').textContent = transactionId;
    document.getElementById('invoiceTo').textContent = customerName;
    document.getElementById('invoiceType').textContent = operationType.charAt(0).toUpperCase() + operationType.slice(1);
    
    // Calculate quantities
    const qtyByPiece = transactionItems
        .filter(item => item.qtyType === 'Piraso')
        .reduce((sum, item) => sum + item.exchangeQty, 0);
    
    const qtyByWeight = transactionItems
        .filter(item => item.qtyType === 'Kilo')
        .reduce((sum, item) => sum + item.exchangeQty, 0);
    
    document.getElementById('invoiceQtyPiece').textContent = qtyByPiece;
    document.getElementById('invoiceQtyWeight').textContent = qtyByWeight;
    document.getElementById('invoiceItems').textContent = transactionItems.length;
    
    // Generate invoice table
    const invoiceTableBody = document.getElementById('invoiceTableBody');
    invoiceTableBody.innerHTML = transactionItems.map(item => {
        const unit = item.qtyType === 'Kilo' ? 'kg' : ' pieces';
        return `
            <tr>
                <td>${item.name}</td>
                <td>₱${item.price.toFixed(2)}</td>
                <td>${item.exchangeQty}${unit}</td>
                <td>₱${item.amount.toFixed(2)}</td>
            </tr>
        `;
    }).join('');
    
    // Calculate total
    const totalAmount = transactionItems.reduce((sum, item) => sum + item.amount, 0);
    document.getElementById('invoiceTotal').textContent = `₱${totalAmount.toFixed(2)}`;
    
    // Show invoice modal
    document.getElementById('invoiceModal').classList.add('active');
    
    showNotification('Transaction saved! Invoice generated.', 'success');
}

// Close invoice
function closeInvoice() {
    document.getElementById('invoiceModal').classList.remove('active');
}

// Print invoice
function printInvoice() {
    window.print();
}

// Show notification
function showNotification(message, type = 'info') {
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
        max-width: 300px;
    `;
    notification.textContent = message;

    document.body.appendChild(notification);

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

console.log('ScrapTrack Transaction initialized successfully!');