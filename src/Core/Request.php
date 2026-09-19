<?php

declare(strict_types=1);

namespace Craw\Core;

final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     * @param array<string, mixed> $server
     * @param array<string, mixed> $files
     */
    private function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $body,
        public readonly array $server,
        public readonly array $files,
    ) {
    }

    public static function capture(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        // Permet PUT/PATCH/DELETE depuis un formulaire HTML.
        if ($method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper((string) $_POST['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $override;
            }
        }

        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) ? $path : '/';
        $path = '/' . trim(rawurldecode($path), '/');

        return new self($method, $path === '/' ? '/' : rtrim($path, '/'), $_GET, $_POST, $_SERVER, $_FILES);
    }

    public function input(string $key, ?string $default = null): ?string
    {
        $value = $this->body[$key] ?? $this->query[$key] ?? null;

        if (is_array($value)) {
            return $default;
        }

        if ($value === null) {
            return $default;
        }

        // Neutralise les octets nuls et normalise les fins de ligne.
        $value = str_replace(["\0", "\r\n"], ['', "\n"], (string) $value);

        return trim($value);
    }

    /**
     * @return array<int, string>
     */
    public function inputArray(string $key): array
    {
        $value = $this->body[$key] ?? $this->query[$key] ?? [];

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_map(static fn ($item): string => trim((string) $item), $value));
    }

    public function boolean(string $key): bool
    {
        $value = $this->input($key);

        return $value !== null && in_array(strtolower($value), ['1', 'true', 'on', 'yes', 'oui'], true);
    }

    public function integer(string $key, int $default = 0): int
    {
        $value = $this->input($key);

        return $value === null || !is_numeric($value) ? $default : (int) $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;

        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return $file;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $key = 'HTTP_' . str_replace('-', '_', strtoupper($name));

        $value = $this->server[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : $default;
    }

    public function ip(): string
    {
        $candidates = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];

        foreach ($candidates as $key) {
            $value = $this->server[$key] ?? null;
            if (!is_string($value) || $value === '') {
                continue;
            }

            $first = trim(explode(',', $value)[0]);
            if (filter_var($first, FILTER_VALIDATE_IP) !== false) {
                return $first;
            }
        }

        return '0.0.0.0';
    }

    /** Empreinte non reversible de l'IP, pour la limitation de debit et l'audit. */
    public function ipHash(): string
    {
        return hash_hmac('sha256', $this->ip(), Config::get('APP_KEY', 'craw') ?? 'craw');
    }

    public function userAgent(): string
    {
        return mb_substr((string) ($this->server['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    public function isAjax(): bool
    {
        return strtolower((string) $this->header('X-Requested-With', '')) === 'xmlhttprequest';
    }

    public function wantsJson(): bool
    {
        return $this->isAjax() || str_contains((string) $this->header('Accept', ''), 'application/json');
    }
}
