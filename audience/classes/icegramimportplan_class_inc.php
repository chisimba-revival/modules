<?php
/** Reviewed Icegram Users-list import policy; retire the source adapter after cutover.
 * @author Derek Keats
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class icegramimportplan
{
    /** Return only eligible addresses. Excluded records are represented by counts, not identities. */
    public static function build(array $contacts, array $memberships, array $blocked)
    {
        $lists=[];
        foreach($memberships as $row)$lists[(string)$row['contact_id']][]=$row;
        $blockedSet=[];
        foreach($blocked as $email){
            $email=strtolower(trim($email));
            if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new DomainException('Unknown blocked-address format');
            $blockedSet[$email]=true;
        }
        $eligible=[];$excluded=[];$seen=[];$soft=0;
        foreach($contacts as $contact){
            $id=(string)$contact['id'];$email=strtolower(trim($contact['email']));
            if(isset($seen[$email]))throw new DomainException('Duplicate source address requires review');
            $seen[$email]=true;$membership=$lists[$id]??[];$reason='';
            if(count($membership)>1)throw new DomainException('Duplicate Users-list membership requires review');
            if(!$membership||$membership[0]['status']!=='subscribed')$reason='not_subscribed_to_users';
            elseif((string)$contact['unsubscribed']!=='0')$reason='contact_unsubscribed';
            elseif($contact['status']!=='verified'||(string)$contact['is_deliverable']!=='1')$reason='spam_or_undeliverable';
            elseif((string)$contact['bounce_status']==='2')$reason='hard_bounce';
            elseif(!in_array((string)$contact['bounce_status'],['0','1'],true))throw new DomainException('Unknown bounce state');
            elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>254)$reason='invalid_address';
            elseif(isset($blockedSet[$email]))$reason='blocked';
            if($reason){$excluded[$reason]=($excluded[$reason]??0)+1;continue;}
            if((string)$contact['bounce_status']==='1')$soft++;
            $eligible[]=['source_id'=>$id,'email'=>$email,'name'=>mb_substr(trim($contact['first_name'].' '.$contact['last_name']),0,200),
                'source_created_at'=>$contact['created_at'],'source_subscribed_at'=>$membership[0]['subscribed_at'],
                'source_optin_type'=>$membership[0]['optin_type'],'soft_bounce'=>(string)$contact['bounce_status']==='1'];
        }
        return ['eligible'=>$eligible,'summary'=>['source_contacts'=>count($contacts),'users_memberships'=>count($memberships),
            'eligible'=>count($eligible),'eligible_soft_bounces'=>$soft,'excluded'=>$excluded]];
    }
}
