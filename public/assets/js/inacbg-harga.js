(function () {
    'use strict';

    function groupDigits(value) {
        return value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function formatAmount(value) {
        if (value === '') return '';
        var parts = value.split('.');
        return groupDigits(parts[0]) + (parts[1] && /[1-9]/.test(parts[1]) ? ',' + parts[1].padEnd(2, '0') : '');
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('input[data-rupiah]').forEach(function (input) {
            if (input.dataset.rupiahReady) return;
            var amount = input.form.querySelector('input[type="hidden"][name="harga"]');
            if (!amount) return;
            input.dataset.rupiahReady = 'true';
            var lastValue = input.value.replace(/[^0-9.,]/g, '');

            function syncAmount(keepCaret) {
                if (/[^0-9.,]/.test(input.value)) {
                    var caret = input.value.slice(0, input.selectionStart).replace(/[^0-9.,]/g, '').length;
                    input.value = lastValue;
                    if (keepCaret) input.setSelectionRange(Math.min(caret, lastValue.length), Math.min(caret, lastValue.length));
                    return;
                }
                lastValue = input.value;
                var value = input.value.replace(/\./g, '');
                if (!/^\d*(?:,\d{0,2})?$/.test(value)) {
                    input.setCustomValidity('Gunakan angka dan koma untuk maksimal dua angka desimal.');
                    return;
                }

                input.setCustomValidity('');
                if (value === '') {
                    amount.value = '';
                    return;
                }

                var beforeCaret = keepCaret ? input.value.slice(0, input.selectionStart).replace(/\./g, '').length : 0;
                var parts = value.split(',');
                var whole = (parts[0] || '0').replace(/^0+(?=\d)/, '');
                if (whole.length > 13) {
                    input.setCustomValidity('Harga terlalu besar.');
                    return;
                }
                amount.value = whole + (parts[1] ? '.' + parts[1] : '');
                input.value = groupDigits(whole) + (parts.length > 1 ? ',' + parts[1] : '');
                lastValue = input.value;

                if (!keepCaret) return;
                var position = 0;
                var count = 0;
                while (position < input.value.length && count < beforeCaret) {
                    if (input.value[position] !== '.') count++;
                    position++;
                }
                input.setSelectionRange(position, position);
            }

            syncAmount(false);
            input.addEventListener('beforeinput', function (event) {
                if (event.data && /[^0-9.,]/.test(event.data)) event.preventDefault();
            });
            input.addEventListener('input', function () {
                syncAmount(true);
            });
            input.addEventListener('change', function () {
                syncAmount(false);
            });

            input.addEventListener('blur', function () {
                syncAmount(false);
                if (input.validity.valid) input.value = formatAmount(amount.value);
            });
        });
    });
})();
