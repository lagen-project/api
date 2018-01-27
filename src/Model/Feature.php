<?php

namespace App\Model;

use JMS\Serializer\Annotation as Serializer;

class Feature
{
    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $name;

    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $description;

    /**
     * @var Scenario[]
     *
     * @Serializer\Type("array<App\Model\Scenario>")
     */
    private $scenarios;

    /**
     * @var bool
     *
     * @Serializer\Type("boolean")
     */
    private $runnable;

    public function __construct()
    {
        $this->scenarios = [];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return Scenario[]
     */
    public function getScenarios(): array
    {
        return $this->scenarios;
    }

    public function setScenarios(array $scenarios = []): void
    {
        $this->scenarios = $scenarios;

        foreach ($scenarios as $scenario) {
            $scenario->setFeature($this);
        }
    }

    public function addScenario(Scenario $scenario): void
    {
        $this->scenarios[] = $scenario;
        $scenario->setFeature($this);
    }

    public function removeScenario(Scenario $scenario): void
    {
        foreach ($this->scenarios as $id => $s) {
            if ($s->getName() === $scenario->getName()) {
                unset($this->scenarios[$id]);
            }
        }
    }

    public function isRunnable(): bool
    {
        return $this->runnable;
    }

    public function setRunnable(bool $runnable): void
    {
        $this->runnable = $runnable;
    }
}
