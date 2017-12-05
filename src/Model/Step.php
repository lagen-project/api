<?php

namespace App\Model;

use JMS\Serializer\Annotation as Serializer;

class Step
{
    const TYPE_GIVEN = 'Given';
    const TYPE_WHEN = 'When';
    const TYPE_THEN = 'Then';
    const TYPE_AND = 'And';
    const TYPE_BUT = 'But';

    /**
     * @var Scenario
     *
     * @Serializer\Type("App\Model\Scenario")
     */
    private $scenario;

    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $sentence;

    /**
     * @var StepParameter
     *
     * @Serializer\Type("App\Model\StepParameter")
     */
    private $parameter;

    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $type;

    public function getScenario(): Scenario
    {
        return $this->scenario;
    }

    public function setScenario(Scenario $scenario = null): void
    {
        $this->scenario = $scenario;
    }

    public function getSentence(): string
    {
        return $this->sentence;
    }

    public function setSentence(string $sentence): void
    {
        $this->sentence = $sentence;
    }

    public function getParameter(): StepParameter
    {
        return $this->parameter;
    }

    public function setParameter(StepParameter $parameter): void
    {
        $this->parameter = $parameter;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }
}
