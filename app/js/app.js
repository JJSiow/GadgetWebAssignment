// ============================================================================
// General Functions
$(() => {
    // When the close element is clicked, hide the modal
    $('.close').on("click", function () {
        $(".form-container").fadeOut().css("display", "none");

        window.location.href = "admin_products.php";
    });

    // When the user clicks outside the modal, hide it
    $(window).on("click", function (event) {
        if ($(event.target).is($(".form-container"))) {
            $(".form-container").fadeOut().css("display", "none");

            window.location.href = "admin_products.php";
        }
    });

    // Photo preview
    $('label.upload input[type=file]').on('change', e => {
        const f = e.target.files[0];
        const img = $(e.target).siblings('img')[0];

        if (!img) return;

        img.dataset.src ??= img.src;

        if (f?.type.startsWith('image/')) {
            img.src = URL.createObjectURL(f);
        }
        else {
            img.src = img.dataset.src;
            e.target.value = '';
        }
    });

    // Reset Modal form
    $('#resetModalBtn').on('click', function (e) {
        e.preventDefault();

        $('#form')[0].reset();
        $('#gname').val('');
        $('#gcategory').val('');
        $('#gbrand').val('');
        $('#gdescribe').val('');
        $('#gprice').val('0.00');
        $('#gstock').val('0');

        $('#input_file').val('');

        $('.err').remove();
        $('input, select, textarea').removeClass('error');
    });

    var changes = [];
    var updatedText;
    
    $('.edit').on('dblclick', function (e) {
        var $editElement = $(this);
        var currentText = $editElement.text();
        var currentId = $editElement.data('id');
        var updateUrl = $editElement.data('update-url')
    
        var inputContainer = $('<div>', {
            class: 'input-container'
        });
    
        var inputField = $('<input>', {
            type: 'text',
            value: currentText,
            class: 'edit-input'
        });
    
        var saveButton = $('<button>', {
            text: 'Save',
            class: 'save-btn',
            click: function(event) {
                event.stopPropagation(); // Prevent double-click from triggering
                updatedText = inputField.val().toUpperCase(); 
    
                // Only add to changes if text actually changed
                if (updatedText != currentText.toUpperCase()) {
                    changes.push({
                        id: currentId,
                        name: updatedText
                    });
    
                    $.ajax({
                        url: updateUrl,
                        method: 'POST',
                        data: {
                            updates: JSON.stringify(changes)
                        },
                        success: function (response) {
                            console.log('Server response:', response);
                            $editElement.text(updatedText);
                            changes = [];
                            inputContainer.replaceWith(inputContainer.text(updatedText));
                        },
                        error: function (xhr, status, error) {
                            console.error('Error updating category:', error);
                            alert('Error updating category');
                            inputContainer.replaceWith(inputContainer.text(currentText));
                        }
                    });
                } else {
                    inputContainer.replaceWith(inputContainer.text(currentText));
                }
            }
        });
    
        var cancelButton = $('<button>', {
            text: 'Cancel',
            class: 'cancel-btn',
            click: function(event) {
                event.stopPropagation(); 
                inputContainer.replaceWith(inputContainer.text(currentText));
            }
        });
    
        inputContainer.append(inputField, saveButton, cancelButton);
        $editElement.html(inputContainer);
        inputField.focus();
    
        inputField.on('keydown', function(event) {
            if (event.key === 'Enter') {
                saveButton.click();
            } else if (event.key === 'Escape') {
                cancelButton.click();
            }
        });
    });

});

// ============================================================================
// Page Load (jQuery)
// ============================================================================

$(() => {
    // Autofocus
    $('form :input:not(button):first').focus();
    $('.err:first').prev().focus();
    $('.err:first').prev().find(':input:first').focus();

    // Confirmation message
    $('[data-confirm]').on('click', e => {
        const text = e.target.dataset.confirm || 'Are you sure?';
        if (!confirm(text)) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    });

    // Initiate GET request
    $('[data-get]').on('click', e => {
        e.preventDefault();
        const url = e.target.dataset.get;
        location = url || location;
    });

    // Initiate POST request
    $('[data-post]').on('click', e => {
        e.preventDefault();
        const url = e.target.dataset.post;
        const f = $('<form>').appendTo(document.body)[0];
        f.method = 'POST';
        f.action = url || location;
        f.submit();
    });

    // Auto uppercase
    $('[data-upper]').on('input', e => {
        const a = e.target.selectionStart;
        const b = e.target.selectionEnd;
        e.target.value = e.target.value.toUpperCase();
        e.target.setSelectionRange(a, b);
    });
});