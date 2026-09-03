<?php
$block=file_get_contents(__DIR__.'/../classes/block_postreply_class_inc.php');
$controller=file_get_contents(__DIR__.'/../controller.php');
$posts=file_get_contents(__DIR__.'/../classes/dbpost_class_inc.php');
$marking=file_get_contents(__DIR__.'/../templates/content/discussion_marking.php');
$checks=array(
    'reply page renders a semantic composer'=>str_contains($block,'discussion-compose-modern')&&str_contains($block,'Send reply'),
    'reply identifies its parent contribution'=>str_contains($block,'Contribution being replied to')&&str_contains($block,'name="parent"'),
    'topic title is the navigation link'=>str_contains($block,'$topicUrl')&&str_contains($block,'$topicTitle'),
    'reply form carries a scoped CSRF token'=>str_contains($block,"'disc_reply_' . hash('sha256', \$postID)"),
    'reply save consumes the scoped CSRF token'=>str_contains($controller,"validManagementMutation('disc_reply_' . hash('sha256', \$postParent))"),
    'discussion and topic ids come from the parent post'=>str_contains($controller,"\$discussion_id = \$parentPostDetails['discussion_id']")&&str_contains($controller,"\$topic_id = \$parentPostDetails['topic_id']"),
    'scope validation also derives from the parent post'=>str_contains($controller,"\$resource = \$this->objPost->getPostDiscussionDetails(\$parent)"),
    'blank replies return to the composer'=>str_contains($controller,"return \$this->nextAction('postreply'"),
    'successful replies return to the topic'=>str_contains($controller,"return \$this->nextAction('viewtopic', array('message' => 'replysaved'"),
    'assessment evidence accepts both user identifier formats'=>str_contains($posts,'p.userid=u.id OR p.userid=u.userid'),
    'new evidence returns a previously marked learner to review'=>str_contains($controller,"\$students[\$index]['needs_review']")&&str_contains($marking,"!empty(\$student['needs_review'])"),
);
$failed=array_keys(array_filter($checks,static fn($ok)=>!$ok));
if($failed){fwrite(STDERR,'Failed: '.implode(', ',$failed).PHP_EOL);exit(1);}
echo 'discussion reply composer security contract passed'.PHP_EOL;
