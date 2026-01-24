// ScrapTrack Dashboard JavaScript
// This handles the interactive elements and chart rendering

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initializeCharts();
    setupEventListeners();
    updateDashboardData();
});

// Global chart instances
let revenueChart = null;
let weeklyChart = null;
let profitChart = null;
let inventoryPieChart = null;

// Setup event listeners for interactive elements
function setupEventListeners() {
    // Quick Action buttons
    const actionButtons = document.querySelectorAll('.action-btn');
    actionButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const action = this.querySelector('span').textContent.trim();
            handleQuickAction(action);
        });
    });

    // View More/All buttons
    const viewButtons = document.querySelectorAll('.btn-view');
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const section = this.closest('.card-header').querySelector('h3').textContent;
            handleViewMore(section);
        });
    });

    // Filter selects
    const filterSelects = document.querySelectorAll('.filter-select');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            handleFilterChange(this.value);
        });
    });
}

// Handle quick action button clicks
function handleQuickAction(action) {
    console.log('Quick action clicked:', action);
    // Actions are handled by onclick attributes in HTML
}

// Handle view more/all button clicks
function handleViewMore(section) {
    console.log('View more clicked for:', section);
    // Actions are handled by onclick attributes in HTML
}

// Handle filter changes
function handleFilterChange(value) {
    console.log('Filter changed to:', value);
    updateDashboardData();
}

// Handle transaction item clicks
function handleTransactionClick(transactionId) {
    console.log('Transaction clicked:', transactionId);
    alert('Transaction details would show here for ID: ' + transactionId);
}

// Handle top item clicks
function handleItemClick(itemId) {
    console.log('Item clicked:', itemId);
    alert('Item details would show here for ID: ' + itemId);
}

// Initialize charts with Chart.js
function initializeCharts() {
    console.log('Initializing charts...');
    
    // Initialize with empty data first
    createRevenueChart();
    createWeeklyChart();
    createProfitChart();
    createInventoryPieChart();
    
    console.log('All charts initialized!');
}

// Create Revenue & Expense Trend Chart
function createRevenueChart(labels = [], revenue = [], expense = []) {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    
    // Destroy existing chart if it exists
    if (revenueChart) {
        revenueChart.destroy();
    }
    
    revenueChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels.length > 0 ? labels : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [
                {
                    label: 'Revenue',
                    data: revenue.length > 0 ? revenue : [0, 0, 0, 0, 0, 0, 0],
                    borderColor: '#4299e1',
                    backgroundColor: 'rgba(66, 153, 225, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Expense',
                    data: expense.length > 0 ? expense : [0, 0, 0, 0, 0, 0, 0],
                    borderColor: '#f56565',
                    backgroundColor: 'rgba(245, 101, 101, 0.1)',
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

// Create Weekly Transactions Chart
function createWeeklyChart(labels = [], data = []) {
    const ctx = document.getElementById('weeklyChart').getContext('2d');
    
    // Destroy existing chart if it exists
    if (weeklyChart) {
        weeklyChart.destroy();
    }
    
    weeklyChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels.length > 0 ? labels : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Transactions',
                data: data.length > 0 ? data : [0, 0, 0, 0, 0, 0, 0],
                backgroundColor: '#4FD1C5',
                borderColor: '#38B2AC',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// Create Net Profit Chart
function createProfitChart(labels = [], revenue = [], expense = []) {
    const ctx = document.getElementById('profitChart').getContext('2d');
    
    // Destroy existing chart if it exists
    if (profitChart) {
        profitChart.destroy();
    }
    
    // Calculate profit data
    const profitData = [];
    if (revenue.length > 0 && expense.length > 0) {
        for (let i = 0; i < revenue.length; i++) {
            profitData.push(revenue[i] - expense[i]);
        }
    } else {
        profitData.push(...[0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]);
    }
    
    profitChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels.length > 0 ? labels : ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Net Profit',
                data: profitData,
                borderColor: '#10B981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

// Create Inventory Pie Chart
function createInventoryPieChart(items = [], total = 0) {
    const ctx = document.getElementById('inventoryPie').getContext('2d');
    
    // Destroy existing chart if it exists
    if (inventoryPieChart) {
        inventoryPieChart.destroy();
    }
    
    // Prepare data
    const labels = items.map(item => item.name);
    const data = items.map(item => item.weight || item.percentage);
    const colors = items.map(item => item.color);
    
    inventoryPieChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels.length > 0 ? labels : ['Metal', 'Plastic', 'Paper', 'Glass', 'Other'],
            datasets: [{
                data: data.length > 0 ? data : [150, 120, 80, 30, 20],
                backgroundColor: colors.length > 0 ? colors : ['#E8B4F5', '#7DD3FC', '#FDE047', '#60A5FA', '#A78BFA'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} kg (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

// Update dashboard data (fetch from backend)
function updateDashboardData() {
    // Show loading state
    document.getElementById('recent-transactions').innerHTML = '<div class="loading">Loading...</div>';
    document.getElementById('top-items').innerHTML = '<div class="loading">Loading...</div>';
    
    fetch('Dashboard.php?ajax=1')
        .then(res => {
            if (!res.ok) throw new Error('Network response was not ok');
            return res.json();
        })
        .then(data => {
            console.log('Dashboard data loaded:', data);
            
            // Update stats
            document.getElementById('today-revenue').textContent = 
                `₱${parseFloat(data.today_revenue).toFixed(2)}`;
            document.getElementById('total-items').textContent = 
                data.total_items;
            document.getElementById('today-transactions').textContent = 
                data.today_transactions;
            document.getElementById('most-weighted').textContent = 
                `${data.most_weighted_item} kg`;
            document.getElementById('net-profit').textContent = 
                `₱${parseFloat(data.net_profit).toFixed(2)}`;
            document.getElementById('inventory-total').textContent = 
                `${data.inventory_weight.total} kg`;
            
            // Update username
            if (data.username) {
                document.getElementById('username').textContent = data.username;
            }
            
            // Update recent transactions
            renderRecentTransactions(data.recent_transactions);
            
            // Update top items
            renderTopItems(data.top_items);
            
            // Update inventory legend
            renderInventoryLegend(data.inventory_weight.items);
            
            // Update weekly total badge
            if (data.weekly_revenue && data.weekly_revenue.revenue) {
                const weeklyTotal = data.weekly_revenue.revenue.reduce((a, b) => a + b, 0);
                document.getElementById('weekly-total').textContent = 
                    `₱${weeklyTotal.toLocaleString()} Total`;
            }
            
            // Update charts
            updateCharts(data);
        })
        .catch(err => {
            console.error('Dashboard fetch error:', err);
            // Show error state
            document.getElementById('recent-transactions').innerHTML = 
                '<div class="error">Failed to load transactions</div>';
            document.getElementById('top-items').innerHTML = 
                '<div class="error">Failed to load top items</div>';
        });
}

// Update charts with fetched data
function updateCharts(data) {
    // Update revenue chart
    if (data.weekly_revenue) {
        createRevenueChart(
            data.weekly_revenue.labels,
            data.weekly_revenue.revenue,
            data.weekly_revenue.expense
        );
    }
    
    // Update weekly chart (use revenue data for now)
    if (data.weekly_revenue) {
        createWeeklyChart(
            data.weekly_revenue.labels,
            data.weekly_revenue.revenue
        );
    }
    
    // Update profit chart
    if (data.weekly_revenue) {
        createProfitChart(
            data.weekly_revenue.labels,
            data.weekly_revenue.revenue,
            data.weekly_revenue.expense
        );
    }
    
    // Update inventory pie chart
    if (data.inventory_weight) {
        createInventoryPieChart(
            data.inventory_weight.items,
            data.inventory_weight.total
        );
    }
}

// Render recent transactions
function renderRecentTransactions(transactions) {
    const container = document.getElementById('recent-transactions');
    container.innerHTML = '';
    
    if (!transactions || transactions.length === 0) {
        container.innerHTML = '<div class="empty">No recent transactions</div>';
        return;
    }
    
    transactions.forEach(tx => {
        const div = document.createElement('div');
        div.className = 'transaction-item';
        div.innerHTML = `
            <div class="transaction-icon">${tx.icon || '💰'}</div>
            <div class="transaction-info">
                <p class="transaction-title">${tx.type || 'Transaction'} ${tx.item_name || ''}</p>
                <p class="transaction-detail">${tx.quantity || ''}</p>
            </div>
            <span class="transaction-time">${tx.time_ago || 'Recently'}</span>
        `;
        container.appendChild(div);
    });
}

// Render top items
function renderTopItems(items) {
    const container = document.getElementById('top-items');
    container.innerHTML = '';
    
    if (!items || items.length === 0) {
        container.innerHTML = '<div class="empty">No top items data</div>';
        return;
    }
    
    items.forEach(item => {
        const div = document.createElement('div');
        div.className = 'item-row';
        div.innerHTML = `
            <div class="item-info">
                <span class="item-icon">${item.icon || '📦'}</span>
                <div>
                    <p class="item-name">${item.name || 'Item'}</p>
                    <p class="item-detail">${item.quantity || '0 pcs'}</p>
                </div>
            </div>
            <span class="item-price">${item.price || '₱0.00'}</span>
        `;
        container.appendChild(div);
    });
}

// Render inventory legend
function renderInventoryLegend(items) {
    const container = document.getElementById('inventory-legend');
    container.innerHTML = '';
    
    if (!items || items.length === 0) {
        container.innerHTML = '<div class="empty">No inventory data</div>';
        return;
    }
    
    items.forEach(item => {
        const div = document.createElement('div');
        div.className = 'legend-item';
        div.innerHTML = `
            <span class="legend-color" style="background: ${item.color || '#ccc'};"></span>
            <span>${item.name || 'Item'}</span>
        `;
        container.appendChild(div);
    });
}

// Modal functions
function openCustomerModal() {
    document.getElementById('customerModal').style.display = 'block';
}

function closeCustomerModal() {
    document.getElementById('customerModal').style.display = 'none';
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('customerModal');
    if (event.target === modal) {
        closeCustomerModal();
    }
});

// Refresh data every 5 minutes
setInterval(updateDashboardData, 300000);

// Handle window resize
window.addEventListener('resize', function() {
    // Reinitialize charts on resize
    if (revenueChart) revenueChart.resize();
    if (weeklyChart) weeklyChart.resize();
    if (profitChart) profitChart.resize();
    if (inventoryPieChart) inventoryPieChart.resize();
});

console.log('ScrapTrack Dashboard initialized successfully!');