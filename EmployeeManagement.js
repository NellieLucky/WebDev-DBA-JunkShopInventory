// Employee Management JavaScript

let employees = [];
let currentEmployeeId = null;
let isEditMode = false;
let currentEmployeeFilter = 0; // Track current filter index (0 = All Employees)
let currentSelectedPosition = null; // Track currently selected position filter

// DOM Elements
const employeeModal = document.getElementById('employeeModal');
const deleteModal = document.getElementById('deleteModal');
const employeeForm = document.getElementById('employeeForm');
const searchInput = document.getElementById('searchInput');
const employeeTableBody = document.getElementById('employeeTableBody');
const modalTitle = document.getElementById('modalTitle');
const passwordGroup = document.getElementById('passwordGroup');

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadEmployees();
    setupEventListeners();
});

// Setup Event Listeners
function setupEventListeners() {
    // Add Employee Button
    document.getElementById('addEmployeeBtn').addEventListener('click', openAddModal);
    
    // Form Submit
    employeeForm.addEventListener('submit', handleFormSubmit);
    
    // Search Input
    searchInput.addEventListener('input', handleSearch);
    
    // Filter Button
    const filterBtn = document.getElementById('filterBtn');
    if (filterBtn) {
        filterBtn.addEventListener('click', handleFilter);
    }
    
    // Close modals when clicking outside
    employeeModal.addEventListener('click', function(e) {
        if (e.target === employeeModal) {
            closeModal();
        }
    });
    
    deleteModal.addEventListener('click', function(e) {
        if (e.target === deleteModal) {
            closeDeleteModal();
        }
    });
}

// Load Employees from Server
function loadEmployees() {
    fetch('EmployeeManagement.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_all'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            employees = data.employees;
            renderEmployees(employees);
        } else {
            showToast('Failed to load employees', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Keep sample data for demo purposes
    });
}

// Render Employees to Table
function renderEmployees(employeeList) {
    employeeTableBody.innerHTML = '';
    
    if (employeeList.length === 0) {
        employeeTableBody.innerHTML = `
            <tr>
                <td colspan="5" style="text-align: center; padding: 40px; color: #718096;">
                    No employees found
                </td>
            </tr>
        `;
        return;
    }
    
    employeeList.forEach(employee => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${employee.FirstName} ${employee.LastName || ''}</td>
            <td>${formatDate(employee.DateStarted)}</td>
            <td><a href="mailto:${employee.Email}">${employee.Email}</a></td>
            <td><span class="role-badge ${getRoleBadgeClass(employee.Position)}">${employee.Position}</span></td>
            <td>
                <div class="action-buttons">
                    <button class="action-btn edit-btn" title="Edit" onclick="openEditModal(${employee.ManagementID})">
                        <span>✏️</span>
                    </button>
                    <button class="action-btn delete-btn" title="Delete" onclick="openDeleteModal(${employee.ManagementID})">
                        <span>❌</span>
                    </button>
                </div>
            </td>
        `;
        employeeTableBody.appendChild(row);
    });
}

// Get Role Badge Class
function getRoleBadgeClass(position) {
    if (!position) return 'employee';
    const pos = position.toLowerCase();
    if (pos.includes('admin')) return 'admin';
    if (pos.includes('finance') || pos.includes('auditor')) return 'finance';
    return 'employee';
}

// Format Date
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toISOString().split('T')[0];
}

// Open Add Modal
function openAddModal() {
    isEditMode = false;
    currentEmployeeId = null;
    modalTitle.textContent = 'Register Employee';
    employeeForm.reset();
    passwordGroup.style.display = '';
    document.getElementById('password').required = true;
    document.getElementById('confirmPassword').required = true;
    document.getElementById('password').placeholder = 'Initial Password...';
    document.getElementById('confirmPassword').placeholder = 'Confirm Password...';
    employeeModal.classList.add('active');
}

// Open Edit Modal
function openEditModal(id) {
    isEditMode = true;
    currentEmployeeId = id;
    modalTitle.textContent = 'Edit Employee';
    
    // Find employee data
    const employee = employees.find(e => e.ManagementID == id);
    
    if (employee) {
        document.getElementById('employeeId').value = employee.ManagementID;
        document.getElementById('firstName').value = employee.FirstName || '';
        document.getElementById('middleName').value = employee.MiddleName || '';
        document.getElementById('lastName').value = employee.LastName || '';
        document.getElementById('email').value = employee.Email || '';
        document.getElementById('contactNumber').value = employee.Contact_Number || '';
        document.getElementById('position').value = employee.Position || '';
        
        // Password is optional when editing
        document.getElementById('password').required = false;
        document.getElementById('confirmPassword').required = false;
        document.getElementById('password').placeholder = 'Leave blank to keep current';
        document.getElementById('confirmPassword').placeholder = 'Leave blank to keep current';
    }
    
    employeeModal.classList.add('active');
}

// Close Modal
function closeModal() {
    employeeModal.classList.remove('active');
    employeeForm.reset();
    currentEmployeeId = null;
    // Reset placeholders
    document.getElementById('password').placeholder = 'Initial Password...';
    document.getElementById('confirmPassword').placeholder = 'Confirm Password...';
}

// Clear Form
function clearForm() {
    employeeForm.reset();
}

// Open Delete Modal
function openDeleteModal(id) {
    currentEmployeeId = id;
    deleteModal.classList.add('active');
}

// Close Delete Modal
function closeDeleteModal() {
    deleteModal.classList.remove('active');
    currentEmployeeId = null;
}

// Handle Form Submit
function handleFormSubmit(e) {
    e.preventDefault();
    
    // Validate password confirmation
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (password && password !== confirmPassword) {
        showToast('Passwords do not match', 'error');
        return;
    }
    
    const formData = new FormData(employeeForm);
    formData.append('action', isEditMode ? 'update' : 'add');
    
    if (isEditMode) {
        formData.append('id', currentEmployeeId);
    }
    
    fetch('EmployeeManagement.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(isEditMode ? 'Employee updated successfully' : 'Employee registered successfully', 'success');
            closeModal();
            loadEmployees();
        } else {
            showToast(data.message || 'Operation failed', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred', 'error');
    });
}

// Confirm Delete
function confirmDelete() {
    if (!currentEmployeeId) return;
    
    fetch('EmployeeManagement.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=delete&id=${currentEmployeeId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Employee deleted successfully', 'success');
            closeDeleteModal();
            loadEmployees();
        } else {
            showToast(data.message || 'Failed to delete employee', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred', 'error');
    });
}

// Handle Search
function handleSearch() {
    const searchTerm = searchInput.value.toLowerCase().trim();
    
    if (searchTerm === '') {
        renderEmployees(employees);
        return;
    }
    
    const filteredEmployees = employees.filter(employee => {
        const fullName = `${employee.FirstName} ${employee.MiddleName || ''} ${employee.LastName || ''}`.toLowerCase();
        const email = (employee.Email || '').toLowerCase();
        const position = (employee.Position || '').toLowerCase();
        const contact = (employee.Contact_Number || '').toLowerCase();
        const dateStarted = (employee.DateStarted || '').toLowerCase();
        const id = (employee.ManagementID || '').toString().toLowerCase();
        
        return id.includes(searchTerm) ||
               fullName.includes(searchTerm) || 
               email.includes(searchTerm) || 
               position.includes(searchTerm) ||
               contact.includes(searchTerm) ||
               dateStarted.includes(searchTerm);
    });
    
    renderEmployees(filteredEmployees);
}

// Show Toast Notification
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast ${type} show`;
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// Handle Filter
function handleFilter() {
    const filterOptions = ['All Employees', 'By Position', 'Recently Added'];
    
    showFilterModal('Filter Employees', filterOptions, function(selectedIndex) {
        currentEmployeeFilter = selectedIndex; // Track current filter
        let filteredEmployees = employees;
        
        switch(selectedIndex) {
            case 0: // All Employees
                currentSelectedPosition = null;
                filteredEmployees = employees;
                renderEmployees(filteredEmployees);
                break;
            case 1: // By Position
                const positions = [...new Set(employees.map(emp => emp.Position))].filter(Boolean);
                showPositionFilterModal(positions);
                return;
            case 2: // Recently Added
                currentSelectedPosition = null;
                filteredEmployees = [...employees].sort((a, b) => new Date(b.DateStarted) - new Date(a.DateStarted));
                renderEmployees(filteredEmployees);
                break;
        }
    }, currentEmployeeFilter);
}

function showPositionFilterModal(positions) {
    // Find the index of the currently selected position
    const currentPositionIndex = currentSelectedPosition ? positions.indexOf(currentSelectedPosition) : -1;
    
    showFilterModal('Filter by Position', positions, function(selectedIndex) {
        const selectedPosition = positions[selectedIndex];
        currentSelectedPosition = selectedPosition; // Track selected position
        currentEmployeeFilter = 1; // Mark as "By Position" filter
        const filteredEmployees = employees.filter(emp => emp.Position === selectedPosition);
        renderEmployees(filteredEmployees);
    }, currentPositionIndex);
}

function showFilterModal(title, options, callback, currentFilterIndex = -1) {
    // Remove any existing modal
    const existingModal = document.querySelector('.filter-modal-overlay');
    if (existingModal) {
        existingModal.remove();
    }
    
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
    
    // Close button handler
    const closeBtn = modal.querySelector('.filter-modal-close');
    closeBtn.addEventListener('click', () => {
        modal.remove();
    });
    
    // Click outside to close
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.remove();
        }
    });
    
    // Option button handlers
    const optionButtons = modal.querySelectorAll('.filter-option');
    optionButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const index = parseInt(btn.getAttribute('data-index'));
            modal.remove();
            callback(index);
        });
    });
}
