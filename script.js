// Combined Admin Dashboard JavaScript

// Utility function for API calls
async function apiRequest(url, method = 'GET', data = null) {
    try {
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json'
            }
        };

        if (data) {
            options.body = JSON.stringify(data);
        }

        // Add query parameter for unassigned branches if requesting branches
        if (url.includes('get_branches.php') && !url.includes('?')) {
            url += '?unassigned_only=true';
        }

        console.log(`Fetching URL: ${url} with method: ${method}`);
        const response = await fetch(url, options);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        if (!result) {
            throw new Error('No data received from server');
        }

        return result;
    } catch (error) {
        console.error('API Request Error:', error);
        throw error;
    }
}

// Enhanced Panel Management
async function showPanel(panelId, mode = 'add', data = null) {
    const panel = document.getElementById(panelId);
    const overlay = document.createElement('div');
    overlay.className = 'panel-overlay';
    document.body.appendChild(overlay);
    document.body.style.overflow = 'hidden'; // Prevent scrolling

    // Show panel and overlay
    panel.classList.add('active');
    overlay.classList.add('active');

    // Update panel title based on mode
    const titleElement = panel.querySelector(`#${panelId}Title`);
    const entityName = panelId.replace('Panel', '');
    titleElement.textContent = `${mode === 'add' ? 'Add New' : 'Edit'} ${entityName.charAt(0).toUpperCase() + entityName.slice(1)}`;

    // Reset form and remove any existing error messages
    const form = panel.querySelector('form');
    form.reset();
    form.querySelectorAll('.error-message').forEach(el => el.remove());
    form.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));

    // If this is a manager panel, update available branches
    if (entityName === 'manager') {
        try {
            const availableBranches = await apiRequest('get_branches.php');
            const branchSelect = panel.querySelector('select[name="bid"]');
            
            // Clear existing options
            branchSelect.innerHTML = '';
            
            // Add default option
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = '-- Select Branch --';
            branchSelect.appendChild(defaultOption);
            
            // Add available branches
            availableBranches.forEach(branch => {
                const option = document.createElement('option');
                option.value = branch.id;
                option.textContent = branch.branch_name;
                
                // If editing, include the current branch in options
                if (mode === 'edit' && data && data.bid === branch.id) {
                    option.selected = true;
                }
                
                branchSelect.appendChild(option);
            });
            
            // If editing, add current branch if not in available branches
            if (mode === 'edit' && data && data.bid) {
                const currentBranchExists = availableBranches.some(b => b.id === data.bid);
                if (!currentBranchExists) {
                    const currentBranch = await apiRequest(`get_branches.php?id=${data.bid}`);
                    const option = document.createElement('option');
                    option.value = currentBranch.id;
                    option.textContent = currentBranch.branch_name;
                    option.selected = true;
                    branchSelect.appendChild(option);
                }
            }
        } catch (error) {
            console.error('Error loading available branches:', error);
            alert('Failed to load available branches. Please try again.');
        }
    }

    // If editing, populate form with data
    if (mode === 'edit' && data) {
        for (const [key, value] of Object.entries(data)) {
            const input = form.querySelector(`[name="${key}"]`);
            if (input && key !== 'bid') { // Skip bid as it's handled above
                input.value = value;
            }
        }

        // Update form action for edit mode
        const entityType = panelId.replace('Panel', '');
        form.action = `edit_${entityType}.php?id=${data.id}`;
    } else {
        // Reset form action for add mode
        const entityType = panelId.replace('Panel', '');
        form.action = `add_${entityType}.php`;
    }

    // Close panel handlers
    const closePanel = () => {
        panel.classList.remove('active');
        overlay.remove();
        document.body.style.overflow = ''; // Enable scrolling
        form.reset();
    };

    panel.querySelector('.close-panel').onclick = closePanel;
    panel.querySelector('.btn-cancel').onclick = closePanel;
    overlay.onclick = closePanel;
}

// Generic edit function for both managers and branches
async function editEntity(type, id) {
    if (!id) {
        console.error(`${type} ID is required`);
        return;
    }

    try {
        const data = await apiRequest(`get_${type}.php?id=${encodeURIComponent(id)}`);
        await showPanel(`${type}Panel`, 'edit', data);
    } catch (error) {
        console.error(`Error loading ${type} data:`, error);
        alert(`Failed to load ${type} data. Please try again.`);
    }
}

// Generic delete function for both managers and branches
async function deleteEntity(type, id) {
    if (!id) {
        console.error(`${type} ID is required`);
        return;
    }

    const confirmMessage = type === 'branch' ? 
        'Are you sure you want to delete this branch? This will also affect associated managers.' :
        'Are you sure you want to delete this manager? This action cannot be undone.';

    try {
        if (!confirm(confirmMessage)) {
            return;
        }

        const result = await apiRequest(`delete_${type}.php?id=${encodeURIComponent(id)}`, 'POST');
        
        if (result.success) {
            location.reload();
        } else {
            throw new Error(result.message || `Failed to delete ${type}`);
        }
    } catch (error) {
        console.error(`Error deleting ${type}:`, error);
        alert(`Failed to delete ${type}: ${error.message}`);
    }
}

// Create convenience functions for specific entity types
const editManager = (id) => editEntity('manager', id);
const deleteManager = (id) => deleteEntity('manager', id);
const editBranch = (id) => editEntity('branch', id);
const deleteBranch = (id) => deleteEntity('branch', id);

// Modal functionality (legacy support)
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // Prevent scrolling
    }
}

// Clear search function
function clearSearch() {
    const searchForm = document.getElementById('logSearchForm');
    if (searchForm) {
        const formInputs = searchForm.querySelectorAll('input, select');
        formInputs.forEach(field => {
            field.value = '';
        });
        searchForm.submit();
    }
}

// Main initialization
document.addEventListener('DOMContentLoaded', function() {
    // Section Navigation
    const navLinks = document.querySelectorAll('.sidebar-nav a');
    const sections = document.querySelectorAll('.section');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all links and sections
            navLinks.forEach(l => l.classList.remove('active'));
            sections.forEach(s => s.classList.remove('active'));
            
            // Add active class to clicked link
            this.classList.add('active');
            
            // Show the corresponding section
            let targetSectionId = this.getAttribute('data-section');
            if (!targetSectionId) {
                // Support for href-based navigation (legacy)
                const href = this.getAttribute('href');
                targetSectionId = href ? href.substring(1) : null;
            }
            
            if (targetSectionId) {
                const targetSection = document.getElementById(targetSectionId);
                if (targetSection) {
                    targetSection.classList.add('active');
                    // Update URL hash when switching tabs
                    window.location.hash = targetSectionId;
                }
            }
        });
    });
    
    // Handle the active section when the page is loaded with a hash in the URL
    const hash = window.location.hash;
    if (hash) {
        const sectionId = hash.substring(1); // Strip the '#' from the hash
        const section = document.getElementById(sectionId);
        const activeLink = document.querySelector(`.sidebar-nav a[data-section="${sectionId}"], .sidebar-nav a[href="#${sectionId}"]`);

        if (section && activeLink) {
            navLinks.forEach(link => link.classList.remove('active'));
            sections.forEach(s => s.classList.remove('active'));

            activeLink.classList.add('active');
            section.classList.add('active');
        }
    }

    // Modal functionality
    const modals = document.querySelectorAll('.modal');
    const closeButtons = document.querySelectorAll('.close');
    
    // Close modal when clicking the X
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            modal.style.display = 'none';
            document.body.style.overflow = ''; // Enable scrolling
        });
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        modals.forEach(modal => {
            if (e.target === modal) {
                modal.style.display = 'none';
                document.body.style.overflow = ''; // Enable scrolling
            }
        });
    });
    
    // Update buttons to use panel system instead of modals
    const addManagerButtons = document.querySelectorAll('button[onclick*="addManagerModal"], .btn-add[onclick*="addManagerModal"]');
    addManagerButtons.forEach(button => {
        button.setAttribute('onclick', 'showPanel("managerPanel")');
    });

    const addBranchButtons = document.querySelectorAll('button[onclick*="addBranchModal"], .btn-add[onclick*="addBranchModal"]');
    addBranchButtons.forEach(button => {
        button.setAttribute('onclick', 'showPanel("branchPanel")');
    });
    
    // Edit functionality
    const editButtons = document.querySelectorAll('.btn-edit');
    
    editButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const href = this.getAttribute('href');
            if (!href) return;
            
            const isManager = href.includes('edit_manager.php');
            const id = href.split('=')[1];
            
            if (isManager) {
                editManager(id);
            } else {
                editBranch(id);
            }
        });
    });
    
    // Make table rows clickable for details
    const userRows = document.querySelectorAll('#users table tbody tr');
    
    userRows.forEach(row => {
        row.addEventListener('click', function() {
            const userId = this.cells[0].textContent;
            
            // Toggle a class for a selected state
            userRows.forEach(r => r.classList.remove('selected'));
            this.classList.add('selected');
            
            // Optional: Show additional details for the selected user
            // This could trigger a panel or load data via API
        });
    });
    
    // Responsive sidebar toggle
    let sidebarToggle = document.querySelector('.sidebar-toggle');
    if (!sidebarToggle) {
        sidebarToggle = document.createElement('button');
        sidebarToggle.className = 'sidebar-toggle';
        sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.prepend(sidebarToggle);
        }
    }
    
    sidebarToggle.addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('collapsed');
        document.querySelector('.main-content').classList.toggle('expanded');
    });
    
    // Add tooltip functionality for buttons
    const actionButtons = document.querySelectorAll('.btn-edit, .btn-delete');
    
    actionButtons.forEach(button => {
        // Using title attribute as a simple tooltip
        if (button.classList.contains('btn-edit') && !button.hasAttribute('title')) {
            button.setAttribute('title', 'Edit');
        } else if (button.classList.contains('btn-delete') && !button.hasAttribute('title')) {
            button.setAttribute('title', 'Delete');
        }
    });
});