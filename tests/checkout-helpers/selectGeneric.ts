import { test, expect, Page } from '@playwright/test';

export async function selectGeneric(page: Page) {
    await page.getByRole('listitem').filter({ hasText: 'Generic Checkout' }).click();
    await page.getByRole('button', { name: 'Place order' }).click();
    await page.locator('[role="tablist"]').waitFor({ state: 'visible' });
    await page.locator('[appdataautomationid="creditcard_payment_tab"]').click();
    await genericSelectCreditCardPayment(page);    
    await page.waitForTimeout(20000);
    await expect(page).toHaveURL(/transaction_approved=true/);
}

async function genericSelectCreditCardPayment(page: Page) {
    // wait for app-card-payment-methods .payment-method-card .card-placeholder to be ready then click it
    await page.locator('app-card-payment-methods .payment-method-card .card-placeholder').waitFor({ state: 'visible' });
    await page.locator('app-card-payment-methods .payment-method-card .card-placeholder').click();
    await page.locator('input[name="cardNumber"]').fill('4761 7390 0101 0010');
    await page.locator('input[name="expiryDate"]').fill('10/30');
    await page.locator('input[name="cvvNumber"]').fill('002');
    await page.locator('input[name="ccname"]').fill('John Doe');
    await page.locator('[appdataautomationid="payment_button"]').click();
}