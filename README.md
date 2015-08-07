# magento-patches-finder
Shows all the patches needed for all magento versions

Use it here:
http://fabrizioballiano.net/magento-patches

The "download" file it looks for it's prepared by a cron script I've running on my server, which is simply a wget of the magento's official download page (http://magentocommerce.com/download), I keep it just for performance reasons.

TODO:
- recreate the direct download for the patches (can't host patches or distribute them, it's against Magento's license) and I don't want to use some sort of proxy system
- check if actually some patches are needed (for example it seems 1.9.2.1 needs some of them, cause they're marked "1.9.x" and they're not in the "comes with bundled patches" section of the release notes)
