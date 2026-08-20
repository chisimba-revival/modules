<?php
/**
 * Reusable rubric service.
 *
 * Provides a neutral, non-UI interface for modules that need to discover
 * and consume rubrics. The rubric module remains the canonical owner of
 * rubric definitions and structure.
 *
 * @package rubric
 */

if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}

class rubricservice extends ChisimbaObject
{
    private $objRubricTables;
    private $objRubricObjectives;
    private $objRubricPerformances;
    private $objRubricCells;

    public function init()
    {
        $this->objRubricTables = $this->getObject('dbrubrictables', 'rubric');
        $this->objRubricObjectives = $this->getObject('dbrubricobjectives', 'rubric');
        $this->objRubricPerformances = $this->getObject('dbrubricperformances', 'rubric');
        $this->objRubricCells = $this->getObject('dbrubriccells', 'rubric');
    }

    /**
     * Return rubrics owned by a context.
     *
     * This intentionally returns rubric metadata only. Consumers that need
     * marking criteria should call getStructuredRubric().
     *
     * @param string $contextCode
     * @return array
     */
    public function listRubrics($contextCode)
    {
        if (!is_string($contextCode) || $contextCode === '') {
            return array();
        }

        $records = $this->objRubricTables->listAll($contextCode, null);
        $rubrics = array();

        foreach ((array) $records as $record) {
            if (empty($record['id'])) {
                continue;
            }

            $rubrics[] = array(
                'id' => $record['id'],
                'contextCode' => isset($record['contextCode']) ? $record['contextCode'] : $contextCode,
                'title' => isset($record['title']) ? $record['title'] : '',
                'description' => isset($record['description']) ? $record['description'] : '',
                'rows' => isset($record['rows']) ? (int) $record['rows'] : 0,
                'cols' => isset($record['cols']) ? (int) $record['cols'] : 0,
            );
        }

        return $rubrics;
    }

    /**
     * Return metadata for one rubric.
     *
     * @param string $rubricId
     * @return array|false
     */
    public function getRubric($rubricId)
    {
        if (!is_string($rubricId) || $rubricId === '') {
            return false;
        }

        $records = $this->objRubricTables->listSingle($rubricId);
        if (empty($records) || empty($records[0]['id'])) {
            return false;
        }

        $record = $records[0];

        return array(
            'id' => $record['id'],
            'contextCode' => isset($record['contextCode']) ? $record['contextCode'] : '',
            'title' => isset($record['title']) ? $record['title'] : '',
            'description' => isset($record['description']) ? $record['description'] : '',
            'rows' => isset($record['rows']) ? (int) $record['rows'] : 0,
            'cols' => isset($record['cols']) ? (int) $record['cols'] : 0,
        );
    }

    /**
     * Return a complete rubric in a neutral structured representation.
     *
     * The result is suitable for assessment modules, APIs, and later AI
     * evaluation without requiring any rubric HTML or controller code.
     *
     * @param string $rubricId
     * @return array|false
     */
    public function getStructuredRubric($rubricId)
    {
        $rubric = $this->getRubric($rubricId);
        if ($rubric === false) {
            return false;
        }

        $performances = array();
        for ($col = 1; $col <= $rubric['cols']; $col++) {
            $record = $this->objRubricPerformances->listSingle($rubricId, $col);
            $performances[] = array(
                'column' => $col,
                'label' => (!empty($record) && isset($record[0]['performance']))
                    ? $record[0]['performance']
                    : '',
            );
        }

        $criteria = array();
        for ($row = 1; $row <= $rubric['rows']; $row++) {
            $objective = $this->objRubricObjectives->listSingle($rubricId, $row);
            $levels = array();

            for ($col = 1; $col <= $rubric['cols']; $col++) {
                $cell = $this->objRubricCells->listSingle($rubricId, $row, $col);
                $levels[] = array(
                    'column' => $col,
                    'description' => (!empty($cell) && isset($cell[0]['contents']))
                        ? $cell[0]['contents']
                        : '',
                );
            }

            $criteria[] = array(
                'row' => $row,
                'objective' => (!empty($objective) && isset($objective[0]['objective']))
                    ? $objective[0]['objective']
                    : '',
                'levels' => $levels,
            );
        }

        $rubric['performances'] = $performances;
        $rubric['criteria'] = $criteria;

        return $rubric;
    }
}
?>
