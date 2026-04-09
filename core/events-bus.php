<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/logs/Logger.php';

final class CoreEventsBus
{
    /** @var array<string,list<callable>> */
    private array $listeners = [];

    /** @var array<string,list<callable>> */
    private array $filters = [];

    private static ?CoreEventsBus $instance = null;

    public static function instance(): CoreEventsBus
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function on(string $event, callable $listener): void
    {
        $event = $this->sanitizeEvent($event);
        if ($event === '') {
            return;
        }
        $this->listeners[$event] ??= [];
        $this->listeners[$event][] = $listener;
    }

    public function hook(string $name, callable $listener): void
    {
        $name = $this->sanitizeEvent($name);
        if ($name === '') {
            return;
        }
        $this->filters[$name] ??= [];
        $this->filters[$name][] = $listener;
    }

    public function emit(string $event, array $payload = []): void
    {
        $event = $this->sanitizeEvent($event);
        if ($event === '') {
            return;
        }

        Core\logs\Logger::info('CATMIN event emitted', [
            'event' => $event,
            'payload' => $payload,
        ]);

        $listeners = $this->listeners[$event] ?? [];
        foreach ($listeners as $listener) {
            try {
                $listener($payload, $event);
            } catch (\Throwable $e) {
                Core\logs\Logger::warning('CATMIN event listener failed', [
                    'event' => $event,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function apply(string $hookName, mixed $value, array $context = []): mixed
    {
        $hookName = $this->sanitizeEvent($hookName);
        if ($hookName === '') {
            return $value;
        }

        $filters = $this->filters[$hookName] ?? [];
        foreach ($filters as $filter) {
            try {
                $value = $filter($value, $context, $hookName);
            } catch (\Throwable $e) {
                Core\logs\Logger::warning('CATMIN hook failed', [
                    'hook' => $hookName,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $value;
    }

    private function sanitizeEvent(string $name): string
    {
        $name = strtolower(trim($name));
        if ($name === '' || preg_match('/^[a-z0-9]+(?:\.[a-z0-9_]+)*$/', $name) !== 1) {
            return '';
        }

        return $name;
    }
}

if (!function_exists('catmin_event_on')) {
    function catmin_event_on(string $event, callable $listener): void
    {
        CoreEventsBus::instance()->on($event, $listener);
    }
}

if (!function_exists('catmin_event_emit')) {
    function catmin_event_emit(string $event, array $payload = []): void
    {
        CoreEventsBus::instance()->emit($event, $payload);
    }
}

if (!function_exists('catmin_hook_register')) {
    function catmin_hook_register(string $hookName, callable $listener): void
    {
        CoreEventsBus::instance()->hook($hookName, $listener);
    }
}

if (!function_exists('catmin_hook_apply')) {
    function catmin_hook_apply(string $hookName, mixed $value, array $context = []): mixed
    {
        return CoreEventsBus::instance()->apply($hookName, $value, $context);
    }
}

