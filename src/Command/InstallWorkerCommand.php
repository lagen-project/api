<?php

namespace App\Command;

use App\Parser\ProjectConfigParser;
use App\Transformer\ProjectConfigToDockerfileTransformer;
use App\Utils\Git;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

class InstallWorkerCommand extends ContainerAwareCommand
{
    /**
     * @var Filesystem
     */
    private $fs;

    protected function configure()
    {
        $this
            ->setName('app:worker:install')
            ->setDescription('Worker that listens to installation requests and processes them.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Install worker launched. Waiting for jobs to process... :)');

        $this->fs = new Filesystem();
        $git = $this->getContainer()->get(Git::class);
        $nodesDir = $this->getContainer()->getParameter('nodes_root_dir');
        $projectConfigParser = $this->getContainer()->get(ProjectConfigParser::class);
        $configToDockerfileTransformer = $this->getContainer()->get(ProjectConfigToDockerfileTransformer::class);

        if (!$this->fs->exists($nodesDir)) {
            $this->fs->mkdir($nodesDir);
            $this->fs->mkdir(sprintf('%s/pending', $nodesDir));
            $this->fs->mkdir(sprintf('%s/ongoing', $nodesDir));
            $this->fs->mkdir(sprintf('%s/done', $nodesDir));
        }

        while (true) {
            $files = iterator_to_array((new Finder())->files()->in(sprintf('%s/pending', $nodesDir))->sortByName());
            if (count($files) === 0) {
                sleep(5);
                continue;
            }

            /** @var \SplFileInfo $file */
            $file = current($files);
            $content = json_decode(file_get_contents($file->getPathname()), true);
            $ongoingPathname = preg_replace('|^(.+)/pending/([^/]+)$|', '$1/ongoing/$2', $file->getPathname());
            $donePathname = preg_replace('|^(.+)/ongoing/([^/]+)$|', '$1/done/$2', $ongoingPathname);
            $deployDir = sprintf('%s/%s', $this->getContainer()->getParameter('deploys_root_dir'), $content['project']);

            $content['status'] = 'ongoing';
            $this->rewriteFile($file->getPathname(), $content);
            $this->fs->rename($file->getPathname(), $ongoingPathname);
            $this->changeProjectStatusFile($content);

            $git->cloneRepository($content['repository'], $content['project']);
            if (isset($content['branch'])) {
                $git->changeBranch($content['branch'], $content['project']);
            }

            $projectConfig = $projectConfigParser->parse($content['project']);
            $configToDockerfileTransformer->transform($projectConfig, $content['project']);

            if (!isset($content['result'])) {
                $content['result'] = '';
            }

            $process = new Process(sprintf(
                'docker build -t %s .',
                $content['project']
            ), $deployDir);
            $process->setTimeout(0);
            $process->start();
            $output->writeln(sprintf('<fg=yellow>%s</>', $process->getCommandLine()));
            $process->wait(function ($type, $buffer) use (&$content, $ongoingPathname) {
                $content['result'] .= preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $buffer);
                $content['result'] = preg_replace('/\[0m\[\d+m/', "\n", $content['result']);
                $this->rewriteFile($ongoingPathname, $content);
                $this->changeProjectStatusFile($content);
                echo $buffer;
            });

            $content['status'] = 'done';
            $this->rewriteFile($ongoingPathname, $content);
            $this->fs->rename($ongoingPathname, $donePathname);
            $this->changeProjectStatusFile($content);
        }
    }

    private function rewriteFile(string $destination, array $content)
    {
        $this->fs->dumpFile($destination, json_encode($content, JSON_PRETTY_PRINT));
    }

    private function changeProjectStatusFile(array $content)
    {
        $projectStatusFilename = sprintf(
            '%s/%s/job', $this->getContainer()->getParameter('projects_root_dir'), $content['project']
        );

        $this->fs->dumpFile($projectStatusFilename, json_encode([
            'status' => $content['status'],
            'result' => isset($content['result']) ? $content['result'] : ''
        ], JSON_PRETTY_PRINT));
    }
}
