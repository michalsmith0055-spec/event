import { test, expect } from '@playwright/test';

test('login page renders', async ({ page }) => {
  await page.goto('http://localhost:5173');
  await expect(page.getByText('Affiliate Pin Publisher')).toBeVisible();
});
