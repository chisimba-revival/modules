"""Local disposable-fixture API regression. No production target accepted."""
import json, subprocess, urllib.request, urllib.error, ssl, http.cookiejar, time
base='https://chisimba.test:8445/ch/api/v1/'
f=json.loads(subprocess.check_output(['docker','exec','-u','www-data','chisimba-php85-web','cat','/tmp/restapi-local-fixture.json']))
assert f['username'].startswith('api_test_') and f['conversationContext'].startswith('msgtest')
client=urllib.request.build_opener(urllib.request.HTTPSHandler(context=ssl._create_unverified_context()),urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
token=None
def req(path,body=None,auth=True):
 headers={'Content-Type':'application/json'}
 if auth and token:headers['Authorization']='Bearer '+token
 try:r=client.open(urllib.request.Request(base+path,headers=headers,data=json.dumps(body).encode() if body is not None else None))
 except urllib.error.HTTPError as e:r=e
 return r.status,json.load(r)
def check(ok,name):assert ok,name;print('PASS',name)
_,e=req('auth/login',auth=False);e=e['data'];time.sleep(max(1,e['minimum_seconds'])+1)
code,r=req('auth/login',dict(username=f['username'],password=f['password'],csrf_token=e['csrf_token'],abuse_issued_at=e['issued_at'],abuse_nonce=e['nonce'],abuse_signature=e['signature'],device_id='messaging-api-check'),False);assert code==201;token=r['data']['tokens']['access_token']
code,r=req('messaging/contexts');check(code==200 and f['conversationContext'] in [c['id'] for c in r['data']['contexts']],'authorised context list')
code,r=req('messaging/announcements?scope=context&context='+f['conversationContext']);check(code==200 and r['data']['can_send'] and any(a['body']=='A message <script>literal</script>' for a in r['data']['announcements']),'announcement chat reaches mobile API')
code,r=req('messaging/announcements?scope=context&context=not-authorised');check(code==403,'foreign announcement scope denied')
code,r=req('messaging/announcements?scope=wrong');check(code==422,'invalid announcement scope rejected')
announcement=dict(scope='context',context=f['conversationContext'],submission_id='8'*32,body='A message <script>literal</script>')
code,r=req('messaging/announcements',announcement);check(code==200,'mobile announcement retry confirms existing send')
code,r=req('messaging/announcements',dict(announcement,body='Changed'));check(code==409,'mobile announcement changed retry rejected')
code,r=req('messaging/announcements',dict(announcement,scope='site',context=''));check(code==403,'mobile course author cannot send administrator messages')
p=dict(context=f['conversationContext'],submission_id='d'*32,body='Mobile API synthetic message')
code,r=req('messaging/messages',p);check(code==200,'mobile send');receipt=r['data']['id']
code,r=req('messaging/messages',p);check(code==200 and r['data']['id']==receipt,'duplicate retry confirms same message')
code,r=req('messaging/messages',dict(p,body='Different'));check(code==409,'changed retry conflicts')
code,r=req('messaging/messages',dict(p,image='not-supported'));check(code==422,'media rejected')
code,r=req('messaging/history?context='+f['conversationContext']);check(code==200 and any(m['id']==receipt and m['sender_name'] for m in r['data']['messages']),'history and sender names')
reply=dict(context=f['conversationContext'],submission_id='f'*32,body='API quoted reply',reply_to=receipt)
code,r=req('messaging/messages',reply);check(code==200,'API quoted reply accepted')
code,r=req('messaging/history?context='+f['conversationContext']);check(code==200 and any(m.get('reply',{}).get('id')==receipt for m in r['data']['messages'] if m.get('reply')),'API history includes canonical quote')
code,r=req('messaging/messages',dict(reply,reply_to=''));check(code==409,'API changed reply target conflicts')
code,r=req('messaging/history?context=not-authorised');check(code==403,'other context denied')
code,r=req('messaging/read',dict(context=f['conversationContext'],sequence=999999));check(code==422,'future read rejected')
def cli(action):subprocess.check_call(['docker','exec','-u','www-data','-e','MESSAGING_LOCAL_TEST=1','chisimba-php85-web','php','/var/www/html/ch/packages/messaging/tests/local_fixture.php',action],stdout=subprocess.DEVNULL)
cli('revoke')
try:
 code,r=req('messaging/history?context='+f['conversationContext']);check(code==403,'revoked bearer cannot read')
 code,r=req('messaging/announcements?scope=context&context='+f['conversationContext']);check(code==403,'revoked bearer cannot read announcements')
 code,r=req('messaging/announcements',announcement);check(code==403,'revoked bearer cannot replay announcement receipt')
 code,r=req('messaging/messages',p);check(code==403,'revoked bearer cannot replay receipt')
finally:cli('restore')
