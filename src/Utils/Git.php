<?php

namespace App\Utils;

use App\Exception\ProjectNotInstalledException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class Git
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $deploysDir;

    public function __construct(Filesystem $filesystem, string $deploysDir)
    {
        $this->filesystem = $filesystem;
        $this->deploysDir = $deploysDir;
    }

    public function cloneRepository(string $repository, string $directory = ''): void
    {
        $dir = sprintf('%s/%s', $this->deploysDir, $directory);
        $this->processCommand(sprintf('rm -rf %s', $dir));
        $this->processCommand(sprintf('git clone %s %s', $repository, $dir));
    }

    public function changeBranch(string $branch, string $directory = ''): void
    {
        $this->processCommand(sprintf('cd %s && git checkout %s', $this->getDir($directory), $branch));
    }

    /**
     * @throws ProcessFailedException
     * @throws ProjectNotInstalledException
     */
    public function getLastCommitInfo(string $directory): array
    {
        $dir = $this->getDir($directory);

        $output = explode("\n", $this->processCommand(sprintf('cd %s && git log | head -n 4', $dir)));
        $isMerge = substr($output[1], 0, 1) === 'M';

        return [
            'commit' => substr($output[0], 7, 5),
            'author' => substr($output[1 + (int) $isMerge], 8),
            'date' => \DateTime::createFromFormat(
                'D M d H:i:s Y O',
                substr($output[2 + (int) $isMerge], 8)
            )->format('F jS Y - H:i:s')
        ];
    }

    private function processCommand(string $command): string
    {
        $this->createRootDirIfNotExists();
        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    private function createRootDirIfNotExists(): void
    {
        if (!$this->filesystem->exists($this->deploysDir)) {
            $this->filesystem->mkdir($this->deploysDir);
        }
    }

    /**
     * @throws ProjectNotInstalledException
     */
    private function getDir(string $subDir): string
    {
        $dir = sprintf('%s/%s', $this->deploysDir, $subDir);

        if (!$this->filesystem->exists($dir)) {
            throw new ProjectNotInstalledException();
        }

        return $dir;
    }
}
