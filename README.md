# Room Reservations
### A WordPress Plugin
Authenticates users based on their email address domain. They must have an email account in your approved list of domains, e.g. _github.com_ or _milligan.edu_. This prevents spamming yet makes it simple to implement without needing user accounts. This design decision was made to meet the needs of an organization with a small number of email domains (our college campus) without the need to implement LDAP or other authentication methods.

### Installation
1. Extract the plugin folder to your WordPress plugin directory, normally `wp-content/plugins`
2. Create a new page and place the `[phw-reserve-page]` shortcode in the text body
3. Configure your email domains and room list in the WordPress Dashboard under `Settings > Room Reservations`

### Configuration
@todo

### Changelog
1.0 Initial release.
