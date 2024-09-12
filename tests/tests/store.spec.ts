import { test, expect, Page } from '@playwright/test';

const STORE_HOST = 'http://fisrv-plugin-dev.com/';

test.beforeEach(async ({ page }) => {
  await page.goto(STORE_HOST + '/?add-to-cart=12');
});

// ADMIN ENV CONFIG

// how do i get the plugin (marketplace, zip file)

test('Woocommerce and Fiserv plugin are installed and activated properly', async ({
  page,
}) => {});

test('Setup plugin and failed API health check due to bad API key (fail flow)', async ({
  page,
}) => {});

test('Setup plugin and successful API health check (success flow)', async ({
  page,
}) => {});

test('Enable generic option and check specific method should be disabled', async ({
  page,
}) => {});

// ... enable specific (apple pay) and disable generic


// WP WEB SHOP 

test('Web shop data (localization based on WP-admin, cart items) are properly passed to redirect page', async ({
  // wp-admin set global language to german
  page,
}) => {});

test('Failed order due to payment validation failure', async ({ page }) => {});

test('Successful order', async ({ page }) => {
  await page.goto(STORE_HOST + '/checkout');

  await fillOutWCCheckoutForm(page);

  await page.locator('#payment_method_fisrv-gateway-generic').click();

  await page.locator('#place_order').click();

  await page.waitForURL('https://ci.checkout-lane.com/#/?checkoutId=**');

  // await expect(
  //   page.getByRole('heading', { name: 'Überprüfen und Bezahlen' })
  // ).toBeVisible();

  await fillOutHostedPaymentPageForm(page, '5424180279791732');

  await page.locator("[appdataautomationid='payment_button']").click();

  await page.waitForURL(
    'http://fisrv-plugin-dev.com/checkout/order-received/**'
  );

  await expect(
    page.locator("[data-block-name='woocommerce/order-confirmation-status']")
  ).toBeVisible();
});

test('Order notes created', async ({ page }) => {});

// get full response report from fiserv server

test('Autocomplete sets order status to complete', async ({ page }) => {});

test('Refund order', async ({ page }) => {});

async function fillOutWCCheckoutForm(page: Page) {
  await page.locator('#billing_first_name').fill('Frodo');
  await page.locator('#billing_last_name').fill('Franklin');
  await page.locator('#billing_address_1').fill('Street');
  await page.locator('#billing_postcode').fill('12345');
  await page.locator('#billing_city').fill('City');
  await page.locator('#billing_phone').fill('0123456');
  await page.locator('#billing_email').fill('dev@mail-playwright-12345.io');
}

async function fillOutHostedPaymentPageForm(page: Page, ccNumber: string) {
  await page.locator("input[name='cardNumber']").fill(ccNumber);
  await page.locator("input[name='expiryDate']").fill('0129');
  await page.locator("input[name='cvvNumber']").fill('123');
  await page.locator("input[name='ccname']").fill('Frodo Franklin');
}
