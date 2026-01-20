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
    document.getElementById('filterBtn').addEventListener('click', openFilterModal);

    // Filter modal controls
    const filterModalCloseBtn = document.getElementById('filterModalCloseBtn');
    const filterModalApplyBtn = document.getElementById('filterModalApplyBtn');
    const filterModalClearBtn = document.getElementById('filterModalClearBtn');
    const presetTodayBtn = document.getElementById('presetTodayBtn');
    const presetWeekBtn = document.getElementById('presetWeekBtn');
    const presetMonthBtn = document.getElementById('presetMonthBtn');
    const filterMonth = document.getElementById('filterMonth');
    const filterYear = document.getElementById('filterYear');

    if (filterModalCloseBtn) filterModalCloseBtn.addEventListener('click', closeFilterModal);
    if (filterModalApplyBtn) filterModalApplyBtn.addEventListener('click', applyFiltersFromModal);
    if (filterModalClearBtn) filterModalClearBtn.addEventListener('click', clearFiltersAndReload);
    if (presetTodayBtn) presetTodayBtn.addEventListener('click', () => setPreset('today'));
    if (presetWeekBtn) presetWeekBtn.addEventListener('click', () => setPreset('week'));
    if (presetMonthBtn) presetMonthBtn.addEventListener('click', () => setPreset('month'));
    if (filterMonth) filterMonth.addEventListener('change', updateMonthYearRange);
    if (filterYear) filterYear.addEventListener('change', updateMonthYearRange);

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
// Open/Close Filter Modal
function openFilterModal() {
    const modal = document.getElementById('filterModal');
    if (modal) modal.classList.add('active');
}

function closeFilterModal() {
    const modal = document.getElementById('filterModal');
    if (modal) modal.classList.remove('active');
}

// Presets for date range
function setPreset(preset) {
    const startInput = document.getElementById('filterStartDate');
    const endInput = document.getElementById('filterEndDate');

    const today = new Date();
    let startDate = new Date(today);
    let endDate = new Date(today);

    if (preset === 'today') {
        // start and end are today
    } else if (preset === 'week') {
        // Set to Monday of this week
        const day = today.getDay(); // 0=Sun, 1=Mon, ...
        const diffToMonday = (day === 0 ? -6 : 1 - day);
        startDate.setDate(today.getDate() + diffToMonday);
        // Set to Sunday of this week
        const diffToSunday = (day === 0 ? 0 : 7 - day);
        endDate.setDate(today.getDate() + diffToSunday);
    } else if (preset === 'month') {
        startDate = new Date(today.getFullYear(), today.getMonth(), 1);
        endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    }

    startInput.value = startDate.toISOString().split('T')[0];
    endInput.value = endDate.toISOString().split('T')[0];
}

function clearFiltersAndReload() {
    const typeSelect = document.getElementById('filterType');
    const startInput = document.getElementById('filterStartDate');
    const endInput = document.getElementById('filterEndDate');
    const monthSelect = document.getElementById('filterMonth');
    const yearSelect = document.getElementById('filterYear');

    if (typeSelect) typeSelect.value = 'all';
    if (startInput) startInput.value = '';
    if (endInput) endInput.value = '';
    if (monthSelect) monthSelect.value = '';
    if (yearSelect) yearSelect.value = '';

    // Reset to all records
    renderTransactionRecords(transactionRecords);
    showNotification('Filters cleared', 'info');
}

function applyFiltersFromModal() {
    const typeSelect = document.getElementById('filterType');
    const startInput = document.getElementById('filterStartDate');
    const endInput = document.getElementById('filterEndDate');

    const type = typeSelect ? typeSelect.value : 'all';
    const startDateStr = startInput ? startInput.value : '';
    const endDateStr = endInput ? endInput.value : '';

    let filtered = [...transactionRecords];

    // Filter by type
    if (type === 'received') {
        filtered = filtered.filter(r => r.type === 'Received');
    } else if (type === 'dispatched') {
        filtered = filtered.filter(r => r.type === 'Dispatched');
    }

    // Filter by date range
    if (startDateStr || endDateStr) {
        const startDate = startDateStr ? new Date(startDateStr) : null;
        const endDate = endDateStr ? new Date(endDateStr) : null;

        filtered = filtered.filter(r => {
            if (!r.date || r.date === 'N/A') return false;
            const d = new Date(r.date);
            if (startDate && d < startDate) return false;
            if (endDate) {
                // include end date boundary (set to end of day)
                const endInclusive = new Date(endDate);
                endInclusive.setHours(23, 59, 59, 999);
                if (d > endInclusive) return false;
            }
            return true;
        });
    }

    renderTransactionRecords(filtered);
    closeFilterModal();
    showNotification(`Applied filters. Showing ${filtered.length} record(s).`, 'success');
}

// Update date range when month/year changes
function updateMonthYearRange() {
    const monthSelect = document.getElementById('filterMonth');
    const yearSelect = document.getElementById('filterYear');
    const startInput = document.getElementById('filterStartDate');
    const endInput = document.getElementById('filterEndDate');

    const today = new Date();
    const selectedMonth = monthSelect && monthSelect.value ? parseInt(monthSelect.value, 10) : (today.getMonth() + 1);
    const selectedYear = yearSelect && yearSelect.value ? parseInt(yearSelect.value, 10) : today.getFullYear();

    // JS Date months are 0-indexed
    const startDate = new Date(selectedYear, selectedMonth - 1, 1);
    const endDate = new Date(selectedYear, selectedMonth, 0); // last day of selected month

    if (startInput) startInput.value = startDate.toISOString().split('T')[0];
    if (endInput) endInput.value = endDate.toISOString().split('T')[0];
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