<?php

declare(strict_types=1);

/**
 * Redesigned Permissions Matrix - By Module Tabs
 * 
 * Better organization with:
 * - Tab navigation per module
 * - Grouped permissions per module
 * - Search/filter capabilities
 * - Better mobile responsiveness
 */

$permissionMatrix = isset($permissionMatrix) && is_array($permissionMatrix) ? $permissionMatrix : [];
$selectedPermissions = isset($selectedPermissions) && is_array($selectedPermissions) ? array_map('intval', $selectedPermissions) : [];

$resolveModuleLabel = static function (string $moduleName): string {
    $key = 'roles.matrix.group_' . $moduleName;
    $translated = __($key);
    if ($translated !== $key) {
        return $translated;
    }

    return match ($moduleName) {
        'admin' => 'Admin',
        'core' => 'Core',
        default => ucfirst(str_replace(['-', '_', '.'], ' ', $moduleName)),
    };
};

// Sort modules - core first, then alphabetical
$sortedMatrix = $permissionMatrix;
usort($sortedMatrix, static function (array $a, array $b): int {
    $aModule = $a['module'] ?? 'core';
    $bModule = $b['module'] ?? 'core';
    
    if ($aModule === 'core') return -1;
    if ($bModule === 'core') return 1;
    
    return strcasecmp($aModule, $bModule);
});
?>
<section class="card border-0 shadow-sm">
    <div class="card-header bg-gradient p-3 border-0">
        <div class="row align-items-center g-3">
            <div class="col">
                <h3 class="h6 mb-0 font-monospace">
                    <i class="icon-lock-open"></i>
                    <?= htmlspecialchars(__('roles.matrix.title'), ENT_QUOTES, 'UTF-8') ?>
                    <span class="badge rounded-pill bg-secondary ms-2"><?= count($permissionMatrix) ?> <?= __('roles.matrix.modules') ?></span>
                </h3>
            </div>
            <div class="col-auto">
                <label class="form-check form-switch m-0">
                    <input class="form-check-input permission-matrix-all" type="checkbox" id="selectAllPermissions">
                    <span class="form-check-label form-check-label-sm"><?= htmlspecialchars(__('roles.matrix.select_all'), ENT_QUOTES, 'UTF-8') ?></span>
                </label>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <?php if ($permissionMatrix === []): ?>
            <div class="alert alert-info m-3 mb-0">
                <i class="icon-alert-circle"></i>
                <?= htmlspecialchars(__('roles.matrix.empty'), ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php else: ?>
            <!-- Tabs Navigation -->
            <div class="border-bottom cat-permissions-tabs-wrap">
                <ul class="nav nav-tabs flex-nowrap m-0 px-3 cat-permissions-tabs" role="tablist">
                    <?php foreach ($sortedMatrix as $idx => $group): ?>
                        <?php $moduleName = (string) ($group['module'] ?? 'core'); ?>
                        <?php $moduleId = preg_replace('/[^a-z0-9-]/i', '-', $moduleName); ?>
                        <?php $moduleLabel = $resolveModuleLabel($moduleName); ?>
                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link <?= $idx === 0 ? 'active' : '' ?>"
                                id="tab-<?= $moduleId ?>"
                                data-bs-toggle="tab"
                                data-bs-target="#pane-<?= $moduleId ?>"
                                type="button"
                                role="tab"
                                aria-controls="pane-<?= $moduleId ?>"
                                aria-selected="<?= $idx === 0 ? 'true' : 'false' ?>"
                            >
                                <small class="fw-semibold">
                                    <span class="permission-module-label"><?= htmlspecialchars($moduleLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="badge bg-light text-dark ms-2 permission-module-count"><?= count($group['permissions'] ?? []) ?></span>
                                </small>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Tabs Content -->
            <div class="tab-content">
                <?php foreach ($sortedMatrix as $idx => $group): ?>
                    <?php 
                        $moduleName = (string) ($group['module'] ?? 'core');
                        $moduleId = preg_replace('/[^a-z0-9-]/i', '-', $moduleName);
                        $moduleLabel = $resolveModuleLabel($moduleName);
                        $permissions = (array) ($group['permissions'] ?? []);
                        $moduleDescriptionKey = 'roles.matrix.module_description_' . $moduleName;
                        $moduleDescription = __($moduleDescriptionKey);
                        if ($moduleDescription === $moduleDescriptionKey) {
                            $moduleDescription = $moduleLabel;
                        }
                    ?>
                    <div 
                        class="tab-pane fade <?= $idx === 0 ? 'show active' : '' ?>" 
                        id="pane-<?= $moduleId ?>" 
                        role="tabpanel" 
                        aria-labelledby="tab-<?= $moduleId ?>"
                    >
                        <div class="p-3">
                            <!-- Module Header -->
                            <div class="mb-3 pb-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="mb-1 fw-bold">
                                            <?= htmlspecialchars($moduleLabel, ENT_QUOTES, 'UTF-8') ?>
                                            <span class="badge bg-light text-dark"><?= count($permissions) ?> <?= __('roles.matrix.permissions') ?></span>
                                        </h5>
                                        <p class="small text-body-secondary mb-0">
                                            <?= htmlspecialchars($moduleDescription, ENT_QUOTES, 'UTF-8') ?>
                                        </p>
                                    </div>
                                    <label class="form-check form-switch">
                                        <input 
                                            class="form-check-input permission-module-toggle" 
                                            type="checkbox"
                                            data-module="<?= htmlspecialchars($moduleName, ENT_QUOTES, 'UTF-8') ?>"
                                        >
                                        <span class="form-check-label"><?= __('roles.matrix.select_module_all') ?></span>
                                    </label>
                                </div>
                            </div>

                            <!-- Permissions Grid -->
                            <div class="row g-2">
                                <?php foreach ($permissions as $permission): ?>
                                    <?php $permId = (int) ($permission['id'] ?? 0); ?>
                                    <?php $isSelected = in_array($permId, $selectedPermissions, true); ?>
                                    <div class="col-12 col-sm-6 col-md-4">
                                        <div class="form-check-card cat-permission-card <?= $isSelected ? 'selected' : '' ?>">
                                            <input
                                                class="form-check-input permission-checkbox"
                                                type="checkbox"
                                                name="permissions[]"
                                                value="<?= $permId ?>"
                                                id="perm-<?= $permId ?>"
                                                data-module="<?= htmlspecialchars($moduleName, ENT_QUOTES, 'UTF-8') ?>"
                                                <?= $isSelected ? 'checked' : '' ?>
                                                
                                            >
                                            <label for="perm-<?= $permId ?>" class="form-check-label d-flex align-items-start gap-2 mb-0 cat-permission-label">
                                                <span class="flex-grow-1">
                                                    <small class="fw-semibold d-block">
                                                        <?= htmlspecialchars((string) ($permission['slug'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                                    </small>
                                                    <small class="text-body-secondary d-block">
                                                        <?= htmlspecialchars((string) ($permission['name'] ?? $permission['slug'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                                    </small>
                                                    <?php if ($permission['description'] ?? ''): ?>
                                                        <small class="text-body-tertiary d-block mt-1" title="<?= htmlspecialchars($permission['description'], ENT_QUOTES, 'UTF-8') ?>">
                                                            <em><?= htmlspecialchars(substr($permission['description'], 0, 60) . (strlen($permission['description']) > 60 ? '...' : ''), ENT_QUOTES, 'UTF-8') ?></em>
                                                        </small>
                                                    <?php endif; ?>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllBtn = document.querySelector('.permission-matrix-all');
    const moduleToggles = document.querySelectorAll('.permission-module-toggle');
    const checkboxes = document.querySelectorAll('.permission-checkbox');
    
    // Select all
    if (selectAllBtn) {
        selectAllBtn.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
                updateCardStyle(cb);
            });
            updateSelectAll();
        });
    }
    
    // Module toggles
    moduleToggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const module = this.dataset.module;
            const moduleCheckboxes = document.querySelectorAll(`.permission-checkbox[data-module="${module}"]`);
            moduleCheckboxes.forEach(cb => {
                cb.checked = this.checked;
                updateCardStyle(cb);
            });
            updateSelectAll();
        });
    });
    
    // Individual checkboxes
    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            updateCardStyle(this);
            updateSelectAll();
        });
    });
    
    function updateCardStyle(checkbox) {
        const card = checkbox.closest('.form-check-card');
        if (card) {
            if (checkbox.checked) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
        }
    }
    
    function updateSelectAll() {
        if (selectAllBtn) {
            const total = checkboxes.length;
            const checked = document.querySelectorAll('.permission-checkbox:checked').length;
            selectAllBtn.indeterminate = checked > 0 && checked < total;
            selectAllBtn.checked = checked === total;
        }
        
        // Update module toggles
        moduleToggles.forEach(toggle => {
            const module = toggle.dataset.module;
            const moduleCheckboxes = document.querySelectorAll(`.permission-checkbox[data-module="${module}"]`);
            const total = moduleCheckboxes.length;
            const checked = document.querySelectorAll(`.permission-checkbox[data-module="${module}"]:checked`).length;
            toggle.indeterminate = checked > 0 && checked < total;
            toggle.checked = checked === total;
        });
    }
    
    // Initial state
    checkboxes.forEach(cb => updateCardStyle(cb));
    updateSelectAll();
});
</script>
