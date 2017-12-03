<?php

namespace App\Transformer;

class TableParameterToStringArrayTransformer
{
    /**
     * @param array $table
     *
     * @return array
     */
    public function transform(array $table)
    {
        $out = [];
        $columnsLengths = $this->getMaxColumnLengths($table);

        foreach ($table as $row) {
            $str = '      |';
            foreach ($row as $cellId => $cell) {
                $str .= sprintf(
                    ' %s |',
                    str_pad($cell, $columnsLengths[$cellId])
                );
            }

            $out[] = $str;
        }

        return $out;
    }

    /**
     * @param array $table
     *
     * @return array
     */
    private function getMaxColumnLengths(array $table)
    {
        $lengths = [];
        foreach ($table[0] as $columnId => $column) {
            $maxLength = 0;
            foreach ($table as $row) {
                $columnLength = strlen($row[$columnId]);
                $maxLength = $columnLength > $maxLength ? $columnLength : $maxLength;
            }

            $lengths[$columnId] = $maxLength;
        }

        return $lengths;
    }
}
