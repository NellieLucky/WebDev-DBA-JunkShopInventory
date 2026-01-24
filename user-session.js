// User Session Manager - Keeps user data synchronized with database
// Refreshes user information every 30 seconds to reflect database changes

let userSessionRefreshInterval = null;
let lastUsername = null;

/**
 * Fetch current user data from database and update all displays
 */
function refreshUserSession() {
    fetch('./user_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'action=get_current_user'
    })
    .then(response => {
        // Check if response status indicates user is not authenticated
        if (response.status === 401) {
            console.warn('User session expired or invalid');
            // Don't automatically logout, just log the warning
            return Promise.reject('Not authenticated');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const newUsername = data.username;
            
            // Only update if username has changed
            if (newUsername !== lastUsername) {
                lastUsername = newUsername;
                
                // Update all username displays on the page
                updateUsernameDisplay(newUsername);
            }
        } else {
            console.warn('Failed to refresh user session:', data.error);
            // Don't crash on API errors, just log them
        }
    })
    .catch(error => {
        // Log error but don't cause logout
        console.warn('User session refresh warning:', error);
    });
}

/**
 * Update all username displays on the page
 */
function updateUsernameDisplay(username) {
    // Update all elements with username class
    const usernameElements = document.querySelectorAll('.username, .user-name, [data-user-name]');
    usernameElements.forEach(element => {
        element.textContent = username;
    });
    
    // Update h1 welcome message if it exists
    const welcomeHeading = document.querySelector('h1');
    if (welcomeHeading && welcomeHeading.textContent.includes('Welcome Back')) {
        welcomeHeading.innerHTML = `Welcome Back, <span class="username">${username}</span>`;
    }
    
    console.log('User session updated: ' + username);
}

/**
 * Initialize user session refresh
 * Runs immediately on page load, then every 30 seconds
 */
function initUserSessionRefresh() {
    // First refresh immediately (disabled by default - only enable if needed for database sync)
    // Uncomment the line below if you need real-time username syncing with database changes
    // refreshUserSession();
    
    // Then set up interval to refresh every 30 seconds
    // Disabled by default to avoid potential session issues
    // Uncomment if you need real-time updates:
    // userSessionRefreshInterval = setInterval(refreshUserSession, 30000);
    
    console.log('User session refresh available (disabled by default for stability)');
}

/**
 * Stop refreshing user session (useful for cleanup)
 */
function stopUserSessionRefresh() {
    if (userSessionRefreshInterval) {
        clearInterval(userSessionRefreshInterval);
        userSessionRefreshInterval = null;
        console.log('User session refresh stopped');
    }
}

/**
 * Manual refresh trigger (can be called from other scripts)
 */
function forceUserSessionRefresh() {
    console.log('Forcing user session refresh...');
    refreshUserSession();
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initUserSessionRefresh();
});

// Refresh user session when page becomes visible (e.g., after switching tabs)
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        forceUserSessionRefresh();
    }
});
