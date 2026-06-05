<script>
    (function () {
        function parseDateRange(value) {
            if (! value || ! window.moment) {
                return null;
            }

            var parts = String(value).split(/\s+(?:to|-)\s+/).filter(Boolean);
            if (! parts.length) {
                return null;
            }

            var start = moment(parts[0], 'YYYY-MM-DD', true);
            var end = moment(parts[1] || parts[0], 'YYYY-MM-DD', true);

            if (! start.isValid() || ! end.isValid()) {
                return null;
            }

            return { start: start, end: end };
        }

        function pickerRanges() {
            return {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            };
        }

        function setPickerValue($picker, start, end, shouldSubmit) {
            var inputSelector = $picker.data('input');
            var $input = inputSelector ? $(inputSelector) : $();
            var label = start.format('D MMM YY') + ' - ' + end.format('D MMM YY');

            $picker.find('.reportrange-picker-field').text(label);
            $input.val(start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));

            if (shouldSubmit && $picker.data('submit-on-apply') === true) {
                $picker.closest('form').trigger('submit');
            }
        }

        function clearPickerValue($picker) {
            var inputSelector = $picker.data('input');
            var $input = inputSelector ? $(inputSelector) : $();

            $picker.find('.reportrange-picker-field').text('Select date range');
            $input.val('');

            if ($picker.data('submit-on-apply') === true) {
                $picker.closest('form').trigger('submit');
            }
        }

        window.uhmsInitDateRangeFilters = function (root) {
            if (! window.jQuery || ! $.fn.daterangepicker || ! window.moment) {
                return;
            }

            $(root || document).find('.uhms-date-range-filter').each(function () {
                var $picker = $(this);
                if ($picker.data('uhms-date-range-ready')) {
                    return;
                }

                var inputSelector = $picker.data('input');
                var value = inputSelector ? $(inputSelector).val() : '';
                var parsed = parseDateRange(value);
                var start = parsed ? parsed.start : moment();
                var end = parsed ? parsed.end : moment();

                $picker.data('uhms-date-range-ready', true);
                if (parsed) {
                    setPickerValue($picker, start, end, false);
                }

                $picker.daterangepicker({
                    startDate: start,
                    endDate: end,
                    autoUpdateInput: false,
                    opens: 'left',
                    ranges: pickerRanges(),
                    locale: {
                        cancelLabel: 'Clear'
                    }
                });

                $picker.on('apply.daterangepicker', function (_event, picker) {
                    setPickerValue($picker, picker.startDate, picker.endDate, true);
                });

                $picker.on('cancel.daterangepicker', function () {
                    clearPickerValue($picker);
                });

                $picker.on('keydown', function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        $picker.trigger('click');
                    }
                });
            });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () {
                window.uhmsInitDateRangeFilters();
            });
        } else {
            window.uhmsInitDateRangeFilters();
        }
    }());
</script>
