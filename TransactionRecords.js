// ScrapTrack Transaction Records JavaScript

// Store transaction records fetched from backend
let transactionRecords = [];
let currentFilter = 0; // Track current filter index (0 = All)

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
                    <button class="action-btn view-btn" onclick="viewTransaction(${record.id})" title="View Receipt">
                        🧾
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
            document.getElementById('detailItems').textContent = record.noOfItems === 1 ? '1 item' : `${record.noOfItems} items`;
            document.getElementById('detailPiece').textContent = `${record.totalPiece} pcs`;
            document.getElementById('detailKilo').textContent = `${record.totalKilo} kg`;
            document.getElementById('detailAmount').textContent = `₱${parseFloat(record.totalAmount).toFixed(2)}`;
            
            // Populate items table for invoice
            const itemsBody = document.getElementById('detailItemsBody');
            if (record.items && record.items.length > 0) {
                itemsBody.innerHTML = record.items.map(item => {
                    return `
                        <tr>
                            <td>${item.name}</td>
                            <td>₱${parseFloat(item.price).toFixed(2)}</td>
                            <td>${item.quantity}</td>
                            <td>₱${parseFloat(item.amount).toFixed(2)}</td>
                        </tr>
                    `;
                }).join('');
            } else {
                itemsBody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px;">No items found</td></tr>';
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

// Handle search
async function handleSearch(e) {
    const searchTerm = e.target.value.toLowerCase().trim();
    
    if (searchTerm === '') {
        // Reload all records if search is cleared
        loadTransactionRecords();
        return;
    }
    
    try {
        // Search locally in already loaded records for faster results
        const filtered = transactionRecords.filter(record => {
            return (
                (record.id && record.id.toString().toLowerCase().includes(searchTerm)) ||
                (record.date && record.date.toLowerCase().includes(searchTerm)) ||
                (record.type && record.type.toLowerCase().includes(searchTerm)) ||
                (record.items && record.items.toLowerCase().includes(searchTerm)) ||
                (record.totalPiece && record.totalPiece.toString().includes(searchTerm)) ||
                (record.totalKilo && record.totalKilo.toString().includes(searchTerm)) ||
                (record.totalAmount && record.totalAmount.toString().includes(searchTerm))
            );
        });
        
        if (filtered.length === 0) {
            showNotification('No matching transactions found', 'info');
        } else {
            showNotification(`Found ${filtered.length} matching transaction(s)`, 'info');
        }
        
        renderTransactionRecords(filtered);
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
    
    showFilterModal('Filter Transactions', filterOptions, (index) => {
        currentFilter = index; // Track current filter
        if (index === 0) { // All
            renderTransactionRecords();
            showNotification('Showing all transactions', 'info');
        } else if (index === 1) { // Received Only
            const received = transactionRecords.filter(r => r.type === 'Received');
            renderTransactionRecords(received);
            showNotification(`Showing ${received.length} received transactions`, 'info');
        } else if (index === 2) { // Dispatched Only
            const dispatched = transactionRecords.filter(r => r.type === 'Dispatched');
            renderTransactionRecords(dispatched);
            showNotification(`Showing ${dispatched.length} dispatched transactions`, 'info');
        } else if (index === 3) { // Today
            const today = new Date().toISOString().split('T')[0];
            const todayRecords = transactionRecords.filter(r => r.date === today);
            renderTransactionRecords(todayRecords);
            showNotification(`Showing ${todayRecords.length} transactions from today`, 'info');
        } else if (index === 4) { // This Week
            const weekRecords = getTransactionsThisWeek();
            renderTransactionRecords(weekRecords);
            showNotification(`Showing ${weekRecords.length} transactions from this week`, 'info');
        } else if (index === 5) { // This Month
            const monthRecords = getTransactionsThisMonth();
            renderTransactionRecords(monthRecords);
            showNotification(`Showing ${monthRecords.length} transactions from this month`, 'info');
        }
    }, currentFilter);
}

// Get transactions from this week
function getTransactionsThisWeek() {
    const today = new Date();
    const currentDay = today.getDay();
    
    // Calculate the start of the week (Sunday)
    const startOfWeek = new Date(today);
    startOfWeek.setDate(today.getDate() - currentDay);
    startOfWeek.setHours(0, 0, 0, 0);
    
    // Calculate the end of the week (Saturday)
    const endOfWeek = new Date(startOfWeek);
    endOfWeek.setDate(startOfWeek.getDate() + 6);
    endOfWeek.setHours(23, 59, 59, 999);
    
    const startStr = startOfWeek.toISOString().split('T')[0];
    const endStr = endOfWeek.toISOString().split('T')[0];
    
    return transactionRecords.filter(r => {
        return r.date >= startStr && r.date <= endStr;
    });
}

// Get transactions from this month
function getTransactionsThisMonth() {
    const today = new Date();
    const year = today.getFullYear();
    const month = today.getMonth();
    
    // First day of this month
    const startOfMonth = new Date(year, month, 1);
    const startStr = startOfMonth.toISOString().split('T')[0];
    
    // Last day of this month
    const endOfMonth = new Date(year, month + 1, 0);
    const endStr = endOfMonth.toISOString().split('T')[0];
    
    return transactionRecords.filter(r => {
        return r.date >= startStr && r.date <= endStr;
    });
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