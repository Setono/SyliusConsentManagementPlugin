# Asking again after a visitor refuses consent

The consent widget does not reappear on later visits for a visitor who refused consent. A refusal is remembered the same way as a consent.

## Why this is out of scope

Showing the widget again on every visit until a visitor consents wears down a refusal rather than asking for a free choice. Regulators expect a refusal to be respected for as long as a consent: the CNIL recommends keeping either choice for the same period, and treats 6 months as best practice before asking again. Consent obtained by asking repeatedly is also harder to defend as freely given under the GDPR.

The plugin aims to make refusing as easy as accepting (see #194, "Reject all"). Asking again after every refusal would undercut that.

Today, when a visitor submits the widget, `ConsentController::update` writes the `sscm_widget` cookie through `WidgetCookieManager`, whatever categories they chose. `CookieBasedWidgetDisplayDecider` hides the widget while that cookie is present, so a refusal and a consent are kept for the same time.

## What is supported instead

- **Ask every visitor again**: change `widget.cookie_value` in the plugin config.
- **Ask again when the consent categories change**: tracked in #192.
- **Configure how long the choice is kept**: the widget cookie lifetime, tracked in #199.
- **Let visitors change their mind themselves**: reopening the widget, tracked in #195.

## Prior requests

- #10: "Option to show consent dialog on each visit if no consent is given"
