import { test, expect } from '@playwright/test';
import { addToBasket, fillCustomerDetails } from './basket-helpers/basket-helpers';
import { preselectIDEAL, preselectCreditCard, selectGeneric} from './checkout-helpers/checkout-helpers';

test.describe('E2E Checkout Flow', () => {
  test('should complete the checkout process', async ({ page }) => {
    await addToBasket(page);
    await page.waitForTimeout(1000);
    await fillCustomerDetails(page);
    await page.waitForTimeout(1000);
    const paymentOption = process.env.PAYMENT_OPTION || 'ideal'; // Default to 'ideal'
    if (paymentOption === 'ideal') {
      await preselectIDEAL(page);
    } else if (paymentOption === 'credit_card') {
      await preselectCreditCard(page);
    } else if (paymentOption === 'generic') {
      await selectGeneric(page);
    } else {
      throw new Error(`Unknown payment option: ${paymentOption}`);
    }
  });
});