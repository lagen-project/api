<?php

namespace AppBundle\Transformer;

class ExamplesToStringArrayTransformer extends TableParameterToStringArrayTransformer
{
    /**
     * @param array $table
     *
     * @return array
     */
    public function transform(array $table)
    {
        return parent::transform(array_merge(
            [array_keys($table[0])],
            array_map(function($row) { return array_values($row); }, $table)
        ));
    }
}
