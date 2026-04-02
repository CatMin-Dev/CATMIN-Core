{{-- Bulk Actions Header Component --}}
<div class="bulk-actions-header">
    <div class="bulk-select-all">
        <input type="checkbox" id="select-all" class="form-check-input" data-action="select-all">
        <label for="select-all" class="form-check-label">Sélectionner tout</label>
    </div>
    
    <div class="bulk-actions-toolbar" id="bulk-toolbar" style="display: none;">
        <span class="selected-count">
            <span id="selected-count-value">0</span> sélectionné(s)
        </span>
        
        <div class="bulk-action-buttons">
            {{ $slot }}
        </div>
    </div>
</div>

<style>
.bulk-actions-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.5rem;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    margin-bottom: 1rem;
}

.bulk-actions-toolbar {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
}

.selected-count {
    font-weight: 600;
    color: #495057;
}

.bulk-action-buttons {
    display: flex;
    gap: 0.5rem;
}

.bulk-action-buttons button,
.bulk-action-buttons a {
    font-size: 0.875rem;
    padding: 0.375rem 0.75rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select-all');
    const toolbar = document.getElementById('bulk-toolbar');
    const countValue = document.getElementById('selected-count-value');
    
    function updateToolbarVisibility() {
        const checkedCount = document.querySelectorAll('input[name="bulk_select[]"]:checked').length;
        countValue.textContent = checkedCount;
        toolbar.style.display = checkedCount > 0 ? 'flex' : 'none';
    }
    
    selectAllCheckbox.addEventListener('change', function() {
        const isChecked = this.checked;
        document.querySelectorAll('input[name="bulk_select[]"]').forEach(checkbox => {
            checkbox.checked = isChecked;
        });
        updateToolbarVisibility();
    });
    
    document.querySelectorAll('input[name="bulk_select[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateToolbarVisibility();
            const totalCount = document.querySelectorAll('input[name="bulk_select[]"]').length;
            const checkedCount = document.querySelectorAll('input[name="bulk_select[]"]:checked').length;
            selectAllCheckbox.checked = (checkedCount === totalCount && checkedCount > 0);
        });
    });
});
</script>
