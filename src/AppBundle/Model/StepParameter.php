<?php

namespace AppBundle\Model;

class StepParameter
{
    const TYPE_STRING = 'string';
    const TYPE_TABLE = 'table';

    /**
     * @var string
     */
    private $type;

    /**
     * @var string|array
     */
    private $value;

    /**
     * @var Step
     */
    private $step;

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

    /**
     * @return array|string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param array|string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return Step
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * @param Step $step
     */
    public function setStep($step)
    {
        $this->step = $step;
    }
}
