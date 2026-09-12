/* Shared composition media selection; native forms retain keyboard and no-JS operation. */
(function () {
    'use strict';if(window.ChisimbaCompositionReady)return;window.ChisimbaCompositionReady=true;
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-composition-media]');
        if (!button || !window.ChisimbaCompositionPicker) return;
        var url = new URL(window.ChisimbaCompositionPicker, window.location.href);
        url.searchParams.set('target', button.dataset.compositionMedia);
        url.searchParams.set('location','user');
        var block=button.closest('.chisimba-composition-instance');
        url.searchParams.set('policy', block && block.querySelector('[name$="[type]"]').value==='video'?'video':'image');
        window.open(url.href, 'chisimbaFilePicker', 'width=920,height=720,resizable=yes,scrollbars=yes');
    });
    function submitCommand(canvas,value) {
        var button=document.createElement('button');button.type='submit';button.name='compose_command';button.value=value;
        button.formNoValidate=true;button.hidden=true;canvas.closest('form').appendChild(button);button.click();button.remove();
    }
    function recognised(event) {
        return Array.from(event.dataTransfer.types).some(function(type){return type==='text/x-chisimba-block'||type==='text/x-chisimba-new-block';});
    }
    function clearMarkers(){document.querySelectorAll('.chisimba-drop-before,.chisimba-drop-end').forEach(function(item){item.classList.remove('chisimba-drop-before','chisimba-drop-end');});}
    document.addEventListener('dragstart',function(event){
        var handle=event.target.closest('[data-composition-drag]');
        if(handle){event.dataTransfer.setData('text/x-chisimba-block',handle.dataset.compositionDrag);event.dataTransfer.effectAllowed='move';}
    });
    document.addEventListener('dragend',clearMarkers);
    document.querySelectorAll('.chisimba-composition-palette button').forEach(function(button){
        button.draggable=true;button.addEventListener('dragstart',function(event){event.dataTransfer.setData('text/x-chisimba-new-block',button.value.split(':')[1]);event.dataTransfer.effectAllowed='copy';});
    });
    function bindCanvas(canvas){
        function target(event){
            var cards=Array.from(canvas.querySelectorAll('.chisimba-composition-instance'));
            return cards.find(function(card){var rect=card.getBoundingClientRect();return event.clientY<rect.top+rect.height/2;});
        }
        canvas.addEventListener('dragover',function(event){
            if(!recognised(event))return;event.preventDefault();clearMarkers();var card=target(event);
            (card||canvas).classList.add(card?'chisimba-drop-before':'chisimba-drop-end');
        });
        canvas.addEventListener('dragleave',function(event){if(!canvas.contains(event.relatedTarget))clearMarkers();});
        canvas.addEventListener('drop',function(event){
            if(!recognised(event))return;event.preventDefault();clearMarkers();var card=target(event);
            var type=event.dataTransfer.getData('text/x-chisimba-new-block'),id=event.dataTransfer.getData('text/x-chisimba-block');
            if(type)submitCommand(canvas,card?'addbefore:'+type+':'+card.dataset.instance:'add:'+type);
            else if(id&&(!card||card.dataset.instance!==id))submitCommand(canvas,card?'before:'+id+':'+card.dataset.instance:'end:'+id);
        });
        canvas.addEventListener('click',function(event){
            var preview=event.target.closest('[data-composition-preview]');if(!preview)return;
            event.preventDefault();submitCommand(canvas,'focus:'+preview.closest('[data-instance]').dataset.instance);
        });
    }
    document.querySelectorAll('[data-composition-canvas]').forEach(bindCanvas);
    // Hosts opt in; the same authenticated form endpoint and validation handle both paths.
    document.addEventListener('submit', async function (event) {
        var form=event.target, button=event.submitter;
        if(!form.matches('[data-composition-ajax]'))return;
        if(form.dataset.compositionBusy){event.preventDefault();return;}
        if(!button || button.name!=='compose_command' || button.value==='category_add' || !window.fetch || !window.ChisimbaEditorMount)return;
        event.preventDefault();
        var canvas=form.querySelector('[data-composition-canvas]'), status=form.querySelector('[data-composition-status]');
        // TinyMCE has not necessarily received a native submit event yet.
        canvas.querySelectorAll('[data-chisimba-editor]').forEach(function(field){window.ChisimbaEditor.sync(field.id);});
        var data=new FormData(form);data.set('compose_command',button.value);
        var x=window.scrollX,y=window.scrollY, scroller=canvas.parentElement, top=scroller.scrollTop,left=scroller.scrollLeft;
        var failureMessage=form.dataset.compositionFailure;
        var abort=new AbortController(),timer=setTimeout(function(){abort.abort();},30000);
        form.dataset.compositionBusy='1';form.inert=true;form.setAttribute('aria-busy','true');
        status.textContent=form.dataset.compositionPending;
        try {
            var response=await fetch(form.action,{method:'POST',body:data,credentials:'same-origin',signal:abort.signal});
            var page=new DOMParser().parseFromString(await response.text(),'text/html');
            var nextForm=page.querySelector('[data-composition-ajax]'), error=page.querySelector('[data-composition-error]');
            // A consumed CSRF token must be renewed even when validation rejects a command.
            if(nextForm)form.elements.csrf_token.value=nextForm.elements.csrf_token.value;
            if(error)failureMessage=error.textContent;
            if(!response.ok || !nextForm || error)throw new Error(failureMessage);
            var next=nextForm.querySelector('[data-composition-canvas]'),history=nextForm.querySelector('[data-composition-history]');
            if(!next || !history)throw new Error(form.dataset.compositionFailure);
            // Never execute scripts from a fetched document. Native fields carry declarative config.
            next.querySelectorAll('script').forEach(function(script){script.remove();});
            window.ChisimbaEditorUnmount(canvas);
            canvas.replaceWith(next);form.querySelector('[data-composition-history]').replaceWith(history);bindCanvas(next);
            scroller.scrollTop=top;scroller.scrollLeft=left;window.scrollTo(x,y);
            await window.ChisimbaEditorMount(next);
            scroller.scrollTop=top;scroller.scrollLeft=left;window.scrollTo(x,y);
            form.inert=false;
            var active=next.querySelector('.chisimba-composition-instance--editing');
            if(active){
                active.focus({preventScroll:true});
                // Bring only an off-screen destination into view, without going via the page top.
                var bounds=active.getBoundingClientRect(), viewport=scroller.getBoundingClientRect();
                if(bounds.top<Math.max(0,viewport.top) || bounds.top>Math.min(innerHeight,viewport.bottom)-60)active.scrollIntoView({block:'start',behavior:'smooth'});
            }
            status.textContent=form.dataset.compositionUpdated;
        } catch(error) {
            status.textContent=failureMessage;
        } finally {
            clearTimeout(timer);form.inert=false;form.removeAttribute('aria-busy');delete form.dataset.compositionBusy;
        }
    });
    // Restore the editing destination after a server round trip, including nested scroll areas.
    var resume=document.querySelector('[data-composition-resume]');
    if(resume){
        var destination=Array.from(document.querySelectorAll('[data-instance]')).find(function(card){return card.dataset.instance===resume.value;});
        if(destination){
            var userMoved=false;
            ['pointerdown','keydown','wheel','touchstart'].forEach(function(name){window.addEventListener(name,function(){userMoved=true;},{once:true,passive:true});});
            function restore(){if(userMoved)return;destination.focus({preventScroll:true});destination.scrollIntoView({block:'start',behavior:'instant'});}
            if(document.readyState==='complete')requestAnimationFrame(restore);
            else window.addEventListener('load',function(){requestAnimationFrame(restore);},{once:true});
        }
    }
    var previous = window.ChisimbaFilePickerReceive;
    window.ChisimbaFilePickerReceive = function (target, file) {
        var field = document.getElementById(target);
        if (field && target.indexOf('comp_') === 0 && file && file.url) {
            field.value = file.url;
            field.dispatchEvent(new Event('change', {bubbles: true}));
        } else if (previous) previous(target, file);
    };
}());
