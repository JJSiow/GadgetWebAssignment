document.addEventListener("DOMContentLoaded", () => {
    const updateCartCount = () => {
        fetch('/path/to/get_cart_count.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('cart-count').textContent = data.total_items;
                } else {
                    console.error('Failed to fetch cart count:', data.message);
                }
            })
            .catch(error => console.error('Error fetching cart count:', error));
    };

    // Initial cart count update on page load
    updateCartCount();

    // Example: Update cart count after adding an item
    document.querySelectorAll('.add-to-cart-button').forEach(button => {
        button.addEventListener('click', () => {
            // Simulate a delay for adding to the cart
            setTimeout(updateCartCount, 500);
        });
    });
});
