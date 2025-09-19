<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discount Requests</title>
    <script src="./js/handlebars.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        #discountCodeOverlay {
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
    <h1>Pending Discount Requests</h1>

    <div id="container-pending-discount-requests"></div>

    <div id="discountCodeOverlay">
        <h2>Discount Code</h2>

        <p>Input this code at the kiosk to apply your discount</p>
        <h4></h4>
        <p>Valid for one-time use only</p>
        <p>Code will expire in 10 minutes</p>
        <button onclick="hideDiscountCodeOverlay()">Close</button>
    </div>

    <audio id="newDiscountSound" src="./sounds/notification.mp3" preload="auto"></audio>

    <script id="pending-discount-template" type="text/x-handlebars-template">
        {{#if requests}}
            {{#each requests}}
                <div class="discount-request" data-request-id="{{id}}">
                    <h2>A guest at KIOSK {{register_no}} has submitted a discount request.</h2>
                    <h4>Please review the details.</h4>
                    <h4>Customer Name: {{name}}</h4>
                    <h4>Customer ID: {{discount_id}}</h4>
                    <p>Date & Time: {{formatDate datetime}}</p>

                    <div>
                        <label for="discount_type_{{id}}">Discount Type</label>
                        <select name="discount_type" id="discount_type_{{id}}" class="discount-type">
                            <option value="">SELECT DISCOUNT TYPE</option>
                            {{#each ../discounts}}
                                <option value="{{code}}">{{description}}</option>
                            {{/each}}
                        </select>

                        <!-- initially hidden -->
                        <select name="customer" id="customer_{{id}}" class="customer-select" style="display:none;">
                            <option value="">SELECT CUSTOMER</option>
                            {{#each ../customers}}
                                <option value="{{middle_initial}}">{{middle_initial}}</option>
                            {{/each}}
                        </select>
                    </div>

                    <div>
                        <button>Reject</button>
                        <button>Generate Code</button>
                    </div>
                </div>
            {{/each}}
        {{else}}
            <p>No pending discount requests.</p>
        {{/if}}
    </script>

    <script>
        let pending_discount_requests = [];
        let customers = [];
        let discounts = [];
        let autoRefreshInterval = null;
        let resumeTimeout = null;
        const INACTIVITY_LIMIT = 1000 * 30; // 30 seconds of inactivity

        let seenRequestIds = new Set();
        const newDiscountSound = document.getElementById('newDiscountSound');

        const discountCodeOverlay = document.getElementById('discountCodeOverlay');
        const containerPendingDiscountRequests = document.getElementById('container-pending-discount-requests');

        Handlebars.registerHelper('formatDate', function(datetime) {
            return new Date(datetime).toLocaleString();
        });

        fetchData();
        startAutoRefresh();

        function stopAutoRefresh() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
                autoRefreshInterval = null;
                console.log("Auto-refresh stopped");
            }

            if (resumeTimeout) clearTimeout(resumeTimeout);
            resumeTimeout = setTimeout(() => {
                console.log("Resuming auto-refresh after inactivity timeout...");
                startAutoRefresh();
            }, INACTIVITY_LIMIT);
        }

        function startAutoRefresh() {
            if (autoRefreshInterval) clearInterval(autoRefreshInterval);
            if (resumeTimeout) {
                clearTimeout(resumeTimeout);
                resumeTimeout = null;
            }

            autoRefreshInterval = setInterval(() => {
                if (window.getComputedStyle(discountCodeOverlay).display === "none") {
                    console.log("Refreshing discount requests...");
                    fetchData();
                }
            }, 1000 * 3);
            console.log("Auto-refresh started");
        }

        function fetchData() {
            fetch('api/manager_discount_request.php')
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        console.error('Failed to fetch data:', data.message);
                        return;
                    }
                    console.log("Fetched data:", data);
                    pending_discount_requests = data.data.pending_discount_requests || [];
                    discounts = data.data.discounts || [];
                    customers = data.data.customers || [];

                    let hasNew = false;
                    for (const req of pending_discount_requests) {
                        const id = req.id + req.name + req.discount_id + req.register_no;
                        if (!seenRequestIds.has(id)) {
                            hasNew = true;
                            seenRequestIds.add(id);
                        }
                    }

                    if (hasNew) {
                        try {
                            newDiscountSound.currentTime = 0;
                            newDiscountSound.play();
                        } catch (e) {
                            console.warn("Autoplay blocked until user interacts:", e);
                        }
                    }

                    renderRequests(pending_discount_requests, discounts, customers);
                })
                .catch(err => {
                    console.error('Error fetching data:', err);
                });
        }

        function renderRequests(requests, discounts, customers) {
            console.log("Rendering requests");

            const templateSource = document.getElementById('pending-discount-template').innerHTML;
            const template = Handlebars.compile(templateSource);

            const html = template({
                requests,
                discounts,
                customers
            });
            containerPendingDiscountRequests.innerHTML = html;

            containerPendingDiscountRequests.querySelectorAll('.discount-request').forEach(requestDiv => {
                requestDiv.addEventListener("click", () => {
                    console.log("User interacted with request, stopping auto-refresh...");
                    stopAutoRefresh();
                    newDiscountSound.pause();
                    newDiscountSound.currentTime = 0;
                });

                const requestId = requestDiv.dataset.requestId;
                const rejectBtn = requestDiv.querySelector('button:nth-of-type(1)');
                const generateBtn = requestDiv.querySelector('button:nth-of-type(2)');
                const discountSelect = requestDiv.querySelector('.discount-type');
                const customerSelect = requestDiv.querySelector('.customer-select');

                rejectBtn.addEventListener('click', () => {
                    handleReject(requestId);
                });

                generateBtn.addEventListener('click', () => {
                    const discountType = discountSelect.value;
                    let customer = '';
                    if (discountType.toLowerCase() === "sp") {
                        customer = customerSelect.value;
                    }
                    handleGenerate(requestId, discountType, customer);
                });
            });
        }

        function handleReject(requestId) {
            stopAutoRefresh();
            if (!confirm("Are you sure you want to reject this request?")) {
                startAutoRefresh();
                return;
            }

            fetch('api/manager_discount_request.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'reject',
                        id: requestId
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        console.error('Failed to reject request:', data.message);
                        startAutoRefresh();
                        return;
                    }
                    console.log("Reject response:", data);
                    alert(data.message);
                    fetchData();
                    startAutoRefresh();
                }).catch(err => {
                    console.error('Error rejecting request:', err);
                });
        }

        function handleGenerate(requestId, discountType, customer) {
            stopAutoRefresh();
            if (!confirm("Generate code for this request?")) {
                startAutoRefresh();
                return;
            }

            console.log("Generate code for:", requestId, discountType, customer);
            fetch('api/manager_discount_request.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'generate_code',
                        id: requestId,
                        discount_type: discountType,
                        customer: customer
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        console.error('Failed to generate code:', data.message);;
                        startAutoRefresh();
                        return;
                    }

                    discountCodeOverlay.style.display = "block";
                    discountCodeOverlay.querySelector("h4").innerText = data.data.code

                    setTimeout(() => {
                        discountCodeOverlay.style.display = "none";
                        fetchData();
                        startAutoRefresh();
                    }, 1000 * 60 * 1);
                }).catch(err => {
                    console.error('Error generating code:', err);
                });
        }

        function hideDiscountCodeOverlay() {
            discountCodeOverlay.style.display = "none";
            fetchData();
            startAutoRefresh(); // resume after closing overlay
        }

        ["click", "mousemove", "keydown", "touchstart"].forEach(evt => {
            document.addEventListener(evt, unlockAudio, {
                once: true
            });
        });

        function unlockAudio() {
            newDiscountSound.play().then(() => {
                newDiscountSound.pause();
                newDiscountSound.currentTime = 0;
                console.log("🔊 Audio unlocked for autoplay");
            });
        }
    </script>
</body>

</html>