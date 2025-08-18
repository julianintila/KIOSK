<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script src="https://cdn.jsdelivr.net/npm/handlebars@latest/dist/handlebars.js"></script>
</head>

<body>
    <div class="container">
        <div id="category-container"></div>
        <div id="item-container"></div>
    </div>

    <script id="category-template" type="text/x-handlebars-template">
        <div class="category" data-category-id="{{id}}">
            <!-- left panel -->
            <div></div>
            <!-- right panel -->
            <div>
                <div class="category-name" style="cursor:pointer">{{name}}</div>
                <ul>
                {{#each descriptions}}
                    <li>{{name}}</li>
                {{/each}}
                </ul>

                <ul>
                {{#each notes}}
                    <li>{{name}}</li>
                {{/each}}
                </ul>
            </div>
        </div>
    </script>

    <script id="item-template" type="text/x-handlebars-template">
        <div class="items" data-catergory-id="{{id}}">
            <h2>{{name}}</h2>
            <div>
                <div>
                    <button id="back-btn">X</button>
                </div>
                {{#each items}}
                <div data-item-id="{{this.id}}">
                    <div>
                        <img src="" alt="">
                    </div>
                    <div>
                        <p>{{extended_description}}</p>
                        <p>{{price}}</p>
                    </div>
                    <div>
                        <button class="decrease">-</button>
                        <span class="quantity">0</span>
                        <button class="increase">+</button>
                    </div>
                </div>
                {{/each}}
                <div>
                    <!-- commit the changes to the cart -->
                    <button id="back-add-to-cart">Add to Cart</button>
                </div>
            </div>
        </div>
    </script>
    <script>
        localStorage.removeItem("categories")
        localStorage.removeItem("currentIndex")

        let categories = JSON.parse(localStorage.getItem("categories")) || [];
        let currentIndex = parseInt(localStorage.getItem("currentIndex")) || 0;
        let cart = JSON.parse(localStorage.getItem("cart")) || [];

        console.log("cart:", cart);


        fetch("api/items.php")
            .then((res) => res.json())
            .then((data) => {
                if (data.categories && data.categories.length) {
                    if (!categories.length) categories = data.categories;
                    renderCategories();
                }
            })
            .catch((err) => console.error("error:", err));

        function saveState() {
            console.log("cart:", cart);

            localStorage.setItem("categories", JSON.stringify(categories));
            localStorage.setItem("currentIndex", currentIndex);
            localStorage.setItem("cart", JSON.stringify(cart));
        }

        function renderCategories() {
            const container = document.getElementById("category-container");
            container.innerHTML = "";
            categories.forEach((category) => {
                const source = document.getElementById("category-template").innerHTML;
                const template = Handlebars.compile(source);
                const html = template(category);
                container.insertAdjacentHTML("beforeend", html);
            });

            // category click
            document.querySelectorAll(".category").forEach(el => {
                el.addEventListener("click", (e) => {
                    const catId = e.currentTarget.dataset.categoryId;
                    const category = categories.find(c => c.id == catId);
                    showItems(category);
                });
            });
        }

        function showItems(category) {
            const container = document.getElementById("category-container");
            const src = document.getElementById("item-template").innerHTML;
            const tpl = Handlebars.compile(src);
            container.innerHTML = tpl(category);

            // build temp qty map from cart (supports older `qty` or newer `quantity`)
            const tempQuantities = {};
            category.items.forEach(item => {
                const existing = cart.find(c => c.id == item.id);
                tempQuantities[item.id] = existing ? (existing.quantity ?? existing.qty ?? 0) : 0;
            });

            // wire up +/- per item (select the elements you actually render)
            container.querySelectorAll("[data-item-id]").forEach(el => {
                const itemId = el.dataset.itemId; // string is fine for object keys
                const qtySpan = el.querySelector(".quantity");
                const decBtn = el.querySelector(".decrease");
                const incBtn = el.querySelector(".increase");

                // init UI
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
    </script>

</body>

</html>