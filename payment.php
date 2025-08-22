<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mode of Payment</title>
    <link rel="stylesheet" href="./dist/style.css">
    <script src="./js/script.js"></script>
</head>

<body class="bg-black text-white font-sans text-3xl">
    <div class="h-screen flex flex-col container mx-auto max-w-4xl">
        <div class="h-[20%] flex items-center justify-center">
            <img src="images/logo/namelogo.png" alt="Main Logo" class="h-44 w-auto">
        </div>

        <div id="payment_container" class="relative flex-1">
            <div id="payment_section" class="flex flex-col h-full">
                <div class="flex-1 overflow-auto">
                    <div class="space-y-28 py-12">
                        <h2 class="text-4xl font-medium text-center">Mode of Payment</h2>
                        <p class="border-t-2 border-white w-full"></p>
                        <div class="flex justify-evenly items-center">
                            <div id="card" class="cursor-pointer flex flex-col items-center space-y-2">
                                <p class="text-lg font-medium">Credit Card / Debit Card</p>
                                <div class="border-2 border-white p-4 flex items-center justify-center w-64 h-64">
                                    <img src="./images/payment/cc.png" class="max-w-full max-h-full object-contain" alt="cc">
                                </div>
                            </div>

                            <div id="qrph" class="cursor-pointer flex flex-col items-center space-y-2">
                                <p class="text-lg font-medium">QRPH</p>
                                <div class="border-2 border-white p-4 flex items-center justify-center w-64 h-64">
                                    <img src="./images/payment/qrph.png" class="max-w-full max-h-full object-contain" alt="qrph">
                                </div>
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

                <div class="h-[15%] flex justify-center items-center p-12">
                    <button id="btnBack" class="border-2 border-white px-9 py-4 cursor-pointer">Back</button>
                </div>
            </div>

            <div id="processingOverlay" class="hidden flex-col h-full">
                <div class="flex-1 flex flex-col items-center justify-center space-y-12">
                    <div class="space-y-5 flex flex-col items-center justify-between">
                        <h4 class="text-4xl font-medium text-center">Kindly process your payment</h4>
                        <h4 class="text-4xl font-medium text-center">using the card terminal.</h4>
                    </div>
                    <p class="border-t-2 border-white w-full"></p>
                    <div class="flex justify-between items-center w-full text-4xl font-medium">
                        <span>Total</span>
                        <span id="payment_total"></span>
                    </div>
                </div>
                <div class="h-[40%]"> </div>
            </div>

            <div id="successOverlay" class="hidden flex-col h-full">
                <div class="flex-1 flex flex-col items-center justify-center space-y-10">
                    <h4 class="text-4xl font-medium">Please get your order ticket and official receipt</h4>
                    <h4 class="text-4xl">Thank you!</h4>
                    <p class="border-t-2 border-white w-full"></p>
                </div>

                <div class="h-[40%]"> </div>
            </div>

            <div id="errorOverlay" class="hidden flex-col h-full items-center justify-center">
                <div class="flex-1 flex flex-col items-center justify-center space-y-10">
                    <h5 class="text-4xl font-medium">Card Terminal Error</h5>
                    <h4 class="text-4xl"></h4>
                </div>

                <div class="h-[40%]"> </div>
            </div>
        </div>

    </div>

    <script>
        redirectToIndexIfNoReferenceNumber();

        const totals = getTotals();
        if (totals.total === 0) {
            sendPayment(null, "ZeroPayment");
        }

        const btnBack = document.getElementById("btnBack");
        btnBack.addEventListener("click", (e) => {
            e.preventDefault();

            const totals = getTotals();
            const hasDiscount = totals.discount && totals.discount > 0;

            if (hasDiscount) {
                const message = "Are you sure you want to go back to the cart?\n⚠️ Any applied discount will be removed.";
                if (!confirm(message)) return;

                const payload = {
                    Action: 'NoDiscount',
                    ReferenceNo: getReferenceNo()
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
                        if (!data.success) return;
                        setTotals(data.data);
                        window.location.href = 'cart.php';
                    })

            } else {
                window.location.href = 'cart.php';
            }
        })

        const card = document.getElementById("card");
        const qrph = document.getElementById("qrph");

        const payment_section = document.getElementById("payment_section");
        const processingOverlay = document.getElementById("processingOverlay")
        const successOverlay = document.getElementById("successOverlay")
        const errorOverlay = document.getElementById("errorOverlay")

        let paymentChecker = null;
        let isChecking = false;

        showTotals();

        card.addEventListener("click", (e) => sendPayment(e, "CreditDebit"));
        qrph.addEventListener("click", (e) => sendPayment(e, "GenericMerchantQR-qr:qrph"));

        function sendPayment(event, type) {
            if (event) event.preventDefault();

            const totals = getTotals();

            payment_section.classList.add("hidden");

            const body = {
                Status: "pay",
                ReferenceNo: getReferenceNo(),
                PaymentType: type,
                ...totals,
            };

            const options = {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(body),
            };

            fetch("api/payment_gateway.php", options)
                .then((result) => result.json())
                .then((data) => {
                    console.log(data);
                    if (!data.success) {
                        console.error("Failed:", data.message);
                        return;
                    }

                    processingOverlay.classList.replace("hidden", "flex");
                    paymentChecker = setTimeout(checkPaymentStatus, 1000);
                })
                .catch((err) => console.error("error:", err));
        }


        function checkPaymentStatus() {
            if (isChecking) return;
            isChecking = true;

            const payload = {
                Status: "checking",
                ReferenceNo: getReferenceNo(),
            };
            const options = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            };

            fetch('api/payment_gateway.php', options)
                .then(res => res.json())
                .then(res => {
                    console.log(res);

                    const status = res.status.toLowerCase();

                    if (!res.success) {
                        console.error(res.message);
                        return;
                    }

                    if (status === "success") {
                        clearTimeout(paymentChecker);
                        console.log("✅ Payment success!");

                        processingOverlay.classList.add("hidden");
                        successOverlay.classList.replace("hidden", "flex");

                        setTimeout(() => {
                            clearAll()
                            window.location.href = "index.php";
                        }, 1000 * 60 * 1);
                        return;
                    }

                    if (status === "error") {
                        clearTimeout(paymentChecker);
                        console.log("❌ Payment failed.");

                        errorOverlay.querySelector("h4").innerText = res.message || "Payment failed.";

                        processingOverlay.classList.add("hidden");
                        errorOverlay.classList.replace("hidden", "flex");

                        setTimeout(() => {
                            errorOverlay.classList.add("hidden");
                            payment_section.classList.replace("hidden", "flex");
                        }, 1000 * 10);
                        return;
                    }

                    paymentChecker = setTimeout(checkPaymentStatus, 1000);
                })
                .catch(err => console.error(err))
                .finally(() => {
                    isChecking = false;
                });
        }
    </script>
</body>

</html>