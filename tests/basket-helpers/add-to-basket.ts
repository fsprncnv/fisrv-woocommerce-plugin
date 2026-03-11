import { test, expect, Page } from '@playwright/test';

export async function addToBasket(page: Page) {
            await page.goto('http://localhost:8080/?post_type=product');
            await page.locator('button.add_to_cart_button').first().click();
            await page.waitForTimeout(1000);
            await page.getByRole('button', { name: 'Number of items in the cart:' }).click();
            await page.waitForTimeout(1000);
            await page.getByRole('link', { name: 'Go to checkout' }).click();
}