/**
 * JavaScript Functions for Warehouse Management System
 * Handles form validation, dynamic interactions, and user experience improvements
 */

// Global variables
let currentPage = 1;
const itemsPerPage = 10;

/**
 * Initialize the application when DOM is loaded
 */
document.addEventListener('DOMContentLoaded', function() {
    initializeSearch();
    initializeFormValidation();
    initializeSorting();
    updateActiveNavigation();
});

/**
 * Update active navigation based on current page
 */
function updateActiveNavigation() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('nav a');
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href'))) {
            link.classList.add('active');
        }
    });
}

/**
 * Initialize search functionality
 */
function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    
    if (searchInput) {
        searchInput.addEventListener('input', debounce(performSearch, 300));
    }
    
    if (categoryFilter) {
        categoryFilter.addEventListener('change', performSearch);
    }
}

/**
 * Debounce function to limit search frequency
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Perform search and filter operations
 */
function performSearch() {
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    const categoryFilter = document.getElementById('categoryFilter')?.value || '';
    const tableRows = document.querySelectorAll('#productTable tbody tr');
    
    let visibleCount = 0;
    
    tableRows.forEach(row => {
        const name = row.querySelector('[data-field="name"]')?.textContent.toLowerCase() || '';
        const sku = row.querySelector('[data-field="sku"]')?.textContent.toLowerCase() || '';
        const category = row.querySelector('[data-field="category"]')?.textContent || '';
        
        const matchesSearch = name.includes(searchTerm) || sku.includes(searchTerm);
        const matchesCategory = !categoryFilter || category === categoryFilter;
        
        if (matchesSearch && matchesCategory) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    updateSearchResults(visibleCount);
}

/**
 * Update search results count
 */
function updateSearchResults(count) {
    const resultsElement = document.getElementById('searchResults');
    if (resultsElement) {
        resultsElement.textContent = `Showing ${count} items`;
    }
}

/**
 * Initialize form validation
 */
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[data-validate="true"]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
        
        // Real-time validation
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                clearFieldError(this);
            });
        });
    });
}

/**
 * Validate entire form
 */
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    requiredFields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    // Custom validations
    const skuField = form.querySelector('input[name="sku"]');
    const quantityField = form.querySelector('input[name="quantity"]');
    const priceField = form.querySelector('input[name="price"]');
    
    if (skuField && !validateSKU(skuField.value)) {
        showFieldError(skuField, 'SKU must be alphanumeric and 3-20 characters long');
        isValid = false;
    }
    
    if (quantityField && !validateQuantity(quantityField.value)) {
        showFieldError(quantityField, 'Quantity must be a non-negative number');
        isValid = false;
    }
    
    if (priceField && !validatePrice(priceField.value)) {
        showFieldError(priceField, 'Price must be a positive number');
        isValid = false;
    }
    
    return isValid;
}

/**
 * Validate individual field
 */
function validateField(field) {
    const value = field.value.trim();
    
    if (field.hasAttribute('required') && !value) {
        showFieldError(field, 'This field is required');
        return false;
    }
    
    clearFieldError(field);
    return true;
}

/**
 * Show field error message
 */
function showFieldError(field, message) {
    clearFieldError(field);
    
    field.style.borderColor = '#e74c3c';
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.style.color = '#e74c3c';
    errorDiv.style.fontSize = '0.9rem';
    errorDiv.style.marginTop = '0.25rem';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

/**
 * Clear field error message
 */
function clearFieldError(field) {
    field.style.borderColor = '#e0e0e0';
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}

/**
 * Validate SKU format
 */
function validateSKU(sku) {
    const skuPattern = /^[A-Za-z0-9]{3,20}$/;
    return skuPattern.test(sku);
}

/**
 * Validate quantity
 */
function validateQuantity(quantity) {
    const num = parseInt(quantity);
    return !isNaN(num) && num >= 0;
}

/**
 * Validate price
 */
function validatePrice(price) {
    const num = parseFloat(price);
    return !isNaN(num) && num > 0;
}

/**
 * Initialize table sorting
 */
function initializeSorting() {
    const sortableHeaders = document.querySelectorAll('th[data-sort]');
    
    sortableHeaders.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            sortTable(this.dataset.sort);
        });
    });
}

/**
 * Sort table by column
 */
function sortTable(column) {
    const table = document.getElementById('productTable');
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    const isAscending = table.dataset.sortDir !== 'asc';
    table.dataset.sortDir = isAscending ? 'asc' : 'desc';
    
    rows.sort((a, b) => {
        const aValue = a.querySelector(`[data-field="${column}"]`)?.textContent || '';
        const bValue = b.querySelector(`[data-field="${column}"]`)?.textContent || '';
        
        // Numeric comparison for quantity and price
        if (column === 'quantity' || column === 'price') {
            const aNum = parseFloat(aValue) || 0;
            const bNum = parseFloat(bValue) || 0;
            return isAscending ? aNum - bNum : bNum - aNum;
        }
        
        // String comparison
        return isAscending ? 
            aValue.localeCompare(bValue) : 
            bValue.localeCompare(aValue);
    });
    
    rows.forEach(row => tbody.appendChild(row));
    
    // Update sort indicators
    updateSortIndicators(column, isAscending);
}

/**
 * Update sort indicators in table headers
 */
function updateSortIndicators(activeColumn, isAscending) {
    const headers = document.querySelectorAll('th[data-sort]');
    
    headers.forEach(header => {
        const indicator = header.querySelector('.sort-indicator') || 
            document.createElement('span');
        indicator.className = 'sort-indicator';
        
        if (header.dataset.sort === activeColumn) {
            indicator.textContent = isAscending ? ' ↑' : ' ↓';
        } else {
            indicator.textContent = '';
        }
        
        if (!header.querySelector('.sort-indicator')) {
            header.appendChild(indicator);
        }
    });
}

/**
 * Confirm delete action
 */
function confirmDelete(itemName) {
    return confirm(`Are you sure you want to delete "${itemName}"? This action cannot be undone.`);
}

/**
 * Show success message
 */
function showMessage(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

/**
 * Format currency
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

/**
 * Format number with commas
 */
function formatNumber(number) {
    return new Intl.NumberFormat('en-US').format(number);
}

/**
 * Check for low stock items and highlight them
 */
function highlightLowStock() {
    const rows = document.querySelectorAll('#productTable tbody tr');
    
    rows.forEach(row => {
        const quantityCell = row.querySelector('[data-field="quantity"]');
        const minStockCell = row.querySelector('[data-field="min_stock_level"]');
        
        if (quantityCell && minStockCell) {
            const quantity = parseInt(quantityCell.textContent) || 0;
            const minStock = parseInt(minStockCell.textContent) || 0;
            
            if (quantity <= minStock) {
                row.classList.add('low-stock');
                quantityCell.innerHTML += ' ⚠️';
            }
        }
    });
}

/**
 * Auto-generate SKU based on product name
 */
function generateSKU() {
    const nameField = document.querySelector('input[name="name"]');
    const skuField = document.querySelector('input[name="sku"]');
    const categoryField = document.querySelector('select[name="category"]');
    
    if (nameField && skuField && !skuField.value) {
        const name = nameField.value.trim();
        const category = categoryField?.value || '';
        
        if (name) {
            // Generate SKU from first letters of words + category prefix
            const nameWords = name.split(' ').filter(word => word.length > 0);
            const namePrefix = nameWords.map(word => word.charAt(0).toUpperCase()).join('');
            const categoryPrefix = category.substring(0, 2).toUpperCase();
            const timestamp = Date.now().toString().slice(-3);
            
            const generatedSKU = `${categoryPrefix}${namePrefix}${timestamp}`;
            skuField.value = generatedSKU;
        }
    }
}

/**
 * Initialize auto-SKU generation
 */
document.addEventListener('DOMContentLoaded', function() {
    const nameField = document.querySelector('input[name="name"]');
    if (nameField) {
        nameField.addEventListener('blur', generateSKU);
    }
    
    // Highlight low stock items if on inventory page
    if (window.location.pathname.includes('inventory.php')) {
        highlightLowStock();
    }
});
