# KingsChat delegate sign-in

## Setup

Create your own application in the [KingsChat developer portal](https://developers.kingschat.online/).
Set these server-side values in ignored `config/secrets.php`, or through the corresponding environment variables:

```php
'kingschat_client_id' => 'your-application-client-id', // KINGSCHAT_CLIENT_ID
'kingschat_api_key' => 'your-project-api-key',        // KINGSCHAT_API_KEY
```

Register `https://experience.ministryprograms.org/delegate-callback.php` as the application's callback URL with **REDIRECT** delivery and enable access to the user's profile. This app does not accept FORM_POST callbacks: cross-site POSTs omit the SameSite=Lax session cookie needed to validate the pending login. Keep the same scheme/host throughout the browser session.

For an existing MySQL installation, apply the missing `database_upgrade_v4.sql` and `database_upgrade_v5.sql` migrations once after any earlier required migrations. They create/link delegates and enable unverified temporary access; historical rows and verified associations are retained. New databases already include these changes. SQLite performs the equivalent upgrade automatically to schema version 4.

## Temporary access before credentials are assigned

If either Client ID or API key is blank, the delegate login route displays a username-and-phone form rather than redirecting to KingsChat. It validates the username and international phone format, normalizes the values, and saves a delegate with `auth_method = manual`, a nullable provider ID, and a unique key for the normalized username/phone pair. This is self-reported access, not phone/OTP or KingsChat verification; the form and reports say so explicitly.

The phone number is kept server-side and omitted from public pages and standard CSV exports. Manual records do not become verified simply because the same username later logs in through KingsChat. Their feedback keeps its original attribution and **Manual (unverified)** label.

When **both** Client ID and API key become nonempty, KingsChat login activates automatically. `current_delegate()` immediately rejects existing manual sessions, the manual form is no longer offered, and stale POSTs go through the KingsChat login route. A Client ID by itself is insufficient. There is no silent manual fallback after provider/network errors in configured mode.

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

The mock requires `APP_ENV=test`, SQLite, and an explicitly configured loopback `KINGSCHAT_API_BASE_URL`. Normal verified login only allows `https://connect.kingsch.at`; the temporary form is available solely while credentials are incomplete and never claims a verified identity. Tests restart the app with missing, partial, and complete credentials to verify automatic activation and old-session rejection. Actual KingsChat authentication requires internet. Real-provider testing and any portal-specific scope/application configuration must be completed with your own Client ID/API key.

## Research references

- [KingsChat's Web SDK](https://github.com/kingschat/kingschat-web-sdk) documents registered application IDs and user-authorized login; its older popup flow is **not** used here.
- [KingsChat Android example](https://github.com/kingschat/KingsLogin-android/blob/master/sample-extended/android/src/main/java/com/newmedia/kingsloginexample/Services.kt) uses `https://connect.kingsch.at/developer/api/` for code exchange.
- The current [KingsChat account login](https://accounts.kingschat.online/log-in) implements `clientId`, `origin`, and REDIRECT callbacks carrying `code`/`origin`.
- The third-party [kingsgo client](https://github.com/theexcelrobin/kingsgo) documents the modern code exchange/profile contract and `api-key` header. This is a reference, not a runtime dependency or an official guarantee; validate the configured application's behavior against the developer dashboard before live rollout.
