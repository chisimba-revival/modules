<?php
/** Empty quiz provisioning for course outline consumers. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class quizoutlineservice extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler')
    { parent::init('tbl_tests',$pearDb,$errorCallback); }

    public function createEmpty($code, $chapter, $title, $actor)
    {
        if (mb_strlen($title) > 60 || trim($title) === '') throw new InvalidArgumentException('Invalid quiz title');
        $id = $this->insert(['id'=>bin2hex(random_bytes(16)), 'context'=>$code,'chapter'=>$chapter,'userid'=>$actor,'name'=>$title,
            'description'=>'','status'=>'inactive','totalmark'=>0,'percentage'=>0,'duration'=>0,'timed'=>0,
            'testtype'=>'Formative','qsequence'=>'Sequential','asequence'=>'Scrambled','comlab'=>'',
            'updated'=>date('Y-m-d H:i:s'),'coursePermissions'=>'Private']);
        if (!$id) throw new RuntimeException('Quiz insertion failed');
        return $id;
    }

    public function snapshot($code, $lock=false)
    {
        $suffix = $lock ? ' FOR UPDATE' : '';
        return ['tests'=>$this->selectPrepared('SELECT * FROM tbl_tests WHERE context=? ORDER BY id'.$suffix, [$code]),
            'questions'=>$this->selectPrepared('SELECT q.* FROM tbl_test_questions q WHERE q.testid IN (SELECT id FROM tbl_tests WHERE context=?) ORDER BY q.id'.$suffix, [$code])];
    }

    public function removeEmpty(array $snapshot)
    {
        if ($snapshot['questions']) throw new DomainException('Quiz contains questions');
        foreach ($snapshot['tests'] as $test) {
            if ($this->executePrepared('DELETE FROM tbl_tests WHERE id=?', [$test['id']]) !== 1) throw new RuntimeException('Quiz removal failed');
        }
    }
}
