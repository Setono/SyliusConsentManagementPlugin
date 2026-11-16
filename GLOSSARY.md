# Consent management

How a Sylius store asks visitors for cookie consent, records their choices, and keeps track of the cookies it sets.

## Language

### Consent

**Necessary category**:
A category whose cookies are always allowed, and which a visitor can't refuse.
_Avoid_: Essential category, required category

**Default consent**:
The categories a visitor is treated as having consented to before making a choice. There are none unless the store configures some.
_Avoid_: Implicit consent, preset consent

### Cookies

**Crawl**:
A visit to the store's pages by a browser that has given no consent, made to discover the cookies the store sets.
_Avoid_: Scan, audit

**Pre-consent cookie**:
A cookie set before the visitor has consented, whose category is neither necessary nor granted by default consent.
_Avoid_: Unconsented cookie, consent violation, illegal cookie

**Uncategorised cookie**:
A discovered cookie that doesn't belong to a service with a category yet, so it can't be judged against consent.
_Avoid_: Unknown cookie, unassigned cookie
