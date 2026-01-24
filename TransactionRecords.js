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
    const tbody = document.getElementById('recordsTableBody');
    tbody.innerHTML = '<tr><td colspan="9" class="loading">Loading transaction records...</td></tr>';
    
    showNotification('Loading transaction records...', 'info');
    
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
            
            if (result.data.length === 0) {
                showNotification('No transaction records found.', 'info');
            } else {
                showNotification(`Loaded ${result.data.length} transaction records`, 'success');
            }
        } else {
            showNotification(result.error || 'Failed to load transaction records', 'error');
            tbody.innerHTML = `<tr><td colspan="9" class="error">${result.error || 'Failed to load records'}</td></tr>`;
        }
    } catch (error) {
        console.error('Error loading transaction records:', error);
        showNotification('Network error. Please check your connection.', 'error');
        tbody.innerHTML = '<tr><td colspan="9" class="error">Network error. Please try again.</td></tr>';
    }
}

// Setup event listeners
function setupEventListeners() {
    // Search input with debouncing
    const searchInput = document.getElementById('searchInput');
    let searchTimeout;
    searchInput.addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            handleSearch(e.target.value);
        }, 300);
    });
    
    // Enter key for search
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            handleSearch(this.value);
        }
    });

    // Filter button
    document.getElementById('filterBtn').addEventListener('click', handleFilter);

    // Add button
    document.getElementById('addBtn').addEventListener('click', handleAdd);
}

// Render transaction records table
function renderTransactionRecords(records = transactionRecords) {
    const tbody = document.getElementById('recordsTableBody');
    
    if (!records || records.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="empty">No transaction records found</td></tr>';
        return;
    }
    
    tbody.innerHTML = records.map(record => {
        // Determine badge class based on transaction type
        let badgeClass = 'badge-info';
        let displayType = record.type || 'Transaction';
        
        // Categorize transaction types
        if (displayType.toLowerCase().includes('purchase') || 
            displayType.toLowerCase().includes('buy') ||
            displayType.toLowerCase().includes('received')) {
            badgeClass = 'badge-received';
        } else if (displayType.toLowerCase().includes('sell') || 
                   displayType.toLowerCase().includes('sale') ||
                   displayType.toLowerCase().includes('dispatched')) {
            badgeClass = 'badge-dispatched';
        } else if (displayType.toLowerCase().includes('exchange')) {
            badgeClass = 'badge-exchange';
        }
        
        const itemText = record.noOfItems === 1 ? '1 item' : `${record.noOfItems} items`;
        
        return `
            <tr data-id="${record.id}">
                <td>#${record.id}</td>
                <td>${formatDate(record.date)}</td>
                <td><span class="badge ${badgeClass}">${displayType}</span></td>
                <td>${record.customer || 'Walk-in'}</td>
                <td>${itemText}</td>
                <td>${record.totalPiece || 0}</td>
                <td>${record.totalKilo || 0}</td>
                <td>₱${parseFloat(record.totalAmount || 0).toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 })} </td>
                <td>
                    <button class="action-btn view-btn" onclick="viewTransaction(${record.id})" title="View Details">
                        👁️ View
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

// Format date to MM-DD-YYYY
function formatDate(dateString) {
    if (!dateString || dateString === 'N/A') return 'N/A';
    
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;
        
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const day = date.getDate().toString().padStart(2, '0');
        const year = date.getFullYear();
        return `${month}-${day}-${year}`;
    } catch (e) {
        return dateString;
    }
}

// Format date time
function formatDateTime(dateString) {
    if (!dateString || dateString === 'N/A') return 'N/A';
    
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;
        
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const day = date.getDate().toString().padStart(2, '0');
        const year = date.getFullYear();
        const hours = date.getHours().toString().padStart(2, '0');
        const minutes = date.getMinutes().toString().padStart(2, '0');
        return `${month}-${day}-${year} ${hours}:${minutes}`;
    } catch (e) {
        return dateString;
    }
}

// View transaction details
async function viewTransaction(id) {
    showNotification(`Loading transaction #${id} details...`, 'info');
    
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
            document.getElementById('detailId').textContent = `#${record.id}`;
            document.getElementById('detailDate').textContent = formatDateTime(record.date);
            document.getElementById('detailType').textContent = record.type;
            document.getElementById('detailCustomer').textContent = record.customer;
            document.getElementById('detailEmployee').textContent = record.employee;
            document.getElementById('detailItems').textContent = record.noOfItems === 1 ? '1 item' : `${record.noOfItems} items`;
            document.getElementById('detailPiece').textContent = `${record.totalPiece || 0} pcs`;
            document.getElementById('detailKilo').textContent = `${record.totalKilo || 0} kg`;
            document.getElementById('detailAmount').textContent = `₱${parseFloat(record.totalAmount || 0).toFixed(2)}`;
            
            // Populate items table
            const itemsBody = document.getElementById('detailItemsBody');
            if (record.items && record.items.length > 0) {
                itemsBody.innerHTML = record.items.map(item => {
                    const unit = item.qty_type === 'Kilo' ? ' kg' : ' pcs';
                    return `
                        <tr>
                            <td>${item.name}</td>
                            <td>${item.category}</td>
                            <td><span class="badge ${item.qty_type === 'Kilo' ? 'badge-warning' : 'badge-info'}">${item.qty_type}</span></td>
                            <td>${item.quantity}${unit}</td>
                            <td>₱${parseFloat(item.price || 0).toFixed(2)}</td>
                            <td>₱${parseFloat(item.amount || 0).toFixed(2)}</td>
                        </tr>
                    `;
                }).join('');
            } else {
                itemsBody.innerHTML = '<tr><td colspan="6" class="empty">No items found in this transaction</td></tr>';
            }
            
            // Show modal
            document.getElementById('detailModal').classList.add('active');
            showNotification('Transaction details loaded successfully', 'success');
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
    const detailId = document.getElementById('detailId').textContent.replace('#', '');
    showNotification(`Preparing invoice for Transaction ${detailId}...`, 'info');
    
    // You can implement actual printing here
    // For now, we'll just show a message
    setTimeout(() => {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Invoice - Transaction ${detailId}</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        .invoice-header { text-align: center; margin-bottom: 30px; }
                        .invoice-details { margin-bottom: 20px; }
                        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                        .items-table th, .items-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        .total { text-align: right; font-size: 18px; font-weight: bold; }
                        @media print { .no-print { display: none; } }
                    </style>
                </head>
                <body>
                    <div class="invoice-header">
                        <h1>ScrapTrack Invoice</h1>
                        <p>Transaction ${detailId}</p>
                    </div>
                    <div class="invoice-details">
                        <p><strong>Date:</strong> ${document.getElementById('detailDate').textContent}</p>
                        <p><strong>Customer:</strong> ${document.getElementById('detailCustomer').textContent}</p>
                        <p><strong>Type:</strong> ${document.getElementById('detailType').textContent}</p>
                    </div>
                    <h3>Items:</h3>
                    ${document.querySelector('.items-table').outerHTML}
                    <div class="total">
                        <p>Total Amount: ${document.getElementById('detailAmount').textContent}</p>
                    </div>
                    <div class="no-print">
                        <button onclick="window.print()">Print Invoice</button>
                        <button onclick="window.close()">Close</button>
                    </div>
                </body>
            </html>
        `);
        printWindow.document.close();
    }, 500);
}

// Handle search
async function handleSearch(searchTerm) {
    if (!searchTerm || searchTerm.trim() === '') {
        // Reload all records if search is cleared
        loadTransactionRecords();
        return;
    }
    
    const tbody = document.getElementById('recordsTableBody');
    tbody.innerHTML = '<tr><td colspan="9" class="loading">Searching...</td></tr>';
    
    showNotification(`Searching for "${searchTerm}"...`, 'info');
    
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
                showNotification(`No transactions found for "${searchTerm}"`, 'info');
            } else {
                showNotification(`Found ${result.data.length} matching transactions`, 'success');
            }
        } else {
            showNotification(result.error || 'Search failed', 'error');
            renderTransactionRecords([]);
        }
    } catch (error) {
        console.error('Error searching transactions:', error);
        showNotification('Search failed. Please try again.', 'error');
        tbody.innerHTML = '<tr><td colspan="9" class="error">Search failed. Please try again.</td></tr>';
    }
}

// Handle filter
async function handleFilter() {
    const filterOptions = [
        'All Transactions',
        'Purchase Only',
        'Sale Only',
        'Today',
        'This Week',
        'This Month',
        'Last 30 Days'
    ];
    
    let filterHtml = '<div style="padding: 20px; font-family: Arial;">';
    filterHtml += '<h3 style="margin-top: 0;">Filter Options</h3>';
    filterOptions.forEach((opt, i) => {
        filterHtml += `<div style="padding: 8px 0; border-bottom: 1px solid #eee;">
            <label style="cursor: pointer; display: block;">
                <input type="radio" name="filter" value="${i}" id="filter${i}">
                <span style="margin-left: 8px;">${opt}</span>
            </label>
        </div>`;
    });
    filterHtml += '<button onclick="applyFilter()" style="margin-top: 20px; padding: 8px 16px; background: #4299e1; color: white; border: none; border-radius: 4px; cursor: pointer;">Apply Filter</button>';
    filterHtml += '</div>';
    
    const filterWindow = window.open('', '_blank', 'width=400,height=400');
    filterWindow.document.write(filterHtml);
    filterWindow.document.close();
    
    // Add applyFilter function to the new window
    filterWindow.applyFilter = function() {
        const selected = filterWindow.document.querySelector('input[name="filter"]:checked');
        if (selected) {
            const index = parseInt(selected.value);
            applySelectedFilter(index);
            filterWindow.close();
        }
    };
}

function applySelectedFilter(index) {
    let filteredRecords = [];
    const today = new Date().toISOString().split('T')[0];
    const weekAgo = new Date();
    weekAgo.setDate(weekAgo.getDate() - 7);
    const weekAgoStr = weekAgo.toISOString().split('T')[0];
    const monthAgo = new Date();
    monthAgo.setDate(monthAgo.getDate() - 30);
    const monthAgoStr = monthAgo.toISOString().split('T')[0];
    
    switch(index) {
        case 0: // All
            filteredRecords = transactionRecords;
            showNotification('Showing all transactions', 'info');
            break;
        case 1: // Purchase Only
            filteredRecords = transactionRecords.filter(r => 
                r.type && (r.type.toLowerCase().includes('purchase') || 
                          r.type.toLowerCase().includes('buy') ||
                          r.type.toLowerCase().includes('received'))
            );
            showNotification(`Showing ${filteredRecords.length} purchase transactions`, 'info');
            break;
        case 2: // Sale Only
            filteredRecords = transactionRecords.filter(r => 
                r.type && (r.type.toLowerCase().includes('sell') || 
                          r.type.toLowerCase().includes('sale') ||
                          r.type.toLowerCase().includes('dispatched'))
            );
            showNotification(`Showing ${filteredRecords.length} sale transactions`, 'info');
            break;
        case 3: // Today
            filteredRecords = transactionRecords.filter(r => r.date === today);
            showNotification(`Showing ${filteredRecords.length} transactions from today`, 'info');
            break;
        case 4: // This Week
            filteredRecords = transactionRecords.filter(r => r.date >= weekAgoStr);
            showNotification(`Showing ${filteredRecords.length} transactions from this week`, 'info');
            break;
        case 5: // This Month
            const thisMonth = new Date();
            const thisMonthStart = new Date(thisMonth.getFullYear(), thisMonth.getMonth(), 1)
                .toISOString().split('T')[0];
            filteredRecords = transactionRecords.filter(r => r.date >= thisMonthStart);
            showNotification(`Showing ${filteredRecords.length} transactions from this month`, 'info');
            break;
        case 6: // Last 30 Days
            filteredRecords = transactionRecords.filter(r => r.date >= monthAgoStr);
            showNotification(`Showing ${filteredRecords.length} transactions from last 30 days`, 'info');
            break;
    }
    
    renderTransactionRecords(filteredRecords);
}

// Handle add button
function handleAdd() {
    if (confirm('Create a new transaction?')) {
        window.location.href = 'Transaction.php';
    }
}

// Show notification
function showNotification(message, type = 'info') {
    // Remove existing notification
    const existing = document.querySelector('.notification');
    if (existing) existing.remove();
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => {
                if (notification.parentNode) notification.remove();
            }, 300);
        }
    }, 3000);
}

// Add CSS for animations and badges
if (!document.querySelector('#dynamic-styles')) {
    const style = document.createElement('style');
    style.id = 'dynamic-styles';
    style.textContent = `
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
        
        /* Badge Styles */
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }
        
        .badge-received {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .badge-dispatched {
            background: #fed7d7;
            color: #742a2a;
        }
        
        .badge-exchange {
            background: #e9d8fd;
            color: #44337a;
        }
        
        .badge-info {
            background: #bee3f8;
            color: #2a4365;
        }
        
        .badge-warning {
            background: #feebc8;
            color: #744210;
        }
        
        /* Action Button */
        .action-btn {
            background: #4299e1;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 12px;
            padding: 6px 12px;
            border-radius: 4px;
            transition: background 0.2s;
            font-weight: 500;
        }
        
        .action-btn:hover {
            background: #3182ce;
        }
        
        .view-btn {
            background: #48bb78;
        }
        
        .view-btn:hover {
            background: #38a169;
        }
        
        /* Print icon */
        .print-icon {
            margin-right: 6px;
        }
    `;
    document.head.appendChild(style);
}

console.log('ScrapTrack Transaction Records initialized successfully!');