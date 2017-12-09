<?php

namespace App\Parser;

use App\Exception\ProjectNotInstallableException;
use App\Model\ProjectConfig;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class ProjectConfigParser
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

    /**
     * @throws ProjectNotInstallableException
     */
    public function parse(string $projectSlug): ProjectConfig
    {
        $fileName = sprintf('%s/%s/.lagen.yml', $this->deploysDir, $projectSlug);

        if (!$this->filesystem->exists($fileName)) {
            throw new ProjectNotInstallableException();
        }

        return new ProjectConfig(Yaml::parse(file_get_contents($fileName)));
    }
}
