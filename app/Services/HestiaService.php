<?php

namespace App\Services;

use App\Exceptions\ProvisioningException;
use Illuminate\Support\Facades\Http;

class HestiaService
{
    protected string $apiUrl;
    protected ?string $accessKey;
    protected ?string $secretKey;
    protected bool $verifySsl;
    protected string $returnCode;
    protected string $defaultWebIp;

    public function __construct()
    {
        $this->apiUrl = rtrim((string) config('hestia.api_url'), '/') . '/';
        $this->accessKey = config('hestia.access_key');
        $this->secretKey = config('hestia.secret_key');
        $this->verifySsl = (bool) config('hestia.verify_ssl', true);
        $this->returnCode = (string) config('hestia.returncode', 'no');
        $this->defaultWebIp = (string) config('hestia.default_web_ip', 'default');
    }

    public function createUser(array $payload): array
    {
        return $this->command('v-add-user', [
            $payload['username'],
            $payload['password'],
            $payload['email'],
            $payload['package'],
            $payload['first_name'] ?? $payload['username'],
            $payload['last_name'] ?? '',
        ]);
    }

    public function createDomain(array $payload): array
    {
        return $this->command('v-add-web-domain', [
            $payload['username'],
            $payload['domain'],
            $payload['ip'] ?? $this->defaultWebIp,
            $payload['restart'] ?? 'yes',
            $payload['aliases'] ?? 'none',
        ]);
    }

    public function deleteUser(string $username): array
    {
        return $this->command('v-delete-user', [
            $username,
            'yes',
        ]);
    }

    public function deleteDomain(string $username, string $domain): array
    {
        return $this->command('v-delete-web-domain', [
            $username,
            $domain,
            'yes',
        ]);
    }

    public function suspendUser(string $username): array
    {
        return $this->command('v-suspend-user', [
            $username,
        ]);
    }

    public function unsuspendUser(string $username): array
    {
        return $this->command('v-unsuspend-user', [
            $username,
        ]);
    }

    public function enableLetsEncryptDomain(
        string $username,
        string $domain,
        string $aliases = '',
        string $mail = 'no'
    ): array {
        return $this->command('v-add-letsencrypt-domain', [
            $username,
            $domain,
            $aliases,
            $mail,
        ]);
    }

    public function enableSslForce(
        string $username,
        string $domain,
        string $restart = 'yes',
        string $quiet = 'no'
    ): array {
        return $this->command('v-add-web-domain-ssl-force', [
            $username,
            $domain,
            $restart,
            $quiet,
        ]);
    }

    public function enableSslHsts(
        string $username,
        string $domain,
        string $restart = 'yes',
        string $quiet = 'no'
    ): array {
        return $this->command('v-add-web-domain-ssl-hsts', [
            $username,
            $domain,
            $restart,
            $quiet,
        ]);
    }

    public function resolvePlanPackage(string $planName): string
    {
        return (string) config("hestia.packages.{$planName}", $planName);
    }

    public function command(string $cmd, array $args = []): array
    {
        if (blank($this->apiUrl)) {
            throw new ProvisioningException('HESTIA_API_URL is not configured.');
        }

        if (blank($this->accessKey) || blank($this->secretKey)) {
            throw new ProvisioningException('HESTIA access_key / secret_key are not configured.');
        }

        $payload = [
            'access_key' => $this->accessKey,
            'secret_key' => $this->secretKey,
            'returncode' => $this->returnCode,
            'cmd' => $cmd,
        ];

        foreach (array_values($args) as $index => $value) {
            $payload['arg' . ($index + 1)] = (string) $value;
        }

        $response = Http::timeout(60)
            ->acceptJson()
            ->asJson()
            ->withOptions([
                'verify' => $this->verifySsl,
            ])
            ->post($this->apiUrl, $payload);

        if ($response->failed()) {
            throw new ProvisioningException(
                "Hestia request failed for [{$cmd}] with status {$response->status()}: " . $response->body()
            );
        }

        $exitCodeHeader = $response->header('hestia-exit-code');
        $body = trim($response->body());

        if ($exitCodeHeader !== null && (int) $exitCodeHeader !== 0) {
            throw new ProvisioningException(
                "Hestia command [{$cmd}] failed with exit code {$exitCodeHeader}: {$body}"
            );
        }

        if ($body === '') {
            return [
                'success' => true,
                'cmd' => $cmd,
                'exit_code' => $exitCodeHeader !== null ? (int) $exitCodeHeader : 0,
                'body' => '',
            ];
        }

        $json = json_decode($body, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            return [
                'success' => true,
                'cmd' => $cmd,
                'exit_code' => $exitCodeHeader !== null ? (int) $exitCodeHeader : 0,
                'data' => $json,
                'body' => $body,
            ];
        }

        return [
            'success' => true,
            'cmd' => $cmd,
            'exit_code' => $exitCodeHeader !== null ? (int) $exitCodeHeader : 0,
            'body' => $body,
        ];
    }
}