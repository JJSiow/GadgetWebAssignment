// ============================================================================
// General Functions
// ============================================================================



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

    // Reset form
    $('[type=reset]').on('click', e => {
        e.preventDefault();
        location = location;
    });

    // Auto uppercase
    $('[data-upper]').on('input', e => {
        const a = e.target.selectionStart;
        const b = e.target.selectionEnd;
        e.target.value = e.target.value.toUpperCase();
        e.target.setSelectionRange(a, b);
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

    // Photo preview (drag & drop)
    $(document).ready(function () {
        $('.drop-zone').each(function () {
            var $dropZone = $(this);
            var $fileInput = $dropZone.find('input[type="file"]');
            var $preview = $dropZone.find('.preview');
    
            $dropZone.on('click', function () {
                $fileInput.click();
            });
    
            $dropZone.on('dragover', function (e) {
                e.preventDefault();
                $dropZone.addClass('dragover');
            });
    
            $dropZone.on('dragleave', function () {
                $dropZone.removeClass('dragover');
            });
    
            $dropZone.on('drop', function (e) {
                e.preventDefault();
                $dropZone.removeClass('dragover');
    
                var files = e.originalEvent.dataTransfer.files;
                if (files.length > 0 && isImageFile(files[0])) {
                    $fileInput[0].files = files;
                    updatePreview(files[0], $preview);
                } else {
                    alert('Please upload an image file.');
                }
            });
    
            $fileInput.on('change', function () {
                if (this.files.length > 0 && isImageFile(this.files[0])) {
                    updatePreview(this.files[0], $preview);
                } else {
                    alert('Please upload an image file.');
                    $fileInput.val(''); // Clear the input if the file is not an image
                }
            });
        });
    
        function updatePreview(file, $preview) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $preview.attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        }
    
        function isImageFile(file) {
            return file.type.startsWith('image/');
        }
    });

});