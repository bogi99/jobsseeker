# Stripe Webhook Setup & Testing 🧾

Short checklist for configuring and testing Stripe webhooks so paid posts are activated automatically.

---

## 1) Routes used by this app
- Webhook endpoint: `POST /webhooks/stripe` → `StripeWebhookController@handle`
- Payment success redirect: `GET /posts/payment/success` → `PostPaymentController@success`

These are defined in `routes/web.php` and handled by the controllers in `app/Http/Controllers`.

---

## 2) Production webhook setup 🔒
1. In Stripe Dashboard → **Developers → Webhooks** → Add endpoint.
2. Set the **URL** to `https://<YOUR_DOMAIN>/webhooks/stripe`.
3. Subscribe to at least:
   - `checkout.session.completed`
   - `payment_intent.succeeded`
   - (optional) `payment_intent.payment_failed`
4. Copy the **Signing secret** (starts with `whsec_...`) and add it to your `.env`:

```dotenv
STRIPE_WEBHOOK_SECRET=whsec_...
```
5. Restart your app / clear config cache: `php artisan config:clear`.

---

## 3) Local development (recommended: Stripe CLI) 🧪
1. Install Stripe CLI: https://stripe.com/docs/stripe-cli
2. Run:
```bash
stripe login
stripe listen --forward-to http://localhost:8000/webhooks/stripe
```
3. Copy the printed `whsec_...` into your local `.env` as `STRIPE_WEBHOOK_SECRET`.
4. Simulate events:
```bash
stripe trigger checkout.session.completed
stripe trigger payment_intent.succeeded
```
Or create a real test Checkout Session via the app and pay with card `4242 4242 4242 4242`.

---

## 4) What the webhook handler does ✅
- `StripeWebhookController` verifies the signature (if set) and on `checkout.session.completed` / `payment_intent.succeeded` reads `metadata.post_id` and `metadata.boost`.
- It finds the `Post` and calls `$post->activateAsPaid((bool) $boost)` to set `is_paid`, `is_active`, `paid_at`, `published_at`, and `expires_at`.
- For dynamic Checkout Sessions the metadata is already set when the session is created in `CreatePost::afterCreate()`.

---

## 5) Hosted Buy Links (static) — caveats ⚠️
- Static Payment Links can't include per-post metadata by default.
- We append `?post_id={id}` to the hosted link as a best-effort fallback. The `PostPaymentController` will redirect users back to the edit page if `post_id` is present, but activation requires webhook verification.
- Best practice: prefer dynamic Checkout Sessions (or generate a Payment Link dynamically per post if you must use Payment Links with metadata).

---

## 6) Debugging & verification 🔍
- Check Stripe Dashboard → **Developers → Webhooks → Recent deliveries** for payload and response (should be 200).
- Inspect Laravel logs: `tail -f storage/logs/laravel.log` for errors.
- Confirm DB: `SELECT is_active, payment_status, paid_at FROM posts WHERE id = <post_id>;` or check Filament → **My posts** (should show Live).
- Use `stripe listen` + `stripe trigger` to replay/test events.

---

## 7) Optional improvements (suggested)
- Add additional logging in `StripeWebhookController` to capture failures.
- Add a UI indicator in Filament listing showing last payment attempt / webhook status.
- Add automated tests that simulate the webhook payload and assert `activateAsPaid()` behavior.

---

If you want, I can add a short test and a small log message in `StripeWebhookController` to make debugging easier — which would you prefer? (add test / add logs / neither)