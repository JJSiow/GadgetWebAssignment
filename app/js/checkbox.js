$(() => {
    $(document).ready(function () {
        // Handle "Check All" functionality
        $('#check-all').on('change', function () {
            $('.checkbox').prop('checked', $(this).is(':checked'));
            toggleMarkAllButton();
        });

        // Handle individual checkbox changes
        $('.checkbox').on('change', function () {
            toggleMarkAllButton();
        });

        $('#submit-mark-all').on('click', function (e) {
            e.preventDefault();
            let selectedGadgetIDs = [];
        
            const selectedOrders = $('.checkbox:checked').map(function () {
                const $row = $(this).closest('tr');
                const gadgetIDs = [];
                
                const firstGadgetID = $row.find('input[name="gadgetID"]').val();
                if (firstGadgetID) {
                    gadgetIDs.push(firstGadgetID);
                }
                
                let nextRow = $row.next();
                while (nextRow.length && !nextRow.find('.checkbox').length) {
                    const nextGadgetID = nextRow.find('input[name="gadgetID"]').val();
                    if (nextGadgetID) {
                        gadgetIDs.push(nextGadgetID);
                    }
                    nextRow = nextRow.next();
                }
                
                selectedGadgetIDs = selectedGadgetIDs.concat(gadgetIDs);
                
                return $(this).val();
            }).get();
        
            console.log('Selected Orders:', selectedOrders);
            console.log('Selected Gadget IDs:', selectedGadgetIDs);
        
            if (selectedOrders.length === 0) {
                alert('No order selected.');
                return;
            }
        
            if (confirm('Are you sure you want to mark all selected orders as delivered?')) {
                // Submit the form with selected order IDs
                $('<input>').attr({
                    type: 'hidden',
                    name: 'checkboxName',
                    value: selectedOrders.join(',')
                }).appendTo('#mark-all-form');
        
                $('<input>').attr({
                    type: 'hidden',
                    name: 'gadgetID',
                    value: selectedGadgetIDs.join(',')
                }).appendTo('#mark-all-form');
        
                $('#mark-all-form').submit();
            }
        });

        $('#submit-mark-unactive, #submit-mark-active, #next_unactive, #next_active').on('click', function (e) {
            e.preventDefault();

            let selected = [];

            if ($(this).is('#submit-mark-unactive, #submit-mark-active')) {
                selected = $('.checkbox:checked').map(function () {
                    return $(this).val();
                }).get();

                if (selected.length === 0) {
                    alert('No item selected.');
                    return;
                }
            }
            else {
                const specficId = $(this).closest('form').find('input[name="checkboxName"]').val();

                if (specficId) {
                    selected.push(specficId);
                }
            }

            // Get the action from the button's data attribute
            const action = $(this).attr('id').includes('unactive') ? 'Unactive' : 'Active';

            // Clear any existing hidden inputs
            $('#mark-all-form').find('input[type="hidden"]').remove();

            // Add the necessary hidden inputs
            $('#mark-all-form').append(`
                    <input type="hidden" name="checkboxName" value="${selected.join(',')}" />
                    <input type="hidden" name="action" value="${action}" />
                `);

            // Submit the form
            $('#mark-all-form').submit();

        });

        // Toggle visibility of "Mark All Delivered" button
        function toggleMarkAllButton() {
            const isChecked = $('.checkbox:checked').length > 0;
            $('#submit-mark-all').toggle(isChecked);
            
            let statusColumnIndex = null;
            $('th').each(function (index) {
                if ($(this).data('status-column') === true) {
                    statusColumnIndex = index;
                    return false; 
                }
            });

            if (statusColumnIndex === null) {
                console.warn('Status column not found!');
                return;
            }

            const hasActive = $('.checkbox:checked').toArray().some(function (checkbox) {
                return $(checkbox).closest('tr').find('td').eq(statusColumnIndex).text().trim() === 'Active';
            });

            const hasUnactive = $('.checkbox:checked').toArray().some(function (checkbox) {
                return $(checkbox).closest('tr').find('td').eq(statusColumnIndex).text().trim() === 'Unactive';
            });

            if (isChecked && hasActive && hasUnactive) {
                $('#submit-mark-active').show();
                $('#submit-mark-unactive').show();
            } else {
                $('#submit-mark-active').toggle(isChecked && !hasActive);
                $('#submit-mark-unactive').toggle(isChecked && !hasUnactive);
            }
        }

        // Initial check
        toggleMarkAllButton();
    });
});