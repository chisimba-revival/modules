/* Uses the shared File Manager picker; server checks remain authoritative. */
(function () {
    'use strict';
    var pickerWindow;
    var previous = window.ChisimbaFilePickerReceive;
    window.ChisimbaFilePickerReceive = function (target, file) {
        if (target === 'wg-file-id' && file && typeof file.id === 'string') {
            document.getElementById('wg-file-id').value = file.id;
            document.getElementById('wg-file-name').value = file.filename || file.name || '';
            document.getElementById('wg-file-name').focus();
            return;
        }
        if (typeof previous === 'function') previous(target, file);
    };
    document.querySelectorAll('[data-workgroup-picker]').forEach(function (button) {
        button.addEventListener('click', function () {
            pickerWindow = window.open(button.dataset.workgroupPicker, 'chisimbaWorkgroupFilePicker');
            if (pickerWindow) pickerWindow.focus();
        });
    });
    document.querySelectorAll('[data-workgroup-clear]').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('wg-file-id').value = '';
            document.getElementById('wg-file-name').value = '';
        });
    });
}());
