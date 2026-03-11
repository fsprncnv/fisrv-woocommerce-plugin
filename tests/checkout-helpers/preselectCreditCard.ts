import { test, expect, Page } from '@playwright/test';

export async function preselectCreditCard(page: Page) {
    await page.getByRole('listitem').filter({ hasText: 'Credit / Debit Card' }).click();
    await page.getByRole('button', { name: 'Place order' }).click();
    await doCreditCardPayment(page);
    await page.waitForTimeout(20000);
    await expect(page).toHaveURL(/transaction_approved=true/);
}

async function doCreditCardPayment(page: Page) {
    await page.locator('[role="tablist"]').waitFor({ state: 'visible' });
    await page.locator('input[name="cardNumber"]').fill('4761 7390 0101 0010');
    await page.locator('input[name="expiryDate"]').fill('10/30');
    await page.locator('input[name="cvvNumber"]').fill('002');
    await page.locator('input[name="ccname"]').fill('John Doe');
    await page.locator('[appdataautomationid="payment_button"]').click();
}
