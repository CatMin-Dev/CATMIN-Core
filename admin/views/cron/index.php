<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$tasks = isset($tasks) && is_array($tasks) ? $tasks : [];
$history = isset($history) && is_array($history) ? $history : [];
$message = trim((string) ($message ?? ''));
$messageType = trim((string) ($messageType ?? 'success'));

$pageTitle = __('cron.title');
$pageDescription = '';
$activeNav = 'cron';
$breadcrumbs = [
    ['label' => __('common.admin'), 'href' => $adminBase . '/'],
    ['label' => __('cron.title')],
];

$csrf = htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8');
$cronHumanize = static function (string $expr): string {
    $expr = trim(preg_replace('/\s+/', ' ', $expr) ?? '');
    if ($expr === '') {
        return __('cron.human.empty');
    }

    $parts = explode(' ', $expr);
    if (count($parts) !== 5) {
        return __('cron.human.invalid_format');
    }

    [$min, $hour, $dom, $mon, $dow] = $parts;

    if (preg_match('/^\*\/([0-9]+)$/', $min, $m) === 1 && $hour === '*' && $dom === '*' && $mon === '*' && $dow === '*') {
        return __('cron.human.every_x_minutes') . ' ' . (int) $m[1] . ' ' . __('cron.human.minutes');
    }
    if (ctype_digit($min) && $hour === '*' && $dom === '*' && $mon === '*' && $dow === '*') {
        return __('cron.human.hourly_at') . ' ' . str_pad($min, 2, '0', STR_PAD_LEFT) . ' ' . __('cron.human.minutes');
    }
    if (ctype_digit($min) && ctype_digit($hour) && $dom === '*' && $mon === '*' && $dow === '*') {
        return __('cron.human.daily_at') . ' ' . str_pad($hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($min, 2, '0', STR_PAD_LEFT);
    }
    if (ctype_digit($min) && ctype_digit($hour) && $dom === '*' && $mon === '*' && ctype_digit($dow)) {
        $days = [__('cron.day.sunday'), __('cron.day.monday'), __('cron.day.tuesday'), __('cron.day.wednesday'), __('cron.day.thursday'), __('cron.day.friday'), __('cron.day.saturday')];
        $idx = max(0, min(6, (int) $dow));
        return __('cron.human.every') . ' ' . $days[$idx] . ' ' . __('cron.human.at') . ' ' . str_pad($hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($min, 2, '0', STR_PAD_LEFT);
    }
    if (ctype_digit($min) && ctype_digit($hour) && ctype_digit($dom) && $mon === '*' && $dow === '*') {
        return __('cron.human.monthly_on_day') . ' ' . (int) $dom . ' ' . __('cron.human.at') . ' ' . str_pad($hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($min, 2, '0', STR_PAD_LEFT);
    }

    return __('cron.human.advanced');
};

ob_start();
?>
<section class="card mb-3">
    <div class="card-header bg-transparent border-0 pt-3">
        <h3 class="h6 mb-0"><?= htmlspecialchars(__('cron.create_php_task'), ENT_QUOTES, 'UTF-8') ?></h3>
    </div>
    <div class="card-body pt-2">
        <form method="post" action="<?= htmlspecialchars($adminBase . '/cron/create', ENT_QUOTES, 'UTF-8') ?>" class="row g-2" data-cron-builder-form>
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <div class="col-12 col-lg-3">
                <label class="form-label"><?= htmlspecialchars(__('common.name'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="form-control" name="name" placeholder="<?= htmlspecialchars(__('cron.placeholder.name'), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label"><?= htmlspecialchars(__('cron.user_script_relative'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="form-control" name="script_path" placeholder="cron/cleanup.php" required>
            </div>
            <div class="col-12 col-lg-5">
                <label class="form-label"><?= htmlspecialchars(__('cron.simple_schedule'), ENT_QUOTES, 'UTF-8') ?></label>
                <div class="input-group">
                    <span class="input-group-text"><?= htmlspecialchars(__('common.mode'), ENT_QUOTES, 'UTF-8') ?></span>
                    <select class="form-select" data-cron-frequency>
                        <option value="interval"><?= htmlspecialchars(__('cron.mode.interval'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="hourly"><?= htmlspecialchars(__('cron.mode.hourly'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="daily" selected><?= htmlspecialchars(__('cron.mode.daily'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="weekly"><?= htmlspecialchars(__('cron.mode.weekly'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="monthly"><?= htmlspecialchars(__('cron.mode.monthly'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="custom"><?= htmlspecialchars(__('cron.mode.custom'), ENT_QUOTES, 'UTF-8') ?></option>
                    </select>
                </div>
                <div class="row g-2 mt-1" data-cron-simple-controls>
                    <div class="col-6 col-lg-3" data-mode="interval">
                        <select class="form-select" data-cron-interval>
                            <?php foreach ([5, 10, 15, 30] as $ival): ?>
                                <option value="<?= $ival ?>" <?= $ival === 5 ? 'selected' : '' ?>><?= $ival ?> min</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-lg-3 d-none" data-mode="hourly">
                        <input type="number" class="form-control" min="0" max="59" value="0" data-cron-hourly-minute placeholder="<?= htmlspecialchars(__('cron.minute'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-6 col-lg-3" data-mode="daily weekly monthly">
                        <input type="time" class="form-control" value="02:00" data-cron-time>
                    </div>
                    <div class="col-6 col-lg-3 d-none" data-mode="weekly">
                        <select class="form-select" data-cron-weekday>
                            <option value="1"><?= htmlspecialchars(__('cron.day.monday'), ENT_QUOTES, 'UTF-8') ?></option>
                            <option value="2"><?= htmlspecialchars(__('cron.day.tuesday'), ENT_QUOTES, 'UTF-8') ?></option>
                            <option value="3"><?= htmlspecialchars(__('cron.day.wednesday'), ENT_QUOTES, 'UTF-8') ?></option>
                            <option value="4"><?= htmlspecialchars(__('cron.day.thursday'), ENT_QUOTES, 'UTF-8') ?></option>
                            <option value="5"><?= htmlspecialchars(__('cron.day.friday'), ENT_QUOTES, 'UTF-8') ?></option>
                            <option value="6"><?= htmlspecialchars(__('cron.day.saturday'), ENT_QUOTES, 'UTF-8') ?></option>
                            <option value="0"><?= htmlspecialchars(__('cron.day.sunday'), ENT_QUOTES, 'UTF-8') ?></option>
                        </select>
                    </div>
                    <div class="col-6 col-lg-3 d-none" data-mode="monthly">
                        <select class="form-select" data-cron-monthday>
                            <?php for ($d = 1; $d <= 28; $d++): ?>
                                <option value="<?= $d ?>" <?= $d === 1 ? 'selected' : '' ?>><?= htmlspecialchars(__('cron.day_label'), ENT_QUOTES, 'UTF-8') ?> <?= $d ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-8 col-lg-2">
                <label class="form-label"><?= htmlspecialchars(__('cron.expression'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="form-control" name="schedule_expr" value="0 2 * * *" data-cron-expression required>
                <div class="form-text" data-cron-human><?= htmlspecialchars(__('cron.default_human'), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="col-4 col-lg-2">
                <label class="form-label d-block"><?= htmlspecialchars(__('common.active'), ENT_QUOTES, 'UTF-8') ?></label>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                </div>
            </div>
            <div class="col-12">
                <div class="form-text mb-2"><?= htmlspecialchars(__('cron.user_scripts_hint'), ENT_QUOTES, 'UTF-8') ?></div>
                <button class="btn btn-primary" type="submit"><?= htmlspecialchars(__('cron.add_task'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </form>
    </div>
</section>

<section class="card mb-3">
    <div class="card-header bg-transparent border-0 pt-3">
        <h3 class="h6 mb-0"><?= htmlspecialchars(__('cron.tasks_title'), ENT_QUOTES, 'UTF-8') ?></h3>
    </div>
    <div class="card-body pt-2">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                <tr>
                    <th><?= htmlspecialchars(__('common.name'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(__('cron.table.script'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(__('cron.table.schedule'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(__('cron.table.translation'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(__('common.status'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(__('cron.table.last_run'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th class="text-end"><?= htmlspecialchars(__('common.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if ($tasks === []): ?>
                    <tr><td colspan="7" class="text-body-secondary"><?= htmlspecialchars(__('cron.empty_tasks'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                        <?php $isActive = ((int) ($task['is_active'] ?? 0)) === 1; ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($task['name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><code><?= htmlspecialchars((string) ($task['script_path'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></code></td>
                            <td><code><?= htmlspecialchars((string) ($task['schedule_expr'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></code></td>
                            <td class="small text-body-secondary"><?= htmlspecialchars($cronHumanize((string) ($task['schedule_expr'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="badge <?= $isActive ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                    <?= htmlspecialchars($isActive ? __('common.active') : __('common.inactive'), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars((string) ($task['last_run_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <form method="post" action="<?= htmlspecialchars($adminBase . '/cron/run', ENT_QUOTES, 'UTF-8') ?>" class="d-inline">
                                        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                        <input type="hidden" name="id" value="<?= (int) ($task['id'] ?? 0) ?>">
                                        <button class="btn btn-sm btn-outline-primary" type="submit"><?= htmlspecialchars(__('cron.action.run'), ENT_QUOTES, 'UTF-8') ?></button>
                                    </form>
                                    <form method="post" action="<?= htmlspecialchars($adminBase . '/cron/toggle', ENT_QUOTES, 'UTF-8') ?>" class="d-inline">
                                        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                        <input type="hidden" name="id" value="<?= (int) ($task['id'] ?? 0) ?>">
                                        <input type="hidden" name="active" value="<?= $isActive ? '0' : '1' ?>">
                                        <button class="btn btn-sm btn-outline-secondary" type="submit"><?= htmlspecialchars($isActive ? __('common.disable') : __('common.enable'), ENT_QUOTES, 'UTF-8') ?></button>
                                    </form>
                                    <form method="post" action="<?= htmlspecialchars($adminBase . '/cron/delete', ENT_QUOTES, 'UTF-8') ?>" class="d-inline" data-cat-confirm="<?= htmlspecialchars(__('cron.confirm_delete'), ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                        <input type="hidden" name="id" value="<?= (int) ($task['id'] ?? 0) ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit"><?= htmlspecialchars(__('common.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="card">
    <div class="card-header bg-transparent border-0 pt-3">
        <h3 class="h6 mb-0"><?= htmlspecialchars(__('cron.history_title'), ENT_QUOTES, 'UTF-8') ?></h3>
    </div>
    <div class="card-body pt-2">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                <tr><th><?= htmlspecialchars(__('logs.table.date'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(__('logs.level'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(__('logs.table.message'), ENT_QUOTES, 'UTF-8') ?></th></tr>
                </thead>
                <tbody>
                <?php if ($history === []): ?>
                    <tr><td colspan="3" class="text-body-secondary"><?= htmlspecialchars(__('cron.empty_history'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                <?php else: ?>
                    <?php foreach ($history as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($row['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge text-bg-dark"><?= htmlspecialchars(strtoupper((string) ($row['level'] ?? 'INFO')), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td><code><?= htmlspecialchars((string) ($row['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<script src="/assets/js/catmin-cron.js?v=1"></script>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
