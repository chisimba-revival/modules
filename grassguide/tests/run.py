#!/usr/bin/env python3
"""Isolated real-PDO fixture; --preview serves synthetic data on loopback until interrupted."""
import pathlib
import os
import secrets
import subprocess
import sys
import time
import tempfile

root = pathlib.Path(__file__).resolve().parents[2]
framework = pathlib.Path(os.environ.get('GRASSGUIDE_FRAMEWORK', root.parent / 'framework')).resolve()
module = root / 'grassguide'
shared_modules = pathlib.Path(os.environ.get('GRASSGUIDE_SHARED_MODULES', root)).resolve()
name = 'chisimba-grassguide-' + secrets.token_hex(5)
password = secrets.token_hex(24)
preview = '--preview' in sys.argv
photo_store = tempfile.TemporaryDirectory(prefix='grassguide-photos-')
def run(args, **kwargs):
    result = subprocess.run(args, **kwargs)
    if result.returncode:
        raise RuntimeError('Fixture command failed; see diagnostics above')
    return result

try:
    run(['docker','network','create','--internal',name], stdout=subprocess.DEVNULL)
    run(['docker','run','--rm','-d','--name',name+'-db','--network',name,'--network-alias','grassguide-db','--tmpfs','/var/lib/mysql','-e','MARIADB_ROOT_PASSWORD='+password,'-e','MARIADB_DATABASE=grassguide_test','mariadb:10.11.18'], stdout=subprocess.DEVNULL)
    for attempt in range(45):
        result = subprocess.run(['docker','exec',name+'-db','mariadb','-uroot','-p'+password,'-e','SELECT 1'],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
        if result.returncode == 0:
            break
        time.sleep(1)
    else:
        raise RuntimeError('Disposable database did not start')
    common = ['-v',str(shared_modules)+':/shared-modules:ro','--user',str(os.getuid())+':'+str(os.getgid()),'-v',photo_store.name+':/photo-store','--network',name,'-e','GRASSGUIDE_TEST=1','-e','GRASSGUIDE_TEST_PASSWORD='+password,'-v',str(framework)+':/framework:ro','-v',str(module)+':/module:ro','--entrypoint','php']
    run(['docker','run','--rm',*common,'chisimba-php85-web','-d','display_errors=1','-d','auto_prepend_file=','/module/tests/fresh_dependencies.php'])
    run(['docker','run','--rm',*common,'chisimba-php85-web','-d','display_errors=1','-d','auto_prepend_file=','/module/tests/fresh_photo_dependencies.php'])
    run(['docker','run','--rm',*common,'chisimba-php85-web','-d','display_errors=1','-d','auto_prepend_file=','/module/tests/integration.php'])
    if preview:
        run(['docker','run','--rm','-d','--name',name+'-web','-p','127.0.0.1:8768:8080',*common,'chisimba-php85-web','-d','display_errors=1','-d','auto_prepend_file=','-d','upload_max_filesize=10M','-d','post_max_size=48M','-S','0.0.0.0:8080','/module/tests/preview.php'],stdout=subprocess.DEVNULL)
        run(['docker','network','connect','bridge',name+'-web'])
        print('Isolated preview: http://127.0.0.1:8768/ (Ctrl-C removes all fixtures)',flush=True)
        while True:
            time.sleep(1)
finally:
    for suffix in ('-web','-db'):
        subprocess.run(['docker','rm','-f',name+suffix],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
    subprocess.run(['docker','network','rm',name],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)

    photo_store.cleanup()
