<?php

declare(strict_types=1);

namespace Core\failsafe;

final class SafeViewRenderer
{
    public function renderMinimal(int $status, string $title, string $message): string
    {
        $safeStatus = max(100, min(599, $status));
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        return '<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>CATMIN Failsafe</title></head><body style="margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#fafaf9;color:#1c1917;font-family:system-ui,Segoe UI,Arial,sans-serif"><main style="max-width:680px;background:#fff;border:1px solid #e7e5e4;border-radius:12px;padding:24px"><h1 style="margin:0 0 8px;font-size:1.45rem">' . $safeStatus . ' · ' . $safeTitle . '</h1><p style="margin:0 0 16px">' . $safeMessage . '</p><a href="/" style="color:#c2234d;text-decoration:none">Retour accueil</a></main></body></html>';
    }
}

