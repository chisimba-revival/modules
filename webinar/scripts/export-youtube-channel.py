#!/usr/bin/env python3
"""Export public YouTube Videos/Live tabs; explicitly exclude the Shorts tab.
No credentials, playback downloads or third-party extraction dependency. The public
page format can change: fail on unknown/partial data, retain the previous catalogue.
Author: Derek Keats
"""
import argparse, json, re, urllib.request, time

def request(url, payload=None):
    data=None if payload is None else json.dumps(payload).encode()
    req=urllib.request.Request(url,data=data,headers={'User-Agent':'Mozilla/5.0','Content-Type':'application/json','Accept-Language':'en-GB,en;q=0.9'})
    with urllib.request.urlopen(req,timeout=40) as r:return r.read().decode()

def initial(html):
    marker='var ytInitialData = '
    if marker not in html:raise RuntimeError('YouTube page format unavailable')
    return json.JSONDecoder().raw_decode(html.split(marker,1)[1])[0]

def walk(obj,key):
    if isinstance(obj,dict):
        if key in obj:yield obj[key]
        for v in obj.values():yield from walk(v,key)
    elif isinstance(obj,list):
        for v in obj:yield from walk(v,key)

def tab(channel,name):
    html=request('https://www.youtube.com/channel/'+channel+'/'+name+'?hl=en')
    data=initial(html)
    tabs=data['contents']['twoColumnBrowseResultsRenderer']['tabs']
    selected=next((x['tabRenderer'] for x in tabs if x.get('tabRenderer',{}).get('selected')),None)
    expected={'videos':'Videos','streams':'Live','shorts':'Shorts'}[name]
    if not selected or selected.get('title')!=expected:return []
    items=selected['content'];result=[];seen=set()
    version=re.search(r'"INNERTUBE_CLIENT_VERSION":"([^"]+)"',html)
    if not version:raise RuntimeError('Missing YouTube public client version')
    for page in range(100):
        for v in walk(items,'lockupViewModel'):
            if v.get('contentType')!='LOCKUP_CONTENT_TYPE_VIDEO':continue
            vid=v.get('contentId','');title=v.get('metadata',{}).get('lockupMetadataViewModel',{}).get('title',{}).get('content','')
            badges=list(walk(v.get('contentImage',{}),'thumbnailBadgeViewModel'))
            duration=next((b.get('text','') for b in badges if re.fullmatch(r'[0-9:]+',b.get('text',''))),'')
            if re.fullmatch(r'[A-Za-z0-9_-]{11}',vid) and title and duration:result.append({'id':vid,'title':title,'duration':duration,'selection':name})
        for v in walk(items,'videoRenderer'):
            vid=v.get('videoId','');title=''.join(x.get('text','') for x in v.get('title',{}).get('runs',[]));duration=v.get('lengthText',{}).get('simpleText','')
            if re.fullmatch(r'[A-Za-z0-9_-]{11}',vid) and title and duration and not v.get('upcomingEventData'):result.append({'id':vid,'title':title,'duration':duration,'selection':name})
        if name=='shorts':
            for v in walk(items,'shortsLockupViewModel'):
                for ep in walk(v,'reelWatchEndpoint'):
                    if re.fullmatch(r'[A-Za-z0-9_-]{11}',ep.get('videoId','')):result.append({'id':ep['videoId'],'selection':'shorts'})
            for v in walk(items,'reelItemRenderer'):
                if re.fullmatch(r'[A-Za-z0-9_-]{11}',v.get('videoId','')):result.append({'id':v['videoId'],'selection':'shorts'})
        continuations=list(walk(items,'continuationItemRenderer'))
        if not continuations:break
        token=continuations[-1]['continuationEndpoint']['continuationCommand']['token']
        if token in seen:raise RuntimeError('Repeated YouTube continuation')
        seen.add(token);time.sleep(.3)
        items=json.loads(request('https://www.youtube.com/youtubei/v1/browse?prettyPrint=false',{'context':{'client':{'clientName':'WEB','clientVersion':version.group(1),'hl':'en','gl':'GB'}},'continuation':token}))
        if not any(walk(items,'appendContinuationItemsAction')):raise RuntimeError('Incomplete YouTube continuation')
    else:raise RuntimeError('Channel exceeds reviewed page limit')
    return result

def export(channel):
    shorts=tab(channel,'shorts');excluded={v['id'] for v in shorts};videos={}
    counts={}
    for name in ['videos','streams']:
        entries=tab(channel,name);counts[name]=len(entries)
        for v in entries:
            if v['id'] not in excluded:videos.setdefault(v['id'],v)
    if not videos:raise RuntimeError('No full-length videos; refusing empty replacement')
    return {'version':1,'channel':channel,'source':'https://www.youtube.com/channel/'+channel,'fetched_at':time.strftime('%Y-%m-%dT%H:%M:%SZ',time.gmtime()),'counts':counts,'excluded_shorts':sorted(excluded),'videos':list(videos.values())}
if __name__=='__main__':
    p=argparse.ArgumentParser();p.add_argument('channel');p.add_argument('output');a=p.parse_args()
    if not re.fullmatch(r'UC[A-Za-z0-9_-]{22}',a.channel):p.error('Invalid channel ID')
    result=export(a.channel)
    with open(a.output,'w') as f:json.dump(result,f,ensure_ascii=False,indent=2)
    print(json.dumps({'videos':len(result['videos']),'excluded_shorts':len(result['excluded_shorts']),'tabs':result['counts']}))
