<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HIKINIKU TO COME</title>
    <link rel="stylesheet" href="css/categories.css">
    <style>

    </style>
</head>

<body>
    <div class="header">
        <img src="images/logo/namelogo.png" style="height: 200px; margin-left:200px;" alt="">
        <button id="btn-back-to-cart" class="backToCart">Back to Cart</button>
    </div>

    <div class="container" id="category-container"></div>
    <script id="category-template" type="text/x-handlebars-template">
        {{#each this}}
            {{#unless (eq name "HIKINIKU TO COME Set")}}
                <div class="menu-section category" data-category-id="{{id}}">
                    <div class="section-icon">
                        <img src="images/category/{{id}}.png" alt="{{name}}"
                            onerror="this.onerror=null; this.src='images/category/default.png';">
                    </div>
                    <div class="section-content">
                        <div class="section-header">{{name}}</div>
                        {{#each descriptions}}
                            <div class="menu-item">{{name}}</div>
                        {{/each}}
                        {{#each notes}}
                            <div class="menu-item">{{name}}</div>
                        {{/each}}
                    </div>
                </div>
            {{/unless}}
        {{/each}}
    </script>


    <script id="item-template" type="text/x-handlebars-template">
        <h2 class="category-title">{{name}}</h2>
        <div class="category-detail">
            <div class="back-controls">
                <button id="back-btn" class="control-btn">×</button>
            </div>
            
            <div class="menu-items-container">
                <div class="menu-items-grid">
                    {{#each items}}
                    <div class="menu-item item-detail" data-item-id="{{id}}">
                        <div class="item-icon">
                             <img src="images/menucategory/{{description}}.png" alt="{{name}}" style="height: 200px;width:200px"
                            onerror="this.onerror=null; this.src='images/menucategory/default.png';">
                        </div>
                        <div class="item-details">
                            <div class="item-name">{{name}}</div>
                            <div class="item-description">{{extended_description}}</div>
                            <div class="item-price">${{price}}</div>
                        </div>
                        <div class="quantity-controls">
                            <button class="quantity-btn decrease">-</button>
                            <span class="quantity">0</span>
                            <button class="quantity-btn increase">+</button>
                        </div>
                    </div>
                    {{/each}}
                </div>
            </div>
            
            <button id="back-add-to-cart" class="control-btn cart-btn">Add to Cart</button>
        </div>
    </script>

    <!-- Handlebars -->
    <script src="./js/handlebars.min.js"></script>
    <script>
        let categories = JSON.parse(localStorage.getItem("categories")) || [];
        let cart = JSON.parse(localStorage.getItem("cart")) || [];

        const referenceNo = localStorage.getItem("referenceNo") || 0;
        const btnBackToCart = document.getElementById("btn-back-to-cart");

        btnBackToCart.addEventListener("click", () => {
            window.location.href = "cart.php";
        })



        fetch("api/items.php")
            .then((res) => res.json())
            .then((data) => {
                if (!data.success) {
                    console.error("Failed to load menu data:", data.message);
                    return;
                }

                if (data.data && data.data.length) {
                    categories = data.data;
                    renderCategories();
                }
            })
            .catch((err) => {
                console.error("Failed to load menu data:", err);
                const container = document.getElementById("category-container");
                container.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; border: 2px solid white;">
                        <h2>Unable to load menu</h2>
                        <p>Please check your API connection and try again.</p>
                        <p style="font-size: 12px; opacity: 0.7; margin-top: 20px;">Error: ${err.message}</p>
                    </div>
                `;
            });

        function addToCart() {
            if (!cart.length) {
                console.warn("Cart is empty!");
                return;
            }
            if (referenceNo === 0) {
                console.warn("No reference number found!");
                return;
            }

            localStorage.setItem("cart", JSON.stringify(cart));

            const body = {
                ReferenceNo: referenceNo,
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
                    if (!data.success) {
                        console.error("Failed to add to cart:", data.message);
                        return;
                    }
                    localStorage.setItem("totals", JSON.stringify(data.data));
                })
                .catch((err) => console.error("error:", err));
        }

        function renderCategories() {
            const container = document.getElementById("category-container");
            const source = document.getElementById("category-template").innerHTML;
            const template = Handlebars.compile(source);
            const html = template(categories);
            container.innerHTML = html;

            // category click (including featured hikiniku)
            document.querySelectorAll(".category").forEach(el => {
                el.addEventListener("click", (e) => {
                    const catId = e.currentTarget.dataset.categoryId;
                    const category = categories.find(c => c.id == catId);
                    if (category) {
                        showItems(category);
                    }
                });
            });
        }

        function showItems(category) {
            const container = document.getElementById("category-container");
            const src = document.getElementById("item-template").innerHTML;
            const tpl = Handlebars.compile(src);
            container.innerHTML = tpl(category);

            const tempQuantities = {};
            category.items.forEach(item => {
                const existing = cart.find(c => c.id == item.id);
                tempQuantities[item.id] = existing ? (existing.quantity ?? existing.qty ?? 0) : 0;
            });

            // wire up +/- per item
            container.querySelectorAll("[data-item-id]").forEach(el => {
                const itemId = el.dataset.itemId;
                const qtySpan = el.querySelector(".quantity");
                const decBtn = el.querySelector(".decrease");
                const incBtn = el.querySelector(".increase");

                qtySpan.textContent = tempQuantities[itemId] || 0;

                decBtn.addEventListener("click", () => {
                    let currentQty = tempQuantities[itemId] || 0;

                    if (category.required && currentQty <= 1) {
                        alert(`At least 1 item is required in ${category.name}.`);
                        return;
                    }

                    const q = Math.max(0, currentQty - 1);
                    tempQuantities[itemId] = q;
                    qtySpan.textContent = q;
                });

                incBtn.addEventListener("click", () => {
                    const q = (tempQuantities[itemId] || 0) + 1;
                    tempQuantities[itemId] = q;
                    qtySpan.textContent = q;
                });
            });

            // back to categories
            document.getElementById("back-btn").addEventListener("click", () => {
                renderCategories();
            });

            // commit to cart
            document.getElementById("back-add-to-cart").addEventListener("click", () => {
                category.items.forEach(item => {
                    const q = tempQuantities[item.id] || 0;
                    const idx = cart.findIndex(c => c.id == item.id);

                    if (q > 0) {
                        const updated = {
                            ...item,
                            quantity: q,
                            total: q * item.price
                        };
                        if (idx >= 0) cart[idx] = updated;
                        else cart.push(updated);
                    } else {
                        if (idx >= 0) cart.splice(idx, 1);
                    }
                });

                addToCart();
                renderCategories();
            });
        }

        Handlebars.registerHelper('eq', function(a, b) {
            return a === b;
        });
    </script>

</body>

</html>