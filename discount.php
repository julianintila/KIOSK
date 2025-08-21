<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discount</title>
    <script src="./js/script.js"></script>
</head>

<body>
    <div>Discount</div>
    <div>
        <p id="error_msg"></p>
        <p id="success_msg"></p>

        <div>
            <label for="">Name</label>
            <input type="text" id="name" placeholder="Enter name of the card holder" />
        </div>
        <div>
            <label for="">Discount ID Number</label>
            <input type="text" id="id_number" placeholder="Enter discount ID number" />
        </div>
        <button id="btnRequestDiscountCode">Request Discount Code</button>

        <div>
            <label for="">Discount Code</label>
            <input type="text" id="discount_code" placeholder="Enter discount code" />
        </div>
    </div>
    <div>
        <div>
            <span>Subtotal</span>
            <span id="subtotal"></span>
        </div>
        <div>
            <span>Discount</span>
            <span id="discount_amount"></span>
        </div>
        <div>
            <span>Service Charge</span>
            <span id="service-charge"></span>
        </div>
        <div>
            <span>Total</span>
            <span id="total"></span>
        </div>
    </div>
    <div>
        <button id="btnBack">Back</button>
        <button id="btnProceedToPayment">Proceed to Payment</button>
    </div>

    <script>
        const referenceNo = localStorage.getItem("referenceNo") || 0;
        const btnRequestDiscountCode = document.getElementById('btnRequestDiscountCode');
        const nameInput = document.getElementById('name');
        const idNumberInput = document.getElementById('id_number');
        const discountCodeInput = document.getElementById('discount_code');

        const errorMsg = document.getElementById('error_msg');
        const successMsg = document.getElementById('success_msg');

        const btnBack = document.getElementById('btnBack');
        const btnProceedToPayment = document.getElementById('btnProceedToPayment');

        let oldDiscountCode = ''

        showTotals()

        function clearMessages() {
            errorMsg.textContent = '';
            successMsg.textContent = '';
        }

        function sendDiscountRequest() {
            clearMessages();

            const name = nameInput.value.trim();
            const idNumber = idNumberInput.value.trim();

            if (referenceNo === 0) {
                console.warn("No reference number found!");
                return;
            }

            const payload = {
                Action: 'RequestDiscount',
                ReferenceNo: referenceNo,
                name: name,
                id_number: idNumber,
            }
            const options = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            }

            fetch('api/request_discount.php', options)
                .then(res => res.json())
                .then(data => {
                    clearMessages();

                    if (!data.success) {
                        errorMsg.textContent = data.message;
                        return;
                    }
                    successMsg.textContent = data.message;
                    localStorage.setItem("totals", JSON.stringify(data.data));
                    showTotals();
                });
        }

        btnRequestDiscountCode.addEventListener('click', function(e) {
            e.preventDefault();
            sendDiscountRequest();
        });

        nameInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                sendDiscountRequest();
            }
        });

        idNumberInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                sendDiscountRequest();
            }
        });

        function applyDiscountCode() {
            clearMessages();

            const discountCode = discountCodeInput.value.trim();
            if (!discountCode) return;

            if (discountCode === oldDiscountCode) {
                successMsg.textContent = 'Discount code already applied.';
                return;
            }

            const payload = {
                Action: 'Discount',
                ReferenceNo: referenceNo,
                discount_code: discountCode
            }
            const options = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            }

            fetch('api/request_discount.php', options)
                .then(res => res.json())
                .then(data => {
                    clearMessages();

                    if (!data.success) {
                        errorMsg.textContent = data.message;
                        return;
                    }
                    successMsg.textContent = data.message;
                    localStorage.setItem("totals", JSON.stringify(data.data));
                    oldDiscountCode = discountCode;
                    showTotals();
                });
        }

        function debounce(func, delay) {
            let timeoutId;

            function debounced(...args) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => func.apply(this, args), delay);
            }
            debounced.cancel = function() {
                clearTimeout(timeoutId);
            };
            return debounced;
        }


        const debouncedApplyDiscountCode = debounce(applyDiscountCode, 500);

        discountCodeInput.addEventListener('input', function() {
            debouncedApplyDiscountCode();
        });

        discountCodeInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                debouncedApplyDiscountCode.cancel();
                applyDiscountCode();
            }
        });

        btnProceedToPayment.addEventListener('click', function(e) {
            window.location.href = 'payment.php';
        });

        btnBack.addEventListener('click', function() {
            clearMessages();
            const payload = {
                Action: 'NoDiscount',
                ReferenceNo: referenceNo
            }
            const options = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            }

            fetch('api/request_discount.php', options)
                .then(res => res.json())
                .then(data => {
                    clearMessages();
                    if (!data.success) return;
                    localStorage.setItem("totals", JSON.stringify(data.data));
                    window.location.href = 'cart.php';
                });
        });
    </script>
</body>

</html>