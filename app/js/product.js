// ============================================================================
// General Functions
// ============================================================================

function updateRowTotal(row) {
    const price = parseFloat(row.querySelector(".item-price").dataset.price);
    const quantity = parseInt(row.querySelector(".item-quantity").value);
    const totalElement = row.querySelector(".item-total");
    totalElement.textContent = "RM " + (price * quantity).toFixed(2);
}

function updateTotalPrice() {
    let totalPrice = 0;
    const rows = document.querySelectorAll(".cart-row");
    rows.forEach(row => {
        const checkbox = row.querySelector(".item-select");
        if (checkbox.checked) {
            const quantity = parseInt(row.querySelector(".item-quantity").value);
            const price = parseFloat(row.querySelector(".item-price").dataset.price);
            totalPrice += price * quantity;
        }
    });
    document.getElementById("total-price").textContent = "RM " + totalPrice.toFixed(2);
}

function submitForm() {
    document.getElementById("filterForm").submit();
}

async function updateQuantity(cartId, quantity) {
    const formData = new FormData();
    formData.append("update_quantity", true);
    formData.append("cart_id", cartId);
    formData.append("quantity", quantity);

    const response = await fetch("order_cart.php", {
        method: "POST",
        body: formData,
    });

    if (!response.ok) {
        alert("Error updating quantity.");
    }
}

// ============================================================================
// Page Load (jQuery)
// ============================================================================

$(() => {
    // Autofocus
    $('form :input:not(button):first').focus();
    $('.err:first').prev().focus();
    $('.err:first').prev().find(':input:first').focus();

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

    // Specific to order_cart.php
    const rows = document.querySelectorAll(".cart-row");
    rows.forEach(row => {
        const quantityInput = row.querySelector(".item-quantity");
        const checkbox = row.querySelector(".item-select");

        quantityInput.addEventListener("input", () => {
            const cartId = quantityInput.dataset.cartId;
            const newQuantity = parseInt(quantityInput.value);

            updateRowTotal(row);
            updateTotalPrice();
            updateQuantity(cartId, newQuantity);
        });

        checkbox.addEventListener("change", () => {
            updateTotalPrice();
        });
    });

const selectAllCheckbox = document.getElementById("select-all");
const itemCheckboxes = document.querySelectorAll(".item-select");

// Function to update "Select All" checkbox status
const updateSelectAllStatus = () => {
    const allChecked = [...itemCheckboxes].every(checkbox => checkbox.checked);
    const someChecked = [...itemCheckboxes].some(checkbox => checkbox.checked);

    selectAllCheckbox.checked = allChecked;
    selectAllCheckbox.indeterminate = !allChecked && someChecked;
};

// Event listener for "Select All" checkbox
selectAllCheckbox.addEventListener("change", () => {
    const isChecked = selectAllCheckbox.checked;
    itemCheckboxes.forEach(checkbox => {
        checkbox.checked = isChecked;
    });
    updateTotalPrice();
});



// Initialize "Select All" checkbox status on page load
updateSelectAllStatus();

    document.querySelector("form").addEventListener("submit", (e) => {
        const checkedItems = document.querySelectorAll(".item-select:checked");
        
    });

    updateTotalPrice();
});

// ============================================================================
// Apply Voucher Logic
// ============================================================================

$(document).ready(function () {
    $('#apply-voucher-btn').click(function () {
        const voucherId = $('#voucher_id').val();
        const totalPrice = parseFloat($('#total-price').text().replace('RM ', '').replace(',', ''));

        if (!voucherId.trim()) {
            alert('Please enter a voucher code.');
            return;
        }

        $.ajax({
            url: 'apply_voucher.php',
            method: 'POST',
            data: { voucher_id: voucherId, total_price: totalPrice },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    $('#final-price').text(response.final_price);
                    $('#hidden-final-price').val(response.final_price.replace(',', ''));
                    $('#voucher-message').text(response.message).css('color', 'green');
                } else {
                    $('#voucher-message').text(response.message).css('color', 'red');
                }
            },
            error: function () {
                alert('Failed to apply the voucher. Please try again.');
            }
        });
    });
});

// ============================================================================
// Cancel Order
// ============================================================================

$(document).on('click', '.cancel-order', function() {
    const orderId = $(this).data('order-id');
    if (confirm('Are you sure you want to cancel this order?')) {
        $.post('update_order_status.php', { order_id: orderId, status: 'CANCELLED' }, function(response) {
            if (response.status === 'success') {
                $('#status-' + orderId).text('CANCELLED');
                $(`button[data-order-id="${orderId}"]`).remove(); // Remove the Cancel button
                alert(response.message); // Display the return payment message
            } else {
                alert(response.message || 'Failed to cancel the order.');
            }
        }, 'json'); 
    }
});



$(document).on('click', '.mark-received', function() {
    const orderId = $(this).data('order-id');
    $.post('update_order_status.php', { order_id: orderId, status: 'RECEIVED' }, function(response) {
        if (response.status === 'success') {
            $('#status-' + orderId).text('RECEIVED');
            alert('Order has been marked as received.');
        } else {
            alert(response.message || 'Failed to update the order status.');
        }
    }, 'json');
});

