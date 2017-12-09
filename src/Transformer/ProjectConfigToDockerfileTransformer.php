<?php

namespace App\Transformer;

use App\Model\ProjectConfig;
use Symfony\Component\Filesystem\Filesystem;

class ProjectConfigToDockerfileTransformer
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $deployDir;

    public function __construct(Filesystem $filesystem, string $deployDir)
    {
        $this->filesystem = $filesystem;
        $this->deployDir = $deployDir;
    }

    public function transform(ProjectConfig $projectConfig, string $projectSlug)
    {
        $content = [
            sprintf(
                'FROM: %s', $projectConfig->getImage()
            ),
            'WORKDIR /app',
            'ADD . /app'
        ];

        foreach ($projectConfig->getEnv() as $key => $value) {
            $content[] = sprintf('RUN export %s=%s', $key, $value);
        }
        $content = array_merge($content, array_map(function(string $command) {
            return sprintf('RUN %s', $command);
        }, $projectConfig->getCommands()));

        $this->filesystem->dumpFile(
            sprintf('%s/%s/Dockerfile', $this->deployDir, $projectSlug),
            implode('\n', $content)
        );
    }
}
