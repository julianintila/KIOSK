<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mode of Payment</title>
    <script src="./js/script.js"></script>
    <style>
        #processingOverlay,
        #errorOverlay,
        #successOverlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            z-index: 9999;
            text-align: center;
            padding-top: 100px;
        }
    </style>

</head>

<body>
    <div>
        <h2>Mode of Payment</h2>
        <div>
            <div id="card">
                Credit Card / Debit Card
            </div>
            <div id="qrph">
                QRPH
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
        </div>
    </div>

    <div id="processingOverlay">
        <h4> Kindly process your payment using the card terminal.</h4>
        <p>Please do not close this window.</p>
        <div>
            <span>Total</span>
            <span id="_total"></span>
        </div>
    </div>

    <div id="successOverlay">
        <h4>Please get your order ticket and official receipt</h4>
        <h4>Thank you</h4>
    </div>

    <div id="errorOverlay">
        <h4></h4>
    </div>

    <script>
        // initialize
        const btnBack = document.getElementById("btnBack");
        const referenceNo = localStorage.getItem("referenceNo") || 0;
        const card = document.getElementById("card");
        const qrph = document.getElementById("qrph");

        const processingOverlay = document.getElementById("processingOverlay")
        const successOverlay = document.getElementById("successOverlay")
        const errorOverlay = document.getElementById("errorOverlay")

        const totals = JSON.parse(localStorage.getItem("totals")) || [];
        const _totalElement = document.getElementById("_total");

        _totalElement.innerText = formatCurrency(totals.total || 0);

        showTotals();

        if (totals.total === 0) {
            sendPayment(null, "ZeroPayment");
        }

        // event listeners
        card.addEventListener("click", (e) => {
            sendPayment(e, "CreditDebit");
        })

        qrph.addEventListener("click", (e) => {
            sendPayment(e, "GenericMerchantQR-qr:qrph");
        })

        btnBack.addEventListener("click", () => {
            window.location.href = "cart.php";
        })

        let paymentChecker = null;
        let isChecking = false;

        function sendPayment(event, type) {
            if (event) event.preventDefault();
            const referenceNo = localStorage.getItem("referenceNo") || 0;
            const totals = JSON.parse(localStorage.getItem("totals")) || [];

            const body = {
                Status: "pay",
                ReferenceNo: referenceNo,
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

                    // show processing overlay
                    document.getElementById("processingOverlay").style.display = "block";

                    // start the first check
                    paymentChecker = setTimeout(checkPaymentStatus, 1000);
                })
                .catch((err) => console.error("error:", err));
        }


        function checkPaymentStatus() {
            if (isChecking) return;
            isChecking = true;

            const referenceNo = localStorage.getItem("referenceNo") || 0;
            if (!referenceNo) {
                console.error("No reference number found!");
                isChecking = false;
                return;
            }

            const payload = {
                Status: "checking",
                ReferenceNo: referenceNo,
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
                    const status = res.status.toLowerCase();

                    console.log(res);

                    if (!res.success) {
                        console.error(res.message);
                        return;
                    }

                    if (status === "success") {
                        clearTimeout(paymentChecker);
                        console.log("✅ Payment success!");
                        processingOverlay.style.display = "none";
                        successOverlay.style.display = "block";

                        setTimeout(() => {
                            window.location.href = "index.php";
                        }, 3000);
                        return;
                    }

                    if (status === "error") {
                        clearTimeout(paymentChecker);
                        console.log("❌ Payment failed.");
                        processingOverlay.style.display = "none";

                        // show error overlay with message
                        errorOverlay.querySelector("h4").innerText = res.message || "Payment failed.";
                        errorOverlay.style.display = "block";

                        setTimeout(() => {
                            errorOverlay.style.display = "none";
                            // return to original UI (mode of payment screen)
                            document.querySelector("div").style.display = "block";
                        }, 3000);
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