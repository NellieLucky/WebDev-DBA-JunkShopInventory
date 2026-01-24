// Logout Confirmation Dialog Handler

/**
 * Show logout confirmation dialog
 */
function showLogoutConfirmation(event) {
    event.preventDefault();
    
    // Remove any existing modal
    const existingModal = document.querySelector('.logout-dialog-overlay');
    if (existingModal) {
        existingModal.remove();
    }
    
    const modal = document.createElement('div');
    modal.className = 'logout-dialog-overlay';
    modal.innerHTML = `
        <div class="logout-dialog">
            <div class="logout-dialog-header">
                <h2>Confirm Logout</h2>
                <button class="logout-dialog-close">&times;</button>
            </div>
            <div class="logout-dialog-body">
                <p class="logout-message">Are you sure you want to log out?</p>
                <p class="logout-submessage">You will be returned to the login page.</p>
            </div>
            <div class="logout-dialog-actions">
                <button class="logout-btn-cancel">Cancel</button>
                <button class="logout-btn-confirm">Yes, Log Out</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Close button handler
    const closeBtn = modal.querySelector('.logout-dialog-close');
    closeBtn.addEventListener('click', () => {
        modal.remove();
    });
    
    // Cancel button handler
    const cancelBtn = modal.querySelector('.logout-btn-cancel');
    cancelBtn.addEventListener('click', () => {
        modal.remove();
    });
    
    // Confirm logout button handler
    const confirmBtn = modal.querySelector('.logout-btn-confirm');
    confirmBtn.addEventListener('click', () => {
        window.location.href = 'logout.php';
    });
    
    // Click outside to close
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

/**
 * Initialize logout buttons on page load
 */
function initLogoutButtons() {
    const logoutLinks = document.querySelectorAll('a[href="logout.php"]');
    logoutLinks.forEach(link => {
        link.addEventListener('click', showLogoutConfirmation);
        link.setAttribute('href', '#');
        link.style.cursor = 'pointer';
    });
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', initLogoutButtons);
