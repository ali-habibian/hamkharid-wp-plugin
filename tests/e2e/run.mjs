import assert from 'node:assert/strict';
import { mkdirSync } from 'node:fs';
import { createRequire } from 'node:module';
import { resolve } from 'node:path';
import { spawnSync } from 'node:child_process';
import { chromium } from 'playwright';

const require = createRequire(import.meta.url);
const axeSource = require('axe-core').source;

const headed = process.argv.includes('--headed');
const wordpressRoot = process.env.HAMKHARID_WP_ROOT;

if (!wordpressRoot) {
  console.error('Set HAMKHARID_WP_ROOT before running browser QA.');
  process.exit(1);
}

function fixture(action, argument = '') {
  const env = { ...process.env };
  const args = ['tests/e2e/fixture.php', action];
  if (argument) args.push(argument);

  const result = spawnSync('php', args, {
    cwd: process.cwd(),
    env,
    encoding: 'utf8',
  });

  if (result.status !== 0) {
    throw new Error(result.stderr.trim() || `Fixture ${action} failed.`);
  }

  return result.stdout.trim();
}

async function login(page, loginUrl, credentials, expectDashboard = true) {
  await page.goto(loginUrl, { waitUntil: 'domcontentloaded' });
  await page.locator('#user_login').fill(credentials.username);
  await page.locator('#user_pass').fill(credentials.password);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
    page.locator('#wp-submit').click(),
  ]);
  if (expectDashboard) {
    assert.match(page.url(), /\/wp-admin\//, 'WordPress login did not reach wp-admin.');
  } else {
    assert.doesNotMatch(page.url(), /\/wp-admin\//, 'A subscriber should not reach wp-admin after login.');
  }
}

async function columnCount(page) {
  return page.locator('.hamkharid-complements ul.products').evaluate((element) => {
    const template = getComputedStyle(element).gridTemplateColumns.trim();
    return template ? template.split(/\s+/).length : 0;
  });
}

async function expectedColumnCount(page) {
  return page.locator('.hamkharid-complements ul.products').evaluate((element) => {
    const style = getComputedStyle(element);
    const gap = Number.parseFloat(style.columnGap) || 0;
    const width = element.clientWidth;
    const itemCount = element.querySelectorAll(':scope > li.product').length;
    const capacity = Math.max(1, Math.floor((width + gap) / (220 + gap)));
    return Math.min(4, itemCount, capacity);
  });
}

async function textContrastRatio(locator) {
  return locator.evaluate((element) => {
    const parse = (value) => {
      const channels = value.match(/[\d.]+/g)?.map(Number) || [];
      return channels.length >= 3 ? channels.slice(0, 3) : null;
    };
    const luminance = (rgb) => {
      const channels = rgb.map((channel) => {
        const value = channel / 255;
        return value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
      });
      return channels[0] * 0.2126 + channels[1] * 0.7152 + channels[2] * 0.0722;
    };
    const foreground = parse(getComputedStyle(element).color);
    let backgroundNode = element;
    let background = [255, 255, 255];

    while (backgroundNode) {
      const style = getComputedStyle(backgroundNode);
      const channels = style.backgroundColor.match(/[\d.]+/g) || [];
      const alpha = channels.length > 3 ? Number(channels[3]) : 1;
      if (alpha > 0) {
        background = parse(style.backgroundColor) || background;
        break;
      }
      backgroundNode = backgroundNode.parentElement;
    }

    if (!foreground) return 0;
    const values = [luminance(foreground), luminance(background)].sort((a, b) => b - a);
    return (values[0] + 0.05) / (values[1] + 0.05);
  });
}

async function waitForText(locator, expected, timeout = 10000) {
  const deadline = Date.now() + timeout;
  let actual = '';

  while (Date.now() < deadline) {
    try {
      actual = (await locator.innerText({ timeout: 1000 })).trim();
      if (actual === expected) {
        return;
      }
    } catch {
      // Navigation may briefly detach the old document; retry against the same locator.
    }

    await new Promise((resolve) => setTimeout(resolve, 200));
  }

  assert.equal(actual, expected, `Timed out waiting for text: ${expected}`);
}

async function toggleRuleAndWait(page, ruleId, expectedLabel) {
  const row = page.locator(`#post-${ruleId}`);
  await row.waitFor();
  const toggleLink = row.locator('a[href*="action=hamkharid_toggle_rule"]');
  const href = (await toggleLink.getAttribute('href')) || '';
  assert.match(href, /action=hamkharid_toggle_rule/, 'Rule toggle action URL was not rendered.');
  assert.match(href, new RegExp(`(?:[?&])rule_id=${ruleId}(?:&|$)`), 'Rule toggle URL did not target the expected rule.');

  await page.goto(href, { waitUntil: 'domcontentloaded' });

  const statusCell = page.locator(`#post-${ruleId} .column-hamkharid_enabled`);
  await waitForText(statusCell, expectedLabel);
}

const results = [];
let state;
let browser;

async function check(ids, title, callback) {
  try {
    await callback();
    results.push({ ids, title, passed: true });
    console.log(`PASS ${ids}: ${title}`);
  } catch (error) {
    results.push({ ids, title, passed: false });
    console.error(`FAIL ${ids}: ${title}`);
    throw error;
  }
}

try {
  state = JSON.parse(fixture('setup'));
  browser = await chromium.launch({ headless: !headed });

  const adminContext = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  const adminPage = await adminContext.newPage();
  await login(adminPage, state.urls.login, state.admin);

  await check('QA-005, QA-006', 'Rule admin loads without Quick Edit or Bulk Edit', async () => {
    await adminPage.goto(state.urls.rules, { waitUntil: 'domcontentloaded' });
    assert.equal(await adminPage.locator('#the-list').count(), 1);
    assert.equal(await adminPage.locator('.row-actions .editinline').count(), 0);
    assert.equal(await adminPage.locator('select[name="action"] option[value="edit"]').count(), 0);
  });

  await check('QA-010–QA-016', 'Rule can be created with multi-source fields and persists', async () => {
    await adminPage.goto(state.urls.new_rule, { waitUntil: 'domcontentloaded' });
    await adminPage.locator('#title').fill(`Hamkharid E2E UI Rule ${state.run_id}`);
    await adminPage.locator('#hamkharid-source-categories').selectOption([
      String(state.ids.admin_source),
      String(state.ids.target_child),
    ]);
    await adminPage.locator('#hamkharid-target-category').selectOption(String(state.ids.target_parent));
    await adminPage.locator('#hamkharid-recommendation-count').fill('1');
    await adminPage.locator('#hamkharid-sort-strategy').selectOption('random');

    await Promise.all([
      adminPage.waitForURL(/post\.php\?post=\d+&action=edit/),
      adminPage.locator('#publish').click(),
    ]);

    const match = adminPage.url().match(/[?&]post=(\d+)/);
    assert.ok(match, 'Created rule ID was not present in the edit URL.');
    state.ui_rule_id = Number(match[1]);

    assert.equal(await adminPage.locator('#hamkharid-target-category').inputValue(), String(state.ids.target_parent));
    assert.equal(await adminPage.locator('#hamkharid-recommendation-count').inputValue(), '1');
    assert.equal(await adminPage.locator('#hamkharid-sort-strategy').inputValue(), 'random');

    const selectedOptions = await adminPage.locator('#hamkharid-source-categories option:checked').evaluateAll((options) => options.map((option) => option.value));
    assert.deepEqual(selectedOptions.sort(), [String(state.ids.admin_source), String(state.ids.target_child)].sort());
  });

  await check('QA-017–QA-019', 'Rule list values and Enable/Disable action are functional', async () => {
    await adminPage.goto(state.urls.rules, { waitUntil: 'domcontentloaded' });
    const row = adminPage.locator(`#post-${state.ui_rule_id}`);
    await row.waitFor();
    assert.equal((await row.locator('.column-hamkharid_count').innerText()).trim(), '1');

    await toggleRuleAndWait(adminPage, state.ui_rule_id, state.expected.disabled_label);
    await toggleRuleAndWait(adminPage, state.ui_rule_id, state.expected.enabled_label);
  });

  await check('QA-050–QA-057, QA-070, QA-071, QA-076', 'Frontend renders one localized, filtered recommendation section', async () => {
    await adminPage.goto(state.urls.current_product, { waitUntil: 'domcontentloaded' });
    const section = adminPage.locator('section.hamkharid-complements');
    assert.equal(await section.count(), 1);
    assert.equal((await section.locator(':scope > h2').innerText()).trim(), state.expected.heading);
    assert.equal(await section.locator('li.product').count(), 3);
    assert.equal(await section.locator(`li.post-${state.ids.current_product}`).count(), 0);
    assert.equal(await section.locator(`li.post-${state.ids.out_of_stock}`).count(), 0);
    assert.equal(await section.locator(`li.post-${state.ids.hidden_product}`).count(), 0);
    assert.equal(await section.locator(`li.post-${state.ids.child_product}`).count(), 1);
  });

  await check('QA-080, QA-083–QA-085', 'WooCommerce native Simple and Variable loop actions are preserved', async () => {
    const section = adminPage.locator('section.hamkharid-complements');
    const simpleButton = section.locator(`[data-product_id="${state.ids.simple_product}"]`);
    const variableButton = section.locator(`[data-product_id="${state.ids.variable_product}"]`);
    assert.equal(await simpleButton.count(), 1);
    assert.match((await simpleButton.getAttribute('class')) || '', /product_type_simple/);
    assert.match((await simpleButton.getAttribute('class')) || '', /add_to_cart_button/);
    assert.equal(await variableButton.count(), 1);
    assert.match((await variableButton.getAttribute('class')) || '', /product_type_variable/);
    assert.doesNotMatch((await variableButton.getAttribute('class')) || '', /ajax_add_to_cart/);
    assert.equal(await variableButton.getAttribute('href'), state.urls.variable_product);
    assert.equal(await section.locator('.variations_form').count(), 0);
  });

  await check('QA-081', 'Simple recommendation can reach the WooCommerce cart', async () => {
    const simpleSelector = `section.hamkharid-complements [data-product_id="${state.ids.simple_product}"]`;
    const simpleButton = adminPage.locator(simpleSelector);
    const addToCartHref = (await simpleButton.getAttribute('href')) || '';

    assert.notEqual(addToCartHref, '', 'Simple product Add-to-Cart URL was not rendered.');

    const addToCartUrl = new URL(addToCartHref, adminPage.url()).href;
    await adminPage.goto(addToCartUrl, { waitUntil: 'load' });
    await adminPage.goto(state.urls.cart, { waitUntil: 'load' });

    const cartProduct = adminPage.getByText(state.names.simple_product, { exact: false }).first();
    await cartProduct.waitFor({ state: 'visible', timeout: 10000 });
    assert.equal(await cartProduct.isVisible(), true);
  });

  await check('QA-032, QA-033, QA-145, QA-146', 'Disabling the matched rule removes and restoring it returns the section', async () => {
    await adminPage.goto(state.urls.rules, { waitUntil: 'domcontentloaded' });
    await toggleRuleAndWait(adminPage, state.ids.base_rule, state.expected.disabled_label);

    await adminPage.goto(state.urls.current_product, { waitUntil: 'domcontentloaded' });
    assert.equal(await adminPage.locator('section.hamkharid-complements').count(), 0);

    await adminPage.goto(state.urls.rules, { waitUntil: 'domcontentloaded' });
    await toggleRuleAndWait(adminPage, state.ids.base_rule, state.expected.enabled_label);

    await adminPage.goto(state.urls.current_product, { waitUntil: 'domcontentloaded' });
    assert.equal(await adminPage.locator('section.hamkharid-complements').count(), 1);
  });

  await adminPage.goto(state.urls.current_product, { waitUntil: 'domcontentloaded' });

  await check('QA-093', 'Desktop viewport fills columns according to component width', async () => {
    await adminPage.setViewportSize({ width: 1200, height: 900 });
    assert.equal(await columnCount(adminPage), await expectedColumnCount(adminPage));
  });

  await check('QA-094', 'Tablet viewport fills columns according to component width', async () => {
    await adminPage.setViewportSize({ width: 700, height: 900 });
    assert.equal(await columnCount(adminPage), await expectedColumnCount(adminPage));
  });

  await check('QA-095, QA-096', 'Mobile viewport uses one column without component overflow', async () => {
    await adminPage.setViewportSize({ width: 420, height: 900 });
    assert.equal(await columnCount(adminPage), await expectedColumnCount(adminPage));
    const overflowDetails = await adminPage.locator('section.hamkharid-complements').evaluate((element) => {
      const overflow = element.scrollWidth - element.clientWidth;
      const sectionRect = element.getBoundingClientRect();
      let offender = null;

      for (const child of element.querySelectorAll('*')) {
        const rect = child.getBoundingClientRect();
        const excess = Math.max(0, rect.right - sectionRect.right, sectionRect.left - rect.left);

        if (excess > (offender?.excess || 0)) {
          offender = {
            excess: Math.round(excess * 100) / 100,
            selector: `${child.tagName.toLowerCase()}${child.id ? `#${child.id}` : ''}${child.classList.length ? `.${[...child.classList].join('.')}` : ''}`,
          };
        }
      }

      return { overflow, offender };
    });

    assert.ok(
      overflowDetails.overflow <= 1,
      `Hamkharid section overflowed by ${overflowDetails.overflow}px${overflowDetails.offender ? `; widest offender: ${overflowDetails.offender.selector} (+${overflowDetails.offender.excess}px)` : ''}.`,
    );
  });

  await check('QA-097', 'A 320 CSS-pixel layout at 200% page zoom keeps the recommendation controls in view', async () => {
    await adminPage.setViewportSize({ width: 640, height: 1000 });
    await adminPage.evaluate(() => {
      document.documentElement.style.zoom = '2';
    });

    try {
      const section = adminPage.locator('section.hamkharid-complements');
      const sectionBounds = await section.evaluate((element) => {
        const rect = element.getBoundingClientRect();
        return {
          left: rect.left,
          right: rect.right,
          width: rect.width,
          viewportWidth: window.innerWidth,
          scrollWidth: element.scrollWidth,
          clientWidth: element.clientWidth,
        };
      });

      assert.ok(sectionBounds.width > 0, 'Recommendation section disappeared at 200% zoom.');
      assert.ok(sectionBounds.left >= -1 && sectionBounds.right <= sectionBounds.viewportWidth + 1, 'Recommendation section extends beyond the effective 320 CSS-pixel viewport.');
      assert.ok(sectionBounds.scrollWidth <= sectionBounds.clientWidth + 1, 'Recommendation section has horizontal overflow at 200% zoom.');
      assert.equal(await section.locator('a.button, button').first().isVisible(), true, 'The primary recommendation control is not visible at 200% zoom.');
    } finally {
      await adminPage.evaluate(() => {
        document.documentElement.style.zoom = '';
      });
      await adminPage.setViewportSize({ width: 1280, height: 900 });
    }
  });

  await check('QA-VISUAL', 'Capture repeatable desktop and mobile screenshots for visual review', async () => {
    const listStyle = await adminPage.locator('section.hamkharid-complements ul.products > li.product').first().evaluate((element) => getComputedStyle(element).listStyleType);
    assert.equal(listStyle, 'none', 'Recommendation product cards should not show theme list markers.');

    const artifactDirectory = resolve(process.env.HAMKHARID_E2E_ARTIFACTS || 'tests/e2e/artifacts');
    mkdirSync(artifactDirectory, { recursive: true });

    for (const width of [ 420, 700, 1200 ]) {
      await adminPage.setViewportSize({ width, height: 900 });
      await adminPage.screenshot({
        path: `${artifactDirectory}/hamkharid-${state.run_id}-${width}.png`,
        fullPage: true,
      });
    }
  });

  await check('QA-100–QA-103', 'Recommendation section has valid labelling and keyboard-focusable controls', async () => {
    const section = adminPage.locator('section.hamkharid-complements');
    const labelledBy = await section.getAttribute('aria-labelledby');
    assert.ok(labelledBy);
    assert.equal(await adminPage.locator(`#${labelledBy}`).count(), 1);

    const focusables = section.locator('a, button');
    assert.ok((await focusables.count()) > 0);
    assert.equal(await focusables.first().evaluate((element) => element.tabIndex >= 0), true);

    await focusables.first().focus();
    await adminPage.keyboard.press('Tab');
    const focusState = await adminPage.evaluate(() => {
      const active = document.activeElement;
      if (!(active instanceof HTMLElement) || !active.closest('.hamkharid-complements')) {
        return null;
      }
      const style = getComputedStyle(active);
      return { outlineStyle: style.outlineStyle, outlineWidth: style.outlineWidth };
    });
    assert.ok(focusState, 'Keyboard focus left the recommendation section unexpectedly.');
    assert.notEqual(focusState.outlineStyle, 'none');
    assert.notEqual(focusState.outlineWidth, '0px');

    const productTitle = section.locator('li.product .woocommerce-loop-product__title').first();
    const contrast = await textContrastRatio(productTitle);
    assert.ok(contrast >= 4.5, `Product title text contrast is ${contrast.toFixed(2)}:1; expected at least 4.5:1.`);

    const productPrice = section.locator('li.product .price bdi').first();
    const priceContrast = await textContrastRatio(productPrice);
    assert.ok(priceContrast >= 4.5, `Product price contrast is ${priceContrast.toFixed(2)}:1; expected at least 4.5:1.`);

    const addToCartButton = section.locator('li.product a.button').first();
    const buttonContrast = await textContrastRatio(addToCartButton);
    assert.ok(buttonContrast >= 4.5, `Add-to-Cart button contrast is ${buttonContrast.toFixed(2)}:1; expected at least 4.5:1.`);
  });

  await check('QA-104', 'Automated WCAG 2.2 A/AA audit reports no violations inside the recommendation section', async () => {
    await adminPage.addScriptTag({ content: axeSource });
    const result = await adminPage.evaluate(async () => window.axe.run(
      document.querySelector('section.hamkharid-complements'),
      { runOnly: { type: 'tag', values: [ 'wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22a', 'wcag22aa' ] } },
    ));

    assert.deepEqual(
      result.violations.map((violation) => ({
        id: violation.id,
        impact: violation.impact,
        nodes: violation.nodes.map((node) => ({ target: node.target, summary: node.failureSummary })),
      })),
      [],
      'The recommendation section has automated WCAG A/AA violations.',
    );
  });

  await check('QA-130, QA-131', 'Administrator can manage rules while Subscriber cannot', async () => {
    const subscriberContext = await browser.newContext({ viewport: { width: 1200, height: 800 } });
    const subscriberPage = await subscriberContext.newPage();
    await login(subscriberPage, state.urls.login, state.subscriber, false);
    const response = await subscriberPage.goto(state.urls.rules, { waitUntil: 'domcontentloaded' });
    const status = response ? response.status() : 0;
    const hasRuleTable = await subscriberPage.locator('#the-list').count();
    assert.equal(status === 403 || hasRuleTable === 0, true);
    await subscriberContext.close();
  });

  await check('QA-130', 'Shop Manager with WooCommerce capability can manage rules and settings', async () => {
    const shopManagerContext = await browser.newContext({ viewport: { width: 1200, height: 800 } });
    const shopManagerPage = await shopManagerContext.newPage();
    await login(shopManagerPage, state.urls.login, state.shop_manager);

    const rulesResponse = await shopManagerPage.goto(state.urls.rules, { waitUntil: 'domcontentloaded' });
    assert.equal(rulesResponse?.status(), 200);
    assert.ok(await shopManagerPage.locator('#the-list').count() > 0);

    const settingsResponse = await shopManagerPage.goto(state.urls.settings, { waitUntil: 'domcontentloaded' });
    assert.equal(settingsResponse?.status(), 200);
    assert.ok(await shopManagerPage.locator('form[action*="admin-post.php"] select[name="hamkharid_display_settings[mode]"]').count() > 0);
    await shopManagerContext.close();
  });

  await check('QA-205', 'Manual mode disables automatic output while shortcode and dynamic block render on pages', async () => {
    await adminPage.goto(state.urls.settings, { waitUntil: 'domcontentloaded' });
    await adminPage.locator('form[action*="admin-post.php"] select[name="hamkharid_display_settings[mode]"]').selectOption('manual');
    await Promise.all([
      adminPage.waitForURL(/settings-updated=true/),
      adminPage.locator('form[action*="admin-post.php"] input[type="submit"]').click(),
    ]);

    await adminPage.goto(state.urls.settings, { waitUntil: 'domcontentloaded' });
    await adminPage.locator('#hamkharid-preview-product-search').fill(`Hamkharid E2E Current ${state.run_id}`);
    const productChoice = adminPage.locator('#hamkharid-preview-product-results button').filter({ hasText: state.run_id }).first();
    await productChoice.waitFor();
    await productChoice.click();
    assert.equal(await adminPage.locator('#hamkharid-preview-product-id').inputValue(), String(state.ids.current_product));
    await Promise.all([
      adminPage.waitForURL(new RegExp(`hamkharid_preview_product_id=${state.ids.current_product}`)),
      adminPage.locator('form[action*="admin.php"] input[type="submit"]').click(),
    ]);
    const previewText = await adminPage.locator('.hamkharid-preview').innerText();
    assert.match(previewText, new RegExp(state.run_id));
    assert.ok((await adminPage.locator('.hamkharid-preview-products li').count()) > 0);

    await adminPage.goto(state.urls.current_product, { waitUntil: 'domcontentloaded' });
    assert.equal(await adminPage.locator('section.hamkharid-complements').count(), 0);

    await adminPage.goto(state.urls.shortcode_page, { waitUntil: 'domcontentloaded' });
    assert.equal(await adminPage.locator('section.hamkharid-complements').count(), 1);

    await adminPage.goto(state.urls.block_page, { waitUntil: 'domcontentloaded' });
    assert.equal(await adminPage.locator('section.hamkharid-complements').count(), 1);

    await adminPage.goto(state.urls.block_editor, { waitUntil: 'domcontentloaded' });
    await adminPage.waitForFunction(() => {
      return Boolean(
        window.wp?.blocks?.getBlockType('hamkharid/complements')
        && window.wp?.data?.select('core/editor')?.getBlocks
      );
    });
    const editorBlockTypes = await adminPage.evaluate(() => {
      return window.wp.data.select('core/editor').getBlocks().map((block) => block.name);
    });
    assert.ok(
      editorBlockTypes.includes('hamkharid/complements'),
      `The saved page did not load the registered Hamkharid block in the editor: ${editorBlockTypes.join(', ')}`
    );
    assert.equal(
      await adminPage.evaluate(() => Boolean(window.wp?.blocks?.getBlockType('hamkharid/complements'))),
      true,
      'The dynamic block editor script did not register the Hamkharid block.'
    );

    fixture('switch-theme', 'twentytwentyfive');
    await adminPage.goto(state.urls.shortcode_page, { waitUntil: 'domcontentloaded' });
    assert.equal(await adminPage.locator('section.hamkharid-complements').count(), 1);
    await adminPage.goto(state.urls.block_page, { waitUntil: 'domcontentloaded' });
    assert.equal(await adminPage.locator('section.hamkharid-complements').count(), 1);
    fixture('switch-theme', state.theme_template);

    const localePage = await browser.newPage({ viewport: { width: 420, height: 900 } });
    fixture('switch-locale', 'en_US');
    await localePage.goto(state.urls.shortcode_page, { waitUntil: 'domcontentloaded' });
    assert.equal(await localePage.locator('html').evaluate((element) => element.getAttribute('dir') || getComputedStyle(element).direction), 'ltr');
    assert.equal((await localePage.locator('section.hamkharid-complements > h2').innerText()).trim(), 'Complete your purchase');

    fixture('switch-locale', 'fa_IR');
    await localePage.goto(state.urls.block_page, { waitUntil: 'domcontentloaded' });
    assert.equal(await localePage.locator('html').evaluate((element) => element.getAttribute('dir') || getComputedStyle(element).direction), 'rtl');
    assert.equal((await localePage.locator('section.hamkharid-complements > h2').innerText()).trim(), 'خریدتان را کامل کنید');
    await localePage.addScriptTag({ content: axeSource });
    const rtlAudit = await localePage.evaluate(async () => window.axe.run(
      document.querySelector('section.hamkharid-complements'),
      { runOnly: { type: 'tag', values: [ 'wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22a', 'wcag22aa' ] } },
    ));
    assert.deepEqual(
      rtlAudit.violations.map((violation) => ({
        id: violation.id,
        impact: violation.impact,
        nodes: violation.nodes.map((node) => ({ target: node.target, summary: node.failureSummary })),
      })),
      [],
      'The Persian RTL recommendation section has automated WCAG A/AA violations.',
    );
    await localePage.close();

    fixture('switch-locale', state.locale);

    await adminPage.goto(state.urls.settings, { waitUntil: 'domcontentloaded' });
    await adminPage.locator('form[action*="admin-post.php"] select[name="hamkharid_display_settings[mode]"]').selectOption('automatic');
    await Promise.all([
      adminPage.waitForURL(/settings-updated=true/),
      adminPage.locator('form[action*="admin-post.php"] input[type="submit"]').click(),
    ]);
  });

  await adminContext.close();

  const passed = results.filter((result) => result.passed).length;
  console.log(`Browser QA passed: ${passed}/${results.length} smoke checks.`);
} catch (error) {
  console.error(error instanceof Error ? error.stack : String(error));
  process.exitCode = 1;
} finally {
  if (browser) {
    await browser.close();
  }

  if (state) {
    console.log(`Browser QA fixtures retained for visual review (run ${state.run_id}).`);
    console.log(`Rule admin: ${state.urls.rules}`);
    console.log(`Product page: ${state.urls.current_product}`);
    console.log('When visual QA is finished, remove fixtures with: npm run e2e:cleanup');
  }
}
