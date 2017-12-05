<?php

namespace App\Command;

use App\Utils\Git;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

class InstallWorkerCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:install-worker')
            ->setDescription('Worker that listens to installation requests and processes them.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Install worker launched. Waiting for jobs to process... :)');

        $fs = new Filesystem();
        $git = $this->getContainer()->get(Git::class);
        $nodesDir = $this->getContainer()->getParameter('nodes_root_dir');

        if (!$fs->exists($nodesDir)) {
            $fs->mkdir($nodesDir);
            $fs->mkdir(sprintf('%s/pending', $nodesDir));
            $fs->mkdir(sprintf('%s/ongoing', $nodesDir));
            $fs->mkdir(sprintf('%s/done', $nodesDir));
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
            $projectDir = sprintf(
                '%s/%s', $this->getContainer()->getParameter('projects_root_dir'), $content['project']
            );

            $content['status'] = 'ongoing';
            $this->rewriteFile($file->getPathname(), $content);
            $fs->rename($file->getPathname(), $ongoingPathname);
            $fs->symlink($ongoingPathname, sprintf('%s/job', $projectDir));

            $git->cloneRepository($content['repository'], $content['project']);
            if (isset($content['branch'])) {
                $git->changeBranch($content['branch'], $content['project']);
            }

            if (!isset($content['result'])) {
                $content['result'] = '';
            }

            foreach ($content['commands'] as $cmd) {
                $process = new Process($cmd, $deployDir);
                $process->setTimeout(0);
                $process->start();
                $output->writeln(sprintf('<fg=yellow>%s</>', $process->getCommandLine()));
                $process->wait(function ($type, $buffer) use (&$content, $ongoingPathname) {
                    $content['result'] .= $buffer;
                    $this->rewriteFile($ongoingPathname, $content);
                    echo $buffer;
                });
            }

            $content['status'] = 'done';
            $this->rewriteFile($ongoingPathname, $content);
            $fs->rename($ongoingPathname, $donePathname);
            $fs->symlink($donePathname, sprintf('%s/job', $projectDir));
        }
    }

    /**
     * @param string $destination
     * @param array $content
     */
    private function rewriteFile(string $destination, array $content)
    {
        file_put_contents($destination, json_encode($content, JSON_PRETTY_PRINT));
    }
}
