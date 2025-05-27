import { test, expect } from "@playwright/test";

test.describe("Sucessful Deployment", () => {

  test("Verify Latest Deployment", async () => {
	  await expect(async () => {
		  const response = await page.request.get('https://api.example.com');
		  expect(response.status()).toBe(200);
		  expect(response.headers()["x-commit"]).toContain(process.env.CI_COMMIT_SHA ?? "test");
	  }).toPass({
          timeout: 120_000
	  });
  });
});
