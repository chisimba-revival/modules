"""Source-backed field highlights, ranked against known regional peers.
No inference from missing/default states and no claim of a definitive diagnosis.
"""
import re

STATE = {
 'panicle_type': ('Flower head', {'1':'an open, spreading panicle','2':'a contracted panicle','3':'a spike-like panicle','4':'a panicle of clustered groups','5':'a compact, head-like panicle'}),
 'racemes_disposition': ('Flower branches', {'1':'single racemes','2':'paired racemes','3':'finger-like racemes arising together','4':'racemes spaced along a central axis'}),
 'ligule_structure_AtMidculmNodeWhenCaulineFoliagePresent': ('Ligule at the leaf–stem junction', {'1':'a membrane without fringe hairs','2':'a membrane with a fringe of short hairs','3':'a membrane with hairs as long as, or longer than, the membrane','4':'a fringe of hairs','5':'absent'}),
 'principalLemmaAwn_overallShape': ('Main awn on the fertile floret', {'1':'straight','2':'curved','3':'wavy','4':'coiled','5':'hooked at the tip','6':'bent like a knee','7':'bent at two points','8':'three-branched'}),
}
NUMERIC = {
 'panicle_length':('Flower-head length','cm','head'),
 'racemes_length':('Raceme length','cm','head'),
 'lemmaApex_numberOfAwns':('Awns on each fertile lemma','','awn'),
 'principalLemmaAwn_lengthIncludingColumn':('Main awn length','mm','awn'),
 'spikelets_length':('Fertile spikelets, excluding awns','mm','spikelet'),
 'leafBlades_width':('Leaf-blade width','mm','leaf'),
 'culms_height':('Stem height','cm','habit'),
}
def state_codes(raw):
    # DELTA implicit states are not observations; exclude them from highlights.
    if '<' in raw or not re.fullmatch(r'\d+(?:\s*[/&]\s*\d+)*',raw): return None
    return set(re.findall(r'\d+',raw))

def candidates(row):
    groups={}
    for key,(label,states) in STATE.items():
        codes=state_codes(row.get(key,''))
        if not codes or not codes.issubset(states): continue
        group='head' if key in ('panicle_type','racemes_disposition') else 'ligule' if key.startswith('ligule') else 'awn'
        phrase=' or '.join(states[c] for c in sorted(codes,key=int))
        groups.setdefault(group,[]).append((key,f'{label}: {phrase}',codes))
    for key,(label,unit,group) in NUMERIC.items():
        raw=row.get(key,'')
        if not re.fullmatch(r'[\d.()\s-]+',raw) or not re.search(r'\d',raw):continue
        nums=[float(n) for n in re.findall(r'\d+(?:\.\d+)?',raw)]
        groups.setdefault(group,[]).append((key,f'{label}: {raw.replace("-","–")}{" "+unit if unit else ""}',(min(nums),max(nums))))
    return groups

def highlights(name,rows):
    row=rows[name]; options=candidates(row)
    # Prefer genus peers; otherwise compare grasses with the same known head state.
    peers=[r for n,r in rows.items() if n!=name and n.split()[0]==name.split()[0]]
    if not peers:
        peers=[r for n,r in rows.items() if n!=name and any(state_codes(row.get(k,'')) and state_codes(row.get(k,''))==state_codes(r.get(k,'')) for k in ('panicle_type','racemes_disposition'))]
    ranked=[]
    for group,values in options.items():
        score=0
        for key,_,value in values:
            known=separated=0
            for p in peers:
                entry=next((v for vs in candidates(p).values() for v in vs if v[0]==key),None)
                if not entry:continue
                other=entry[2];known+=1
                separated+=int(value.isdisjoint(other) if isinstance(value,set) else value[1]<other[0] or other[1]<value[0])
            # Sparse evidence must not make a rare measurement look diagnostic.
            score=max(score,separated/max(len(peers),1))
        priority={'head':6,'awn':5,'spikelet':4,'ligule':3,'leaf':2,'habit':1}[group]
        ranked.append((group,score,priority,'; '.join(v[1] for v in values)+'.'))
    ranked.sort(key=lambda x:(x[0]=='head',x[1],x[2]),reverse=True)
    return '\n'.join(x[3] for x in ranked[:4])
