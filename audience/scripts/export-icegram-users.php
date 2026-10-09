<?php
/** Read-only source export. Personal data goes only to a protected server-side file.
 * @author Derek Keats
 */
if(PHP_SAPI!=='cli')exit(1);
$policy=$argv[1]??'';$output=$argv[2]??'';
if(!is_file($policy)||!str_starts_with($output,'/tmp/ltb-')||file_exists($output))throw new RuntimeException('Use a new protected migration output path');
$GLOBALS['kewl_entry_point_run']=true;require $policy;
define('SHORTINIT',true);require '/var/www/learnthebirds.com/wp-load.php';global $wpdb;$p=$wpdb->prefix;
$home=$wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name='home'");
if(!in_array(parse_url($home,PHP_URL_HOST),['learnthebirds.com','www.learnthebirds.com'],true))throw new RuntimeException('Wrong source site');
$users=$wpdb->get_results("SELECT id FROM {$p}ig_lists WHERE name='Users' AND deleted_at IS NULL",ARRAY_A);
if(count($users)!==1)throw new RuntimeException('Users list is ambiguous');$list=(int)$users[0]['id'];
$contacts=$wpdb->get_results("SELECT id,email,first_name,last_name,created_at,status,unsubscribed,bounce_status,is_deliverable FROM {$p}ig_contacts ORDER BY id",ARRAY_A);
if($wpdb->last_error||!$contacts)throw new RuntimeException('Incomplete contact inventory');
$memberships=$wpdb->get_results($wpdb->prepare("SELECT contact_id,status,subscribed_at,optin_type FROM {$p}ig_lists_contacts WHERE list_id=%d",$list),ARRAY_A);
if($wpdb->last_error||!$memberships)throw new RuntimeException('Incomplete subscription inventory');
$blocked=$wpdb->get_col("SELECT email FROM {$p}ig_blocked_emails");
if($wpdb->last_error||!$contacts||!$memberships)throw new RuntimeException('Incomplete source inventory');
$plan=icegramimportplan::build($contacts,$memberships,$blocked);
$document=['version'=>1,'source'=>'https://learnthebirds.com','list_id'=>$list,'list_name'=>'Users','exported_at'=>gmdate('c'),
    'source_ids'=>array_map('strval',array_column($contacts,'id'))]+$plan;
umask(0077);$handle=fopen($output,'x');if(!$handle)throw new RuntimeException('Cannot create export');
fwrite($handle,json_encode($document,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));fclose($handle);chmod($output,0600);
echo json_encode($plan['summary']+['sha256'=>hash_file('sha256',$output)],JSON_THROW_ON_ERROR)."\n";
