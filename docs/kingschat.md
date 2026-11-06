# KingsChat delegate sign-in

## Setup

Create your own application in the [KingsChat developer portal](https://developers.kingschat.online/).
Set these server-side values in ignored `config/secrets.php`, or through the corresponding environment variables:

```php
'kingschat_client_id' => 'your-application-client-id', // KINGSCHAT_CLIENT_ID
'kingschat_api_key' => 'your-project-api-key',        // KINGSCHAT_API_KEY
```

Register `https://experience.ministryprograms.org/delegate-callback.php` as the application's callback URL with **REDIRECT** delivery and enable access to the user's profile. This app does not accept FORM_POST callbacks: cross-site POSTs omit the SameSite=Lax session cookie needed to validate the pending login. Keep the same scheme/host throughout the browser session.

For an existing MySQL installation, apply `database_upgrade_v4.sql` once after any earlier required migrations. It creates `delegates` and adds a nullable `feedback.delegate_id`; historical rows are retained. New databases already include these changes. SQLite performs the equivalent upgrade automatically.

## Flow

1. Browsing program/service details requires no sign-in. **Rate this service** shows the KingsChat sign-in gate to visitors.
2. `delegate-login.php` validates the published program/service, stores a random, ten-minute, session-bound nonce and the selected program/service, and redirects to `https://accounts.kingschat.online/log-in?clientId=...&origin=...`.
3. KingsChat sends `code` and the same `origin` back to the registered callback. The callback validates and consumes the nonce before contacting KingsChat. User-supplied return URLs are never accepted.
4. PHP exchanges the code at `POST https://connect.kingsch.at/developer/api/oauth2/token`, with JSON `grant_type: "code"`, `client_id`, and `code`.
5. PHP requests `GET https://connect.kingsch.at/developer/api/user/profile`, using `Authorization: Bearer ...` and the project's `api-key`. Only the provider-returned `profile.id`, `profile.username`, and `profile.name` establish identity. A typed name, decoded-but-unverified JWT, or access token posted by the browser cannot establish a session.
6. The stable KingsChat ID identifies a unique delegate row. The session ID is regenerated and the delegate session expires no later than the provider token's lifetime, capped at eight hours. Tokens are discarded after profile verification; this app stores no access/refresh tokens and does not request messaging access.
7. The delegate returns to the selected program/service's feedback form. `submit-feedback.php` independently enforces delegate authentication, validates the service, and sets `delegate_id` and name from the verified session. Admin access is unaffected and remains at `/admin`.

Identity-hidden feedback remains internally associated with the signed-in delegate, but both display name and KingsChat handle are omitted from admin feedback reports and CSV exports. The checkbox explains this behavior. Existing unidentified records are supported.

## Verification and limitations

`python3 tests/offline_smoke.py` starts a disposable PHP/SQLite app and a local mock implementing the actual token/profile HTTP contract. It tests invalid codes/profiles, callback nonce rejection/replay, provider-token secrecy, session expiry, return context, impersonation prevention, independent admin/delegate sessions, historical data migration, and CSV identity masking.

The mock requires `APP_ENV=test`, SQLite, and an explicitly configured loopback `KINGSCHAT_API_BASE_URL`. Normal runtime only allows `https://connect.kingsch.at`; there is no fake-login route or bypass in the public app. Actual KingsChat authentication requires internet. Real-provider testing and any portal-specific scope/application configuration must be completed with your own Client ID/API key.

## Research references

- [KingsChat's Web SDK](https://github.com/kingschat/kingschat-web-sdk) documents registered application IDs and user-authorized login; its older popup flow is **not** used here.
- [KingsChat Android example](https://github.com/kingschat/KingsLogin-android/blob/master/sample-extended/android/src/main/java/com/newmedia/kingsloginexample/Services.kt) uses `https://connect.kingsch.at/developer/api/` for code exchange.
- The current [KingsChat account login](https://accounts.kingschat.online/log-in) implements `clientId`, `origin`, and REDIRECT callbacks carrying `code`/`origin`.
- The third-party [kingsgo client](https://github.com/theexcelrobin/kingsgo) documents the modern code exchange/profile contract and `api-key` header. This is a reference, not a runtime dependency or an official guarantee; validate the configured application's behavior against the developer dashboard before live rollout.
