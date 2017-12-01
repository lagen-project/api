<?php

namespace AppBundle\Manager;

use AppBundle\Exception\FeatureRunErrorException;
use AppBundle\Exception\ProjectConfigurationNotFoundException;
use AppBundle\Model\Feature;
use AppBundle\Parser\FeatureParser;
use AppBundle\Parser\TestResultParser;
use AppBundle\Transformer\FeatureToStringTransformer;
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

    /**
     * @param Filesystem $filesystem
     * @param string $deploysDir
     * @param string $projectsDir
     * @param Slugify $slugify
     * @param FeatureParser $featureParser
     * @param FeatureToStringTransformer $featureToStringTransformer
     * @param ProjectManager $projectManager
     * @param TestResultParser $testResultParser
     */
    public function __construct(
        Filesystem $filesystem,
        $deploysDir,
        $projectsDir,
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

    /**
     * @param string $projectSlug
     * @param string $featureSlug
     *
     * @return Feature
     */
    public function getFeature($projectSlug, $featureSlug)
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

    /**
     * @param string $projectSlug
     * @param string $featureName
     */
    public function createFeature($projectSlug, $featureName)
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

    /**
     * @param string $projectSlug
     * @param string $featureSlug
     * @param Feature $feature
     */
    public function editFeature($projectSlug, $featureSlug, Feature $feature)
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

    /**
     * @param string $projectSlug
     * @param string $featureSlug
     *
     * @return Feature
     */
    public function exportFeature($projectSlug, $featureSlug)
    {
        return file_get_contents(
            sprintf('%s/%s/%s', $this->projectsDir, $projectSlug, $featureSlug)
        );
    }

    /**
     * @param string $projectSlug
     * @param string $featureSlug
     * @param array $metadata
     */
    public function setFeatureMetadata($projectSlug, $featureSlug, array $metadata)
    {
        $this->checkMetadataFile($projectSlug);
        $file = sprintf('%s/%s/features.metadata.json', $this->projectsDir, $projectSlug);

        $content = file_get_contents($file);
        $projectMetadata = json_decode($content, true);
        $projectMetadata[$featureSlug] = $metadata;

        file_put_contents($file, json_encode($projectMetadata));
    }

    /**
     * @param string $projectSlug
     * @param string $featureSlug
     *
     * @return array|null
     */
    public function getFeatureMetadata($projectSlug, $featureSlug)
    {
        $this->checkMetadataFile($projectSlug);

        $content = file_get_contents(sprintf('%s/%s/features.metadata.json', $this->projectsDir, $projectSlug));
        $metadata = json_decode($content, true);

        return isset($metadata[$featureSlug]) ? $metadata[$featureSlug] : null;
    }

    /**
     * @param string $projectSlug
     */
    public function checkMetadataFile($projectSlug)
    {
        $file = sprintf('%s/%s/features.metadata.json', $this->projectsDir, $projectSlug);
        if (!$this->filesystem->exists($file)) {
            file_put_contents($file, '{}');
        }
    }

    /**
     * @param string $projectSlug
     * @param string $featureSlug
     *
     * @return array
     */
    public function runFeature($projectSlug, $featureSlug)
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
