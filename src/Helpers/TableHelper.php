<?php
/**
 * Created by PhpStorm.
 * User: aj
 * Date: 19/10/2018
 * Time: 09.49
 */

namespace CLImax\Helpers;


class TableHelper
{
    public static function flipRows($rows) {
        $columnValues = [];
        $mainColumn = null;

        foreach ($rows as $row) {
            if (!empty($row)) {
                foreach ($row as $column => $value) {
                    if ($mainColumn === null) {
                        $mainColumn = $column;
                    }

                    $columnValues[$column][] = $value;
                }
            }
        }

        foreach ($columnValues as $column => $_columnValues) {
            if ($column === $mainColumn) {
                continue;
            }

            $columnValuesNew = [$mainColumn => $column];

            foreach ($_columnValues as $i => $columnValue) {
                $key = $columnValues[$mainColumn][$i];

                $columnValuesNew[$key] = $columnValue;
            }

            $columnValues[$column] = $columnValuesNew;
        }

        unset($columnValues[$mainColumn]);

        return array_values($columnValues);
    }
}