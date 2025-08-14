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