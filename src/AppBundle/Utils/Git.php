<?php

namespace AppBundle\Utils;

use AppBundle\Exception\ProjectNotInstalledException;
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

    /**
     * @param Filesystem $filesystem
     * @param $deploysDir
     */
    public function __construct(Filesystem $filesystem, $deploysDir)
    {
        $this->filesystem = $filesystem;
        $this->deploysDir = $deploysDir;
    }

    /**
     * @param string $repository
     * @param string $directory
     */
    public function cloneRepository($repository, $directory = '')
    {
        $dir = sprintf('%s/%s', $this->deploysDir, $directory);
        $this->processCommand(sprintf('rm -rf %s', $dir));
        $this->processCommand(sprintf('git clone %s %s', $repository, $dir));
    }

    /**
     * @param string $directory
     *
     * @return array
     *
     * @throws ProcessFailedException
     * @throws ProjectNotInstalledException
     */
    public function getLastCommitInfo($directory)
    {
        $dir = sprintf('%s/%s', $this->deploysDir, $directory);

        if (!$this->filesystem->exists($dir)) {
            throw new ProjectNotInstalledException();
        }

        $output = explode("\n", $this->processCommand(sprintf('cd %s && git log | head -n 4', $dir)));
        $isMerge = substr($output[1], 0, 1) === 'M';

        return [
            'commit' => substr($output[0], 7, 5),
            'author' => substr($output[1 + (int) $isMerge], 8),
            'date' => substr($output[2 + (int) $isMerge], 8)
        ];
    }

    /**
     * @param string $command
     *
     * @return string
     */
    private function processCommand($command)
    {
        $this->createRootDirIfNotExists();
        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    private function createRootDirIfNotExists()
    {
        if (!$this->filesystem->exists($this->deploysDir)) {
            $this->filesystem->mkdir($this->deploysDir);
        }
    }
}
