<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discount</title>
    <link rel="stylesheet" href="./dist/style.css">
    <script src="./js/script.js"></script>
    <style>
    @font-face {
        font-family: 'iowan_old_stylebold';
        src: url('2131-font-webfont.woff2') format('woff2'),
            url('2131-font-webfont.woff') format('woff');
        font-weight: normal;
        font-style: normal;
    }

    * {
        font-family: 'iowan_old_stylebold', serif;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100vh;
        align-items: center;
        justify-content: center;
        background-color: rgba(0, 0, 0, 0.8);
    }

    .modal-content {
        background-color: rgba(0, 0, 0, 0.9);
        margin: 0 auto;
        padding: 40px;
        width: 100%;
        max-width: 1000px;
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
        border: 2px solid #a7a0a0;
        box-shadow: 0 0 20px rgba(255, 255, 255, 0.1);
    }

    .modal-header {
        align-items: center;
        display: flex;
        justify-content: center;
        flex-direction: column;
    }

    .modal h1 {
        margin-bottom: 20px;
        color: #fff;
        font-size: 3rem;
        text-align: center;
    }

    .modal .message {
        color: #fff;
        font-size: 1.8rem;
        margin: 2rem 0;
        text-align: center;
    }

    .modal-buttons {
        display: flex;
        gap: 100px;
        justify-content: center;
        margin-top: 4rem;
    }

    .btn {
        padding: 20px;
        cursor: pointer;
        font-size: 2.5rem;
        height: 120px;
        width: 300px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: normal;
        background: transparent;
        color: white;
        border: 2px solid #a7a0a0;
    }

    .btn:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }

    .btn-no:hover {
        background-color: #c82333;
    }
    </style>
</head>

<body class="bg-black text-white font-sans text-3xl">
    <div class="h-screen flex flex-col container mx-auto max-w-4xl">
        <div class="h-[20%] flex items-center justify-center">
            <img src="images/logo/namelogo.png" alt="Main Logo" class="h-44 w-auto">
        </div>

        <div id="payment_container" class="relative flex-1">
            <div id="payment_section" class="flex flex-col h-full">
                <div class="flex-1 overflow-auto">
                    <div class="space-y-10">
                        <h2 class="text-4xl font-medium text-center">Discount</h2>
                        <p class="border-t-2 border-white w-full"></p>
                        <div class="space-y-6 max-w-2xl mx-auto">
                            <div>
                                <p id="error_msg" class="hidden text-red-400 font-medium text-center text-base"></p>
                                <p id="success_msg" class="hidden text-green-400 font-medium text-center text-base"></p>
                            </div>

                            <div class="flex flex-col items-center justify-between">
                                <label for="name" class="text-2xl">Name</label>
                                <input id="name" type="text" autocomplete="name"
                                    placeholder="Enter name of the card holder"
                                    class="block mt-2 w-full text-center rounded-xs bg-transparent px-4 py-4 text-lg text-white outline-1 -outline-offset-1 outline-gray-50 placeholder:text-gray-100 focus:outline-2 focus:-outline-offset-2 focus:outline-gray-100" />

                            </div>

                            <div class="flex flex-col items-center justify-between">
                                <label for="id_number" class="text-2xl">Discount ID Number</label>
                                <input type="text" id="id_number" placeholder="Enter discount ID number"
                                    class="block mt-2 w-full text-center rounded-xs bg-transparent px-4 py-4 text-lg text-white outline-1 -outline-offset-1 outline-gray-50 placeholder:text-gray-100 focus:outline-2 focus:-outline-offset-2 focus:outline-gray-100" />
                            </div>

                            <button id="btnRequestDiscountCode"
                                class="border-2 border-white px-9 py-4 cursor-pointer text-2xl w-full">Request Discount
                                Code</button>

                            <div class="flex flex-col items-center justify-between">
                                <label for="discount_code" class="text-2xl">Discount Code</label>
                                <input type="text" id="discount_code" placeholder="Enter discount code"
                                    class="block mt-2 w-full text-center rounded-xs bg-transparent px-4 py-4 text-lg text-white outline-1 -outline-offset-1 outline-gray-50 placeholder:text-gray-100 focus:outline-2 focus:-outline-offset-2 focus:outline-gray-100" />
                            </div>
                        </div>
                        <p class="border-t-2 border-white w-full"></p>
                    </div>
                </div>

                <div class="h-[30%] flex flex-col items-center justify-evenly space-y-14">
                    <div class="w-full space-y-5">
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

                <div class="h-[15%] flex justify-evenly items-center p-12">
                    <button id="btnBack" class="border-2 border-white px-9 py-4 cursor-pointer">Back</button>
                    <button id="btnProceedToPayment" class="border-2 border-white px-9 py-4 cursor-pointer">Proceed to
                        Payment</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h1>Warning</h1>
                <p class="message">⚠️ Are you sure you want to go back to the cart?<br>Any applied discount will be
                    removed.</p>
                <div class="modal-buttons">
                    <button id="btnConfirmBack" class="btn">Yes</button>
                    <button id="btnCancelBack" class="btn btn-no">No</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    redirectToIndexIfNoReferenceNumber();

    const btnRequestDiscountCode = document.getElementById('btnRequestDiscountCode');
    const nameInput = document.getElementById('name');
    const idNumberInput = document.getElementById('id_number');
    const discountCodeInput = document.getElementById('discount_code');

    const errorMsg = document.getElementById('error_msg');
    const successMsg = document.getElementById('success_msg');

    const btnBack = document.getElementById('btnBack');
    const btnProceedToPayment = document.getElementById('btnProceedToPayment');

    let oldDiscountCode = null

    showTotals()

    function clearMessages() {
        errorMsg.classList.add('hidden');
        successMsg.classList.add('hidden');
    }

    function sendDiscountRequest() {
        clearMessages();

        const name = nameInput.value.trim();
        const idNumber = idNumberInput.value.trim();

        const payload = {
            Action: 'RequestDiscount',
            ReferenceNo: getReferenceNo(),
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

                document.querySelectorAll("p[data-field]").forEach(p => p.remove());

                if (!data.success) {
                    if (data.data.length === 0) {
                        errorMsg.classList.remove('hidden');
                        errorMsg.textContent = data.message;
                    } else {
                        data.data.forEach(msgObj => {
                            const key = Object.keys(msgObj)[0];
                            const value = msgObj[key];

                            const input = document.getElementById(key);
                            if (input) {
                                const p = document.createElement("p");
                                p.textContent = value;
                                p.className = "text-sm text-red-500 mt-1";
                                p.dataset.field = key;
                                input.insertAdjacentElement("afterend", p);
                            }
                        });
                    }
                    return;
                }
                successMsg.classList.remove('hidden');
                successMsg.textContent = data.message;
                setTotals(data.data);
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

        if (discountCode !== '' && discountCode === oldDiscountCode) {
            successMsg.classList.remove('hidden');
            successMsg.textContent = 'Discount code already applied.';
            return;
        }

        const payload = {
            Action: 'Discount',
            ReferenceNo: getReferenceNo(),
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

                document.querySelectorAll("p[data-field]").forEach(p => p.remove());

                if (!data.success) {
                    if (data.data.length === 0) {
                        errorMsg.classList.remove('hidden');
                        errorMsg.textContent = data.message;
                    } else {
                        data.data.forEach(msgObj => {
                            const key = Object.keys(msgObj)[0];
                            const value = msgObj[key];

                            const input = document.getElementById(key);
                            if (input) {
                                const p = document.createElement("p");
                                p.textContent = value;
                                p.className = "text-sm text-red-500 mt-1";
                                p.dataset.field = key;
                                input.insertAdjacentElement("afterend", p);
                            }
                        });
                    }
                    return;
                }
                successMsg.classList.remove('hidden');
                successMsg.textContent = data.message;
                setTotals(data.data);
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

    btnBack.addEventListener('click', function(e) {
        e.preventDefault();
        clearMessages();

        const totals = getTotals();
        const hasDiscount = totals.discount && totals.discount > 0;

        if (hasDiscount) {
            const modal = document.getElementById('confirmationModal');
            modal.style.display = 'flex';

            document.getElementById('btnConfirmBack').onclick = function() {
                modal.style.display = 'none';
                const payload = {
                    Action: 'NoDiscount',
                    ReferenceNo: getReferenceNo()
                };
                const options = {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                };

                fetch('api/request_discount.php', options)
                    .then(res => res.json())
                    .then(data => {
                        if (!data.success) return;
                        setTotals(data.data);
                        window.location.href = 'cart.php';
                    });
            };

            document.getElementById('btnCancelBack').onclick = function() {
                modal.style.display = 'none';
            };
        } else {
            window.location.href = 'cart.php';
        }
    });
    </script>
</body>

</html>