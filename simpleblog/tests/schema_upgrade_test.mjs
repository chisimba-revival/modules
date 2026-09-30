/** Rehearse both historical 0.070 branches in a network-isolated disposable DB. */
import fs from 'node:fs';
import {execFileSync} from 'node:child_process';
const container=process.env.SIMPLEBLOG_MIGRATION_CONTAINER;
if(!container?.startsWith('chisimba-release-migration-'))throw Error('Explicit disposable container required');
const details=JSON.parse(execFileSync('docker',['inspect',container],{encoding:'utf8'}))[0];
if(details.HostConfig.NetworkMode!=='none'||!Object.hasOwn(details.HostConfig.Tmpfs??{},'/var/lib/mysql'))throw Error('Database must be isolated and ephemeral');
const xml=fs.readFileSync(new URL('../sql/sql_updates.xml',import.meta.url),'utf8');
const update=[...xml.matchAll(/<update>([\s\S]*?)<\/update>/g)].map(m=>m[1]).find(s=>s.includes('<version>0.075</version>'));
if(!update)throw Error('Forward reconciliation migration absent');
const statements=[...update.matchAll(/<SQL>([\s\S]*?)<\/SQL>/g)].map(m=>m[1]).join('\n');
const sql=input=>execFileSync('docker',['exec','-i',container,'mariadb','--batch','--skip-column-names','-uroot'],{input,encoding:'utf8'}).trim();
const provenance='author_credit VARCHAR(250) NULL, source_key VARCHAR(191) NULL, source_url LONGTEXT NULL, source_hash VARCHAR(64) NULL, import_hash VARCHAR(64) NULL, UNIQUE KEY simpleblog_source_unique (source_key)';
for(const variant of ['old','access','import']) {
    const database='release_blog_'+variant;
    const extra=variant==='access'?', required_tier_code VARCHAR(32) NULL':variant==='import'?', '+provenance:'';
    sql(`CREATE DATABASE ${database}; USE ${database}; CREATE TABLE tbl_simpleblog_posts (id VARCHAR(32) PRIMARY KEY, post_content LONGTEXT${extra}) ENGINE=InnoDB;
        INSERT INTO tbl_simpleblog_posts (id,post_content) VALUES ('kept','original content');`);
    if(variant==='access')sql(`UPDATE ${database}.tbl_simpleblog_posts SET required_tier_code='tier_1';`);
    if(variant==='import')sql(`UPDATE ${database}.tbl_simpleblog_posts SET author_credit='Original author',source_key='legacy:123',source_url='https://example.invalid/original',source_hash='source',import_hash='import';`);
    sql(`USE ${database}; ${statements}`);
    const first=sql(`SELECT * FROM ${database}.tbl_simpleblog_posts;`);
    sql(`USE ${database}; ${statements}`);
    if(first!==sql(`SELECT * FROM ${database}.tbl_simpleblog_posts;`))throw Error('Repeat migration changed data: '+variant);
    if(!first.includes('original content')||(variant==='access'&&!first.includes('tier_1'))||(variant==='import'&&!first.includes('Original author')))throw Error('Original content or restrictions lost: '+variant);
    const columns=sql(`SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='${database}' AND TABLE_NAME='tbl_simpleblog_posts';`).split('\n');
    for(const name of ['required_tier_code','author_credit','source_key','source_url','source_hash','import_hash'])if(!columns.includes(name))throw Error('Missing '+name);
    const indexes=sql(`SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA='${database}' AND TABLE_NAME='tbl_simpleblog_posts' AND INDEX_NAME='simpleblog_source_unique' AND NON_UNIQUE=0;`);
    if(indexes!=='1')throw Error('Source uniqueness missing or duplicated');
    console.log('PASS: '+variant+' branch, forward migration, repeatability and data preservation');
}
