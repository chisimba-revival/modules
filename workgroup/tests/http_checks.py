from pathlib import Path
import subprocess,json,re,html,time,os
if os.environ.get('CHISIMBA_WORKGROUP_DISPOSABLE') != 'yes':
 raise SystemExit('Set CHISIMBA_WORKGROUP_DISPOSABLE=yes only for the isolated fixture clone')
from urllib.request import build_opener,HTTPCookieProcessor,Request
from urllib.error import HTTPError
from http.cookiejar import CookieJar
from html.parser import HTMLParser
from urllib.parse import urlencode
base='http://127.0.0.1:8088/ch/'
container='chisimba-no-jquery-rehearsal-20261016-web'
class LoopbackJar(CookieJar):
 def set_cookie(self,cookie,*args,**kw):
  assert cookie.domain=='127.0.0.1';cookie.secure=False;return super().set_cookie(cookie,*args,**kw)
class Fields(HTMLParser):
 def __init__(self):super().__init__();self.values={}
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if tag=='input' and a.get('name') and a.get('type') not in ['checkbox','radio']:self.values[a['name']]=a.get('value','')
def call(op,params,data=None):
 url=base+'index.php?'+urlencode(params)
 try:
  with op.open(Request(url,None if data is None else urlencode(data).encode()),timeout=30) as r:return r.status,r.read().decode(),r.url
 except HTTPError as e:return e.code,e.read().decode(),e.url
def login(username="admin"):
 op=build_opener(HTTPCookieProcessor(LoopbackJar()));_,body,_=call(op,{'module':'security','action':'showlogin'});f=Fields();f.feed(body);f.values.update(username=username,password='EditorFixture!2026-local');time.sleep(1.1)
 status,body,_=call(op,{'module':'security','action':'login'},f.values);assert status==200 and 'native-login-title' not in body
 call(op,{'module':'context','action':'joincontext','contextcode':'admissionfree'});return op
def fields(body):
 f=Fields();f.feed(body);return f.values
def join(op):
 status,body,_=call(op,dict(module='context',action='joincontext',contextcode='admissionfree'));assert status==200
member=login('editorfixture');peer=login('wgpeer');outsider=login('wgoutsider');teacher=login('wgteacher');admin=login('admin')
for op in [member,peer,outsider,teacher,admin]:join(op)
group=dict(module='workgroup',workgroupid='wg-fixture')
for op,private in [(member,True),(peer,False),(teacher,True),(admin,True)]:
 status,body,_=call(op,group);assert status==200,(status,body[-1200:]);assert 'Synthetic wg-share-public' in body
 assert ('Synthetic wg-share-private' in body)==private
 assert 'workgroup.js' not in body and 'jquery' not in body.lower()
print('PASS native lists and existing file visibility')
for op,expected in [(member,True),(peer,False),(outsider,False)]:
 status,body,_=call(op,dict(module='filemanager',action='file',id='wg-file-private',filename='wg-file-private.txt'))
 assert status==200,status
 assert (body=='Synthetic workgroup file\n')==expected
 if not expected:assert 'Access denied' in body or 'Access Denied' in body
assert call(peer,dict(module='filemanager',action='file',id='wg-file-public',filename='wg-file-public.txt'))[0]==200
print('PASS original file delivery enforces private/public access')

for action in ['', 'ajaxgetfiles','upload','editfile','deletefile']:
 status,body,_=call(outsider,dict(group,action=action,fileid='wg-share-public'));assert status==403,(action,status)
status,body,_=call(member,dict(group,workgroupid='wg-other'));assert status==403
for action in ['editfile','deletefile']:
 status,body,_=call(peer,dict(group,action=action,fileid='wg-share-public'));assert status==403
print('PASS outsider, cross-context and non-owner denial')
status,body,_=call(member,dict(group,action='editfile',fileid='wg-share-public'));assert status==200
form=fields(body);form.update(title='Preserved <draft>',description='Typed notes',version='2',fileupload='wg-file-public',csrf_token='expired')
status,body,_=call(member,dict(group,action='editfileupload',fileid='wg-share-public'),form);assert status==403 and 'Preserved &lt;draft&gt;' in body
form['csrf_token']=fields(body)['csrf_token'];stale=dict(form)
status,body,_=call(member,dict(group,action='editfileupload',fileid='wg-share-public'),form);assert status==200
status,body,_=call(member,dict(group,action='editfileupload',fileid='wg-share-public'),stale);assert status==409 and 'Typed notes' in body
print('PASS CSRF draft recovery, save, stale rejection')
status,body,_=call(member,dict(group,action='upload'));form=fields(body);form.update(title='New share',description='New description',version='1',fileupload='wg-file-foreign')
status,body,_=call(member,dict(group,action='uploadconfirm'),form);assert status==403
form['fileupload']='wg-file-public';form['csrf_token']=fields(body)['csrf_token']
for i in range(2):
 status,body,_=call(member,dict(group,action='uploadconfirm'),form);assert status==200,(status,body[-700:])
assert body.count('<h3>New share</h3>')==1
newid=form['id']
status,body,_=call(member,dict(group,action='deletefile',fileid=newid));assert status==200 and 'Confirm removal' in body
confirm=fields(body);confirm['csrf_token']='invalid'
status,body,_=call(member,dict(group,action='deletefile',fileid=newid),confirm);assert status==403
status,body,_=call(member,dict(group,action='deletefile',fileid=newid));confirm=fields(body)
status,body,_=call(member,dict(group,action='deletefile',fileid=newid),confirm);assert status==200 and '<h3>New share</h3>' not in body
status,body,_=call(member,dict(group,action='uploadconfirm'));assert status==405
print('PASS file ownership, idempotent creation, safe GET and checked POST deletion')
for op in [member,outsider]:
 status,body,_=call(op,dict(module='workgroupadmin'));assert status==403
status,body,_=call(teacher,dict(module='workgroupadmin',action='create'));assert status==200
form=fields(body);form.update(description='New synthetic group',members=['editor-fixture-member'])
# urlencode with doseq for membership arrays: PHP expects members[].
form.pop('members');form['members[]']='editor-fixture-member'
status,body,_=call(teacher,dict(module='workgroupadmin',action='createconfirm'),form);assert status==200,(status,body[-900:])
newgroup=form['id']
edit=fields(body);edit['members[]']='editor-fixture-member';edit['description']='Renamed synthetic group';old=dict(edit)
status,body,_=call(teacher,dict(module='workgroupadmin',action='processform',workgroupId=newgroup),edit);assert status==200
status,body,_=call(teacher,dict(module='workgroupadmin',action='processform',workgroupId=newgroup),old);assert status==409
status,body,_=call(teacher,dict(module='workgroupadmin',action='manage',workgroupId=newgroup));edit=fields(body);edit['members[]']='not-a-course-member'
status,body,_=call(teacher,dict(module='workgroupadmin',action='processform',workgroupId=newgroup),edit);assert status==422
status,body,_=call(member,dict(module='workgroup',workgroupid=newgroup));assert status==200
status,body,_=call(teacher,dict(module='workgroupadmin',action='delete',workgroupId=newgroup));confirm=fields(body)
status,body,_=call(teacher,dict(module='workgroupadmin',action='delete',workgroupId=newgroup),confirm);assert status==200
status,body,_=call(member,dict(module='workgroup',workgroupid=newgroup));assert status==403
print('PASS scoped administration, member validation, stale membership edits and confirmed group deletion')

def revoke(mode):
 subprocess.run(['docker','exec','-i',container,'php','--',mode],input=Path(__file__).with_name('revoke_fixture.php').read_bytes(),check=True)
revoke('group')
assert call(member,group)[0]==403
revoke('restoregroup')
assert call(member,group)[0]==200
revoke('course')
assert call(member,group)[0]==403
revoke('teacher')
assert call(teacher,dict(module='workgroupadmin'))[0]==403
assert call(teacher,group)[0]==403
print('PASS current group, course and teacher revocation with existing sessions')

visitor=build_opener(HTTPCookieProcessor(LoopbackJar()))
for module in ['workgroup','workgroupadmin']:
 status,body,_=call(visitor,dict(module=module,action='ajaxgetfiles',workgroupid='wg-fixture'))
 assert 'Synthetic wg-' not in body and 'Browser shared file' not in body
print('PASS anonymous routes disclose no shared data')
