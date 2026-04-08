<?php

declare(strict_types=1);

namespace Core\auth;

use Core\config\Config;

final class PasswordPolicy
{
    public function validate(string $password): array
    {
        $errors = [];
        $min = (int) Config::get('security.admin_password_min', 12);

        if (mb_strlen($password) < $min) {
            $errors[] = 'Le mot de passe doit contenir au moins ' . $min . ' caracteres.';
        }
        if ((bool) Config::get('security.admin_password_require_upper', true) && preg_match('/[A-Z]/', $password) !== 1) {
            $errors[] = 'Le mot de passe doit contenir une majuscule.';
        }
        if ((bool) Config::get('security.admin_password_require_lower', true) && preg_match('/[a-z]/', $password) !== 1) {
            $errors[] = 'Le mot de passe doit contenir une minuscule.';
        }
        if ((bool) Config::get('security.admin_password_require_digit', true) && preg_match('/\d/', $password) !== 1) {
            $errors[] = 'Le mot de passe doit contenir un chiffre.';
        }
        if ((bool) Config::get('security.admin_password_require_symbol', true) && preg_match('/[^a-zA-Z0-9]/', $password) !== 1) {
            $errors[] = 'Le mot de passe doit contenir un symbole.';
        }

        return [
            'ok' => $errors === [],
            'errors' => $errors,
        ];
    }
}

