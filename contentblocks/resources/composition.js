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
        button.formNoValidate=true;button.hidden=true;canvas.closest('form').appendChild(button);button.click();
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
    document.querySelectorAll('[data-composition-canvas]').forEach(function(canvas){
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
    });
    var previous = window.ChisimbaFilePickerReceive;
    window.ChisimbaFilePickerReceive = function (target, file) {
        var field = document.getElementById(target);
        if (field && target.indexOf('comp_') === 0 && file && file.url) {
            field.value = file.url;
            field.dispatchEvent(new Event('change', {bubbles: true}));
        } else if (previous) previous(target, file);
    };
}());
