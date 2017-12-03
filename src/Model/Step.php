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

    /**
     * @return Scenario
     */
    public function getScenario()
    {
        return $this->scenario;
    }

    /**
     * @param Scenario $scenario
     */
    public function setScenario($scenario)
    {
        $this->scenario = $scenario;
    }

    /**
     * @return string
     */
    public function getSentence()
    {
        return $this->sentence;
    }

    /**
     * @param string $sentence
     */
    public function setSentence($sentence)
    {
        $this->sentence = $sentence;
    }

    /**
     * @return StepParameter
     */
    public function getParameter()
    {
        return $this->parameter;
    }

    /**
     * @param StepParameter $parameter
     */
    public function setParameter(StepParameter $parameter)
    {
        $this->parameter = $parameter;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }
}
