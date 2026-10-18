"""Local fixture-only HTTP checks; no live user messages are touched."""
import subprocess,json,urllib.request,urllib.error,urllib.parse,http.cookiejar,ssl,time,re,html,concurrent.futures
web='chisimba-php85-web';base='https://chisimba.test:8445/ch'
f=json.loads(subprocess.check_output(['docker','exec','-u','www-data',web,'cat','/tmp/restapi-local-fixture.json']))
assert f['username'].startswith('api_test_') and f['conversationContext'].startswith('msgtest')
def cli(op):return subprocess.check_output(['docker','exec','-u','www-data','-e','MESSAGING_LOCAL_TEST=1',web,'php','/var/www/html/ch/packages/messaging/tests/local_fixture.php',op]).decode()
ctx=ssl._create_unverified_context() # Local certificate only; target is fixed above.
client=urllib.request.build_opener(urllib.request.HTTPSHandler(context=ctx),urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
anon=urllib.request.build_opener(urllib.request.HTTPSHandler(context=ctx))
def request(path,data=None,jsonbody=False,c=client):
 headers={}
 if data is not None:headers['Content-Type']='application/json' if jsonbody else 'application/x-www-form-urlencoded';data=(json.dumps(data) if jsonbody else urllib.parse.urlencode(data)).encode()
 try:r=c.open(urllib.request.Request(base+path,data=data,headers=headers),timeout=30)
 except urllib.error.HTTPError as e:r=e
 return r.status,r.read().decode()
def route(**kw):return '/index.php?'+urllib.parse.urlencode(dict(module='messaging',context=f['conversationContext'],**kw))
def token(page):return html.unescape(re.search(r'name="csrf_token" value="([^"]+)"',page).group(1))
def check(ok,name):assert ok,name;print('PASS',name,flush=True)
with concurrent.futures.ThreadPoolExecutor(max_workers=2)as pool:
 ids=list(pool.map(lambda _:cli('race').strip(),range(2)))
check(ids[0]==ids[1] and re.fullmatch('[a-f0-9]{32}',ids[0]),'concurrent duplicate yields one receipt')
code,raw=request('/api/v1/auth/login');e=json.loads(raw)['data'];time.sleep(max(1,e['minimum_seconds'])+1)
code,raw=request('/api/v1/auth/login',dict(username=f['username'],password=f['password'],csrf_token=e['csrf_token'],abuse_issued_at=e['issued_at'],abuse_nonce=e['nonce'],abuse_signature=e['signature'],device_id='messaging-web-test'),True);check(code==201,'fixture login')
code,page=request(route());check(code==200 and 'Your message' in page and 'Concurrent retry' in page,'member sees conversation and form')
code,raw=request(route(action='send'));check(code==405,'GET cannot send')
post=dict(channel=f['conversationChannel'],submission_id='c'*32,body='<script>alert(1)</script>',csrf_token='invalid')
code,raw=request(route(action='send'),post);check(code==403,'invalid CSRF rejected')
code,page=request(route());post['csrf_token']=token(page);code,page=request(route(action='send'),post);check(code==200 and '&lt;script&gt;alert(1)&lt;/script&gt;' in page and '<script>alert(1)</script>' not in page,'sent text is escaped')
for tab in ['announcements','site']:
 code,page=request(route(tab=tab));check(code==200 and 'read-only here' in page and 'name="body"' not in page,'one-way '+tab)
code,page=request(route(action='getchat'));check(code==404,'retired chat handler closed')
code,page=request(route(),c=anon);check('Concurrent retry' not in page,'anonymous history withheld')
cli('revoke')
try:
 code,page=request(route());check('Concurrent retry' not in page and 'Your message' not in page,'revoked member cannot read or send')
finally:cli('restore')
print('Local messaging HTTP checks passed.')
