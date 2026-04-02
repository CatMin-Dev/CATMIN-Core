{{-- Bulk Actions Dropdown Component --}}
<div class="bulk-actions-dropdown">
    <div class="dropdown">
        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
            Actions de masse
        </button>
        <ul class="dropdown-menu">
            {{ $slot }}
        </ul>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bulkForm = document.getElementById('bulk-form');
    
    if (bulkForm) {
        document.querySelectorAll('[data-bulk-action]').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const action = this.dataset.bulkAction;
                const requiresConfirmation = this.dataset.requiresConfirmation === 'true';
                const message = this.dataset.confirmationMessage || 'Êtes-vous sûr ?';
                
                const selectedCount = document.querySelectorAll('input[name="bulk_select[]"]:checked').length;
                
                if (selectedCount === 0) {
                    alert('Veuillez sélectionner au moins un élément');
                    return;
                }
                
                if (requiresConfirmation && !confirm(message)) {
                    return;
                }
                
                // Set the action and submit the form
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'bulk_action';
                actionInput.value = action;
                bulkForm.appendChild(actionInput);
                bulkForm.submit();
            });
        });
    }
});
</script>

<style>
.bulk-actions-dropdown {
    display: inline-block;
}

.dropdown-menu [data-bulk-action] {
    cursor: pointer;
    padding: 0.5rem 1rem;
}

.dropdown-menu [data-bulk-action]:hover {
    background-color: #f8f9fa;
}

[data-bulk-action][data-requires-confirmation="true"] {
    color: #dc3545;
}
</style>
