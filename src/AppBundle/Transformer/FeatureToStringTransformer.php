<?php

namespace AppBundle\Transformer;

use AppBundle\Model\Feature;
use AppBundle\Model\Scenario;
use AppBundle\Model\Step;
use AppBundle\Model\StepParameter;

class FeatureToStringTransformer
{
    /**
     * @var TableParameterToStringArrayTransformer
     */
    private $tableParameterTransformer;

    /**
     * @var ExamplesToStringArrayTransformer
     */
    private $examplesTransformer;

    /**
     * @param TableParameterToStringArrayTransformer $tableParameterTransformer
     * @param ExamplesToStringArrayTransformer $examplesTransformer
     */
    public function __construct(
        TableParameterToStringArrayTransformer $tableParameterTransformer,
        ExamplesToStringArrayTransformer $examplesTransformer
    ) {
        $this->tableParameterTransformer = $tableParameterTransformer;
        $this->examplesTransformer = $examplesTransformer;
    }

    /**
     * @param Feature $feature
     *
     * @return string
     */
    public function transform(Feature $feature)
    {
        $asArray = ['Feature: ' . $feature->getName()];
        $asArray = array_merge($asArray, $this->transformDescription($feature->getDescription()));
        foreach ($feature->getScenarios() as $scenario) {
            $asArray[] = '';
            $asArray[] = sprintf(
                '  %s: %s',
                $scenario->getType() === Scenario::TYPE_BACKGROUND ? 'Background' :
                    ($scenario->isOutline() ? 'Scenario Outline' : 'Scenario'),
                trim($scenario->getName())
            );
            foreach ($scenario->getSteps() as $step) {
                $asArray[] = sprintf('    %s %s', $this->transformType($step->getType()), $step->getSentence());
                if ($step->getParameter()) {
                    if ($step->getParameter()->getType() === StepParameter::TYPE_STRING) {
                        $asArray[] = '    """';
                        foreach (explode("\n", trim($step->getParameter()->getValue())) as $line) {
                            $asArray[] = sprintf('    %s', $line);
                        }
                        $asArray[] = '    """';
                    } elseif ($step->getParameter()->getType() === StepParameter::TYPE_TABLE) {
                        $asArray = array_merge(
                            $asArray,
                            $this->tableParameterTransformer->transform($step->getParameter()->getValue())
                        );
                    }
                }
            }
            if ($scenario->isOutline()) {
                $asArray[] = '';
                $asArray[] = '    Examples:';
                $asArray = array_merge(
                    $asArray,
                    $this->examplesTransformer->transform($scenario->getExamples())
                );
            }
        }
        $asArray[] = '';

        return implode("\n", $asArray);
    }

    /**
     * @param int $type
     *
     * @return string
     */
    private function transformType($type)
    {
        $types = [
            Step::TYPE_GIVEN => 'Given',
            Step::TYPE_WHEN => 'When',
            Step::TYPE_THEN => 'Then',
            Step::TYPE_AND => 'And',
            Step::TYPE_BUT => 'But'
        ];

        return $types[$type];
    }

    /**
     * @param string $description
     *
     * @return array
     */
    private function transformDescription($description)
    {
        return array_map(function($line) { return '  ' . $line; }, explode(PHP_EOL, $description));
    }
}
