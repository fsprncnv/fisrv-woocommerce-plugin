const restBasePath = '/fiserv_woocommerce_plugin/v1';

const checkoutInquiryInclusions = [
    'orderId',
    'subtotal',
    'shipping',
    'transactionType',
    'transactionStatus',
    'ipgTransactionId',
    'apiTraceId',
    'approvalCode',
    'terminalId',
    'merchantId',
    'schemeTransactionId',
    'referenceNumber',
    'paymentMethodType',
    'brand',
    'cardNumber'
];

function buildError(scope, error) {
    console.error(`Fiserv Plugin for Woocommerce: ${scope}`, error);
}

async function fsAddImage(button) {
    const input = document.getElementById("fs-icons-data").value;
    const gateway_id = button.getAttribute("gateway-id");
    button.innerHTML = "<span class='fs-loader-status'></span>";
    const data = await wp.apiFetch({
        method: "POST",
        path: `${restBasePath}/image?gateway-id=${gateway_id}&data=${input}`,
    });
    if (data["status"] === "ok") {
        button.innerHTML = "✓";
    } else {
        button.innerHTML = "🞭";
    }
    document.getElementById("fs-icons-data").value = '';
    document.getElementById("fs-icons-data").placeholder = data['message'];
}

async function removeImage(index, node) {
    const gateway_id = node.getAttribute("gateway-id");
    const overlay = document.getElementById(`fs-icon-overlay-${index}`);
    overlay.innerHTML = "<span class='fs-loader-status'></span>";
    await wp.apiFetch({
        method: "DELETE",
        path: `${restBasePath}/image?gateway-id=${gateway_id}&icon-id=${index}`
    });
    node.innerHTML = '';
}

async function fsFetchHealth(is_prod) {
    const indicator = document.getElementById("fs-status-indicator");
    const text = document.getElementById("fs-status-text");
    const fetchHealthBtn = document.getElementById("fs-health-btn");
    const readField = (key) => document.getElementById(`woocommerce_fiserv-gateway-generic_${key}`).value;
    fetchHealthBtn.innerHTML = "<span class='fs-loader-status'></span>";
    try {
        const data = await wp.apiFetch({
            method: "GET",
            path: `${restBasePath}/health?is_prod=${is_prod}&api_key=${readField('api_key')}&api_secret=${readField('api_secret')}&store_id=${readField('store_id')}`
        });
        text.innerHTML = data.message;
        if (data["status"] !== "ok") {
            indicator.style.background = "OrangeRed";
            fetchHealthBtn.innerHTML = "🞭";
        } else {
            indicator.style.background = "LightGreen";
            fetchHealthBtn.innerHTML = "✓";
        }
    } catch (error) {
        buildError("Failed health fetch", error);
        text.innerHTML = 'Could not retrieve API health';
        indicator.style.background = "OrangeRed";
        fetchHealthBtn.innerHTML = "🞭";
    }
}

async function fiservRestorePaymentSettings(gateway_id, wc_settings_data, button) {
    const settingsObject = JSON.parse(atob(wc_settings_data));
    for (let key in settingsObject) {
        let option = settingsObject[key];
        let node = document.getElementById(`woocommerce_${gateway_id}_${key}`);
        if (node && option['default'] && node.hasAttribute('value')) {
            node.value = option['default'];
        }
    }
    button.innerHTML = "Fields restored";
}

async function fetchCheckoutReport(checkout_id, button) {
    if (button.getAttribute("reported") === "true") {
        return;
    }
    button.setAttribute("reported", "true");
    const container = document.getElementById('fs-checkout-info-container');
    button.innerHTML = "<span class='fs-loader-status'></span>";
    try {
        const data = await wp.apiFetch({
            method: 'GET',
            path: `${restBasePath}/checkout-details?checkout-id=${checkout_id}`
        });
        if (data["status"] !== "ok") {
            button.innerHTML = "🞭 Sorry, something went wrong";
        } else {
            button.innerHTML = "✓";
            const report = JSON.parse(data['message']);
            renderObjectFields(report, container, '');
        }
    } catch (error) {
        buildError("Failed order info", error);
        button.innerHTML = "🞭 Sorry, couldn't retrieve order";
    }
}

let renderedKeys = [];

function renderObjectFields(report, container, currency) {
    for (const key in report) {
        if (renderedKeys.includes(key)) {
            continue;
        }
        let value = report[key];
        if (typeof value === 'object') {
            renderObjectFields(value, container, currency);
            continue;
        }
        if (key === 'currency') {
            currency = report[key];
        }
        if (!checkoutInquiryInclusions.includes(key)) {
            continue;
        }
        if (['total', 'subtotal', 'vatAmount', 'shipping'].includes(key)) {
            value = `${currency} ${value}`;
        }
        const capitalize = (word) => word.charAt(0).toUpperCase() + word.slice(1);
        renderedKeys.push(key);
        container.innerHTML += `
            <h4 stlye="margin-top: 8px;">${capitalize(key)}</h4>
            <span class="order-attribution-total-orders">${value}</span>
        `;
    }
}

async function fsCopyColor(color, node) {
    navigator.clipboard.writeText(color).then(() => {
        node.innerHTML = `Copied ${color}!`;
    });
}