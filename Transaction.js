// ScrapTrack Transaction Management JavaScript

// Transaction data storage
let transactionItems = [];
let currentEditId = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Transaction page initialized');
    try {
        setupEventListeners();
        updateSummary();
        updateStockInfo();
        loadInventoryItems();
    } catch (error) {
        console.error('Error initializing transaction page:', error);
        showNotification('Error initializing page: ' + error.message, 'error');
    }
});

// Setup all event listeners
function setupEventListeners() {
    // Operation type change - RESET TABLE when changing operation
    document.getElementById('operationType').addEventListener('change', function() {
        const newOperation = this.value;
        
        if (transactionItems.length > 0) {
            if (!confirm('Changing operation type will clear the current transaction items. Continue?')) {
                // Revert to previous operation
                this.value = transactionItems[0]?.operation || '';
                return;
            }
        }
        
        // Clear transaction
        transactionItems = [];
        renderTransactionTable();
        updateSummary();
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

    // Print button
    const printBtn = document.getElementById('printBtn');
    if (printBtn) {
        printBtn.addEventListener('click', printTransaction);
    }

    // Quantity input enter key
    document.getElementById('quantity').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            addTransactionItem();
        }
    });
}

// Load inventory items from database
function loadInventoryItems() {
    const select = document.getElementById('itemSelect');
    if (!select) {
        console.error('Item select element not found');
        return;
    }
    
    // Show loading state
    select.innerHTML = '<option value="">Loading items...</option>';
    select.disabled = true;
    
    fetch('transaction_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_inventory'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        select.disabled = false;
        if (data.success && Array.isArray(data.data)) {
            populateItemSelect(data.data);
        } else {
            select.innerHTML = '<option value="">Failed to load items</option>';
            console.error('Failed to load inventory:', data);
            showNotification(data.error || 'Failed to load inventory items', 'error');
        }
    })
    .catch(error => {
        select.disabled = false;
        select.innerHTML = '<option value="">Error loading items</option>';
        console.error('Error loading inventory:', error);
        showNotification('Error loading inventory items: ' + error.message, 'error');
    });
}

// Populate item select dropdown
function populateItemSelect(items) {
    const select = document.getElementById('itemSelect');
    // Clear existing options except the first one
    select.innerHTML = '<option value="">-- Select an Item --</option>';

    if (items.length === 0) {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'No items available';
        select.appendChild(option);
        return;
    }

    items.forEach(item => {
        const option = document.createElement('option');
        option.value = item.id;
        option.setAttribute('data-name', item.name);
        option.setAttribute('data-category', item.category);
        option.setAttribute('data-stock', item.quantity);
        option.setAttribute('data-qty-type', item.qty_type || 'by piece');
        option.setAttribute('data-buying-price', item.buying_price);
        option.setAttribute('data-selling-price', item.selling_price);
        option.textContent = item.name;
        select.appendChild(option);
    });
}

// Setup all event listeners (REMOVE DUPLICATE)
function setupEventListeners_OLD() {
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
        const qtyType = selectedOption.getAttribute('data-qty-type') || 'by piece';
        const unit = (qtyType === 'by kilo' || qtyType === 'Kilo') ? ' kg' : ' pieces';
        stockInfo.textContent = `(There is ${stock}${unit} current stock)`;
    } else {
        stockInfo.textContent = '(Select an item to see stock)';
    }
}

// Add transaction item
function addTransactionItem() {
    const operationType = document.getElementById('operationType').value;
    const itemSelect = document.getElementById('itemSelect');
    const quantity = parseInt(document.getElementById('quantity').value);

    // Validation
    if (!operationType) {
        showNotification('Please select an operation type (Receiving or Dispatching)', 'error');
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
    const currentStock = parseInt(selectedOption.getAttribute('data-stock'));
    const itemId = itemSelect.value;
    const itemName = selectedOption.getAttribute('data-name');

    // DISPATCHING: Validate stock is available
    if (operationType === 'dispatching') {
        if (quantity > currentStock) {
            showStockErrorPopup(itemName, currentStock);
            return;
        }
    }

    // Check if item already exists in cart
    const existingItem = transactionItems.find(item => item.itemId === itemId);
    
    if (existingItem) {
        if (operationType === 'dispatching') {
            // DISPATCHING: Check if total would exceed stock
            const totalQty = existingItem.exchangeQty + quantity;
            if (totalQty > currentStock) {
                showStockErrorPopup(itemName, currentStock);
                return;
            }
        }
        // RECEIVING or DISPATCHING with sufficient stock: Append quantity
        existingItem.exchangeQty += quantity;
        existingItem.amount = existingItem.price * existingItem.exchangeQty;
    } else {
        // New item - add to cart
        const itemData = {
            id: Date.now(),
            itemId: itemId,
            name: itemName,
            category: selectedOption.getAttribute('data-category'),
            qtyType: selectedOption.getAttribute('data-qty-type') || 'by kilo',
            currentQty: currentStock,
            exchangeQty: quantity,
            price: parseFloat(selectedOption.getAttribute('data-' + (operationType === 'receiving' ? 'buying' : 'selling') + '-price')),
            amount: 0,
            operation: operationType
        };
        itemData.amount = itemData.price * itemData.exchangeQty;
        transactionItems.push(itemData);
    }

    // Update UI
    renderTransactionTable();
    updateSummary();

    // Clear quantity input
    document.getElementById('quantity').value = '';
    itemSelect.value = '';
    updateStockInfo();

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
        const isKilo = item.qtyType === 'by kilo' || item.qtyType === 'Kilo';
        const unit = isKilo ? ' kg' : ' pieces';
        const displayQtyType = item.qtyType || 'by piece';
        return `
            <tr data-id="${item.id}">
                <td>${item.name}</td>
                <td>${item.category}</td>
                <td>${displayQtyType}</td>
                <td>${item.currentQty}${unit}</td>
                <td>${item.exchangeQty}${unit}</td>
                <td>₱${item.price.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 })}</td>
                <td>₱${item.amount.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 })}</td>
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
    // Calculate total items by piece (items with qty_type 'by piece' or 'Piraso')
    const totalPieceItems = transactionItems
        .filter(item => {
            const qtyType = item.qtyType || 'by piece';
            return qtyType === 'by piece' || qtyType === 'Piraso';
        })
        .reduce((sum, item) => sum + item.exchangeQty, 0);
    document.getElementById('totalPieceItems').textContent = totalPieceItems;
    
    // Calculate total items by weight (items with qty_type 'by kilo' or 'Kilo')
    const totalWeightItems = transactionItems
        .filter(item => {
            const qtyType = item.qtyType || 'by piece';
            return qtyType === 'by kilo' || qtyType === 'Kilo';
        })
        .reduce((sum, item) => sum + item.exchangeQty, 0);
    document.getElementById('totalWeightItems').textContent = totalWeightItems;

    // Calculate total value
    const totalValue = transactionItems.reduce((sum, item) => sum + item.amount, 0);
    document.getElementById('totalValue').textContent = `₱${totalValue.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 })}`;
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
    const customerName = document.getElementById('customerName').value.trim();

    // Validation
    if (!operationType) {
        showNotification('Please select an operation type', 'error');
        return;
    }

    if (!customerName) {
        showNotification('Please enter customer name', 'error');
        return;
    }

    if (transactionItems.length === 0) {
        showNotification('Please add at least one item', 'error');
        return;
    }

    // Show confirmation dialog with list of items
    showSaveConfirmation(operationType, customerName);
}

// Show confirmation dialog before saving
function showSaveConfirmation(operationType, customerName) {
    // Build list of items for confirmation
    let itemsList = '';
    transactionItems.forEach((item, index) => {
        const unit = (item.qtyType === 'by kilo' || item.qtyType === 'Kilo') ? 'kg' : 'pieces';
        itemsList += `${index + 1}. ${item.name} - ${item.exchangeQty} ${unit} - ₱${item.amount.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 })}\n`;
    });

    const totalValue = transactionItems.reduce((sum, item) => sum + item.amount, 0);
    const message = `Are you sure you want to save this transaction? You can't modify these after saving.\n\nList of items:\n${itemsList}
            \nTotal Value: ₱${totalValue.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 })}`;

    if (confirm(message)) {
        // User confirmed, proceed with saving
        createAndCompleteTransaction(operationType, customerName);
    }
}

// Create transaction and add all items
function createAndCompleteTransaction(operationType, customerName) {
    // First create the transaction
    const formData = new FormData();
    formData.append('action', 'create_transaction');
    formData.append('customer_name', customerName);
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
            // Generate and show receipt/invoice
            generateInvoice(operationType, customerName, transactionId);
            showNotification('Transaction saved successfully!', 'success');

            // Reset transaction UI after showing receipt
            // Don't reset immediately - let user close receipt first
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
    document.getElementById('customerName').value = '';
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
    
    // Update price header based on operation type
    const priceHeader = document.getElementById('invoicePriceHeader');
    if (priceHeader) {
        priceHeader.textContent = operationType === 'receiving' ? 'BUYING PRICE' : 'SELLING PRICE';
    }
    
    // Calculate quantities
    const qtyByPiece = transactionItems
        .filter(item => {
            const qtyType = item.qtyType || 'by piece';
            return qtyType === 'by piece' || qtyType === 'Piraso';
        })
        .reduce((sum, item) => sum + item.exchangeQty, 0);
    
    const qtyByWeight = transactionItems
        .filter(item => {
            const qtyType = item.qtyType || 'by piece';
            return qtyType === 'by kilo' || qtyType === 'Kilo';
        })
        .reduce((sum, item) => sum + item.exchangeQty, 0);
    
    document.getElementById('invoiceQtyPiece').textContent = qtyByPiece;
    document.getElementById('invoiceQtyWeight').textContent = qtyByWeight;
    document.getElementById('invoiceItems').textContent = transactionItems.length;
    
    // Generate invoice table - mirroring the transaction table
    const invoiceTableBody = document.getElementById('invoiceTableBody');
    const priceLabel = operationType === 'receiving' ? 'BUYING PRICE' : 'SELLING PRICE';
    
    invoiceTableBody.innerHTML = transactionItems.map(item => {
        const isKilo = item.qtyType === 'by kilo' || item.qtyType === 'Kilo';
        const unit = isKilo ? ' kg' : ' pieces';
        return `
            <tr>
                <td>${item.name}</td>
                <td>₱${item.price.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 })}</td>
                <td>${item.exchangeQty}${unit}</td>
                <td>₱${item.amount.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 })}</td>
            </tr>
        `;
    }).join('');
    
    // Calculate total
    const totalAmount = transactionItems.reduce((sum, item) => sum + item.amount, 0);
    document.getElementById('invoiceTotal').textContent = `₱${totalAmount.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 })}`;
    
    // Show invoice modal
    document.getElementById('invoiceModal').classList.add('active');
    
    showNotification('Transaction saved! Invoice generated.', 'success');
}

// Close invoice
function closeInvoice() {
    document.getElementById('invoiceModal').classList.remove('active');
    // Reset transaction UI after closing receipt
    resetTransactionUI();
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

// Stock error popup
function showStockErrorPopup(itemName, currentStock) {
    alert(`${itemName}\n\nStocks is not enough\nCurrent Stock: ${currentStock}\n\nOK`);
}

// Print transaction function
function printTransaction() {
    if (transactionItems.length === 0) {
        showNotification('No items to print', 'error');
        return;
    }

    const operationType = document.getElementById('operationType').value;
    const customerName = document.getElementById('customerName').value || 'Unknown Customer';
    const printWindow = window.open('', '', 'height=600,width=800');
    
    let tableHTML = '<table border="1" cellpadding="10" style="width:100%;"><thead><tr><th>Item</th><th>Category</th><th>Qty Type</th><th>Current</th><th>Exchange</th><th>Price</th><th>Amount</th></tr></thead><tbody>';
    
    let total = 0;
    transactionItems.forEach(item => {
        tableHTML += `<tr>
            <td>${item.name}</td>
            <td>${item.category}</td>
            <td>${item.qtyType}</td>
            <td>${item.currentQty}</td>
            <td>${item.exchangeQty}</td>
            <td>₱${item.price.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 })}</td>
            <td>₱${item.amount.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 })}</td>
        </tr>`;
        total += item.amount;
    });

    tableHTML += '</tbody></table>';

    printWindow.document.write(`
        <html>
        <head>
            <title>Transaction Receipt</title>
            <style>
                body { font-family: Arial; margin: 20px; }
                h2 { text-align: center; }
                .info { margin: 20px 0; }
                table { margin: 20px 0; }
                .total { text-align: right; font-size: 18px; font-weight: bold; margin-top: 20px; }
            </style>
        </head>
        <body>
            <h2>ScrapTrack Transaction Receipt</h2>
            <div class="info">
                <p><strong>Operation:</strong> ${operationType.toUpperCase()}</p>
                <p><strong>Customer:</strong> ${customerName}</p>
                <p><strong>Date:</strong> ${new Date().toLocaleDateString()}</p>
            </div>
            ${tableHTML}
            <div class="total">
                <p>Total Amount: ₱${total.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 })}</p>
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

console.log('ScrapTrack Transaction initialized successfully!');