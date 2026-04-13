<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$dashboard = isset($dashboard) && is_array($dashboard) ? $dashboard : ['profiles' => []];
$profiles = isset($dashboard['profiles']) && is_array($dashboard['profiles']) ? $dashboard['profiles'] : [];
$message = isset($message) ? trim((string) $message) : '';
$messageType = isset($messageType) ? trim((string) $messageType) : 'info';
$tr = isset($tr) && is_array($tr) ? $tr : [];
$adminBase = isset($adminBase) ? (string) $adminBase : '/admin';
$csrf = htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8');

$pageTitle = (string) ($tr['title'] ?? 'Auteurs');
$pageDescription = (string) ($tr['description'] ?? 'Gestion des auteurs');
$activeNav = 'author-bridge';
$breadcrumbs = [
    ['label' => __('common.admin'), 'href' => $adminBase . '/'],
    ['label' => 'Organisation'],
    ['label' => $pageTitle],
];

$visibilities = [
    'public' => (string) ($tr['visibility_public'] ?? 'Public'),
    'private' => (string) ($tr['visibility_private'] ?? 'Prive'),
    'unlisted' => (string) ($tr['visibility_unlisted'] ?? 'Non liste'),
];

$normalizeSocials = static function (mixed $raw): array {
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        $raw = is_array($decoded) ? $decoded : [];
    }

    if (!is_array($raw)) {
        return [];
    }

    $socials = [];
    foreach ($raw as $item) {
        if (!is_array($item)) {
            continue;
        }
        $network = strtolower(trim((string) ($item['network'] ?? '')));
        $url = trim((string) ($item['url'] ?? ''));
        if ($network === '' || $url === '') {
            continue;
        }
        $socials[] = ['network' => $network, 'url' => $url];
    }

    return $socials;
};

ob_start();
?>

<?php if ($message !== ''): ?>
<div class="alert alert-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?> mb-3">
  <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<section class="card mb-3">
  <div class="cat-staff-manage-bar">
    <span class="small cat-staff-manage-bar-label"><?= htmlspecialchars((string) ($tr['manage_accounts'] ?? 'Gestion des auteurs'), ENT_QUOTES, 'UTF-8') ?></span>
    <a class="btn btn-primary cat-staff-manage-cta" href="<?= htmlspecialchars($adminBase . '/modules/author-bridge/create', ENT_QUOTES, 'UTF-8') ?>">
      <?= htmlspecialchars((string) ($tr['btn_add_author'] ?? 'Ajouter un auteur'), ENT_QUOTES, 'UTF-8') ?>
    </a>
  </div>
</section>

<div class="card">
  <div class="card-body p-0">
    <?php if ($profiles === []): ?>
      <div class="p-4 text-body-secondary small"><?= htmlspecialchars((string) ($tr['no_profiles'] ?? 'Aucun auteur actif.'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th><?= htmlspecialchars((string) ($tr['account'] ?? 'Compte'), ENT_QUOTES, 'UTF-8') ?></th>
              <th><?= htmlspecialchars((string) ($tr['identity'] ?? 'Identite editoriale'), ENT_QUOTES, 'UTF-8') ?></th>
              <th><?= htmlspecialchars((string) ($tr['slug'] ?? 'Slug'), ENT_QUOTES, 'UTF-8') ?></th>
              <th><?= htmlspecialchars((string) ($tr['visibility'] ?? 'Visibilite'), ENT_QUOTES, 'UTF-8') ?></th>
              <th><?= htmlspecialchars((string) ($tr['socials'] ?? 'Reseaux sociaux'), ENT_QUOTES, 'UTF-8') ?></th>
              <th class="text-end"><?= htmlspecialchars((string) ($tr['actions'] ?? 'Actions'), ENT_QUOTES, 'UTF-8') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($profiles as $profile): ?>
              <?php
                $profileId = (int) ($profile['id'] ?? 0);
                $userId = (int) ($profile['user_id'] ?? 0);
                $fullName = trim((string) ($profile['first_name'] ?? '') . ' ' . (string) ($profile['last_name'] ?? ''));
                $socials = $normalizeSocials($profile['socials_json'] ?? null);
                $visibilityLabel = $visibilities[(string) ($profile['visibility'] ?? 'public')] ?? (string) ($profile['visibility'] ?? 'public');
              ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?= htmlspecialchars((string) ($profile['username'] ?? ('#' . $userId)), ENT_QUOTES, 'UTF-8') ?></div>
                  <?php if (!empty($profile['email'])): ?><div class="small text-body-secondary"><?= htmlspecialchars((string) $profile['email'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                </td>
                <td>
                  <div class="fw-semibold"><?= htmlspecialchars((string) ($profile['display_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                  <?php if ($fullName !== ''): ?><div class="small text-body-secondary"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                </td>
                <td><code><?= htmlspecialchars((string) ($profile['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
                <td><span class="badge text-bg-light"><?= htmlspecialchars((string) $visibilityLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                <td><span class="small text-body-secondary"><?= count($socials) ?></span></td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm" role="group">
                    <a class="btn btn-outline-primary" href="<?= htmlspecialchars($adminBase . '/modules/author-bridge/edit?id=' . $profileId, ENT_QUOTES, 'UTF-8') ?>">
                      <i class="bi bi-pencil-square"></i>
                    </a>
                    <form method="post" action="<?= htmlspecialchars($adminBase . '/modules/author-bridge/profile/delete', ENT_QUOTES, 'UTF-8') ?>" data-cat-confirm="<?= htmlspecialchars((string) ($tr['confirm_delete'] ?? 'Retirer cette fiche auteur ?'), ENT_QUOTES, 'UTF-8') ?>">
                      <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                      <input type="hidden" name="id" value="<?= $profileId ?>">
                      <button class="btn btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
