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
    $('label.upload').find('input[type=file]').on('change', e => {
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

    $('.edit, .edit2').on('dblclick', function (e) {
        var $editElement = $(this);
        // Prevent re-initialization if already in edit mode
        if ($editElement.find('.input-container').length > 0) {
            return; // Exit if already editing
        }

        var currentId = $editElement.data('id');
        var updateUrl = $editElement.data('update-url');
        var isEditableText = $editElement.hasClass('edit');
        var isEditableNumber = $editElement.hasClass('edit2');
        var currentText = $editElement.text().trim();

        console.log(currentText);
        if (isEditableNumber) {
            currentText = currentText.startsWith('RM ')
                ? parseFloat(currentText.replace('RM ', '').replace(/,/g, '').trim())
                : parseFloat(currentText.replace(/,/g, '').trim());

            console.log(currentText);
            if (isNaN(currentText)) currentText = 0;
        }

        var inputContainer = $('<div>', {
            class: 'input-container'
        });

        if (isEditableNumber) {
            var rmLabel = $('<span>', {
                text: 'RM ',
                class: 'rm-label'
            });
            inputContainer.append(rmLabel); // Add RM before the input field
        }

        var inputField = $('<input>', {
            type: isEditableNumber ? 'number' : 'text',
            value: isEditableNumber ? currentText.toFixed(2) : currentText,
            class: isEditableNumber ? 'edit2-input' : 'edit-input',
            step: isEditableNumber ? '0.01' : null,
            min: isEditableNumber ? '0.01' : null,
            max: isEditableNumber ? '10000.00' : null
        });

        var saveButton = $('<button>', {
            text: 'Save',
            class: 'save-btn',
            click: function (event) {
                event.stopPropagation(); // Prevent double-click from triggering
                updatedText = inputField.val();
                console.log(updatedText);

                if (isEditableNumber) {
                    updatedText = parseFloat(updatedText).toFixed(2);
                    if (isNaN(updatedText)) {
                        alert('Invalid number');
                        return;
                    }
                    if (updatedText < 0) {
                        alert('The value cannot be negative. Please enter a positive number.');
                        return;
                    }
                } else if (isEditableText) {
                    updatedText = updatedText.trim().toUpperCase(); // Convert to uppercase for text input
                }

                // Only add to changes if text actually changed
                if (updatedText != currentText.toString()) {
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
                            if (isEditableNumber) {
                                const formattedText = `RM ${parseFloat(updatedText).toLocaleString(undefined, {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                })}`;
                                $editElement.text(formattedText);
                                inputContainer.replaceWith(formattedText);
                            } else {
                                $editElement.text(updatedText);
                                inputContainer.replaceWith(updatedText);
                            }
                            changes = [];
                        },
                        error: function (xhr, status, error) {
                            console.error('Error updating:', error);
                            alert('Error updating');
                            if (isEditableNumber) {
                                const formattedText = `RM ${parseFloat(currentText).toLocaleString(undefined, {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                })}`;
                                inputContainer.replaceWith(formattedText);
                            } else {
                                inputContainer.replaceWith(currentText);
                            }
                        }
                    });
                } else {
                    if (isEditableNumber) {
                        const formattedText = `RM ${parseFloat(currentText).toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        })}`;
                        inputContainer.replaceWith(formattedText);
                    } else {
                        inputContainer.replaceWith(currentText);
                    }
                }
            }
        });

        var cancelButton = $('<button>', {
            text: 'Cancel',
            class: 'cancel-btn',
            click: function (event) {
                event.stopPropagation();
                if (isEditableNumber) {
                    const formattedText = `RM ${parseFloat(currentText).toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })}`;
                    inputContainer.replaceWith(formattedText);
                } else {
                    inputContainer.replaceWith(currentText);
                }
            }
        });

        inputContainer.append(inputField, saveButton, cancelButton);
        $editElement.html(inputContainer);
        inputField.focus();

        inputField.on('keydown', function (event) {
            if (event.key === 'Enter') {
                saveButton.click();
            } else if (event.key === 'Escape') {
                cancelButton.click();
            }
        });
    });

    // $('.add-stock-btn').on('click', function (e) {
    //     window.location.href = '../admin/adminHome.php';
    //     localStorage.setItem('scrollToBottom', true);
    // });

    // $(document).ready(function () {
    //     if (localStorage.getItem('scrollToBottom')) {
    //         $('html, body').animate({ scrollTop: $(document).height() }, 'slow');
    //         localStorage.removeItem('scrollToBottom');
    //     }
    // });

    function initializeSlideshows() {
        $('.slideshow-container').each(function () {
            const $slideshow = $(this);
            const $images = $slideshow.find('.gadget-image');

            if ($images.length <= 1) return; // Skip if only one or no image

            let currentIndex = 0;

            setInterval(function () {
                $images.eq(currentIndex).removeClass('active');
                currentIndex = (currentIndex + 1) % $images.length;
                $images.eq(currentIndex).addClass('active');
            }, 3500);
        });
    }

    initializeSlideshows();

    $('.dismiss-alert').click(function() {
        $('#stock-alert').fadeOut('fast');
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


