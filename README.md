# Sylius Consent Management Plugin

[![Build Status][ico-github-actions]][link-github-actions]

This plugin is based on the guidelines in this document: https://gdpr.eu/cookies

## Development

### Behat
1. Create an alias like: `chrome: aliased to /Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome`
2. Run `chrome --enable-automation --disable-background-networking --no-default-browser-check --no-first-run --disable-popup-blocking --disable-default-apps --allow-insecure-localhost --disable-translate --disable-extensions --no-sandbox --enable-features=Metal --headless --remote-debugging-port=9222 --window-size=2880,1800 --proxy-server='direct://' --proxy-bypass-list='*' http://127.0.0.1`
3. Run `symfony server:start --port=8080 --dir=public --daemon`
4. Run `vendor/bin/behat --strict -vvv`

[ico-github-actions]: https://github.com/Setono/SyliusConsentManagementPlugin/workflows/build/badge.svg

[link-github-actions]: https://github.com/Setono/SyliusConsentManagementPlugin/actions
