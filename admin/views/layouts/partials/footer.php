<?php
declare(strict_types=1);

$gitMetaResolver = static function (): array {
    $gitDir = CATMIN_ROOT . '/.git';
    $headFile = $gitDir . '/HEAD';
    if (!is_file($headFile)) {
        return ['branch' => '-', 'commit' => '-'];
    }

    $headRaw = trim((string) file_get_contents($headFile));
    if ($headRaw === '') {
        return ['branch' => '-', 'commit' => '-'];
    }

    $branch = 'detached';
    $commit = '';
    if (str_starts_with($headRaw, 'ref: ')) {
        $ref = trim(substr($headRaw, 5));
        $branch = basename($ref);
        $refFile = $gitDir . '/' . $ref;
        if (is_file($refFile)) {
            $commit = trim((string) file_get_contents($refFile));
        } elseif (is_file($gitDir . '/packed-refs')) {
            $lines = (array) file($gitDir . '/packed-refs', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim((string) $line);
                if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, '^')) {
                    continue;
                }
                $parts = preg_split('/\s+/', $line);
                if (is_array($parts) && count($parts) >= 2 && $parts[1] === $ref) {
                    $commit = (string) $parts[0];
                    break;
                }
            }
        }
    } else {
        $commit = $headRaw;
    }

    return [
        'branch' => $branch !== '' ? $branch : '-',
        'commit' => $commit !== '' ? substr($commit, 0, 12) : '-',
    ];
};

$gitMeta = $gitMetaResolver();
$catminVersion = \Core\versioning\Version::current();
?>
<footer class="cat-footer">
    <div class="cat-footer-inner">
        <small class="cat-footer-left">
            CATMIN <?= htmlspecialchars($catminVersion, ENT_QUOTES, 'UTF-8') ?>
            · <?= htmlspecialchars((string) ($gitMeta['branch'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
            · <?= htmlspecialchars((string) ($gitMeta['commit'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
        </small>
        <small class="cat-footer-right">© <?= date('Y') ?> CATMIN. <?= htmlspecialchars(__('footer.rights'), ENT_QUOTES, 'UTF-8') ?></small>
    </div>
</footer>
