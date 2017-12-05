<?php

namespace App\Parser;

use App\Exception\FeatureLineDuplicatedException;
use App\Exception\UnrecognizedLineTypeException;
use App\Model\Feature;
use App\Model\Scenario;
use App\Model\Step;
use App\Model\StepParameter;

class FeatureParser
{
    const TYPE_FEATURE = 'feature';
    const TYPE_BACKGROUND = 'background';
    const TYPE_SCENARIO = 'scenario';
    const TYPE_STEP_GIVEN = 'step_given';
    const TYPE_STEP_WHEN = 'step_when';
    const TYPE_STEP_AND = 'step_and';
    const TYPE_STEP_THEN = 'step_then';
    const TYPE_STEP_BUT = 'step_but';
    const TYPE_STEP_PARAMETER_TABLE_DELIMITER = 'table';
    const TYPE_STEP_PARAMETER_STRING_DELIMITER = 'string';
    const TYPE_BLANK = '';
    const TYPE_COMMENT = '#';
    const TYPE_DESCRIPTION = 'description';
    const TYPE_EXAMPLES = 'examples';

    /**
     * @var array
     */
    private $contents;

    /**
     * @var int
     */
    private $index;

    /**
     * @var Feature
     */
    private $feature;

    /**
     * @var Scenario
     */
    private $scenario;

    /**
     * @var Step
     */
    private $step;

    /**
     * @var bool
     */
    private $consumeExamples;

    private function init()
    {
        $this->index = 0;
        $this->feature = null;
        $this->scenario = null;
        $this->step = null;
        $this->consumeExamples = false;
    }

    public function parse(string $filename): Feature
    {
        $this->init();
        $this->contents = file($filename);
        $this->index = 0;
        while ($this->index < count($this->contents)) {
            $line = trim($this->contents[$this->index]);
            $type = $this->getLineType($this->index, $line);
            switch($type) {
                case self::TYPE_FEATURE:
                    $this->createFeature($line);
                    break;
                case self::TYPE_DESCRIPTION:
                    $this->consumeDescription();
                    break;
                case self::TYPE_SCENARIO:
                    $this->createScenario($line, Scenario::TYPE_SCENARIO);
                    break;
                case self::TYPE_BACKGROUND:
                    $this->createScenario($line, Scenario::TYPE_BACKGROUND);
                    break;
                case self::TYPE_STEP_GIVEN:
                case self::TYPE_STEP_WHEN:
                case self::TYPE_STEP_AND:
                case self::TYPE_STEP_THEN:
                    $this->createStep($line, $type);
                    break;
                case self::TYPE_STEP_PARAMETER_STRING_DELIMITER:
                    $this->consumeStringParameter();
                    break;
                case self::TYPE_EXAMPLES:
                    $this->consumeExamples = true;
                    break;
                case self::TYPE_STEP_PARAMETER_TABLE_DELIMITER:
                    if (!$this->consumeExamples) {
                        $this->consumeTableParameter();
                    } else {
                        $this->consumeExamples();
                    }
                    break;
            }
            $this->index++;
        }
        return $this->feature;
    }

    /**
     * @throws FeatureLineDuplicatedException
     */
    private function createFeature(string $line)
    {
        if ($this->feature) {
            throw new FeatureLineDuplicatedException;
        }
        $this->feature = new Feature();
        $this->feature->setName(substr($line, 9));
    }

    private function createScenario(string $line, string $type)
    {
        $this->scenario = new Scenario();
        $this->feature->addScenario($this->scenario);
        $this->scenario->setName($type === Scenario::TYPE_BACKGROUND ? '' : substr($line, strpos($line, ':') + 1));
        $this->scenario->setType($type);
    }

    private function createStep(string $line, string $type)
    {
        $this->step = new Step();
        switch ($type) {
            case self::TYPE_STEP_GIVEN:
                $delimiter = 6;
                $this->step->setType(Step::TYPE_GIVEN);
                break;
            case self::TYPE_STEP_WHEN:
                $delimiter = 5;
                $this->step->setType(Step::TYPE_WHEN);
                break;
            case self::TYPE_STEP_AND:
                $delimiter = 4;
                $this->step->setType(Step::TYPE_AND);
                break;
            case self::TYPE_STEP_THEN:
                $delimiter = 5;
                $this->step->setType(Step::TYPE_THEN);
                break;
            case self::TYPE_STEP_BUT:
                $delimiter = 4;
                $this->step->setType(Step::TYPE_BUT);
                break;
            default:
                $delimiter = 0;
        }
        $this->scenario->addStep($this->step);
        $this->step->setSentence(substr($line, $delimiter));
    }

    private function consumeStringParameter()
    {
        $line = '';
        $this->index++;
        while (trim($this->contents[$this->index]) !== '"""') {
            $line .= substr(trim($this->contents[$this->index], "\t\n\r\0\x0B"), 4) . "\n";
            $this->index++;
        }
        $parameter = new StepParameter();
        $parameter->setType(StepParameter::TYPE_STRING);
        $parameter->setValue($line);
        $this->step->setParameter($parameter);
    }

    private function consumeDescription()
    {
        $description = '';
        $line = '';
        while (substr($line, 0, 8) !== 'Scenario' && substr($line, 0, 10) !== 'Background') {
            $description .= trim($this->contents[$this->index], " \t\n\r\0\x0B") . "\n";
            $this->index++;
            $line = trim($this->contents[$this->index], " \t\n\r\0\x0B");
        }
        $this->index -= 2;
        $this->feature->setDescription(trim($description));
    }

    private function consumeTableParameter()
    {
        $parameter = new StepParameter();
        $parameter->setType(StepParameter::TYPE_TABLE);
        $value = [];
        do {
            $value[] = $this->tableStringToArray($this->contents[$this->index]);
            $this->index++;
        } while ($this->index < count($this->contents) && substr(trim($this->contents[$this->index]), 0, 1) === '|');
        $this->index--;
        $parameter->setValue($value);
        $this->step->setParameter($parameter);
    }

    private function consumeExamples()
    {
        $value = [];
        $parameters = $this->tableStringToArray($this->contents[$this->index]);
        $this->index++;

        do {
            $value[] = array_combine(
                $parameters,
                $this->tableStringToArray($this->contents[$this->index])
            );
            $this->index++;
        } while ($this->index < count($this->contents) && substr(trim($this->contents[$this->index]), 0, 1) === '|');
        $this->index--;

        $this->scenario->setExamples($value);
        $this->consumeExamples = false;
    }

    private function tableStringToArray(string $line): array
    {
        return array_values(array_filter(array_map(function($string) {
            return trim($string);
        }, explode('|', $line)), function($string) {
            return $string !== '';
        }));
    }

    /**
     * @throws UnrecognizedLineTypeException
     */
    private function getLineType(int $index, string $line): string
    {
        if ($line === '') {
            return self::TYPE_BLANK;
        }
        if (substr($line, 0, 8) === 'Feature:') {
            return self::TYPE_FEATURE;
        }
        if (substr($line, 0, 11) === 'Background:') {
            return self::TYPE_BACKGROUND;
        }
        if (substr($line, 0, 9) === 'Scenario:' || substr($line, 0, 17) === 'Scenario Outline:') {
            return self::TYPE_SCENARIO;
        }
        if (substr($line, 0, 9) === 'Examples:') {
            return self::TYPE_EXAMPLES;
        }
        if (substr($line, 0, 5) === 'Given') {
            return self::TYPE_STEP_GIVEN;
        }
        if (substr($line, 0, 3) === 'And') {
            return self::TYPE_STEP_AND;
        }
        if (substr($line, 0, 4) === 'When') {
            return self::TYPE_STEP_WHEN;
        }
        if (substr($line, 0, 4) === 'Then') {
            return self::TYPE_STEP_THEN;
        }
        if (substr($line, 0, 3) === 'But') {
            return self::TYPE_STEP_BUT;
        }
        if (substr($line, 0, 1) === '|') {
            return self::TYPE_STEP_PARAMETER_TABLE_DELIMITER;
        }
        if (substr($line, 0, 1) === '#') {
            return self::TYPE_COMMENT;
        }
        if (substr($line, 0, 3) === '"""') {
            return self::TYPE_STEP_PARAMETER_STRING_DELIMITER;
        }
        if ($index === 1) {
            return self::TYPE_DESCRIPTION;
        }

        throw new UnrecognizedLineTypeException(sprintf('Unrecognized feature line %d (%s)', $index, $line));
    }
}
