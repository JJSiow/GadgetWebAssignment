$(() => {
    let currentIndex = 0; // Start with the first image
    const photos = $('.gadget-photo'); // All images

    function showPhoto(index) {
        photos.removeClass('active').addClass('hidden'); // Hide all photos
        $(photos[index]).addClass('active').removeClass('hidden'); // Show the active photo
        updateImageCounter2(); // Update the counter
    }

    function updateImageCounter2() {
        // Select or create the counter element
        let $counter = $('#imageCounter');
        if ($counter.length === 0) {
            $('.gallery-photos-container').append(
                '<div id="imageCounter" class="image-counter"></div>'
            );
            $counter = $('#imageCounter');
        }

        // Update the text with the current image index and total images
        $counter.text(`${currentIndex + 1} of ${photos.length}`).show();
    }

    $('.next-btn').click(function () {
        currentIndex = (currentIndex + 1) % photos.length; // Increment index, loop back if at the end
        showPhoto(currentIndex);
    });

    $('.prev-btn').click(function () {
        currentIndex = (currentIndex - 1 + photos.length) % photos.length; // Decrement index, loop back if at the start
        showPhoto(currentIndex);
    });

});