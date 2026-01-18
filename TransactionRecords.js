// ScrapTrack Transaction Records JavaScript

// Store transaction records fetched from backend
let transactionRecords = [];

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    loadTransactionRecords();
});

// Load transaction records from backend
async function loadTransactionRecords() {
    try {
        const formData = new FormData();
        formData.append('action', 'get_records');
        
        const response = await fetch('TransactionRecords.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            transactionRecords = result.data;
            renderTransactionRecords();
        } else {
            showNotification(result.error || 'Failed to load transaction records', 'error');
            // Show error message in table
            const tbody = document.getElementById('recordsTableBody');
            tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: #f56565;">Failed to load records. Please try again.</td></tr>';
        }
    } catch (error) {
        console.error('Error loading transaction records:', error);
        showNotification('Network error. Please check your connection.', 'error');
        const tbody = document.getElementById('recordsTableBody');
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: #f56565;">Network error. Please try again.</td></tr>';
    }
}

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
async function viewTransaction(id) {
    try {
        const formData = new FormData();
        formData.append('action', 'get_transaction_detail');
        formData.append('transaction_id', id);
        
        const response = await fetch('TransactionRecords.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            const record = result.data;
            
            // Populate modal with transaction details
            document.getElementById('detailId').textContent = record.id;
            document.getElementById('detailDate').textContent = formatDate(record.date);
            document.getElementById('detailType').textContent = record.type;
            document.getElementById('detailCustomer').textContent = record.customer;
            document.getElementById('detailEmployee').textContent = record.employee || 'N/A';
            document.getElementById('detailItems').textContent = record.noOfItems === 1 ? '1 item' : `${record.noOfItems} items`;
            document.getElementById('detailPiece').textContent = `${record.totalPiece} pcs`;
            document.getElementById('detailKilo').textContent = `${record.totalKilo} kg`;
            document.getElementById('detailAmount').textContent = `₱${parseFloat(record.totalAmount).toFixed(2)}`;
            
            // Populate items table
            const itemsBody = document.getElementById('detailItemsBody');
            if (record.items && record.items.length > 0) {
                itemsBody.innerHTML = record.items.map(item => {
                    // Determine if it's by kilo or piece based on category
                    const weightCategories = ['Paper', 'Metals', 'Plastics'];
                    const isKilo = weightCategories.includes(item.category);
                    const qtyType = isKilo ? 'Kilo' : 'Piece';
                    const unit = isKilo ? ' kg' : ' pcs';
                    
                    return `
                        <tr>
                            <td>${item.name}</td>
                            <td>${item.category}</td>
                            <td>${qtyType}</td>
                            <td>${item.quantity}${unit}</td>
                            <td>₱${parseFloat(item.price).toFixed(2)}</td>
                            <td>₱${parseFloat(item.amount).toFixed(2)}</td>
                        </tr>
                    `;
                }).join('');
            } else {
                itemsBody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px;">No items found</td></tr>';
            }
            
            // Show modal
            document.getElementById('detailModal').classList.add('active');
        } else {
            showNotification(result.error || 'Failed to load transaction details', 'error');
        }
    } catch (error) {
        console.error('Error loading transaction details:', error);
        showNotification('Failed to load transaction details', 'error');
    }
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
async function handleSearch(e) {
    const searchTerm = e.target.value.trim();
    
    if (searchTerm === '') {
        // Reload all records if search is cleared
        loadTransactionRecords();
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'search_records');
        formData.append('search', searchTerm);
        
        const response = await fetch('TransactionRecords.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            transactionRecords = result.data;
            renderTransactionRecords();
            
            if (result.data.length === 0) {
                showNotification('No matching transactions found', 'info');
            }
        } else {
            showNotification(result.error || 'Search failed', 'error');
        }
    } catch (error) {
        console.error('Error searching transactions:', error);
        showNotification('Search failed', 'error');
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
        window.location.href = 'Transaction.php';
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