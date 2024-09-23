async function addImage() {
    const btn = document.getElementById("fs-icon-btn");
    const input = document.getElementById("fs-icons-data").value;
    const gateway_id = btn.getAttribute("gatewayid");

    btn.innerHTML = "<span class='fs-loader-status'></span>";

    const res = await fetch(`/wp-json/fisrv_woocommerce_plugin/v1/image?gateway-id=${gateway_id}&data=${input}`, {
        method: "POST",
    });
    const data = await res.json();

    if (data["status"] === "ok") {
        btn.innerHTML = "✓";
    }
}

async function removeImage(index, node) {
    const gateway_id = node.getAttribute("gateway-id");
    const overlay = document.getElementById(`fs-icon-overlay-${index}`);
    overlay.innerHTML = "<span class='fs-loader-status'></span>";

    const res = await fetch(`/wp-json/fisrv_woocommerce_plugin/v1/image?gateway-id=${gateway_id}&icon-id=${index}`, {
        method: "DELETE",
    });
    const data = await res.json();

    node.innerHTML = '';
}

async function fetchHealth() {
    const indicator = document.getElementById("fs-status-indicator");
    const text = document.getElementById("fs-status-text");
    const fetchHealthBtn = document.getElementById("fs-health-btn");

    fetchHealthBtn.innerHTML = "<span class='fs-loader-status'></span>";
    const res = await fetch("/wp-json/fisrv_woocommerce_plugin/v1/health", {
        method: "GET",
    });
    const data = await res.json();
    text.innerHTML = data["message"];

    if (data["status"] !== "ok") {
        indicator.style.background = "OrangeRed";
        fetchHealthBtn.innerHTML = "🞭";
    } else {
        indicator.style.background = "LightGreen";
        fetchHealthBtn.innerHTML = "✓";
    }
}

async function fisrvRestorePaymentSettings(gateway_id, button) {
    button.innerHTML = "<span class='fs-loader-status'></span>";
    const res = await fetch(`/wp-json/fisrv_woocommerce_plugin/v1/restore-settings?gateway-id=${gateway_id}`, {
        method: "GET",
    });
    const data = await res.json();
    button.innerHTML = "Restored";
}

async function fetchCheckoutReport(checkout_id, button) {
    if (button.getAttribute("reported") === "true") {
        return;
    }

    button.setAttribute("reported", "true");

    const container = document.getElementById('fs-checkout-info-container');
    button.innerHTML = "<span class='fs-loader-status'></span>";

    const res = await fetch(`/wp-json/fisrv_woocommerce_plugin/v1/checkout-details?checkout-id=${checkout_id}`, {
        method: "GET",
    });
    const data = await res.json();

    if (data["status"] !== "ok") {
        button.innerHTML = "🞭 Sorry, something went wrong";
    } else {
        button.innerHTML = "✓";
        const report = JSON.parse(data['message']);
        renderObjectFields(report, container, '');
    }
}

function renderObjectFields(report, container, currency) {
    for (let key in report) {
        if (key === 'currency') {
            currency = report[key];
        }

        if (['traceId', 'httpCode', 'checkoutId', 'orderId', 'requestSent', 'month', 'year', 'currency'].includes(key)) {
            continue;
        }

        let value = report[key];

        if (['total', 'subtotal', 'vatAmount', 'shipping'].includes(key)) {
            value = `${currency} ${value}`;
        }

        if (typeof value === 'object') {
            renderObjectFields(value, container, currency);
            continue;
        }

        const capitalize = (word) => word.charAt(0).toUpperCase() + word.slice(1);

        container.innerHTML += `
            <h4 stlye="margin-top: 8px;">${capitalize(key)}</h4>
            <span class="order-attribution-total-orders">${value}</span>
        `;
    }
}