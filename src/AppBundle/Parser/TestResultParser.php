<?php

namespace AppBundle\Parser;

use AppBundle\Model\Feature;
use AppBundle\Model\Scenario;

class TestResultParser
{
    /**
     * @param string $resultLine
     * @param Feature $feature
     *
     * @return array
     */
    public function parse($resultLine, Feature $feature)
    {
        $index = 0;
        $hasBg = $feature->getScenarios() && $feature->getScenarios()[0]->getType() === Scenario::TYPE_BACKGROUND;
        $bgResult = [];
        $regularResults = [];

        foreach ($feature->getScenarios() as $scenario) {
            if ($hasBg) {
                if ($scenario->getType() === Scenario::TYPE_BACKGROUND) {
                    continue;
                }
                for ($i = 0 ; $i < count($feature->getScenarios()[0]->getSteps()) ; $i++) {
                    $bgResult[$i] = $this->convertResult($resultLine[$index]);
                    $index++;
                }
            }

            $scenarioResult = [];
            for ($i = 0 ; $i < count($scenario->getSteps()) ; $i++) {
                $scenarioResult[] = $this->convertResult($resultLine[$index]);
                $index++;
            }

            $regularResults[] = $scenarioResult;
        }

        return $bgResult ? array_merge([$bgResult], $regularResults) : $regularResults;
    }

    /**
     * @param string $char
     *
     * @return array
     */
    private function convertResult($char)
    {
        return [
            'success' => $char === '.',
            'reason' => $char !== '.' ? $char : null
        ];
    }
}
