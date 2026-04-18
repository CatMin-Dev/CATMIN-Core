<?php

declare(strict_types=1);

final class ContractDemoService
{
    /** @return array<string, mixed> */
    public function health(): array
    {
        return [
            'ok' => true,
            'module' => 'cat-contract-demo',
        ];
    }
}
