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

        const response = await fetch(url);
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

// Panel Management
async function showPanel(panelId, mode = 'add', data = null) {
    const panel = document.getElementById(panelId);
    const overlay = document.createElement('div');
    overlay.className = 'panel-overlay';
    document.body.appendChild(overlay);

    // Show panel and overlay
    panel.classList.add('active');
    overlay.classList.add('active');

    // Update panel title based on mode
    const titleElement = panel.querySelector(`#${panelId}Title`);
    const entityName = panelId.replace('Panel', '');
    titleElement.textContent = `${mode === 'add' ? 'Add New' : 'Edit'} ${entityName}`;

    // If this is a manager panel, update available branches
    if (entityName === 'manager') {
        try {
            // Get unassigned branches using the modified endpoint
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
        const form = panel.querySelector('form');
        for (const [key, value] of Object.entries(data)) {
            const input = form.querySelector(`[name="${key}"]`);
            if (input && key !== 'bid') { // Skip bid as it's handled above
                input.value = value;
            }
        }
    }

    // Close panel handlers
    const closePanel = () => {
        panel.classList.remove('active');
        overlay.remove();
        panel.querySelector('form').reset();
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
        
        // Update form action
        const form = document.getElementById(`${type}Form`);
        form.action = `update_${type}.php?id=${encodeURIComponent(id)}`;
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

// Navigation functionality
document.addEventListener('DOMContentLoaded', function() {
    // Navigation setup
    const navLinks = document.querySelectorAll('.sidebar-nav a');
    const sections = document.querySelectorAll('.section');

    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            navLinks.forEach(l => l.classList.remove('active'));
            sections.forEach(s => s.classList.remove('active'));
            
            this.classList.add('active');
            
            const sectionId = this.getAttribute('data-section');
            document.getElementById(sectionId).classList.add('active');
        });
    });

    // Update add buttons to use panel system
    document.querySelector('button[onclick="showModal(\'addManagerModal\')"]')
        .setAttribute('onclick', 'showPanel("managerPanel")');
    document.querySelector('button[onclick="showModal(\'addBranchModal\')"]')
        .setAttribute('onclick', 'showPanel("branchPanel")');
});