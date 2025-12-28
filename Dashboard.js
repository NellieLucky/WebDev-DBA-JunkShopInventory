// ScrapTrack Dashboard JavaScript
// This handles the interactive elements and chart rendering

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initializeCharts();
    setupEventListeners();
    updateDashboardData();
});

// Setup event listeners for interactive elements
function setupEventListeners() {
    // Quick Action buttons
    const actionButtons = document.querySelectorAll('.action-btn');
    actionButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const action = this.textContent.trim();
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

    // Transaction items click
    const transactionItems = document.querySelectorAll('.transaction-item');
    transactionItems.forEach(item => {
        item.addEventListener('click', function() {
            handleTransactionClick(this);
        });
    });

    // Top items click
    const itemRows = document.querySelectorAll('.item-row');
    itemRows.forEach(row => {
        row.addEventListener('click', function() {
            handleItemClick(this);
        });
    });
}

// Handle quick action button clicks
function handleQuickAction(action) {
    console.log('Quick action clicked:', action);
    
    switch(action) {
        case 'New Transaction':
            alert('Opening New Transaction form...\n(This will redirect to Transaction page)');
            // window.location.href = 'Transaction.html';
            break;
        case 'Add Item':
            alert('Opening Add Item form...\n(This will open a modal or redirect to Inventory page)');
            // window.location.href = 'Inventory.html?action=add';
            break;
        case 'New Customer':
            alert('Opening New Customer form...\n(This will open a customer registration form)');
            break;
        case 'New Employee':
            alert('Opening New Employee form...\n(This will redirect to Employee Management page)');
            // window.location.href = 'EmployeeManagement.html?action=add';
            break;
    }
}

// Handle view more/all button clicks
function handleViewMore(section) {
    console.log('View more clicked for:', section);
    
    switch(section) {
        case 'Revenue & Expense Trend (7 days)':
            alert('Opening detailed revenue analysis...');
            break;
        case 'Recent Transactions':
            alert('Redirecting to Transaction Records...');
            // window.location.href = 'TransactionRecords.html';
            break;
        case 'Weekly Transactions':
            alert('Opening weekly transaction details...');
            break;
        case 'Top 5 Items by Sale':
            alert('Opening complete sales report...');
            break;
        case 'Inventory by Weight (Top 5)':
            alert('Redirecting to Inventory...');
            // window.location.href = 'Inventory.html';
            break;
    }
}

// Handle filter changes
function handleFilterChange(value) {
    console.log('Filter changed to:', value);
    alert('Filtering data by: ' + value);
}

// Handle transaction item clicks
function handleTransactionClick(item) {
    const title = item.querySelector('.transaction-title').textContent;
    const detail = item.querySelector('.transaction-detail').textContent;
    console.log('Transaction clicked:', title, detail);
    
    alert(`Transaction Details:\n${title}\n${detail}\n\n(This would show full transaction details)`);
}

// Handle top item clicks
function handleItemClick(row) { //Example ng query, baguhin nalang yung name
    const itemName = row.querySelector('.item-name').textContent;
    const itemDetail = row.querySelector('.item-detail').textContent;
    const itemPrice = row.querySelector('.item-price').textContent;
    
    console.log('Item clicked:', itemName, itemDetail, itemPrice);
    alert(`Item: ${itemName}\nQuantity: ${itemDetail}\nTotal Sales: ${itemPrice}\n\n(This would show detailed item analytics)`);
}

// Initialize charts with placeholder data
function initializeCharts() {
    console.log('Initializing charts...');
    
    // Revenue & Expense Chart
    createRevenueChart();
    
    // Weekly Transactions Chart
    createWeeklyChart();
    
    // Net Profit Chart
    createProfitChart();
    
    // Inventory Pie Chart
    createInventoryPieChart();
    
    console.log('All charts initialized!');
}

// Create Revenue & Expense Trend Chart
function createRevenueChart() {
    const canvas = document.getElementById('revenueChart');
    if (!canvas) {
        console.error('Revenue chart canvas not found');
        return;
    }
    
    const ctx = canvas.getContext('2d');
    const width = canvas.width = canvas.parentElement.offsetWidth;
    const height = canvas.height = 250;
    
    // Sample data
    const labels = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    const revenue = [6000, 6500, 3000, 5000, 4500, 5500, 5000];
    const expense = [4000, 4500, 2000, 5500, 3000, 6000, 4000];
    
    drawAreaChart(ctx, width, height, labels, revenue, expense);

    // Add hover functionality
    let tooltip = document.getElementById('revenue-tooltip');
    if (!tooltip) {
        tooltip = document.createElement('div');
        tooltip.id = 'revenue-tooltip';
        tooltip.style.position = 'absolute';
        tooltip.style.background = 'rgba(0, 0, 0, 0.8)';
        tooltip.style.color = 'white';
        tooltip.style.padding = '10px 15px';
        tooltip.style.borderRadius = '8px';
        tooltip.style.fontSize = '14px';
        tooltip.style.pointerEvents = 'none';
        tooltip.style.display = 'none';
        document.body.appendChild(tooltip);
    }

    canvas.addEventListener('mousemove', function(event) {
        const rect = canvas.getBoundingClientRect();
        const mouseX = event.clientX - rect.left;
        const mouseY = event.clientY - rect.top;

        const padding = {top: 20, right: 20, bottom: 40, left: 50};
        const chartWidth = width - padding.left - padding.right;
        const stepX = chartWidth / (labels.length - 1);

        // Find the closest index
        const index = Math.round((mouseX - padding.left) / stepX);
        if (index >= 0 && index < labels.length) {
            const rev = revenue[index];
            const exp = expense[index];
            tooltip.innerHTML = `<strong>${labels[index]}</strong>: <span style="color: #4299e1; font-weight: bold;">Revenue: </span>₱${rev.toLocaleString()}, <span style="color: #f56565; font-weight: bold;">Expense: </span>₱${exp.toLocaleString()}`;
            tooltip.style.left = `${event.pageX + 10}px`;
            tooltip.style.top = `${event.pageY - 10}px`;
            tooltip.style.display = 'block';
        } else {
            tooltip.style.display = 'none';
        }
    });

    canvas.addEventListener('mouseout', function() {
        tooltip.style.display = 'none';
    });
}

// Create Weekly Transactions Chart
function createWeeklyChart() {
    const canvas = document.getElementById('weeklyChart');
    if (!canvas) {
        console.error('Weekly chart canvas not found');
        return;
    }
    
    const ctx = canvas.getContext('2d');
    const width = canvas.width = canvas.parentElement.offsetWidth;
    const height = canvas.height = 250;
    
    const labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const data = [8, 12, 16, 20, 18, 28, 24];
    const totalTransactions = data.reduce((sum, value) => sum + value, 0);
    const transactionElement = document.querySelector('.total-badge'); // Kukuhain niya yung may name na class na ito from html
    if (transactionElement) {
        transactionElement.textContent = totalTransactions.toString();
    }
    
    drawBarChart(ctx, width, height, labels, data);
}

// Create Net Profit Chart
function createProfitChart() {
    const canvas = document.getElementById('profitChart');
    if (!canvas) {
        console.error('Profit chart canvas not found');
        return;
    }
    
    const ctx = canvas.getContext('2d');
    const width = canvas.width = canvas.parentElement.offsetWidth;
    const height = canvas.height = 400;
    
    const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const revenue = [20000, 55000, 60000, 65000, 30000, 75000, 100000, 80000, 100000, 70000, 85000, 95000];
    const expense = [10000, 18000, 12000, 20000, 25000, 60000, 70000, 35000, 65000, 55000, 75000, 45000];
    const totalRevenue = revenue.reduce((sum, value) => sum + value, 0);
    const totalExpense = expense.reduce((sum, value) => sum + value, 0);
    const totalProfit = totalRevenue - totalExpense;
    const profitElement = document.querySelector('.profit-value'); // Kukuhain niya yung may name na class na ito from html
    if (profitElement) {
        profitElement.textContent = `₱${totalProfit.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }

    let currentHoveredIndex = null;

    function redrawChart(hoveredIndex = null) {
        drawLineChart(ctx, width, height, labels, revenue, expense, hoveredIndex);
    }

    redrawChart();

    // Add hover functionality
    let tooltip = document.getElementById('profit-tooltip');
    if (!tooltip) {
        tooltip = document.createElement('div');
        tooltip.id = 'profit-tooltip';
        tooltip.style.position = 'absolute';
        tooltip.style.background = 'rgba(0, 0, 0, 0.8)';
        tooltip.style.color = 'white';
        tooltip.style.padding = '10px 15px';
        tooltip.style.borderRadius = '8px';
        tooltip.style.fontSize = '14px';
        tooltip.style.pointerEvents = 'none';
        tooltip.style.display = 'none';
        document.body.appendChild(tooltip);
    }

    canvas.addEventListener('mousemove', function(event) {
        const rect = canvas.getBoundingClientRect();
        const mouseX = event.clientX - rect.left;
        const mouseY = event.clientY - rect.top;

        const padding = {top: 40, right: 20, bottom: 50, left: 60};
        const chartWidth = width - padding.left - padding.right;
        const stepX = chartWidth / (labels.length - 1);

        // Find the closest index
        const index = Math.round((mouseX - padding.left) / stepX);
        if (index >= 0 && index < labels.length) {
            if (currentHoveredIndex !== index) {
                currentHoveredIndex = index;
                redrawChart(currentHoveredIndex);
            }
            const rev = revenue[index];
            const exp = expense[index];
            tooltip.innerHTML = `<strong>${labels[index]}</strong>: <span style="color: #4299e1; font-weight: bold;">Revenue: </span>₱${rev.toLocaleString()}, <span style="color: #f56565; font-weight: bold;">Expense: </span>₱${exp.toLocaleString()}`;
            tooltip.style.left = `${event.pageX + 10}px`;
            tooltip.style.top = `${event.pageY - 10}px`;
            tooltip.style.display = 'block'
        } else {
            if (currentHoveredIndex !== null) {
                currentHoveredIndex = null;
                redrawChart();
            }
            tooltip.style.display = 'none';
        }
    });

    canvas.addEventListener('mouseout', function() {
        if (currentHoveredIndex !== null) {
            currentHoveredIndex = null;
            redrawChart();
        }
        tooltip.style.display = 'none';
    });

    canvas.addEventListener('click', function(event) {
        // Optional: Keep tooltip visible on click, or handle differently
        // For now, just log or do nothing extra
        console.log('Profit chart clicked');
    });
}

// Create Inventory Pie Chart
function createInventoryPieChart() {
    const canvas = document.getElementById('inventoryPie');
    if (!canvas) {
        console.error('Inventory pie chart canvas not found');
        return;
    }
    
    const ctx = canvas.getContext('2d');
    const size = 200;
    canvas.width = size;
    canvas.height = size;
    
    const data = [400, 300, 250, 200, 75]; // kg
    const labels = ['Plastic', 'Metal', 'Paper', 'Glass', 'Other'];
    const colors = ['#A78BFA', '#7DD3FC', '#FDE047', '#60A5FA', '#E8B4F5'];
    const totalWeight = data.reduce((sum, value) => sum + value, 0);
    const weightElement = document.querySelector('.pie-value'); // Kukuhain niya yung may name na class na ito from html
    if (weightElement) {
        weightElement.textContent = `${totalWeight.toLocaleString()} kg`;
    }

    drawDonutChart(ctx, size, data, colors);

    // Sort legend from highest to lowest weight
    const legendItems = labels.map((label, i) => ({ label, color: colors[i], weight: data[i] }));
    legendItems.sort((a, b) => b.weight - a.weight);

    const legendDiv = document.querySelector('.inventory-legend');
    legendDiv.innerHTML = '';
    legendItems.forEach(item => {
        const div = document.createElement('div');
        div.className = 'legend-item';
        div.innerHTML = `<span class="legend-color" style="background: ${item.color};"></span><span>${item.label}</span>`;
        legendDiv.appendChild(div);
    });

    // Add hover functionality
    let tooltip = document.getElementById('inventory-tooltip');
    if (!tooltip) {
        tooltip = document.createElement('div');
        tooltip.id = 'inventory-tooltip';
        tooltip.style.position = 'absolute';
        tooltip.style.background = 'rgba(0, 0, 0, 0.8)';
        tooltip.style.color = 'white';
        tooltip.style.padding = '10px 15px';
        tooltip.style.borderRadius = '8px';
        tooltip.style.fontSize = '14px';
        tooltip.style.pointerEvents = 'none';
        tooltip.style.display = 'none';
        document.body.appendChild(tooltip);
    }

    canvas.addEventListener('mousemove', function(event) {
        const rect = canvas.getBoundingClientRect();
        const mouseX = event.clientX - rect.left;
        const mouseY = event.clientY - rect.top;

        const centerX = size / 2;
        const centerY = size / 2;
        const radius = size / 2 - 10;
        const innerRadius = radius * 0.55;

        const dx = mouseX - centerX;
        const dy = mouseY - centerY;
        const dist = Math.sqrt(dx * dx + dy * dy);

        if (dist >= innerRadius && dist <= radius) {
            let angle = Math.atan2(dy, dx);
            if (angle < 0) angle += 2 * Math.PI; // Normalize to 0-2PI
            angle += Math.PI / 2; // Adjust for starting angle
            if (angle > 2 * Math.PI) angle -= 2 * Math.PI;

            const total = data.reduce((a, b) => a + b, 0);
            let currentAngle = 0;
            let index = -1;
            for (let i = 0; i < data.length; i++) {
                const sliceAngle = (data[i] / total) * 2 * Math.PI;
                if (angle >= currentAngle && angle < currentAngle + sliceAngle) {
                    index = i;
                    break;
                }
                currentAngle += sliceAngle;
            }

            if (index !== -1) {
                tooltip.innerHTML = `<strong><span style="font-weight: bold; color: ${colors[index]};">${labels[index]}:</span></strong> ${data[index]} kg`;
                tooltip.style.left = `${event.pageX + 10}px`;
                tooltip.style.top = `${event.pageY - 10}px`;
                tooltip.style.display = 'block';
            } else {
                tooltip.style.display = 'none';
            }
        } else {
            tooltip.style.display = 'none';
        }
    });

    canvas.addEventListener('mouseout', function() {
        tooltip.style.display = 'none';
    });
}

// Draw area chart (for revenue/expense)
function drawAreaChart(ctx, width, height, labels, data1, data2) {
    const padding = {top: 20, right: 20, bottom: 40, left: 50};
    const chartWidth = width - padding.left - padding.right;
    const chartHeight = height - padding.top - padding.bottom;
    
    const maxValue = Math.max(...data1, ...data2);
    const minValue = 0;
    const range = maxValue - minValue;
    
    // Clear canvas
    ctx.clearRect(0, 0, width, height);
    
    // Draw background
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, width, height);
    
    // Draw grid lines
    ctx.strokeStyle = '#e2e8f0';
    ctx.lineWidth = 1;
    for (let i = 0; i <= 4; i++) {
        const y = padding.top + (chartHeight / 4) * i;
        ctx.beginPath();
        ctx.moveTo(padding.left, y);
        ctx.lineTo(width - padding.right, y);
        ctx.stroke();
    }
    
    // Draw Y-axis labels
    ctx.fillStyle = '#718096';
    ctx.font = '11px sans-serif';
    ctx.textAlign = 'right';
    for (let i = 0; i <= 4; i++) {
        const value = maxValue - (range / 4 * i);
        const y = padding.top + (chartHeight / 4) * i;
        ctx.fillText(Math.round(value).toString(), padding.left - 10, y + 4);
    }
    
    // Calculate points
    const stepX = chartWidth / (labels.length - 1);
    
    // Draw Expense area (background)
    ctx.fillStyle = 'rgba(245, 101, 101, 0.2)';
    ctx.beginPath();
    ctx.moveTo(padding.left, padding.top + chartHeight);
    data2.forEach((value, i) => {
        const x = padding.left + stepX * i;
        const y = padding.top + chartHeight - ((value - minValue) / range * chartHeight);
        ctx.lineTo(x, y);
    });
    ctx.lineTo(padding.left + chartWidth, padding.top + chartHeight);
    ctx.closePath();
    ctx.fill();
    
    // Draw Revenue area
    ctx.fillStyle = 'rgba(66, 153, 225, 0.2)';
    ctx.beginPath();
    ctx.moveTo(padding.left, padding.top + chartHeight);
    data1.forEach((value, i) => {
        const x = padding.left + stepX * i;
        const y = padding.top + chartHeight - ((value - minValue) / range * chartHeight);
        ctx.lineTo(x, y);
    });
    ctx.lineTo(padding.left + chartWidth, padding.top + chartHeight);
    ctx.closePath();
    ctx.fill();
    
    // Draw Expense line
    ctx.strokeStyle = '#f56565';
    ctx.lineWidth = 3;
    ctx.beginPath();
    data2.forEach((value, i) => {
        const x = padding.left + stepX * i;
        const y = padding.top + chartHeight - ((value - minValue) / range * chartHeight);
        if (i === 0) ctx.moveTo(x, y);
        else ctx.lineTo(x, y);
    });
    ctx.stroke();
    
    // Draw Revenue line
    ctx.strokeStyle = '#4299e1';
    ctx.lineWidth = 3;
    ctx.beginPath();
    data1.forEach((value, i) => {
        const x = padding.left + stepX * i;
        const y = padding.top + chartHeight - ((value - minValue) / range * chartHeight);
        if (i === 0) ctx.moveTo(x, y);
        else ctx.lineTo(x, y);
    });
    ctx.stroke();
    
    // Draw X-axis labels
    ctx.fillStyle = '#718096';
    ctx.font = '11px sans-serif';
    ctx.textAlign = 'center';
    labels.forEach((label, i) => {
        const x = padding.left + stepX * i;
        ctx.fillText(label, x, height - padding.bottom + 20);
    });
    
    // Draw legend
    const legendY = padding.top - 5;
    ctx.fillStyle = '#4299e1';
    ctx.fillRect(width - 150, legendY, 15, 15);
    ctx.fillStyle = '#2d3748';
    ctx.font = '12px sans-serif';
    ctx.textAlign = 'left';
    ctx.fillText('Revenue', width - 130, legendY + 12);
    
    ctx.fillStyle = '#f56565';
    ctx.fillRect(width - 70, legendY, 15, 15);
    ctx.fillStyle = '#2d3748';
    ctx.fillText('Expense', width - 50, legendY + 12);
}

// Draw bar chart
function drawBarChart(ctx, width, height, labels, data) {
    const padding = {top: 20, right: 20, bottom: 40, left: 40};
    const chartWidth = width - padding.left - padding.right;
    const chartHeight = height - padding.top - padding.bottom;
    
    const maxValue = Math.max(...data);
    const barWidth = chartWidth / labels.length * 0.6;
    const barGap = chartWidth / labels.length * 0.4;
    
    // Clear canvas
    ctx.clearRect(0, 0, width, height);
    
    // Draw background
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, width, height);
    
    // Draw bars
    data.forEach((value, i) => {
        const x = padding.left + (barWidth + barGap) * i + barGap / 2;
        const barHeight = (value / maxValue) * chartHeight;
        const y = padding.top + chartHeight - barHeight;
        
        // Gradient
        const gradient = ctx.createLinearGradient(0, y, 0, y + barHeight);
        gradient.addColorStop(0, '#4FD1C5');
        gradient.addColorStop(1, '#38B2AC');
        
        ctx.fillStyle = gradient;
        ctx.fillRect(x, y, barWidth, barHeight);
        
        // Value on top
        ctx.fillStyle = '#2d3748';
        ctx.font = 'bold 12px sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(value.toString(), x + barWidth / 2, y - 5);
    });
    
    // Draw X-axis labels
    ctx.fillStyle = '#718096';
    ctx.font = '11px sans-serif';
    ctx.textAlign = 'center';
    labels.forEach((label, i) => {
        const x = padding.left + (barWidth + barGap) * i + barGap / 2 + barWidth / 2;
        ctx.fillText(label, x, height - padding.bottom + 20);
    });
}

// Draw line chart
function drawLineChart(ctx, width, height, labels, data1, data2) {
    const padding = {top: 40, right: 20, bottom: 50, left: 60};
    const chartWidth = width - padding.left - padding.right;
    const chartHeight = height - padding.top - padding.bottom;
    
    const maxValue = Math.max(...data1, ...data2);
    const minValue = 0;
    const range = maxValue - minValue;
    
    // Clear canvas
    ctx.clearRect(0, 0, width, height);
    
    // Draw background
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, width, height);
    
    // Draw grid lines
    ctx.strokeStyle = '#e2e8f0';
    ctx.lineWidth = 1;
    for (let i = 0; i <= 5; i++) {
        const y = padding.top + (chartHeight / 5) * i;
        ctx.beginPath();
        ctx.moveTo(padding.left, y);
        ctx.lineTo(width - padding.right, y);
        ctx.stroke();
    }
    
    // Draw Y-axis labels
    ctx.fillStyle = '#718096';
    ctx.font = '11px sans-serif';
    ctx.textAlign = 'right';
    for (let i = 0; i <= 5; i++) {
        const value = maxValue - (range / 5 * i);
        const y = padding.top + (chartHeight / 5) * i;
        ctx.fillText(Math.round(value).toString(), padding.left - 10, y + 4);
    }
    
    // Calculate points
    const stepX = chartWidth / (labels.length - 1);
    
    // Draw Revenue line
    ctx.strokeStyle = '#4299e1';
    ctx.lineWidth = 3;
    ctx.beginPath();
    data1.forEach((value, i) => {
        const x = padding.left + stepX * i;
        const y = padding.top + chartHeight - ((value - minValue) / range * chartHeight);
        if (i === 0) ctx.moveTo(x, y);
        else ctx.lineTo(x, y);
    });
    ctx.stroke();
    
    // Draw Expense line
    ctx.strokeStyle = '#f56565';
    ctx.lineWidth = 3;
    ctx.beginPath();
    data2.forEach((value, i) => {
        const x = padding.left + stepX * i;
        const y = padding.top + chartHeight - ((value - minValue) / range * chartHeight);
        if (i === 0) ctx.moveTo(x, y);
        else ctx.lineTo(x, y);
    });
    ctx.stroke();
    
    // Draw X-axis labels
    ctx.fillStyle = '#718096';
    ctx.font = '11px sans-serif';
    ctx.textAlign = 'center';
    labels.forEach((label, i) => {
        const x = padding.left + stepX * i;
        ctx.fillText(label, x, height - padding.bottom + 25);
    });
    
    // Draw legend
    const legendY = 15;
    ctx.fillStyle = '#4299e1';
    ctx.fillRect(width - 150, legendY, 15, 15);
    ctx.fillStyle = '#2d3748';
    ctx.font = '12px sans-serif';
    ctx.textAlign = 'left';
    ctx.fillText('Revenue', width - 130, legendY + 12);
    
    ctx.fillStyle = '#f56565';
    ctx.fillRect(width - 70, legendY, 15, 15);
    ctx.fillStyle = '#2d3748';
    ctx.fillText('Expense', width - 50, legendY + 12);
}

// Draw donut chart
function drawDonutChart(ctx, size, data, colors) {
    const centerX = size / 2;
    const centerY = size / 2;
    const radius = size / 2 - 10;
    const innerRadius = radius * 0.55;
    
    const total = data.reduce((a, b) => a + b, 0);
    let currentAngle = -Math.PI / 2;
    
    // Clear canvas
    ctx.clearRect(0, 0, size, size);
    
    // Draw segments
    data.forEach((value, i) => {
        const sliceAngle = (value / total) * Math.PI * 2;
        
        ctx.fillStyle = colors[i];
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, currentAngle, currentAngle + sliceAngle);
        ctx.arc(centerX, centerY, innerRadius, currentAngle + sliceAngle, currentAngle, true);
        ctx.closePath();
        ctx.fill();
        
        // Add subtle border
        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = 2;
        ctx.stroke();
        
        currentAngle += sliceAngle;
    });
    
    // Draw center circle
    ctx.fillStyle = '#ffffff';
    ctx.beginPath();
    ctx.arc(centerX, centerY, innerRadius, 0, Math.PI * 2);
    ctx.fill();
}

// Update dashboard data (placeholder function)
function updateDashboardData() {
    console.log('Dashboard data updated');
}

// Refresh data every 5 minutes
setInterval(updateDashboardData, 300000);

// Handle window resize
window.addEventListener('resize', function() {
    initializeCharts();
});

console.log('ScrapTrack Dashboard initialized successfully!');