import { test, expect } from "@playwright/test";

test.describe("Sucessful Deployment", () => {

  test("Verify Latest Deployment", async ({ page }) => {
	  await expect(async () => {
		  const response = await page.request.get(process.env.WP_URL);
		  expect(response.status()).toBe(200);
		  expect(response.headers()["x-commit"]).toContain(process.env.CI_COMMIT_SHA ?? "test");
	  }).toPass({
          timeout: 120_000
	  });
  });
});
