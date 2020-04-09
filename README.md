# Magento Patches Finder
Shows all the patches needed for all magento versions

## Update 2020
Magento 1 is reaching EOL, with Magento 2 everything is different thus this project doesn't need to exist anymore, the repo will be archived as read only.

## Original documentation

Use it here:
http://fabrizioballiano.net/magento-patches

The "download" file it looks for it's automatically downloaded and saved by the "index.php" script every hour, I keep it just for performance reasons.

TODO:
- recreate the direct download for the patches (can't host patches or distribute them, it's against Magento's license) and I don't want to use some sort of proxy system
- check if actually some patches are needed (for example it seems 1.9.2.1 needs some of them, cause they're marked "1.9.x" and they're not in the "comes with bundled patches" section of the release notes)
