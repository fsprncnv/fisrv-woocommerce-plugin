import { test, expect } from "@playwright/test";

test.describe("Sucessful Deployment", () => {

  test("Verify Latest Deployment", async ({ page }) => {
	  await expect(async () => {
		  const response = await page.request.get(process.env.WP_URL);
		  console.log(response.headers()["x-commit-id"]);
		  expect(response.status()).toBe(200);
		  expect(response.headers()["x-commit-id"]).toContain(process.env.CI_COMMIT_SHORT_SHA ?? "test");
	  }).toPass({
          timeout: 120_000
	  });
  });
});
