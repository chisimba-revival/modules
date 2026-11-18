#!/usr/bin/env python3
"""Run course blueprint persistence in disposable containers; never use installed site data."""
import pathlib,secrets,subprocess,sys,time
root=pathlib.Path(__file__).resolve().parents[2]
framework=pathlib.Path(sys.argv[1]).resolve()
name='chisimba-map-course-'+secrets.token_hex(6); password=secrets.token_hex(24)
def run(args,**kwargs):
 result=subprocess.run(args,**kwargs)
 if result.returncode: raise RuntimeError('Disposable fixture failed; see diagnostics above')
 return result
run(['docker','network','create','--internal',name],stdout=subprocess.DEVNULL)
try:
 run(['docker','run','--rm','-d','--name',name,'--network',name,'--network-alias','baseline-db','--tmpfs','/var/lib/mysql','-e','MARIADB_ROOT_PASSWORD='+password,'-e','MARIADB_DATABASE=contract_fixture','mariadb:10.11.18'],stdout=subprocess.DEVNULL)
 for attempt in range(60):
  if subprocess.run(['docker','exec',name,'mariadb','-uroot','-p'+password,'-e','SELECT 1'],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL).returncode==0: break
  time.sleep(1)
 else: raise RuntimeError('Disposable database did not start')
 run(['docker','run','--rm','--network',name,'--entrypoint','php','-e','CHISIMBA_DB_BASELINE=1','-e','CHISIMBA_DB_PASSWORD='+password,'-v',str(framework)+':/framework:ro','-v',str(root)+':/modules:ro','chisimba-php85-web','-d','display_errors=1','-d','auto_prepend_file=','/modules/knowledgemap/tests/course_pdo_test.php'])
finally:
 subprocess.run(['docker','rm','-f',name],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
 subprocess.run(['docker','network','rm',name],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
