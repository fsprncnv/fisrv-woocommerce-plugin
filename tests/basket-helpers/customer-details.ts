import { test, expect, Page } from '@playwright/test';

export async function fillCustomerDetails(page: Page) {
    await page.getByRole('textbox', { name: 'First name' }).fill('John');
    await page.getByRole('textbox', { name: 'Last name' }).fill('Doe');
    await page.locator('#select2-billing_country-container').click();
    await page.locator('.select2-results__option').filter({ hasText: 'France' }).click();
    await page.getByRole('textbox', { name: 'Street address' }).fill('France House');
    await page.getByRole('textbox', { name: 'Apartment, suite, unit, etc' }).fill('France St');
    await page.getByRole('textbox', { name: 'Postcode / ZIP' }).fill('12345');
    await page.getByRole('textbox', { name: 'Town / City' }).fill('FranceTown');
    await page.getByRole('textbox', { name: 'Email address' }).fill('john@whatever.com');
}