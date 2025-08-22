<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart</title>
    <script src="./js/script.js"></script>
</head>

<body>
    <div class="container" id="cart-container"></div>
    <div>
        <div>
            <span>Subtotal</span>
            <span id="subtotal"></span>
        </div>
        <div class="flex justify-between items-center">
            <span>Discount</span>
            <span id="discount_amount"></span>
        </div>
        <div>
            <span id="service-charge-label">Service Charge</span>
            <span id="service-charge"></span>
        </div>
        <div>
            <span>Total</span>
            <span id="total"></span>
        </div>
    </div>
    <div>
        <button id="btn-back-to-menu">Back to Menu</button>
        <button id="btn-next">Next</button>
    </div>

    <script id="cart-template" type="text/x-handlebars-template">
        {{#if this.length}}
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Qty</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    {{#each this}}
                        <tr data-id="{{id}}">
                            <td>{{extended_description}}</td>
                            <td>
                                <button class="decrease">-</button>
                                <span class="quantity">{{quantity}}</span>
                                <button class="increase">+</button>
                            </td>
                            <td class="line-total">{{currency total}}</td>
                        </tr>
                    {{/each}}
                </tbody>
            </table>
        {{else}}
            <p>Your cart is empty.</p>
        {{/if}}
    </script>
    <script src="./js/handlebars.min.js"></script>

    <script>
        redirectToIndexIfNoReferenceNumber();

        Handlebars.registerHelper("currency", function(value) {
            return formatCurrency(value || 0);
        });

        let cart = getCart();
        const btnBackToMenu = document.getElementById("btn-back-to-menu");
        const btnNext = document.getElementById("btn-next");

        btnBackToMenu.addEventListener("click", () => window.location.href = "categories.php")

        btnNext.addEventListener("click", async () => {
            const hasPrivilege = confirm("Do you have a privilege card?");
            window.location.href = hasPrivilege ? "discount.php" : "payment.php";
        })

        showTotals();
        renderCart();

        function renderCart() {
            const container = document.getElementById("cart-container");
            const source = document.getElementById("cart-template").innerHTML;
            const template = Handlebars.compile(source);
            const html = template(cart);
            container.innerHTML = html;

            bindQuantityEvents();
        }

        function bindQuantityEvents() {
            document.querySelectorAll(".increase").forEach(btn => {
                btn.addEventListener("click", function() {
                    const row = this.closest("tr");
                    const id = parseInt(row.dataset.id);
                    updateQuantity(id, 1);
                });
            });

            document.querySelectorAll(".decrease").forEach(btn => {
                btn.addEventListener("click", function() {
                    const row = this.closest("tr");
                    const id = parseInt(row.dataset.id);
                    updateQuantity(id, -1);
                });
            });
        }

        async function updateQuantity(id, change) {
            cart = cart.map(item => {
                if (item.id === id) {
                    let newQty = item.quantity + change;

                    if (newQty <= 0) {
                        const confirmRemove = confirm("The item will be removed from the cart. Continue?");
                        if (confirmRemove) {
                            item.quantity = 0;
                            item.total = 0;
                        } else {
                            newQty = item.quantity;
                        }
                    } else {
                        item.quantity = newQty;
                        item.total = item.quantity * item.price;
                    }
                }
                return item;
            }).filter(item => item.quantity > 0);

            setCart(cart);

            await addToCart();
            await renderCart();
        }

        function addToCart() {
            const body = {
                ReferenceNo: getReferenceNo(),
                cart: cart,
            };

            const options = {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(body),
            };

            fetch("api/add_to_cart.php", options)
                .then((result) => result.json())
                .then((data) => {
                    console.log(data);
                    if (!data.success) {
                        console.error("Failed to add to cart:", data.message);
                        return;
                    }
                    setTotals(data.data);
                    showTotals();
                })
                .catch((err) => console.error("error:", err));
        }
    </script>

</body>

</html>