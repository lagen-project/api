<?php

namespace App\Model;

class ProjectConfig
{
    /**
     * @var string
     */
    private $image = '';

    /**
     * @var array
     */
    private $env = [];

    /**
     * @var array
     */
    private $commands = [];

    /**
     * @var string
     */
    private $testCommand = '';

    public function __construct(array $asArray)
    {
        if (isset($asArray['install'])) {
            if (isset($asArray['install']['image'])) {
                $this->setImage($asArray['install']['image']);
            }
            if (isset($asArray['install']['env'])) {
                $this->setEnv($asArray['install']['env']);
            }
            if (isset($asArray['install']['commands'])) {
                $this->setCommands($asArray['install']['commands']);
            }
        }

        if (isset($asArray['test'])) {
            $this->setTestCommand($asArray['test']);
        }
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function setImage(string $image)
    {
        $this->image = $image;
    }

    public function getEnv(): array
    {
        return $this->env;
    }

    public function setEnv(array $env)
    {
        $this->env = $env;
    }

    public function getCommands(): array
    {
        return $this->commands;
    }

    public function setCommands(array $commands)
    {
        $this->commands = $commands;
    }

    public function getTestCommand(): string
    {
        return $this->testCommand;
    }

    public function setTestCommand(string $testCommand)
    {
        $this->testCommand = $testCommand;
    }
}
