## Changelog
v 1.1.3
- [Fix] - Payment improvements.

v 1.1.2
- [Feature] - Subscription panel on the product page now auto-expands when a Frisbii plan is already assigned.
- [Fix] - Payment improvements.
- [Fix] - Removed unrelated settings (API key, status, self-service, debug mode, Save button) from the embedded config form on the product page.
- [Fix] - Fixed broken Frisbii Subscription layout styling on the product edit page.
- [Fix] - Fixed subscription plans failing to load from Frisbii on the product form.
- [Fix] - Fixed "array offset on int" warning in getContent.php when Frisbii Billing and Frisbii Pay are both enabled, caused by hardcoded language variables conflicting with HelperForm's own.
- [Docs] - Updated Readme with detailed step-by-step installation guide for Prestashop.