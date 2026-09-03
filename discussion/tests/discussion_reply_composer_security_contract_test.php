<?php
$block=file_get_contents(__DIR__.'/../classes/block_postreply_class_inc.php');
$controller=file_get_contents(__DIR__.'/../controller.php');
$checks=array(
    'reply page renders a semantic composer'=>str_contains($block,'discussion-compose-modern')&&str_contains($block,'Send reply'),
    'reply identifies its parent contribution'=>str_contains($block,'Contribution being replied to')&&str_contains($block,'name="parent"'),
    'topic title is the navigation link'=>str_contains($block,'$topicUrl')&&str_contains($block,'$topicTitle'),
    'reply form carries a scoped CSRF token'=>str_contains($block,"'disc_reply_' . hash('sha256', \$postID)"),
    'reply save consumes the scoped CSRF token'=>str_contains($controller,"validManagementMutation('disc_reply_' . hash('sha256', \$postParent))"),
    'discussion and topic ids come from the parent post'=>str_contains($controller,"\$discussion_id = \$parentPostDetails['discussion_id']")&&str_contains($controller,"\$topic_id = \$parentPostDetails['topic_id']"),
    'blank replies return to the composer'=>str_contains($controller,"return \$this->nextAction('postreply'"),
    'successful replies return to the topic'=>str_contains($controller,"return \$this->nextAction('viewtopic', array('message' => 'replysaved'"),
);
$failed=array_keys(array_filter($checks,static fn($ok)=>!$ok));
if($failed){fwrite(STDERR,'Failed: '.implode(', ',$failed).PHP_EOL);exit(1);}
echo 'discussion reply composer security contract passed'.PHP_EOL;
