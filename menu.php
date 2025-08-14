<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Document</title>
    <script src="https://cdn.jsdelivr.net/npm/handlebars@latest/dist/handlebars.js"></script>
    <style>
      .items {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 15px;
      }
      .item {
        border: 1px solid #ddd;
        padding: 10px;
        border-radius: 5px;
        width: 200px;
      }
      .item img {
        max-width: 100%;
        border-radius: 5px;
      }
      .controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 5px;
      }
      .nav-buttons {
        margin-top: 20px;
        display: flex;
        gap: 10px;
      }
      .nav-buttons button {
        padding: 6px 12px;
        border: none;
        background-color: #007bff;
        color: #fff;
        border-radius: 4px;
        cursor: pointer;
      }
      .nav-buttons button:hover {
        background-color: #0056b3;
      }
    </style>
  </head>
  <body>
    <div id="category-container"></div>

    <script id="category-template" type="text/x-handlebars-template">
      {{#if name}}
        <h1>{{name}}</h1>
      {{/if}}
      {{#if items.length}}
        <div class="items">
          {{#each items}}
            <div class="item" data-item-id="{{id}}">
              <img src="" alt="" />
              <h4>{{extended_description}}</h4>
              <p>{{price}}</p>
              <div class="controls">
                <button class="decrease">-</button>
                <span class="quantity">0</span>
                <button class="increase">+</button>
              </div>
              <p class="total">Total: 0</p>
            </div>
          {{/each}}
        </div>
      {{/if}}
      <div class="nav-buttons">
        {{#if previous_category_id}}
          <button onclick="navigateCategory('{{previous_category_id}}')">
            Back
          </button>
        {{else}}
          <button onclick="backToIndex()">Back</button>
        {{/if}}
        {{#if next_category_id}}
          <button onclick="navigateCategory('{{next_category_id}}')">
            Next
          </button>
        {{else}}
          <button onclick="addToCart()">View Cart</button>
        {{/if}}
      </div>
    </script>

    <script>
      let categories = JSON.parse(localStorage.getItem("categories")) || [];
      let currentIndex = parseInt(localStorage.getItem("currentIndex")) || 0;
      let cart = JSON.parse(localStorage.getItem("cart")) || [];

      fetch("api/items.php")
        .then((res) => res.json())
        .then((data) => {
          if (data.categories && data.categories.length) {
            if (!categories.length) categories = data.categories;
            renderCategory(currentIndex);
          }
        })
        .catch((err) => console.error("error:", err));

      function saveState() {
        localStorage.setItem("categories", JSON.stringify(categories));
        localStorage.setItem("currentIndex", currentIndex);
        localStorage.setItem("cart", JSON.stringify(cart));
      }

      function updateCartById(itemId, delta) {
        const category = categories[currentIndex];
        const item = category.items.find((i) => i.id === itemId);
        if (!item) return;

        item.quantity = (item.quantity || 0) + delta;
        if (item.quantity < 0) item.quantity = 0;
        item.total = item.quantity * item.price;

        const cartIndex = cart.findIndex((i) => i.id === itemId);
        if (cartIndex > -1) {
          if (item.quantity === 0) cart.splice(cartIndex, 1);
          else cart[cartIndex] = { ...item };
        } else if (item.quantity > 0) {
          cart.push({ ...item });
        }

        const itemEl = document.querySelector(
          `.item[data-item-id='${itemId}']`
        );
        if (itemEl) {
          itemEl.querySelector(".quantity").textContent = item.quantity;
          itemEl.querySelector(".total").textContent =
            "Total: " + item.total.toFixed(2);
        }

        saveState();
        console.log("cart:", cart);
      }

      function renderCategory(index) {
        const source = document.getElementById("category-template").innerHTML;
        const template = Handlebars.compile(source);
        document.getElementById("category-container").innerHTML = template(
          categories[index]
        );

        document.querySelectorAll(".item").forEach((itemEl) => {
          const itemId = parseInt(itemEl.dataset.itemId);
          itemEl
            .querySelector(".increase")
            .addEventListener("click", () => updateCartById(itemId, 1));
          itemEl
            .querySelector(".decrease")
            .addEventListener("click", () => updateCartById(itemId, -1));

          // Update quantity and total from stored values
          const item = categories[currentIndex].items.find(
            (i) => i.id === itemId
          );
          if (item) {
            itemEl.querySelector(".quantity").textContent = item.quantity || 0;
            itemEl.querySelector(".total").textContent =
              "Total: " + (item.total || 0).toFixed(2);
          }
        });
      }

      function navigateCategory(id) {
        const newIndex = categories.findIndex((cat) => cat.id == id);
        if (newIndex !== -1) {
          currentIndex = newIndex;
          saveState();
          renderCategory(currentIndex);
        }
      }

      function addToCart() {
        if (!cart.length) {
          console.warn("Cart is empty!");
          return;
        }

        const options = {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ cart }),
        };

        fetch("api/add_to_cart.php", options)
          .then((result) => console.log(result))
          .catch((err) => console.error("error:", err));

        window.location.href = "cart.php";
      }

      function backToIndex() {
        window.location.href = "index.php";
      }
    </script>
  </body>
</html>
