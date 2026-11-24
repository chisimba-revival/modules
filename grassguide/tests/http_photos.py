#!/usr/bin/env python3
"""HTTP photo checks against run.py --preview's disposable loopback fixture only."""
import json
import urllib.error
import urllib.parse
import urllib.request

base = 'http://127.0.0.1:8768/'
def request(query, method='GET', anonymous=False):
    req = urllib.request.Request(base+'?'+urllib.parse.urlencode({'module':'grassguide', **query}), method=method,
        headers={'X-Fixture-Role':'anonymous'} if anonymous else {})
    try:
        with urllib.request.urlopen(req) as r:
            return r.status, r.headers, r.read()
    except urllib.error.HTTPError as e:
        return e.code, e.headers, e.read()
status, _, data = request({'action':'export'})
assert status == 200
species = next(s for s in json.loads(data)['species'] if s['scientific_name']=='Uploadus fixture')
active = next(m for m in species['media'] if m.get('_file_id') and m['state']=='active')
archived = next(m for m in species['media'] if m.get('_file_id') and m['state']=='archived')
query = {'action':'photo', 'id':active['id']}
status, headers, image = request(query, anonymous=True)
assert status == 200 and headers['Content-Type'].startswith('image/') and image
assert headers['Access-Control-Allow-Origin'] == '*' and headers['X-Content-Type-Options'] == 'nosniff'
status, headers, body = request(query, 'HEAD', True)
assert status == 200 and not body and int(headers['Content-Length']) == len(image)
assert request(query, 'OPTIONS', True)[0] == 204
assert request(query, 'POST', True)[0] == 405
assert request({'action':'photo','id':archived['id']}, anonymous=True)[0] == 404
assert request({'action':'photo','id':archived['id']})[0] == 200
assert request({'action':'photos','id':species['id']}, anonymous=True)[0] == 403
print('PASS HTTP photos: anonymous published bytes, admin archive access, HEAD, CORS, methods and private upload page.')
