function formatCurrency(amount) {
  return new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
  }).format(amount);
}

function showTotals() {
  const totals = JSON.parse(localStorage.getItem("totals")) || [];
  const subtotalElement = document.getElementById("subtotal");
  const discountElement = document.getElementById("discount_amount");
  const serviceChargeElement = document.getElementById("service-charge");
  const totalElement = document.getElementById("total");

  subtotalElement.innerText = formatCurrency(totals.subtotal || 0);

  if (discountElement) {
    discountElement.innerText = formatCurrency(totals.discount || 0);
  }

  serviceChargeElement.innerText = formatCurrency(totals.service_charge || 0);
  totalElement.innerText = formatCurrency(totals.total || 0);
}
