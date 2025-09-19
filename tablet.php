<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Discount Requests</title>
  <script src="./js/handlebars.min.js"></script>
  <link rel="stylesheet" href="css/tablet.css">
  <style>

  </style>
</head>

<body>
  <div class="container">
    <div>
      <img src="images/logo/namelogo.png" alt="" style="height: 70px;">
    </div>

    <div id="container-pending-discount-requests"></div>


    <div id="discountCodeOverlay" class="overlay">
      <div class="modal discount-code-modal">
        <div class="modal-content discount-code-content">
          <h2 class="modal-title discount-code-title">Discount Code</h2>
          <div class="divider"></div>
          <p class="modal-text discount-code-message">Input this code at the kiosk to apply your discount.</p>
          <div class="code-display discount-code-display">
            <div class="code-text discount-code-value"></div>
          </div>
          <p class="modal-note">Valid for one-time use only.</p>
          <p class="modal-note">Code will expire in 10 minutes.</p>
          <button onclick="hideDiscountCodeOverlay()" class="close-btn discount-code-close">Close</button>
        </div>
      </div>
    </div>

    <div id="confirmModal" class="confirm-modal-overlay">
      <div class="confirm-modal">
        <div class="confirm-modal-header">
          <h3 class="confirm-modal-title" id="confirmModalTitle">Confirm Action</h3>
        </div>
        <div class="confirm-modal-body">
          <p id="confirmModalMessage">Are you sure?</p>
        </div>
        <div class="confirm-modal-footer">
          <button class="confirm-btn confirm-btn-cancel" onclick="closeConfirmModal(false)">Cancel</button>
          <button class="confirm-btn confirm-btn-confirm" id="confirmModalButton"
            onclick="closeConfirmModal(true)">Confirm</button>
        </div>
      </div>
    </div>

    <audio id="newDiscountSound" src="./sounds/notification.mp3" preload="auto"></audio>
  </div>

  <script id="pending-discount-template" type="text/x-handlebars-template">
    {{#if requests}}
      <div class="requests-container">
        {{#each requests}}
          <div class="discount-request" data-request-id="{{id}}">

            <div class="alert-header">
              <img src="images/icons/bell.png" alt="" style="margin-right: 10px;">
              <h2 class="alert-title">Discount Request Alert</h2>
            </div>

            <div class="divider"></div>

            <div class="description">
              <p class="description-text">A guest at <span class="kiosk-number">Kiosk # {{register_no}}</span> has submitted a discount request.</p>
              <p class="description-text" style="font-weight: bold;">Please review the details.</p>

              <div class="customer-details">
                <div class="detail-row">
                  <span class="detail-label">Customer Name:</span>
                  <span class="detail-value">{{name}}</span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Customer ID:</span>
                  <span class="detail-value">{{discount_id}}</span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Date & Time:</span>
                  <span class="detail-value">{{formatDate datetime}}</span>
                </div>
              </div>
            </div>

            <div class="form-section">
              <div class="form-group">
                <label for="discount_type_{{id}}">Discount Type</label>
                <select name="discount_type" id="discount_type_{{id}}" class="form-select discount-type">
                  <option value="">Select Discount Type</option>
                  {{#each ../discounts}}
                    <option value="{{code}}">{{description}}</option>
                  {{/each}}
                </select>

                <select name="customer" id="customer_{{id}}" class="form-select customer-select">
                  <option value="">SELECT CUSTOMER</option>
                  {{#each ../customers}}
                    <option value="{{middle_initial}}">{{middle_initial}}</option>
                  {{/each}}
                </select>
              </div>

              <div class="button-container">
                <button type="button" class="generate-btn">
                  Are you sure?
                </button>
              </div>
            </div>
          </div>
        {{/each}}
      </div>
    {{else}}
      <div class="empty-state">
        <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
        </svg>
        <p class="empty-text">No pending discount requests.</p>
      </div>
    {{/if}}
  </script>

  <script>
    let pending_discount_requests = [];
    let customers = [];
    let discounts = [];
    let autoRefreshInterval = null;
    let resumeTimeout = null;
    let pauseTimeout = null;
    const INACTIVITY_LIMIT = 1000 * 30; // 30 seconds of inactivity

    let seenRequestIds = new Set();
    const newDiscountSound = document.getElementById('newDiscountSound');

    const discountCodeOverlay = document.getElementById('discountCodeOverlay');
    const containerPendingDiscountRequests = document.getElementById('container-pending-discount-requests');

    // Confirmation modal variables
    let confirmCallback = null;
    let confirmParams = null;
    const confirmModal = document.getElementById('confirmModal');

    Handlebars.registerHelper('formatDate', function(datetime) {
      return new Date(datetime).toLocaleString();
    });

    fetchData();
    startAutoRefresh();

    // Confirmation Modal Functions
    function showConfirmModal(title, message, confirmText, callback, params) {
      document.getElementById('confirmModalTitle').textContent = title;
      document.getElementById('confirmModalMessage').textContent = message;
      document.getElementById('confirmModalButton').textContent = confirmText;

      // Set button color based on action
      const confirmButton = document.getElementById('confirmModalButton');
      confirmButton.className = 'confirm-btn';
      if (confirmText.toLowerCase().includes('reject')) {
        confirmButton.classList.add('confirm-btn-reject');
      } else {
        confirmButton.classList.add('confirm-btn-confirm');
      }

      confirmCallback = callback;
      confirmParams = params;
      confirmModal.classList.add('show');
    }

    function closeConfirmModal(confirmed) {
      confirmModal.classList.remove('show');

      if (confirmed && confirmCallback) {
        confirmCallback(...confirmParams);
      } else {
        // If cancelled, restart auto refresh
        startAutoRefresh();
      }

      confirmCallback = null;
      confirmParams = null;
    }

    // Close modal when clicking outside
    confirmModal.addEventListener('click', function(e) {
      if (e.target === confirmModal) {
        closeConfirmModal(false);
      }
    });

    function pauseAutoRefresh() {
      if (pauseTimeout) clearTimeout(pauseTimeout);

      pauseTimeout = setTimeout(() => {
        console.log("Resuming auto-refresh after user interaction pause...");
        pauseTimeout = null;
      }, 10000);
    }

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
        if (!discountCodeOverlay.classList.contains('show') && !pauseTimeout && !confirmModal.classList
          .contains('show')) {
          console.log("Background refresh - checking for updates...");
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

      // Only update if content has actually changed
      if (containerPendingDiscountRequests.innerHTML !== html) {
        // Store current form values before updating
        const currentFormValues = {};
        containerPendingDiscountRequests.querySelectorAll('.discount-request').forEach(requestDiv => {
          const requestId = requestDiv.dataset.requestId;
          const discountSelect = requestDiv.querySelector('.discount-type');
          const customerSelect = requestDiv.querySelector('.customer-select');

          if (discountSelect && customerSelect) {
            currentFormValues[requestId] = {
              discountType: discountSelect.value,
              customer: customerSelect.value,
              customerVisible: (customerSelect.style.display === 'block' && discountSelect
                .value && discountSelect.value.toLowerCase() === "sp")
            };
          }
        });

        containerPendingDiscountRequests.innerHTML = html;

        // Add entrance animation only for completely new renders
        const cards = containerPendingDiscountRequests.querySelectorAll('.discount-request');
        const isFirstRender = Object.keys(currentFormValues).length === 0;

        if (isFirstRender) {
          cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
              card.style.transition = 'all 0.3s ease-out';
              card.style.opacity = '1';
              card.style.transform = 'translateY(0)';
            }, index * 100);
          });
        }

        // Restore form values and add event listeners
        containerPendingDiscountRequests.querySelectorAll('.discount-request').forEach(requestDiv => {
          const requestId = requestDiv.dataset.requestId;
          const generateBtn = requestDiv.querySelector('.generate-btn');
          const discountSelect = requestDiv.querySelector('.discount-type');
          const customerSelect = requestDiv.querySelector('.customer-select');

          // Restore previous form values if they existed
          if (currentFormValues[requestId]) {
            discountSelect.value = currentFormValues[requestId].discountType;
            customerSelect.value = currentFormValues[requestId].customer;
            // Only show customer select if discount type is "sp" AND it was previously visible
            if (currentFormValues[requestId].discountType &&
              currentFormValues[requestId].discountType.toLowerCase() === "sp" &&
              currentFormValues[requestId].customerVisible) {
              customerSelect.style.display = "block";
            } else {
              customerSelect.style.display = "none";
            }
          } else {
            // Ensure customer select is hidden by default for new requests
            customerSelect.style.display = "none";
          }

          // Add click listener to stop refresh temporarily when interacting
          requestDiv.addEventListener("click", (e) => {
            // Only stop refresh for form interactions, not the whole card
            if (e.target.tagName === 'SELECT' || e.target.tagName === 'BUTTON') {
              console.log("User interacting with form, pausing auto-refresh...");
              pauseAutoRefresh();
              newDiscountSound.pause();
              newDiscountSound.currentTime = 0;
            }
          });

          // Show/hide customer select based on discount type
          discountSelect.addEventListener('change', function() {
            pauseAutoRefresh(); // Pause while user is selecting
            console.log("Discount type selected:", this.value);
            if (this.value && this.value.toLowerCase() === "sp") {
              customerSelect.style.display = "block";
              console.log("Showing customer select for SP discount");
            } else {
              customerSelect.style.display = "none";
              customerSelect.value = ""; // Clear selection when hiding
              console.log("Hiding customer select for non-SP discount");
            }
          });

          generateBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            console.log('Generate button clicked for request:', requestId);

            const discountType = discountSelect.value;
            if (!discountType) {
              alert('Please select a discount type first.');
              return;
            }

            let customer = '';
            if (discountType.toLowerCase() === "sp") {
              customer = customerSelect.value;
              if (!customer) {
                alert('Please select a customer for SP discount.');
                return;
              }
            }
            handleGenerate(requestId, discountType, customer);
          });
        });
      }
    }

    function handleReject(requestId) {
      stopAutoRefresh();
      showConfirmModal(
        "Reject Request",
        "Are you sure you want to reject this discount request?",
        "Reject",
        function(requestId) {
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
        },
        [requestId]
      );
    }

    function handleGenerate(requestId, discountType, customer) {
      stopAutoRefresh();
      showConfirmModal(
        "Are you sure?",
        "Generate discount code for this request?",
        "Generate",
        function(requestId, discountType, customer) {
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
                console.error('Failed to generate code:', data.message);
                startAutoRefresh();
                return;
              }

              discountCodeOverlay.classList.add('show');
              discountCodeOverlay.querySelector(".code-text").innerText = data.data.code;

              setTimeout(() => {
                discountCodeOverlay.classList.remove('show');
                fetchData();
                startAutoRefresh();
              }, 1000 * 60 * 1);
            }).catch(err => {
              console.error('Error generating code:', err);
            });
        },
        [requestId, discountType, customer]
      );
    }

    function hideDiscountCodeOverlay() {
      discountCodeOverlay.classList.remove('show');
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