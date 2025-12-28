// ScrapTrack Transaction Records JavaScript

// Sample transaction records data (replace with database later)
let transactionRecords = [
    {
        id: 1,
        date: "2025-12-23",
        type: "Received",
        customer: "Gwyneth",
        noOfItems: 1,
        totalPiece: 69,
        totalKilo: 0,
        totalAmount: 69.00,
        items: [
            {
                name: "Glass Bottle",
                category: "Babasagin",
                qtyType: "Piraso",
                quantity: 69,
                price: 69.00,
                amount: 69.00
            }
        ]
    },
    {
        id: 2,
        date: "2025-12-23",
        type: "Dispatched",
        customer: "Christine",
        noOfItems: 5,
        totalPiece: 69,
        totalKilo: 69,
        totalAmount: 345.00,
        items: [
            {
                name: "Glass Bottle",
                category: "Babasagin",
                qtyType: "Piraso",
                quantity: 20,
                price: 69.00,
                amount: 1380.00
            },
            {
                name: "White Paper",
                category: "Paper",
                qtyType: "Kilo",
                quantity: 30,
                price: 45.00,
                amount: 1350.00
            },
            {
                name: "Cardboard",
                category: "Paper",
                qtyType: "Kilo",
                quantity: 19,
                price: 35.00,
                amount: 665.00
            }
        ]
    },
    {
        id: 3,
        date: "2025-12-23",
        type: "Received",
        customer: "Juan",
        noOfItems: 6,
        totalPiece: 50,
        totalKilo: 120,
        totalAmount: 4500.00,
        items: [
            {
                name: "Metal",
                category: "Metal",
                qtyType: "Kilo",
                quantity: 100,
                price: 40.00,
                amount: 4000.00
            },
            {
                name: "Plastic Bottles",
                category: "Plastic",
                qtyType: "Piraso",
                quantity: 50,
                price: 10.00,
                amount: 500.00
            }
        ]
    }
];

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    renderTransactionRecords();
});

// Setup event listeners
function setupEventListeners() {
    // Search input
    document.getElementById('searchInput').addEventListener('input', handleSearch);

    // Filter button
    document.getElementById('filterBtn').addEventListener('click', handleFilter);

    // Add button
    document.getElementById('addBtn').addEventListener('click', handleAdd);
}

// Render transaction records table
function renderTransactionRecords(records = transactionRecords) {
    const tbody = document.getElementById('recordsTableBody');
    
    if (records.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: #a0aec0;">No transaction records found</td></tr>';
        return;
    }
    
    tbody.innerHTML = records.map(record => {
        const badgeClass = record.type === 'Received' ? 'badge-received' : 'badge-dispatched';
        const itemText = record.noOfItems === 1 ? '1 item' : `${record.noOfItems} items`;
        
        return `
            <tr data-id="${record.id}">
                <td>${record.id}</td>
                <td>${formatDate(record.date)}</td>
                <td><span class="badge ${badgeClass}">${record.type}</span></td>
                <td>${itemText}</td>
                <td>${record.totalPiece} pcs</td>
                <td>${record.totalKilo} kg</td>
                <td>₱${record.totalAmount.toFixed(2)}</td>
                <td>
                    <button class="action-btn view-btn" onclick="viewTransaction(${record.id})" title="View Details">
                        📄
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

// Format date to MM-DD-YYYY
function formatDate(dateString) {
    const date = new Date(dateString);
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');
    const year = date.getFullYear();
    return `${month}-${day}-${year}`;
}

// View transaction details
function viewTransaction(id) {
    const record = transactionRecords.find(r => r.id === id);
    if (!record) {
        showNotification('Transaction not found', 'error');
        return;
    }
    
    // Populate modal with transaction details
    document.getElementById('detailId').textContent = record.id;
    document.getElementById('detailDate').textContent = record.date;
    document.getElementById('detailType').textContent = record.type;
    document.getElementById('detailCustomer').textContent = record.customer;
    document.getElementById('detailItems').textContent = record.noOfItems === 1 ? '1 item' : `${record.noOfItems} items`;
    document.getElementById('detailPiece').textContent = `${record.totalPiece} pcs`;
    document.getElementById('detailKilo').textContent = `${record.totalKilo} kg`;
    document.getElementById('detailAmount').textContent = `₱${record.totalAmount.toFixed(2)}`;
    
    // Populate items table
    const itemsBody = document.getElementById('detailItemsBody');
    itemsBody.innerHTML = record.items.map(item => {
        const unit = item.qtyType === 'Kilo' ? 'kg' : ' pcs';
        return `
            <tr>
                <td>${item.name}</td>
                <td>${item.category}</td>
                <td>${item.qtyType}</td>
                <td>${item.quantity}${unit}</td>
                <td>₱${item.price.toFixed(2)}</td>
                <td>₱${item.amount.toFixed(2)}</td>
            </tr>
        `;
    }).join('');
    
    // Show modal
    document.getElementById('detailModal').classList.add('active');
}

// Close detail modal
function closeDetailModal() {
    document.getElementById('detailModal').classList.remove('active');
}

// Print transaction
function printTransaction() {
    // In a real application, this would redirect to the invoice page or generate a printable invoice
    const detailId = document.getElementById('detailId').textContent;
    showNotification(`Preparing invoice for Transaction #${detailId}...`, 'info');
    
    // You could redirect to Transaction.html with the transaction data
    // or generate a new invoice modal similar to the Transaction page
    setTimeout(() => {
        alert('This would open the printable invoice.\n\nIn production, this would:\n1. Generate a full invoice\n2. Open print dialog\n3. Or redirect to invoice page');
    }, 500);
}

// Handle search
function handleSearch(e) {
    const searchTerm = e.target.value.toLowerCase().trim();
    
    if (searchTerm === '') {
        renderTransactionRecords();
        return;
    }
    
    const filteredRecords = transactionRecords.filter(record => 
        record.id.toString().includes(searchTerm) ||
        record.type.toLowerCase().includes(searchTerm) ||
        record.customer.toLowerCase().includes(searchTerm) ||
        record.date.includes(searchTerm)
    );
    
    renderTransactionRecords(filteredRecords);
    
    if (filteredRecords.length === 0) {
        showNotification('No matching transactions found', 'info');
    }
}

// Handle filter
function handleFilter() {
    const filterOptions = [
        'All Transactions',
        'Received Only',
        'Dispatched Only',
        'Today',
        'This Week',
        'This Month'
    ];
    
    const choice = prompt(`Filter Options:\n\n${filterOptions.map((opt, i) => `${i + 1}. ${opt}`).join('\n')}\n\nEnter your choice (1-${filterOptions.length}):`);
    
    if (!choice) return;
    
    const index = parseInt(choice) - 1;
    
    if (index >= 0 && index < filterOptions.length) {
        switch(index) {
            case 0: // All
                renderTransactionRecords();
                showNotification('Showing all transactions', 'info');
                break;
            case 1: // Received Only
                const received = transactionRecords.filter(r => r.type === 'Received');
                renderTransactionRecords(received);
                showNotification(`Showing ${received.length} received transactions`, 'info');
                break;
            case 2: // Dispatched Only
                const dispatched = transactionRecords.filter(r => r.type === 'Dispatched');
                renderTransactionRecords(dispatched);
                showNotification(`Showing ${dispatched.length} dispatched transactions`, 'info');
                break;
            case 3: // Today
                const today = new Date().toISOString().split('T')[0];
                const todayRecords = transactionRecords.filter(r => r.date === today);
                renderTransactionRecords(todayRecords);
                showNotification(`Showing ${todayRecords.length} transactions from today`, 'info');
                break;
            case 4: // This Week
                showNotification('Week filter - Feature coming soon!', 'info');
                break;
            case 5: // This Month
                showNotification('Month filter - Feature coming soon!', 'info');
                break;
        }
    } else {
        showNotification('Invalid choice', 'error');
    }
}

// Handle add button
function handleAdd() {
    if (confirm('Do you want to create a new transaction?')) {
        // Redirect to Transaction page
        window.location.href = 'Transaction.html';
    }
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

console.log('ScrapTrack Transaction Records initialized successfully!');