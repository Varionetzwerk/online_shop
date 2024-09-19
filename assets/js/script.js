function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}

document.addEventListener('DOMContentLoaded', () => {
    const userId = getCookie('user_id');

    if (!userId) {
        console.error('Benutzer-ID fehlt.');
        return;
    }

    const addToCartButtons = document.querySelectorAll('.add-to-cart');

    addToCartButtons.forEach(button => {
        button.addEventListener('click', () => {
            const productId = button.getAttribute('data-product-id');

            fetch('products/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `product_id=${encodeURIComponent(productId)}&user_id=${encodeURIComponent(userId)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.cart_count) {
                    document.querySelector('.cart-count').textContent = data.cart_count;
                } else {
                    console.error(data.error);
                }
            })
            .catch(error => console.error('Fehler:', error));
        });
    });
});
