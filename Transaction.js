// ScrapTrack Transaction Management JavaScript

// Transaction data storage
let transactionItems = [
    {
        id: 1,
        name: "Glass Bottle",
        category: "Babasagin",
        qtyType: "Piraso",
        currentQty: 69,
        exchangeQty: 69,
        price: 69.00,
        amount: 69.00
    },
    {
        id: 2,
        name: "White",
        category: "Paper",
        qtyType: "Kilo",
        currentQty: 69,
        exchangeQty: 69,
        price: 69.00,
        amount: 69.00
    },
    {
        id: 3,
        name: "White",
        category: "Paper",
        qtyType: "Kilo",
        currentQty: 69,
        exchangeQty: 69,
        price: 69.00,
        amount: 69.00
    }
];

let currentEditId = null;
let transactionIdCounter = 4;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    updateSummary();
    updateStockInfo();
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
        stockInfo.textContent = '(There is 69kg current stock)';
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
        id: transactionIdCounter++,
        name: selectedOption.getAttribute('data-name'),
        category: selectedOption.getAttribute('data-category'),
        qtyType: selectedOption.getAttribute('data-type'),
        currentQty: parseInt(selectedOption.getAttribute('data-stock')),
        exchangeQty: parseInt(quantity),
        price: parseFloat(selectedOption.getAttribute('data-price')),
        amount: parseFloat(selectedOption.getAttribute('data-price')) * parseInt(quantity)
    };
    
    // Add to array
    transactionItems.push(itemData);
    
    // Update table
    renderTransactionTable();
    updateSummary();
    
    // Clear inputs
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
    
    // Generate invoice
    generateInvoice(operationType, customerName);
}

// Clear transaction
function clearTransaction() {
    if (transactionItems.length === 0) {
        showNotification('Transaction is already empty', 'info');
        return;
    }
    
    if (!confirm('Are you sure you want to clear all items?')) return;
    
    transactionItems = [];
    document.getElementById('customerName').value = '';
    document.getElementById('operationType').value = '';
    document.getElementById('itemSelect').value = '';
    document.getElementById('quantity').value = '';
    
    renderTransactionTable();
    updateSummary();
    updateOperationTitle();
    updateStockInfo();
    
    showNotification('Transaction cleared!', 'info');
}

// Generate invoice
function generateInvoice(operationType, customerName) {
    // Set invoice data
    const today = new Date();
    const dateStr = `${today.getMonth() + 1} / ${today.getDate()} / ${today.getFullYear()}`;
    const transactionId = Math.floor(Math.random() * 10000) + 1;
    
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