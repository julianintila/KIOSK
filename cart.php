<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart</title>
    <link rel="stylesheet" href="./dist/style.css">
    <link rel="stylesheet" href="css/cart.css">
    <script src="./js/script.js"></script>
</head>

<body class="bg-black text-white font-sans text-3xl">
    <div class="h-screen flex flex-col container mx-auto max-w-4xl">
        <div class="flex-none flex items-center justify-center py-12">
            <img src="images/logo/namelogo.png" alt="Main Logo" class="h-44 w-auto">
        </div>

        <div class="flex-1 flex flex-col overflow-hidden">
            <div class="flex-none space-y-6 px-4 py-2">
                <div class="grid grid-cols-5 font-medium">
                    <div class="col-span-3">Menu Detail</div>
                    <div class="text-center">Quantity</div>
                    <div class="text-right">Price</div>
                </div>
                <p class="border-t-2 border-white w-full"></p>
            </div>

            <div class="flex-1 overflow-y-auto scrollbar-hide px-4 space-y-4" id="cart-container">
            </div>

            <p class="border-t-2 border-white w-full"></p>

            <div class="flex-none py-12 space-y-8">
                <div class="w-full space-y-4">
                    <div class="flex justify-between items-center">
                        <span>Subtotal</span>
                        <span id="subtotal"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span>Discount</span>
                        <span id="discount_amount"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span id="service-charge-label">Service Charge</span>
                        <span id="service-charge"></span>
                    </div>
                </div>
                <div class="w-full flex justify-between items-center">
                    <span>Total</span>
                    <span id="total"></span>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex-none flex justify-evenly items-center px-6 py-12">
                <button id="btn-start-over" class="border-2 border-white px-9 py-4 cursor-pointer">Start Over</button>
                <button id="btn-back-to-menu" class="border-2 border-white px-9 py-4 cursor-pointer">Back to
                    Menu</button>
                <button id="btn-next" class="border-2 border-white px-9 py-4 cursor-pointer">Next</button>
            </div>
        </div>
    </div>


    <!-- Modal -->
    <div id="privilegeModal" class="modal">
        <div class="modal-content">
            <button id="closeModal" class="close-button">&times;</button>
            <div class="modal-header">
                <h1>Do you have a privilege card?</h1>
                <p class="subtitle">(Senior Citizen, PWD card, etc.)</p>
                <div class="modal-buttons">
                    <button id="btnYes" class="btn btn-yes">Yes</button>
                    <button id="btnNo" class="btn btn-no">No</button>
                </div>
                <p class="note">Note: If you have a privilege card, kindly ask the staff for assistance.</p>
            </div>
        </div>
    </div>

    <script id="cart-template" type="text/x-handlebars-template">
        {{#if this.length}}
            {{#each this}}
                <div data-id="{{id}}" class="grid grid-cols-5">
                    <div class="col-span-3">{{extended_description}}</div>
                    <div class="flex items-center justify-between">
                        <button class="decrease">-</button>
                        <span class="quantity">{{quantity}}</span>
                        <button class="increase">+</button>
                    </div>
                    <div class="line-total text-right">{{currency total}}</div>
                </div>
            {{/each}}
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
        const btnStartOver = document.getElementById("btn-start-over");
        const btnBackToMenu = document.getElementById("btn-back-to-menu");


        btnStartOver.addEventListener("click", () => {
            if (confirm("Are you sure you want to start over?")) {
                clearAll();
                window.location.href = "index.php";
            }
        });

        btnBackToMenu.addEventListener("click", () => window.location.href = "categories.php")

        const btnNext = document.getElementById("btn-next");
        const modal = document.getElementById("privilegeModal");
        const btnYes = document.getElementById("btnYes");
        const btnNo = document.getElementById("btnNo");

        btnNext.addEventListener("click", async () => {
            modal.style.display = "flex";
        });

        btnYes.addEventListener("click", () => {
            modal.style.display = "none";
            window.location.href = "discount.php";
        });

        btnNo.addEventListener("click", () => {
            modal.style.display = "none";
            window.location.href = "payment.php";
        });





        document.getElementById('closeModal').addEventListener('click', () => {
            modal.style.display = "none";
        });

        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && modal.style.display === "block") {
                modal.style.display = "none";
            }
        });

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
                    const row = this.closest("div[data-id]");
                    const id = parseInt(row.dataset.id);
                    updateQuantity(id, 1);
                });
            });

            document.querySelectorAll(".decrease").forEach(btn => {
                btn.addEventListener("click", function() {
                    const row = this.closest("div[data-id]");
                    const id = parseInt(row.dataset.id);
                    updateQuantity(id, -1);
                });
            });
        }

        async function updateQuantity(id, change) {
            const categories = getCategories();

            cart = cart.map(item => {
                if (item.id !== id) return item;

                let newQty = item.quantity + change;
                const currentCategory = categories.find(c => c.items.some(i => i.id === id));
                const isRequired = currentCategory?.required;
                const otherItemsSelected = currentCategory?.items.some(i => i.id !== id && (i.quantity || 0) >
                    0);

                if (newQty <= 0) {
                    if (isRequired && !otherItemsSelected) {
                        alert("You must select at least one item in this category.");
                        newQty = item.quantity;
                    } else if (confirm("The item will be removed from the cart. Continue?")) {
                        item.quantity = 0;
                        item.total = 0;
                    } else {
                        newQty = item.quantity;
                    }
                }

                if (newQty > 0) {
                    item.quantity = newQty;
                    item.total = item.quantity * item.price;
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