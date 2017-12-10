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
                'FROM %s', $projectConfig->getImage()
            ),
            'WORKDIR /app',
            'ADD . /app'
        ];

        $commands = [];
        foreach ($projectConfig->getEnv() as $key => $value) {
            $commands[] = sprintf('export %s=%s', $key, $value);
        }
        $commands = array_merge($commands, $projectConfig->getCommands());

        $content[] = sprintf('RUN %s', implode(sprintf(' && \%s', PHP_EOL), $commands));

        file_put_contents(
            sprintf('%s/%s/Dockerfile', $this->deployDir, $projectSlug),
            implode(PHP_EOL, $content)
        );
    }
}
