import { test, expect, Page } from '@playwright/test';
import dotenv from 'dotenv';

// ADMIN ENV CONFIG

// how do i get the plugin (marketplace, zip file)

async function restartWoocommercePlugin(page: Page) {
  if (await page.locator('#activate-woocommerce').isVisible()) {
    await page.locator('#activate-woocommerce').click();
    return;
  }

  await page.goto('/wp-admin/plugins.php');
  await page.locator('#deactivate-woocommerce').click();
  await page.waitForLoadState('load');
  await page.locator('#activate-woocommerce').click();
}

test('Setup plugin and failed API health check due to bad API key (fail flow)', async ({
  page,
}) => {});

test('Setup plugin and successful API health check (success flow)', async ({
  page,
}) => {});

test('Exclusively on enable either generic method or one or more specific method', async ({
  page,
}) => {});

// WP WEB SHOP

test('Web shop data (localization based on WP-admin, cart items) are properly passed to redirect page', async ({
  page,
}) => {
  // wp-admin set global language to german
});

test('Failed order due to payment validation failure', async ({ page }) => {});

test.describe('Successful order and partial refund', () => {
  let page: Page;
  let orderNumber: string;

  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage();
    // await authenticate(page);
    // await restartWoocommercePlugin(page);
  });

  test('00. Woocommerce and Fiserv plugin are installed and activated properly', async ({
    page,
  }) => {
    //await expect(page.locator('#deactivate-woocommerce')).toBeVisible();
  });

  test('01. Fill cart and fill in billing info in guest session', async ({
    page,
  }) => {
    // await page.goto('/?add-to-cart=12');
    // await page.goto('/checkout');
    // await fillOutBillingFormOnStore(page);
  });

  test('02. Select payment, successful redirect to hosted checkout page and redirect back to thank you page', async ({
    page,
  }) => {
    // orderNumber = await createSuccessfulOrder(page);
  });

  test('04. Order notes created', async ({ page }) => {
    // await page.goto(
    //   `/wp-admin/admin.php?page=wc-orders&action=edit&id=${orderNumber}`
    // );
    // await expect(
    //   page.getByText(/Fiserv checkout link, [A-Za-z]+$/i)
    // ).toBeVisible();
  });

  test('05. Refund order', async ({ page }) => {});
});

test('Autocomplete sets order status to complete', async ({ page }) => {});

test('Checkout details report box on order page', async ({ page }) => {});

test('Change generic payment icon and revert back', async ({ page }) => {});

async function fillOutBillingFormOnStore(page: Page) {
  await page.locator('#billing_first_name').fill('Frodo');
  await page.locator('#billing_last_name').fill('Franklin');
  await page.locator('#billing_address_1').fill('Street');
  await page.locator('#billing_postcode').fill('12345');
  await page.locator('#billing_city').fill('City');
  await page.locator('#billing_phone').fill('0123456');
  await page.locator('#billing_email').fill('dev@mail-playwright-12345.io');
}

async function fillOutPaymentFormOnHostedPaymentPage(
  page: Page,
  ccNumber: string
) {
  await page.locator("input[name='cardNumber']").fill(ccNumber);
  await page.locator("input[name='expiryDate']").fill('0129');
  await page.locator("input[name='cvvNumber']").fill('123');
  await page.locator("input[name='ccname']").fill('Frodo Franklin');
}

async function createSuccessfulOrder(page: Page): Promise<any> {
  if (await page.locator('#payment_method_fisrv-gateway-generic').isVisible()) {
    await page.locator('#payment_method_fisrv-gateway-generic').click();
  }

  await page.locator('#place_order').click();

  await page.waitForURL('https://ci.checkout-lane.com/#/?checkoutId=**');

  await fillOutPaymentFormOnHostedPaymentPage(page, '5424180279791732');

  await page.locator("[appdataautomationid='payment_button']").click();

  await page.waitForURL(
    'http://fisrv-plugin-dev.com/checkout/order-received/**'
  );

  //await expect(
  //  page.locator("[data-block-name='woocommerce/order-confirmation-status']")
  //).toBeVisible();

  return page
    .locator('.wc-block-order-confirmation-summary-list-item__value')
    .first()
    .innerHTML();
}

async function authenticate(page: Page) {
  await page.goto('/wp-admin');

  if (page.url().match(/.*wp-login.*$/)) {
    await page.locator('#user_login').fill('admin');
    await page
      .locator('#user_pass')
      .fill(process.env.WP_PASSWORD ?? 'password');

    await page.getByRole('button', { name: 'Log In' }).click();
    await page.waitForURL(/.*wp-admin.*$/);
  }
}
