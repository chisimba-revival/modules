#!/usr/bin/env python3
"""Build a reproducible regional cache from the licensed GrassBase Zenodo API release.
Never changes the catalogue names. Downloads are checksum verified; no fuzzy matching.
"""
import argparse,csv,hashlib,html,json,pathlib,re,urllib.request,zipfile
from html.parser import HTMLParser
from key_features import highlights
RECORD='22302582'
FILES=['grassbase_species.tsv','grassbase_complete.tsv','grassbase_html_descriptions.zip','grassbase_character_states.tsv']
class Paragraphs(HTMLParser):
    def __init__(self):super().__init__();self.parts=[];self.current=None
    def handle_starttag(self,tag,attrs):
        if tag=='p':
            self.flush();self.current=[]
    def handle_endtag(self,tag):
        if tag=='p':self.flush()
    def handle_data(self,data):
        if self.current is not None:self.current.append(data)
    def flush(self):
        if self.current is not None:
            value=re.sub(r'\s+',' ',''.join(self.current)).strip()
            if value and not value.startswith('Please cite'):self.parts.append(value)
        self.current=None

def codes(value):
    text=re.sub(r'<[^>]*>','',value).strip()
    return set(re.findall(r'\d+',text)) if re.fullmatch(r'\d+(?:\s*[/&]\s*\d+)*',text) else set()

def build(cache,seed,record):
    metadata=list(csv.DictReader((cache/'grassbase_species.tsv').open(),delimiter='\t'))
    with (cache/'grassbase_complete.tsv').open() as f:
        rows=csv.reader(f,delimiter='\t');header=next(rows)
        # DELTA export includes row names without a header cell: validate alignment explicitly.
        traits={}
        for row in rows:
            if len(row)!=len(header)+1:raise ValueError('Unexpected trait table alignment')
            traits[row[0]]=dict(zip(header,row[1:]))
    archive=zipfile.ZipFile(cache/'grassbase_html_descriptions.zip')
    index=archive.read('Grassbase/sppindex.htm').decode('utf-8')
    descriptions={html.unescape(name):path for path,name in re.findall(r'<a href="(descriptions/imp[0-9]+\.htm)">([^<]+)</a>',index)}
    result={};unmatched=[]
    for entry in csv.DictReader(seed.open()):
        name=entry['Accepted_Genus']+' '+entry['Accepted_Species'];aliases=entry['Synonyms'].split('|')
        hits=[r for r in metadata if r['speciesGrassBase']==name] or [r for r in metadata if r['speciesGrassBase'] in aliases] or [r for r in metadata if r['scientificName']==name]
        if len(hits)!=1:unmatched.append(name);continue
        taxon=hits[0];original=taxon['speciesGrassBase'];path=descriptions.get(original);row=traits.get(original)
        if not path or row is None:unmatched.append(name);continue
        text=archive.read('Grassbase/'+path).decode('utf-8');body=re.search(r'<div id="BodyText">(.*?)</div>',text,re.S)
        if not body:raise ValueError('Missing botanical description '+path)
        parser=Paragraphs();parser.feed(body[1]);parser.flush();parts=parser.parts
        field={'description':'\n\n'.join(parts)[:12000]}
        diagnostic=[p for p in parts if not p.startswith(('DISTRIBUTION','NOTES'))]
        field['diagnostic']='\n\n'.join(diagnostic)[:12000]
        heads=[];panicle=codes(row['panicle_type']);racemes=codes(row['racemes_disposition'])
        if '1' in panicle:heads.append('open_panicle')
        if panicle & {'2','3'}:heads.append('contracted_panicle')
        if panicle & {'4','5'}:heads.append('capitate')
        for state,key in [('1','single_raceme'),('2','paired_racemes'),('3','digitate'),('4','racemes_axis')]:
            if state in racemes:heads.append(key)
        if '2' in codes(row['synflorescence_typeOfInflorescenceAggregation']):heads.append('compound')
        if heads:field['heads']=heads
        awns=codes(row['lemmaApex_presenceOfAwn'])
        if '5' in awns or ('4' in awns and '1' in awns):field['awns']='variable'
        elif '4' in awns:field['awns']='present'
        elif awns=={'1'}:field['awns']='absent'
        citation='Vorontsova et al. (2026), GrassBase, DOI 10.5281/zenodo.22302582; original descriptions Clayton et al. (2016). Source taxon: '+original+'; dataset accepted name: '+taxon['scientificName']+'; '+path+'.'
        evidence={k:row[k] for k in ['synflorescence_typeOfInflorescenceAggregation','inflorescence_type','panicle_type','racemes_disposition','lemmaApex_presenceOfAwn']}
        field['trait_source']=citation+' Structured characters 213, 229, 260, 315 and 870. Awns refer to the fertile lemma. Source implicit/default states are retained; unrepresented character states remain unknown. https://doi.org/10.5281/zenodo.22302582'
        result[name]={'fields':field,'citation':citation,'original_name':original,'accepted_name':taxon['scientificName'],'worldfloraonline_id':taxon['worldFloraOnlineID'],'characters':evidence,'archive_path':path,'match':'original' if original==name else 'supplied_synonym' if original in aliases else 'dataset_accepted_name'}
    regional={name:traits[value['original_name']] for name,value in result.items()}
    for name,value in result.items():
        value['fields']['key_features']=highlights(name,regional)
    return {'format':1,'doi':'10.5281/zenodo.22302582','licence':'CC-BY-4.0','publication_date':record['metadata']['publication_date'],'files':{f['key']:{'checksum':f['checksum'],'sha256':hashlib.sha256((cache/f['key']).read_bytes()).hexdigest()} for f in record['files'] if f['key'] in FILES},'species':result,'unmatched':unmatched}

def main():
    p=argparse.ArgumentParser();p.add_argument('--cache',type=pathlib.Path,required=True);p.add_argument('--output',type=pathlib.Path,default=pathlib.Path(__file__).resolve().parents[1]/'data/grassbase-lowveld.json');a=p.parse_args();a.cache.mkdir(parents=True,exist_ok=True)
    record=json.load(urllib.request.urlopen('https://zenodo.org/api/records/'+RECORD,timeout=40))
    if record['metadata']['license']['id']!='cc-by-4.0':raise ValueError('Review changed dataset licence')
    for f in record['files']:
        if f['key'] not in FILES:continue
        target=a.cache/f['key'];expected=f['checksum']
        if not target.exists() or 'md5:'+hashlib.md5(target.read_bytes()).hexdigest()!=expected:
            temp=target.with_suffix('.part')
            with urllib.request.urlopen(f['links']['self'],timeout=120) as r,temp.open('wb') as w:
                while chunk:=r.read(1024*1024):w.write(chunk)
            if 'md5:'+hashlib.md5(temp.read_bytes()).hexdigest()!=expected:raise ValueError('Source checksum mismatch')
            temp.replace(target)
    result=build(a.cache,pathlib.Path(__file__).resolve().parents[1]/'data/lowveld-kruger-v4.csv',record)
    a.output.write_text(json.dumps(result,ensure_ascii=False,indent=2)+'\n')
    print(json.dumps({'matched':len(result['species']),'unmatched':result['unmatched'],'heads':sum(bool(r['fields'].get('heads')) for r in result['species'].values()),'awns':sum(bool(r['fields'].get('awns')) for r in result['species'].values())}))
if __name__=='__main__':main()
