/**
 * WAREHOUSE MANAGEMENT SYSTEM - MAIN JAVASCRIPT
 * Vanilla JavaScript (No Frameworks)
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Sidebar Toggle
    initSidebarToggle();

    // 2. Auto-Uppercase for Item Code fields
    initItemCodeAutoUppercase();

    // 3. Dismissible Alerts & Auto-hide
    initAlerts();

    // 4. Confirmation on Delete / Critical Actions
    initConfirmActions();

    // 5. Modal Dialog Handlers
    initModals();

    // 6. Stock Movement Dynamic Item Stock Info
    initMovementForm();
});

/**
 * Mobile Sidebar Drawer Navigation
 */
function initSidebarToggle() {
    const toggleBtn = document.getElementById('headerSidebarToggle');
    const sidebar = document.getElementById('appSidebar');
    let backdrop = document.getElementById('sidebarBackdrop');

    if (!toggleBtn || !sidebar) return;

    if (!backdrop) {
        backdrop = document.createElement('div');
        backdrop.id = 'sidebarBackdrop';
        backdrop.className = 'sidebar-backdrop';
        document.body.appendChild(backdrop);
    }

    const openSidebar = () => {
        sidebar.classList.add('sidebar-open');
        backdrop.classList.add('active');
    };

    const closeSidebar = () => {
        sidebar.classList.remove('sidebar-open');
        backdrop.classList.remove('active');
    };

    toggleBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        if (sidebar.classList.contains('sidebar-open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    backdrop.addEventListener('click', closeSidebar);
}

/**
 * Automatically converts item code inputs to uppercase
 */
function initItemCodeAutoUppercase() {
    const codeInputs = document.querySelectorAll('input[name="item_code"], .auto-uppercase');
    codeInputs.forEach(input => {
        input.addEventListener('input', () => {
            const start = input.selectionStart;
            const end = input.selectionEnd;
            input.value = input.value.toUpperCase();
            input.setSelectionRange(start, end);
        });
    });
}

/**
 * Flash alert auto-close & dismiss buttons
 */
function initAlerts() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const closeBtn = alert.querySelector('.alert-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                alert.style.transition = 'opacity 0.25s ease, height 0.25s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 250);
            });
        }

        // Auto fade out success alerts after 6 seconds
        if (alert.classList.contains('alert-success')) {
            setTimeout(() => {
                if (document.body.contains(alert)) {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }
            }, 6000);
        }
    });
}

/**
 * Data-confirm interceptor for deletion buttons
 */
function initConfirmActions() {
    document.addEventListener('click', (e) => {
        const target = e.target.closest('[data-confirm]');
        if (target) {
            const message = target.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
            if (!confirm(message)) {
                e.preventDefault();
                e.stopPropagation();
            }
        }
    });
}

/**
 * Vanilla Modal Dialog System
 */
function initModals() {
    // Open modal triggers
    document.querySelectorAll('[data-modal-target]').forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            const modalId = trigger.getAttribute('data-modal-target');
            openModal(modalId);
        });
    });

    // Close modal triggers
    document.querySelectorAll('[data-modal-close], .modal-close-btn').forEach(button => {
        button.addEventListener('click', () => {
            const modal = button.closest('.modal-overlay');
            if (modal) closeModal(modal.id);
        });
    });

    // Close modal when clicking on backdrop
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal(modal.id);
            }
        });
    });

    // Close modal on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const openModalEl = document.querySelector('.modal-overlay.active');
            if (openModalEl) closeModal(openModalEl.id);
        }
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

/**
 * Stock Movements form dynamic item details display
 */
function initMovementForm() {
    const itemSelect = document.getElementById('movementItemSelect');
    const infoDisplay = document.getElementById('selectedItemStockInfo');
    const movementTypeRadios = document.querySelectorAll('input[name="movement_type"]');
    const quantityInput = document.getElementById('movementQuantity');

    if (!itemSelect) return;

    function updateStockInfo() {
        const selectedOption = itemSelect.options[itemSelect.selectedIndex];
        if (!selectedOption || !selectedOption.value) {
            if (infoDisplay) infoDisplay.innerHTML = '';
            return;
        }

        const currentQty = parseInt(selectedOption.getAttribute('data-stock') || '0', 10);
        const unit = selectedOption.getAttribute('data-unit') || 'units';
        const reorder = parseInt(selectedOption.getAttribute('data-reorder') || '0', 10);

        if (infoDisplay) {
            let badgeClass = currentQty <= reorder ? 'badge-warning' : 'badge-success';
            infoDisplay.innerHTML = `
                <div style="margin-top: 8px; font-size: 0.88rem; display: flex; align-items: center; gap: 8px;">
                    <span>Current Available Stock:</span>
                    <strong style="font-size: 1rem;">${currentQty.toLocaleString()} ${unit}</strong>
                    <span class="badge ${badgeClass}">${currentQty <= reorder ? 'Low Stock Warning' : 'In Stock'}</span>
                </div>
            `;
        }

        // Validate OUT quantity against available stock in real time
        validateStockLimit();
    }

    function validateStockLimit() {
        if (!quantityInput) return;
        const selectedOption = itemSelect.options[itemSelect.selectedIndex];
        if (!selectedOption || !selectedOption.value) return;

        const currentQty = parseInt(selectedOption.getAttribute('data-stock') || '0', 10);
        let selectedType = 'IN';
        movementTypeRadios.forEach(r => {
            if (r.checked) selectedType = r.value;
        });

        const enteredQty = parseInt(quantityInput.value || '0', 10);
        if (selectedType === 'OUT' && enteredQty > currentQty) {
            quantityInput.setCustomValidity(`Cannot exceed available stock of ${currentQty}`);
        } else {
            quantityInput.setCustomValidity('');
        }
    }

    itemSelect.addEventListener('change', updateStockInfo);
    movementTypeRadios.forEach(radio => radio.addEventListener('change', validateStockLimit));
    if (quantityInput) {
        quantityInput.addEventListener('input', validateStockLimit);
    }

    // Trigger initial on page load if an item is preselected
    if (itemSelect.value) {
        updateStockInfo();
    }
}

/**
 * Report Print Function
 */
function printReport() {
    window.print();
}

/**
 * Helper to populate demo credentials on login page
 */
function fillDemoLogin(username, password) {
    const userField = document.getElementById('loginUsername');
    const passField = document.getElementById('loginPassword');
    if (userField && passField) {
        userField.value = username;
        passField.value = password;
        userField.focus();
    }
}
