# Magento 2 Page Builder requirejs-config fix

Fixes an issue with the page builder render iframe, where the requirejs-config.js file is prevented from loading due to CORS.

See: https://github.com/magento/magento2/issues/39076#issuecomment-2445353497 for an explanation of the cause of this.

To fix the issue, we move the "requirejs-config.js" file back into the normal js asset group using a block.
This prevents the integrity and crossorigin attributes from being applied to the asset, and allows the fix to be applied only where needed, like on specific pages.

Currently only tested on open source 2.4.6-p6
