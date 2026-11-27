<?php
$names=['Themeda triandra','Themeda','T. triandra','Panicum maximum var. trichoglume'];
$format=fn($s)=>grassguiderenderer::formatBotanical($s,$names);
ggCheck($format('Themeda triandra Forssk. and T. triandra.')==='<i>Themeda triandra</i> Forssk. and <i>T. triandra</i>.','Names italic, author upright');
ggCheck($format('*Unknown species* near Themeda triandra')==='<i>Unknown species</i> near <i>Themeda triandra</i>','Explicit italics support names outside the catalogue');
ggCheck($format('Panicum maximum var. trichoglume')==='<i>Panicum maximum </i>var. <i>trichoglume</i>','Botanical rank stays upright');
ggCheck($format('*<img src=x onerror=alert(1)>*')==='<i>&lt;img src=x onerror=alert(1)&gt;</i>','Editorial markup cannot inject HTML');
ggCheck($format('NotThemeda and Themeda-like')==='NotThemeda and Themeda-like','Only whole scientific names match');
echo "PASS botanical typography, explicit italics, upright ranks/authors and escaped HTML.\n";
