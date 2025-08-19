<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>HIKINIKU</title>
  <script src="https://cdn.jsdelivr.net/npm/handlebars@latest/dist/handlebars.js"></script>
  <script src="js/script.js"></script>
  <link rel="stylesheet" href="css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Spectral:wght@300&display=swap" rel="stylesheet">

</head>

<body>
  <div class="container">
    <div id="category-container"></div>
  </div>

  <script id="category-template" type="text/x-handlebars-template">
    <div
    style="text-align: center; justify-content: center;margin-left: 450px; background-color: #00000000; position:absolute; top:50px;">
    <img src="images/logo/namelogo.png" alt="Main Logo"
      style="width: 180px; height: 180px; filter: brightness(0.9);">

  </div>
    {{#if name}}
      <h1>{{name}}</h1>
    {{/if}}
    {{#if items.length}}
      <div class="item-container">
        <div class="items">
          <div>
            {{#each items}}
              <div class="item" data-item-id="{{id}}">
                <img src="images/menucategory/maindish.png" alt="" />
                <div style="border: 2px solid white;width:100%; padding:20px; margin-left:100px;">
                  <h4>{{extended_description}}</h4>
                  <p class="price">{{price}}</p>
                </div>
                <div class="controls">
                  <button class="decrease">-</button>
                  <span class="quantity">0</span>
                  <button class="increase">+</button>
                </div>
                <div style="text-align: center; margin-left: 10px; font-size:24px;width: 800px;">
                  <p>Freshly grilled burger patty (3 beef patties freshly grilled</p>
                  <p> one after another) with freshly cooked rice and miso soup</p><br>
                  <p> The set comes with one raw egg per set, & unilimited rice</p>
                </div>
              </div>
            {{/each}}
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
    </div>
    </div>

  </script>

  <script>
    let categories = JSON.parse(localStorage.getItem("categories")) || [];
    let currentIndex = parseInt(localStorage.getItem("currentIndex")) || 0;
    let cart = JSON.parse(localStorage.getItem("cart")) || [];
    let required = JSON.parse(localStorage.getItem("required")) || [];

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
        else cart[cartIndex] = {
          ...item
        };
      } else if (item.quantity > 0) {
        cart.push({
          ...item
        });
      }

      const itemEl = document.querySelector(`.item[data-item-id='${itemId}']`);
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

        const item = categories[currentIndex].items.find((i) => i.id === itemId);
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
      const body = {
        kioskRegNo: "1",
        ReferenceNo: "A0001",
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
          const {
            discount,
            service_charge,
            subtotal,
            total
          } = data;
          localStorage.setItem(
            "totals",
            JSON.stringify({
              discount,
              service_charge,
              subtotal,
              total
            })
          );
          window.location.href = "cart.php";
        })
        .catch((err) => console.error("error:", err));
    }

    function nextValidation() {

    }


    function backToIndex() {
      if (cart.length > 0) {
        showConfirmModal(function(confirmed) {
          if (confirmed) {
            window.location.href = "index.php";
          }
        });
      } else {
        window.location.href = "index.php";
      }
    }

    function showConfirmModal(callback) {

      const modalHTML = document.getElementById("confirm-modal-template").innerHTML;
      document.body.insertAdjacentHTML("beforeend", modalHTML);

      const overlay = document.getElementById("confirmOverlay");
      const yesBtn = document.getElementById("confirmYes");
      const noBtn = document.getElementById("confirmNo");

      yesBtn.addEventListener("click", () => {
        overlay.remove();
        callback(true);

      });

      noBtn.addEventListener("click", () => {
        overlay.remove();
        callback(false);
      });
    }
  </script>
  <script id="confirm-modal-template" type="text/html">
    <div class="custom-modal-overlay" id="confirmOverlay">
      <div class="custom-modal">
        <div>

          <div style="text-align: center; margin-top: 50px;">
            <img src="images/icons/caution-sign.png" alt="Logo" style="max-width: 120px; height: auto;">
          </div>


          <div
            style="margin-top: 50px; font-size: 34px; display: flex; flex-direction: column; justify-content: center; text-align: center;">
            <p>You have items in your cart.</p>
            <p>Are you sure you want to leave?</p>
          </div>


          <div class="modal-buttons" style="text-align: center; margin-top: 80px;">
            <button id="confirmYes">Yes</button>
            <button id="confirmNo">No</button>
          </div>
        </div>
      </div>

    </div>
    <style>
      .custom-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
      }

      .custom-modal {
        background: black;
        padding: 20px;
        border-radius: 8px;
        width: 800px;
        border: 2px solid white;
        text-align: center;
        height: 600px;
      }

      .modal-buttons {
        margin-top: 150px;
        display: flex;
        gap: 30px;
        justify-content: center;
      }

      .modal-buttons button {
        margin: 0 5px;
        padding: 6px 12px;
        background: black;
        color: white;
        border-radius: 20px;
        width: 200px;
        height: 70px;
        font-size: 24px;
        border: 2px solid white;
      }
    </style>
  </script>

</body>

</html>