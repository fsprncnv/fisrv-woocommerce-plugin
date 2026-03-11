import { test, expect, Page } from '@playwright/test';

export async function preselectIDEAL(page: Page) {
    await page.getByRole('listitem').filter({ hasText: 'iDEAL' }).click();
    await page.getByRole('button', { name: 'Place order' }).click();
    await page.getByTestId('payment-action-button').click();
    await page.getByRole('button', { name: 'TESTNL2A' }).click();
    await page.getByRole('button', { name: 'Success' }).click();
    await page.waitForTimeout(15000);
    await expect(page).toHaveURL(/transaction_approved=true/);
}