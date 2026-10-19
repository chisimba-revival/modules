"""Normal login and real local HTTP. Synthetic account only; no TLS bypass."""
import base64, http.cookiejar, json, pathlib, ssl, subprocess, time, urllib.request, urllib.error
base='https://chisimba.test:8445/ch/api/v1/'
f=json.loads(subprocess.check_output(['docker','exec','--user','www-data','chisimba-php85-web','cat','/tmp/sightings-http-fixture.json']))
assert f['username'].startswith('sighting_test_') and f['context'].startswith('sighttest')
ctx=ssl.create_default_context(cafile=str(pathlib.Path(__file__).resolve().parents[3]/'dev-environment/local-https/certs/chisimba.test.crt'))
ctx.verify_flags |= ssl.VERIFY_X509_PARTIAL_CHAIN
client=urllib.request.build_opener(urllib.request.HTTPSHandler(context=ctx),urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
token=None

def req(path,data=None,auth=True):
    headers={'Content-Type':'application/json'}
    if token and auth: headers['Authorization']='Bearer '+token
    try: response=client.open(urllib.request.Request(base+path,headers=headers,data=json.dumps(data).encode() if data is not None else None))
    except urllib.error.HTTPError as error: response=error
    return response.status,json.load(response),response.headers

def check(ok,label):
    assert ok,label
    print('PASS',label,flush=True)

code,login,_=req('auth/login',auth=False);e=login['data'];time.sleep(max(1,e['minimum_seconds'])+1)
code,result,_=req('auth/login',dict(username=f['username'],password=f['password'],csrf_token=e['csrf_token'],abuse_issued_at=e['issued_at'],abuse_nonce=e['nonce'],abuse_signature=e['signature'],device_id='sightings-local-test'),False)
check(code==201,'normal login');token=result['data']['tokens']['access_token']
# The fixture image was generated locally; no personal photograph is sent.
image=base64.b64encode(pathlib.Path('/tmp/sighting-small-fixture.jpg').read_bytes()).decode()
payload=dict(context=f['context'],submission_id='9'*32,body='Synthetic sightings API image',image=image)
code,result,_=req('messaging/sightings',payload);check(code==200,'photo and caption upload '+str(code)+' '+str(result.get('error',{})));receipt=result['data']
code,result,_=req('messaging/sightings',payload);check(code==200 and result['data']==receipt,'same upload retry returns same receipt')
code,_,_=req('messaging/sightings',dict(payload,body='Changed'));check(code==409,'changed payload conflicts')
code,_,_=req('messaging/sightings',dict(payload,submission_id='8'*32,image='invalid'));check(code==422,'invalid image rejected')
path='messaging/sightingimage?context='+f['context']+'&id='+receipt['id']
code,result,headers=req(path);check(code==200 and result['data']['mime']=='image/jpeg' and 'no-store' in headers['Cache-Control'],'authorised private image bytes')
code,_,_=req(path,auth=False);check(code==401,'anonymous image denied even in public course')
code,result,_=req('messaging/sightings?context='+f['context']);check(code==200 and len(result['data']['sightings'])==1,'one saved sighting')
text=dict(context=f['context'],submission_id='7'*32,body='Same lion yesterday',reply_to=receipt['id'])
code,result,_=req('messaging/sightings',text);check(code==200,'text-only reply');text_receipt=result['data']
code,result,_=req('messaging/sightings',text);check(code==200 and result['data']==text_receipt,'text reply retry')
code,_,_=req('messaging/sightings',dict(text,reply_to=''));check(code==409,'changed reply conflicts')
code,result,_=req('messaging/sightings?context='+f['context']);check(code==200 and any(r.get('reply',{}).get('id')==receipt['id'] for r in result['data']['sightings'] if r.get('reply')),'quoted photo in history')
subprocess.check_call(['docker','exec','--user','www-data','-e','SIGHTINGS_LOCAL_TEST=1','chisimba-php85-web','php','/var/www/html/ch/packages/messaging/tests/sightings_http_fixture.php','revoke'])
for path,data in [(path,None),('messaging/sightings?context='+f['context'],None),('messaging/sightings',payload)]:
    code,_,_=req(path,data);check(code==403,'revoked membership blocks image, history and receipt replay')
