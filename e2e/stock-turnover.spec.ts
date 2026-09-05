import { test, expect } from '@playwright/test';

test.describe('Stock Turnover Module', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/');
    });

    test('shows stock turnover metrics page', async ({ page }) => {
        await expect(page.locator('h1')).toContainText('Stock Turnover');
    });

    test('displays days of inventory for items', async ({ page }) => {
        await page.goto('/modules/ksf_FA_StockTurnover/');
        const doiColumn = page.locator('text=Days of Inventory');
        await expect(doiColumn).toBeVisible();
    });
});