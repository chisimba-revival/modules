<?php
$GLOBALS['kewl_entry_point_run'] = true;
class FakeProviderBase {}
$source = file_get_contents(dirname(__DIR__) . '/classes/fakepaymentprovider_class_inc.php');
$source = preg_replace('/^<\?php|\?>\s*$/', '', $source);
$source = str_replace('class fakepaymentprovider extends ChisimbaObject', 'class fakepaymentprovider extends FakeProviderBase', $source);
eval($source);
$provider = new fakepaymentprovider();
$expect = function ($condition, $message) { if (!$condition) { throw new RuntimeException($message); } };
$id = str_repeat('a', 32);

$expect($provider->createCheckout(array('id'=>$id), 'timeout')['code'] === 'provider_timeout', 'Timeout scenario must be retryable without inventing success.');
$expect($provider->script('card_decline',$id)[0]['event']['reasonCode'] === 'card_declined', 'Card decline must be explicit.');
$expect($provider->script('insufficient_funds',$id)[0]['event']['reasonCode'] === 'insufficient_funds', 'Insufficient funds must be explicit.');
$expect($provider->script('expired_card',$id)[0]['event']['reasonCode'] === 'expired_card', 'Expired card must be explicit.');
$expect($provider->script('abandoned_checkout',$id)[0]['event']['type'] === 'checkout.abandoned', 'Abandoned checkout must not become payment failure.');
$duplicate = $provider->script('duplicate',$id);
$expect($duplicate[0]['event']['providerEventId'] === $duplicate[1]['event']['providerEventId'], 'Duplicate delivery must retain provider event identity.');
$outOfOrder = $provider->script('out_of_order',$id);
$expect($outOfOrder[0]['event']['occurredAt'] > $outOfOrder[1]['event']['occurredAt'], 'Out-of-order delivery must be deterministic.');
$recovery = $provider->script('recovery',$id);
$expect($recovery[0]['event']['type'] === 'payment.failed' && $recovery[1]['event']['type'] === 'payment.succeeded', 'Recovery must follow a failed payment.');
foreach (array('refund'=>'payment.refunded','reversal'=>'payment.reversed','dispute'=>'payment.disputed') as $scenario=>$type) {
    $script=$provider->script($scenario,$id);
    $expect($script[1]['event']['type'] === $type, ucfirst($scenario).' scenario is missing.');
}
$signed=$provider->script('success',$id)[0];
$expect(!empty($provider->verifyAndNormalize($signed)['ok']), 'Signed fake event must verify.');
$signed['signature'][0] = $signed['signature'][0] === 'a' ? 'b' : 'a';
$expect(empty($provider->verifyAndNormalize($signed)['ok']), 'Tampered fake event must fail verification.');
fwrite(STDOUT,"PASS: deterministic fake payment provider\n");
?>
