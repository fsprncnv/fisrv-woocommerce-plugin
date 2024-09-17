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

    console.log(`Removing icon of index ${index} with node ${node.id}`);

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