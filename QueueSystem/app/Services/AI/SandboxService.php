<?php

namespace Pterodactyl\Http\Controllers\Admin\Services\AI;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Exception;

class SandboxService
{
    protected $dockerImage = 'ubuntu:20.04';
    protected $timeoutSeconds = 30;
    protected $memoryLimit = '128m';
    protected $cpuLimit = '0.5';

    public function executeCode(string $code, string $language, array $context = []): array
    {
        try {
            $containerId = $this->createContainer($language);
            $result = $this->runCodeInContainer($containerId, $code, $language);
            $this->cleanupContainer($containerId);
            
            return $result;
        } catch (Exception $e) {
            Log::error('Sandbox execution failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'output' => '',
                'error' => 'Sandbox execution failed: ' . $e->getMessage(),
                'exit_code' => -1
            ];
        }
    }

    protected function createContainer(string $language): string
    {
        $image = $this->getImageForLanguage($language);
        
        $command = [
            'docker', 'run', '-d', '--rm',
            '--memory=' . $this->memoryLimit,
            '--cpus=' . $this->cpuLimit,
            '--network=none',
            '--read-only',
            '--tmpfs=/tmp:rw,noexec,nosuid,size=100m',
            $image,
            'sleep', '60'
        ];

        $result = Process::run(implode(' ', $command));
        
        if (!$result->successful()) {
            throw new Exception('Failed to create container: ' . $result->errorOutput());
        }

        return trim($result->output());
    }

    protected function runCodeInContainer(string $containerId, string $code, string $language): array
    {
        $filename = $this->getFilenameForLanguage($language);
        $executor = $this->getExecutorForLanguage($language);

        // Write code to container
        $writeCommand = [
            'docker', 'exec', $containerId,
            'sh', '-c', 'echo ' . escapeshellarg($code) . ' > /tmp/' . $filename
        ];

        $writeResult = Process::timeout($this->timeoutSeconds)->run(implode(' ', $writeCommand));
        
        if (!$writeResult->successful()) {
            throw new Exception('Failed to write code to container');
        }

        // Execute code
        $execCommand = [
            'docker', 'exec', $containerId,
            'timeout', '10s',
            $executor, '/tmp/' . $filename
        ];

        $execResult = Process::timeout($this->timeoutSeconds)->run(implode(' ', $execCommand));

        return [
            'success' => $execResult->successful(),
            'output' => $execResult->output(),
            'error' => $execResult->errorOutput(),
            'exit_code' => $execResult->exitCode()
        ];
    }

    protected function cleanupContainer(string $containerId): void
    {
        Process::run("docker stop {$containerId}");
    }

    protected function getImageForLanguage(string $language): string
    {
        $images = [
            'bash' => 'ubuntu:20.04',
            'python' => 'python:3.9-slim',
            'php' => 'php:8.1-cli',
            'javascript' => 'node:16-slim'
        ];

        return $images[$language] ?? 'ubuntu:20.04';
    }

    protected function getFilenameForLanguage(string $language): string
    {
        $extensions = [
            'bash' => 'script.sh',
            'python' => 'script.py',
            'php' => 'script.php',
            'javascript' => 'script.js'
        ];

        return $extensions[$language] ?? 'script.txt';
    }

    protected function getExecutorForLanguage(string $language): string
    {
        $executors = [
            'bash' => 'bash',
            'python' => 'python3',
            'php' => 'php',
            'javascript' => 'node'
        ];

        return $executors[$language] ?? 'cat';
    }
}