<?php

namespace App\Services;

use App\Exceptions\RemoteExecutionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class RemoteCommandService
{
    protected string $host;
    protected int $port;
    protected string $username;
    protected ?string $privateKeyPath;
    protected ?string $passphrase;
    protected int $connectTimeout;
    protected int $commandTimeout;

    public function __construct()
    {
        $this->host = (string) config('remote.host');
        $this->port = (int) config('remote.port', 22);
        $this->username = (string) config('remote.username');
        $this->privateKeyPath = config('remote.private_key_path');
        $this->passphrase = config('remote.passphrase');
        $this->connectTimeout = (int) config('remote.connect_timeout', 20);
        $this->commandTimeout = (int) config('remote.command_timeout', 300);
    }

    public function run(string $command, ?string $errorMessage = null, bool $throwOnFailure = true): array
    {
        $this->validateConfiguration();

        $sshCommand = $this->buildSshCommand($command);

        $result = Process::timeout($this->commandTimeout)->run($sshCommand);

        $payload = [
            'success' => $result->successful(),
            'command' => $command,
            'ssh_command' => $sshCommand,
            'output' => trim($result->output()),
            'error' => trim($result->errorOutput()),
            'exit_code' => $result->exitCode(),
        ];

        if (! $result->successful() && $throwOnFailure) {
            throw new RemoteExecutionException(
                ($errorMessage ?: 'Remote command execution failed.') .
                ' STDERR: ' . ($payload['error'] ?: 'No error output.') .
                ' STDOUT: ' . ($payload['output'] ?: 'No standard output.')
            );
        }

        return $payload;
    }

    protected function buildSshCommand(string $command): string
    {
        $parts = [
            'ssh',
            '-o BatchMode=yes',
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-o ConnectTimeout=' . $this->connectTimeout,
            '-p ' . (int) $this->port,
        ];

        if (filled($this->privateKeyPath)) {
            $parts[] = '-i ' . escapeshellarg($this->privateKeyPath);
        }

        $destination = escapeshellarg($this->username . '@' . $this->host);
        $remoteCommand = escapeshellarg('bash -lc ' . escapeshellarg($command));

        return implode(' ', $parts) . ' ' . $destination . ' ' . $remoteCommand;
    }

    protected function validateConfiguration(): void
    {
        if (blank($this->host)) {
            throw new RemoteExecutionException('REMOTE_HOST is not configured.');
        }

        if (blank($this->username)) {
            throw new RemoteExecutionException('REMOTE_USERNAME is not configured.');
        }

        if (filled($this->privateKeyPath) && ! File::exists($this->privateKeyPath)) {
            throw new RemoteExecutionException("Remote SSH private key not found at: {$this->privateKeyPath}");
        }

        if (filled($this->passphrase)) {
            throw new RemoteExecutionException(
                'Passphrase-protected SSH keys are not supported by this v1 shell-based SSH executor. Use a deploy key without passphrase.'
            );
        }
    }
}