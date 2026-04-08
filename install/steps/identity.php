<?php

declare(strict_types=1);

return [
    'title' => 'Identity',
    'validate' => static function (array $input, \Install\InstallerContext|null $context = null): array {
        $appName = trim((string) ($input['app_name'] ?? 'CATMIN'));
        $appUrl = trim((string) ($input['app_url'] ?? '/'));
        $operatorType = trim((string) ($input['operator_type'] ?? 'particulier'));
        $operator = trim((string) ($input['operator_name'] ?? ''));
        $allowedTypes = ['particulier', 'entreprise', 'asbl', 'association', 'collectivite', 'administration', 'autre'];

        if ($appName === '') {
            return ['ok' => false, 'message' => 'App name required.', 'data' => []];
        }

        if (!in_array($operatorType, $allowedTypes, true)) {
            return ['ok' => false, 'message' => 'Invalid operator type.', 'data' => []];
        }

        if ($operatorType !== 'particulier' && $operator === '') {
            return ['ok' => false, 'message' => 'Operator name required.', 'data' => []];
        }

        return ['ok' => true, 'data' => [
            'app_name' => $appName,
            'app_url' => $appUrl !== '' ? $appUrl : '/',
            'operator_type' => $operatorType,
            'operator_name' => $operator,
        ]];
    },
];
