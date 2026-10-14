/* Presentation-only category filters. No event data is fetched or saved. */
(function(){
    'use strict';
    function start(){
        var root=document.getElementById('widecalendar');if(!root)return;
        [['userbox','event_user'],['contextbox','event_context'],['otherbox','event_othercontext'],['sitebox','event_site']].forEach(function(pair){
            var checkbox=root.querySelector('#'+pair[0]);if(!checkbox)return;
            function apply(){root.querySelectorAll('.'+pair[1]).forEach(function(event){event.hidden=!checkbox.checked;});}
            checkbox.addEventListener('change',apply);apply();
        });
    }
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start);else start();
}());
