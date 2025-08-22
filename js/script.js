// format currency

function formatCurrency(amount) {
  return new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
  }).format(amount);
}
// show totals

function showTotals() {
  const totals = getTotals();
  const subtotalElement = document.getElementById("subtotal");
  const discountElement = document.getElementById("discount_amount");
  const serviceChargeElement = document.getElementById("service-charge");
  const serviceChargeLabelElement = document.getElementById(
    "service-charge-label"
  );
  const totalElement = document.getElementById("total");

  const paymentTotalElement = document.getElementById("payment_total");

  subtotalElement.innerText = formatCurrency(totals.subtotal || 0);

  if (discountElement) {
    discountElement.innerText = formatCurrency(totals.discount || 0);
  }

  serviceChargeLabelElement.innerText = "Service Charge 10%";
  serviceChargeElement.innerText = formatCurrency(totals.service_charge || 0);
  totalElement.innerText = formatCurrency(totals.total || 0);

  if (paymentTotalElement) {
    paymentTotalElement.innerText = formatCurrency(totals.total || 0);
  }
}

// clear

function clearAll() {
  clearCategories();
  clearCurrentIndex();
  clearCart();
  clearTotals();
  clearReferenceNo();
}

// categories

function getCategories() {
  return JSON.parse(localStorage.getItem("categories")) || [];
}

function setCategories(categories) {
  localStorage.setItem("categories", JSON.stringify(categories));
}

function clearCategories() {
  localStorage.removeItem("categories");
}

// currentIndex

function getCurrentIndex() {
  return parseInt(localStorage.getItem("currentIndex")) || 0;
}

function setCurrentIndex(index) {
  localStorage.setItem("currentIndex", index);
}

function clearCurrentIndex() {
  localStorage.removeItem("currentIndex");
}

// cart

function getCart() {
  return JSON.parse(localStorage.getItem("cart")) || [];
}

function setCart(cart) {
  localStorage.setItem("cart", JSON.stringify(cart));
}

function clearCart() {
  localStorage.removeItem("cart");
}

// totals

function getTotals() {
  return JSON.parse(localStorage.getItem("totals")) || [];
}

function setTotals(totals) {
  localStorage.setItem("totals", JSON.stringify(totals));
}

function clearTotals() {
  localStorage.removeItem("totals");
}

// referenceNo

function getReferenceNo() {
  return localStorage.getItem("referenceNo") || 0;
}

function setReferenceNo(referenceNo) {
  localStorage.setItem("referenceNo", referenceNo);
}

function clearReferenceNo() {
  localStorage.removeItem("referenceNo");
}

function redirectToIndexIfNoReferenceNumber() {
  const referenceNo = getReferenceNo();

  if (referenceNo === 0) {
    console.warn("No reference number found!");
    window.location.href = "index.php";
  }
}
