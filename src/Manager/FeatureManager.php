<?php

namespace App\Manager;

use App\Exception\FeatureRunErrorException;
use App\Exception\ProjectConfigurationNotFoundException;
use App\Model\Feature;
use App\Parser\FeatureParser;
use App\Parser\TestResultParser;
use App\Transformer\FeatureToStringTransformer;
use Cocur\Slugify\Slugify;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class FeatureManager
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
     * @var string
     */
    private $projectsDir;

    /**
     * @var Slugify
     */
    private $slugify;

    /**
     * @var FeatureParser
     */
    private $featureParser;

    /**
     * @var FeatureToStringTransformer
     */
    private $featureToStringTransformer;

    /**
     * @var ProjectManager
     */
    private $projectManager;

    /**
     * @var TestResultParser
     */
    private $testResultParser;

    public function __construct(
        Filesystem $filesystem,
        string $deploysDir,
        string $projectsDir,
        Slugify $slugify,
        FeatureParser $featureParser,
        FeatureToStringTransformer $featureToStringTransformer,
        ProjectManager $projectManager,
        TestResultParser $testResultParser
    ) {
        $this->filesystem = $filesystem;
        $this->deploysDir = $deploysDir;
        $this->projectsDir = $projectsDir;
        $this->slugify = $slugify;
        $this->featureParser = $featureParser;
        $this->featureToStringTransformer = $featureToStringTransformer;
        $this->projectManager = $projectManager;
        $this->testResultParser = $testResultParser;
    }

    public function getFeature(string $projectSlug, string $featureSlug): Feature
    {
        $feature = $this->featureParser->parse(
            sprintf('%s/%s/%s', $this->projectsDir, $projectSlug, $featureSlug)
        );

        $deployDirExists = $this->filesystem->exists(sprintf('%s/%s', $this->deploysDir, $projectSlug));
        $featureMetadata = $this->getFeatureMetadata($projectSlug, $featureSlug);
        try {
            $lagenConfig = $this->projectManager->retrieveProjectLagenConfig($projectSlug);
        } catch (ProjectConfigurationNotFoundException $e) {
            $lagenConfig = null;
        }
        $feature->setRunnable(
            $deployDirExists &&
            !empty($featureMetadata) &&
            !empty($lagenConfig) &&
            isset($lagenConfig['test'])
        );

        return $feature;
    }

    public function createFeature(string $projectSlug, string $featureName): void
    {
        file_put_contents(
            sprintf(
                '%s/%s/%s.feature',
                $this->projectsDir,
                $projectSlug,
                $this->slugify->slugify($featureName)
            ),
            sprintf("Feature: $featureName\n")
        );
    }

    public function editFeature(string $projectSlug, string $featureSlug, Feature $feature): void
    {
        file_put_contents(
            sprintf(
                '%s/%s/%s',
                $this->projectsDir,
                $projectSlug,
                $featureSlug
            ),
            $this->featureToStringTransformer->transform($feature)
        );
    }

    public function exportFeature(string $projectSlug, string $featureSlug): Feature
    {
        return file_get_contents(
            sprintf('%s/%s/%s', $this->projectsDir, $projectSlug, $featureSlug)
        );
    }

    public function setFeatureMetadata(string $projectSlug, string $featureSlug, array $metadata): void
    {
        $this->checkMetadataFile($projectSlug);
        $file = sprintf('%s/%s/features.metadata.json', $this->projectsDir, $projectSlug);

        $content = file_get_contents($file);
        $projectMetadata = json_decode($content, true);
        $projectMetadata[$featureSlug] = $metadata;

        file_put_contents($file, json_encode($projectMetadata));
    }

    public function getFeatureMetadata(string $projectSlug, string $featureSlug): array
    {
        $this->checkMetadataFile($projectSlug);

        $content = file_get_contents(sprintf('%s/%s/features.metadata.json', $this->projectsDir, $projectSlug));
        $metadata = json_decode($content, true);

        return isset($metadata[$featureSlug]) ? $metadata[$featureSlug] : null;
    }

    public function checkMetadataFile(string $projectSlug): void
    {
        $file = sprintf('%s/%s/features.metadata.json', $this->projectsDir, $projectSlug);
        if (!$this->filesystem->exists($file)) {
            file_put_contents($file, '{}');
        }
    }

    /**
     * @throws FeatureRunErrorException
     */
    public function runFeature(string $projectSlug, string $featureSlug): array
    {
        $feature = $this->getFeature($projectSlug, $featureSlug);
        $testCmd = $this->projectManager->retrieveProjectLagenConfig($projectSlug)['test'];
        $featureMetadata = $this->getFeatureMetadata($projectSlug, $featureSlug);

        $move = file_exists(sprintf(
            '%s/%s/%s/%s',
            $this->deploysDir,
            $projectSlug,
            $featureMetadata['dir'],
            $featureMetadata['filename'])
        );

        $cmd = sprintf(
            'cd %s/%s %s && cp %s/%s/%s %s/%s && %s %s/%s; %s',
            $this->deploysDir,
            $projectSlug,
            $move ? sprintf(
                '&& mv %s/%s %s/%s.backup',
                $featureMetadata['dir'],
                $featureMetadata['filename'],
                $featureMetadata['dir'],
                $featureMetadata['filename']
            ) : '',
            $this->projectsDir,
            $projectSlug,
            $featureSlug,
            $featureMetadata['dir'],
            $featureMetadata['filename'],
            $testCmd,
            $featureMetadata['dir'],
            $featureMetadata['filename'],
            $move ? sprintf(
                'mv %s/%s.backup %s/%s',
                $featureMetadata['dir'],
                $featureMetadata['filename'],
                $featureMetadata['dir'],
                $featureMetadata['filename']
            ) : ''
        );

        $process = new Process($cmd);
        $process->run();

        if ($process->getErrorOutput() !== '') {
            throw new FeatureRunErrorException($process->getErrorOutput());
        }

        return $this->testResultParser->parse($process->getOutput(), $feature);
    }
}
