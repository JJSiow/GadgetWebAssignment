$(() => {
    let currentIndex = 0;
    let previewImages = [];
    let imageFiles = []; // Store File objects

    $('.existing-image').each(function () {
        previewImages.push({
            element: $(this),
            path: $(this).attr('src'),
            id: $(this).data('id'),
            isExisting: true
        });
    });

    function updatePreview() {
        const $defaultPreview = $('#defaultPreview');
        const $prevBtn = $('#prevPhoto');
        const $nextBtn = $('#nextPhoto');
        const $deleteBtn1 = $('#deletePhoto');
        const $deleteBtn2 = $('#deletePhotos');
        const $addMoreBtn = $('#addMorePhotos');

        if (previewImages.length > 0) {
            const currentImage = previewImages[currentIndex];
            $defaultPreview.attr('src', currentImage.path);

            // Show/hide navigation buttons
            $prevBtn.toggle(previewImages.length > 1);
            $nextBtn.toggle(previewImages.length > 1);

            // Show delete button when there are images
            $deleteBtn1.show();
            $deleteBtn2.show();
            $addMoreBtn.show();

            updateImageCounter();
        } else {
            $defaultPreview.attr('src', '/images/defaultImage.png');
            $prevBtn.hide();
            $nextBtn.hide();
            $deleteBtn1.hide();
            $deleteBtn2.hide();
            $addMoreBtn.hide();
            $('#imageCounter').hide();
        }
    }

    function updateImageCounter() {
        const $counter = $('#imageCounter');
        if ($counter.length === 0) {
            $('.image-preview-container').append(
                '<div id="imageCounter" class="image-counter"></div>'
            );
        }
        $('#imageCounter').text(`${currentIndex + 1} / ${previewImages.length}`).show();
    }

    function updateFileInput() {
        // Create a new DataTransfer object
        const dataTransfer = new DataTransfer();

        // Add all current files to it
        imageFiles.forEach(file => {
            dataTransfer.items.add(file);
        });

        // Set the new FileList to the input
        $('input[name="photos[]"]')[0].files = dataTransfer.files;
    }

    function calculateImageHash(dataUrl) {
        let hash = 0;
        for (let i = 0; i < dataUrl.length; i++) {
            hash = ((hash << 5) - hash) + dataUrl.charCodeAt(i);
            hash = hash & hash;
        }
        return hash;
    }

    function isDuplicateImage(newDataUrl) {
        const newHash = calculateImageHash(newDataUrl);

        // Check for duplicates in previewImages
        return previewImages.some(existing => {
            // Use stored hash if available, otherwise calculate and store it
            if (!existing.hash) {
                existing.hash = calculateImageHash(existing.path);
            }
            return existing.hash === newHash;
        });
    }

    function processFiles(files) {
        const maxFileSize = 2 * 1024 * 1024; // 2MB
        let errors = [];

        const filePromises = Array.from(files).map(file => {
            return new Promise((resolve, reject) => {
                if (!file.type.startsWith('image/')) {
                    reject(`${file.name} is not an image file`);
                    return;
                }
                if (file.size > maxFileSize) {
                    reject(`${file.name} exceeds 2MB size limit`);
                    return;
                }

                const reader = new FileReader();
                reader.onload = (e) => {
                    const dataUrl = e.target.result;
                    if (isDuplicateImage(dataUrl)) {
                        reject(`${file.name} is a duplicate image`);
                        return;
                    }
                    resolve({ file: file, dataUrl: dataUrl });
                };
                reader.onerror = () => reject(`Error reading ${file.name}`);
                reader.readAsDataURL(file);
            });
        });

        Promise.allSettled(filePromises)
            .then(results => {
                let duplicatesFound = false;

                results.forEach(result => {
                    if (result.status === 'fulfilled') {
                        previewImages.push({
                            element: $(this),
                            path: result.value.dataUrl,
                            id: null,
                            isExisting: false
                        });
                        imageFiles.push(result.value.file);
                    } else {
                        if (result.reason.includes('duplicate')) {
                            duplicatesFound = true;
                        }
                        errors.push(result.reason);
                    }
                });

                if (errors.length > 0) {
                    if (duplicatesFound) {
                        alert('Some images were not added because they are duplicates.\n' +
                            errors.join('\n'));
                    } else {
                        alert('Errors occurred:\n' + errors.join('\n'));
                    }
                }
                console.log(previewImages);
                updateFileInput();
                updatePreview();
            });
    }

    // Handle initial file input change
    $('input[name="photos[]"]').on('change', function (event) {
        const files = event.target.files;
        if (files.length > 0) {
            previewImages = [];
            imageFiles = [];
            currentIndex = 0;
            processFiles(files);
        }
    });

    // Handle add more photos
    $('#addMorePhotos').on('click', function () {
        const $newInput = $('<input>', {
            type: 'file',
            accept: 'image/*',
            multiple: true,
            style: 'display: none'
        });

        $newInput.on('change', function (event) {
            if (event.target.files.length > 0) {
                processFiles(event.target.files);
            }
            $newInput.remove();
        });

        $('body').append($newInput);
        $newInput.click();
    });
    
    // Handle delete current photo
    $('#deletePhoto').on('click', function () {
        if (previewImages.length > 0) {
            // Remove current image
            previewImages.splice(currentIndex, 1);
            imageFiles.splice(currentIndex, 1);

            // Adjust current index if necessary
            if (currentIndex >= previewImages.length) {
                currentIndex = Math.max(0, previewImages.length - 1);
            }

            console.log('Preview Images:', previewImages);
            console.log('Image Files:', imageFiles);
            console.log('Current Index:', currentIndex);
            updateFileInput();
            updatePreview();
        }
    });

    let imagesToDelete = []; // Array to store images to be deleted

    // Handle the Delete button click
    $('#deletePhotos').on('click', function () {
        if (previewImages.length > 0) {
            const currentImage = previewImages[currentIndex];
    
            // Handle deletion for existing images
            if (currentImage.isExisting) {
                imagesToDelete.push(currentImage);
            }
    
            // Remove image from preview and file inputs
            previewImages.splice(currentIndex, 1);
    
            if (!currentImage.isExisting) {
                let imageFileIndex = 0;
                for (let i = 0; i < currentIndex; i++) {
                    if (!previewImages[i].isExisting) {
                        imageFileIndex++;
                    }
                }
                imageFiles.splice(imageFileIndex, 1);
            }
    
            // Adjust the current index after deletion
            currentIndex = Math.max(0, Math.min(currentIndex, previewImages.length - 1));
    
            // Debugging logs
            console.log('Preview Images:', previewImages);
            console.log('Image Files:', imageFiles);
            console.log('Current Index:', currentIndex);
    
            // Update file input and preview
            updateFileInput();
            updatePreview();
        }
    });
    
    $('#addBtn').on('click', function () {
        // Perform the delete operation for images in imagesToDelete array
        if (imagesToDelete.length > 0) {
            const imagesToDeleteCopy = [...imagesToDelete]; // Copy to prevent modification during iteration
            imagesToDelete = []; // Clear immediately to avoid duplicates in future requests
    
            imagesToDeleteCopy.forEach(function (image) {
                console.log('Deleting image:', image.path); // Debugging
                $.ajax({
                    url: '../admin/add_gadget.php', // Your PHP script to handle image deletion
                    type: 'POST',
                    data: {
                        filePath: image.path // Send the image path to the server
                    },
                    success: function (response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                console.log('Image deleted successfully:', image.path);
                            } else {
                                console.error('Failed to delete image:', result.error);
                            }
                        } catch (e) {
                            console.error('Invalid JSON response:', response);
                        }
                    },
                    error: function () {
                        alert('Error connecting to server');
                    }
                });
            });
        }
    });

    // Handle the Update button click
    $('#updateBtn').on('click', function () {
        // Perform the delete operation for the images in imagesToDelete array
        if (imagesToDelete.length > 0) {
            imagesToDelete.forEach(function (image) {
                $.ajax({
                    url: "../admin/update_gadget.php",
                    type: 'POST',
                    data: {
                        action: 'delete_image',
                        gallery_id: image.id,
                        photo_path: image.path.split('/').pop()
                    },
                    success: function (response) {
                        console.log('Image deleted successfully:', image.path);
                    },
                    error: function () {
                        alert('Error connecting to server');
                    }
                });
            });
        }
    });

    // Navigation handlers
    $('#nextPhoto').on('click', function () {
        if (previewImages.length > 1) {
            const $preview = $('#defaultPreview');
            $preview.fadeOut(150, function () {
                currentIndex = (currentIndex + 1) % previewImages.length;
                updatePreview();
                $preview.fadeIn(150);
            });
        }
    });

    $('#prevPhoto').on('click', function () {
        if (previewImages.length > 1) {
            const $preview = $('#defaultPreview');
            $preview.fadeOut(150, function () {
                currentIndex = (currentIndex - 1 + previewImages.length) % previewImages.length;
                updatePreview();
                $preview.fadeIn(150);
            });
        }
    });

    // Reset functionality
    $('#resetModalBtn').on('click', function () {
        const $preview = $('#defaultPreview');
        $preview.fadeOut(150, function () {
            $('input[name="photos[]"]').val('');
            previewImages = [];
            imageFiles = [];
            currentIndex = 0;
            updatePreview();
            $preview.fadeIn(150);
        });
    });

    // Keyboard navigation
    $(document).on('keydown', function (e) {
        if (previewImages.length > 1) {
            if (e.key === 'ArrowLeft') {
                $('#prevPhoto').click();
            } else if (e.key === 'ArrowRight') {
                $('#nextPhoto').click();
            } else if (e.key === 'Delete') {
                $('#deletePhoto').click();
            }
        }
    });

    // Initialize preview
    updatePreview();
});