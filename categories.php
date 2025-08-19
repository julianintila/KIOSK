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
        <img src="images/logo/namelogo.png" style="height: 150px;" alt="">
    </div>

    <div class="container" id="category-container"></div>
    <script id="category-template" type="text/x-handlebars-template">
        {{#each this}}
            {{#unless (eq name "HIKINIKU TO COME Set")}}
                <div class="menu-section category" data-category-id="{{id}}">
                    <div class="section-icon">
                        <img src="{{getCategoryImage name}}" alt="{{name}}">
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
        <div class="category-detail">
            <div class="back-controls">
                <button id="back-btn" class="control-btn">← Back to Categories</button>
                <button id="back-add-to-cart" class="control-btn cart-btn">Add to Cart</button>
            </div>
            <h2 class="category-title">{{name}}</h2>
            {{#each items}}
            <div class="menu-item item-detail" data-item-id="{{id}}">
                <div class="item-icon"></div>
                <div class="item-details">
                    <div class="item-name">{{name}}</div>
                    <div class="item-description">{{description}}</div>
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
    </script>

    <!-- Handlebars -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.7.7/handlebars.min.js"></script>
    <script>
        Handlebars.registerHelper("getCategoryImage", function(name) {
            const map = {
                "hikiniku to come set": "images/category/maindish.png",
                "extras": "images/category/extras.png",
                "side dishes": "images/category/potsalad.png",
                "non-alcoholic": "images/category/gingerale.png",
                "alcohol": "images/category/beer.png",
                "shirts": "images/category/shirt.png"
            };

            return map[name.toLowerCase()] || "images/category/default.png";
        });

        let categories = [];
        let cart = [];

        console.log("cart:", cart);

        // Fetch from API
        fetch("api/items.php")
            .then((res) => {
                if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
                const contentType = res.headers.get("content-type");
                if (!contentType || !contentType.includes("application/json")) {
                    throw new Error("Response is not JSON");
                }
                return res.json();
            })
            .then((data) => {
                if (data.categories && data.categories.length) {
                    categories = data.categories;
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

        function saveState() {
            console.log("cart:", cart);
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
                    const q = Math.max(0, (tempQuantities[itemId] || 0) - 1);
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

                saveState();
                renderCategories();
            });
        }

        Handlebars.registerHelper('eq', function(a, b) {
            return a === b;
        });
    </script>

</body>

</html>