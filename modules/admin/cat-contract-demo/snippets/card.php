<?php

declare(strict_types=1);

return static function (string $title, string $content): string {
    return '<article class="cat-contract-demo-card"><h3>'
        . htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
        . '</h3><p>'
        . htmlspecialchars($content, ENT_QUOTES, 'UTF-8')
        . '</p></article>';
};
