import { test, expect, Page } from '@playwright/test';

const STORE_HOST = 'http://fisrv-plugin-dev.com/';

test.beforeEach(async ({ page }) => {
  await page.goto(STORE_HOST + '/?add-to-cart=12');
});

test('Successful order', async ({ page }) => {
  await page.goto(STORE_HOST + '/checkout');

  await fillOutWCCheckoutForm(page);

  await page.locator('#place_order').click();

  await page.waitForURL('https://ci.checkout-lane.com/#/?checkoutId=**');

  await expect(
    page.getByRole('heading', { name: 'Überprüfen und Bezahlen' })
  ).toBeVisible();

  await fillOutHostedPaymentPageForm(page, '5424180279791732');

  await page.locator("[appdataautomationid='payment_button']").click();

  await page.waitForURL(
    'http://fisrv-plugin-dev.com/checkout/order-received/**'
  );

  await expect(
    page.getByRole('heading', { name: 'Bestellung erhalten' })
  ).toBeVisible();
});

test('Failed order due to bad credit card number', async ({ page }) => {
  await page.goto(STORE_HOST + '/checkout');

  await fillOutWCCheckoutForm(page);

  await page.locator('#place_order').click();

  await page.waitForURL('https://ci.checkout-lane.com/#/?checkoutId=**');

  await expect(
    page.getByRole('heading', { name: 'Überprüfen und Bezahlen' })
  ).toBeVisible();

  await fillOutHostedPaymentPageForm(page, '4182917993774394');

  await page.locator("[appdataautomationid='payment_button']").click();
  await page.waitForURL('https://ci.checkout-lane.com/#/?checkoutId=**');
  await page.locator("[appdataautomationid='payment_button']").click();
  await page.waitForURL('https://ci.checkout-lane.com/#/?checkoutId=**');
  await page.locator("[appdataautomationid='payment_button']").click();

  await page.waitForURL(
    'http://fisrv-plugin-dev.com/checkout/order-received/**'
  );

  await expect(
    page.getByRole('heading', { name: 'Bestellung erhalten' })
  ).toBeVisible();
});

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
