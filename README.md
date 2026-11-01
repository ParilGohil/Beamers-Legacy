# Beamers Legacy — BMW Dealership Project

## What is included

This build is organised for XAMPP and includes:

- PHP showroom pages and shared premium styling
- Firebase-first customer registration and sign-in for email/password and Google
- Server-verified Firebase ID tokens synchronized to the existing MySQL `register` table and PHP session
- Customer account page with profile, test-drive history, sales-booking history and feedback history
- Test-drive, car-service, sales-booking and feedback forms with validation and CSRF protection
- Staff/admin login and protected dashboard for customers, test drives, service requests, sales bookings, feedback and cars
- CSRF-protected admin actions and prepared SQL statements
- BMW configurator with live option pricing
- Responsive, database-driven BMW Lifestyle storefront with cart feedback stored in browser local storage
- Terms and Privacy pages
- Standard browser cursor (the custom cursor has been completely removed)
- Session-aware shared navigation: logged-in customers see their profile/avatar and Sign Out instead of Login/Register
- Login/Register pages automatically redirect authenticated customers to My Profile
- Admin-managed active cars are now read from the database and displayed on the public Models and All Lineup pages
- Test-drive, car-service and sales-booking forms prefill logged-in customer details where available and use post/redirect/get success handling
- Customers can request maintenance or repairs from Services, or reserve an active BMW from its details page with the selected vehicle prefilled
- Admins can manage service appointments and sales-booking statuses; customer status notifications are sent when the request belongs to a registered account and notifications are enabled
- Public navigation highlights the current section instead of leaving Home active on every page
- Legacy demo/presentation routes redirected to the main showroom instead of serving broken assets

## XAMPP setup

1. Copy this project folder into `C:\xampp\htdocs\BMW`.
2. Start **Apache** and **MySQL** in XAMPP.
3. Open phpMyAdmin and import `database.sql`.
4. Confirm the database credentials in `php/config.php` match your MySQL installation.
5. Open `http://localhost/BMW/`.

The database schema also self-heals missing application tables when the admin portal is opened. Fresh installations include the `car_service_requests` and `car_bookings` tables alongside the test-drive table.

## Service and sales requests

Visitors can request a test drive or BMW maintenance/repair appointment from **Services**. Prospective buyers can use **Reserve this BMW** on a vehicle details page to submit a purchase request. A request does not confirm an appointment or complete a vehicle purchase; staff follow up to confirm next steps.

Administrators can review requests under **Admin → Car service** and **Admin → Sales bookings**. Service requests can be accepted, rejected, or rescheduled. Sales bookings can be marked pending, contacted, reserved, completed, or cancelled. Status changes notify the customer when their email matches a registered account and the relevant notification preference is enabled.

## Admin portal

Open `php/adminlogin.php` from the website. The project does not create an administrator with a shared default password. Before the first request to an admin page, set these environment variables for the PHP/Apache process:

- `BEAMERS_ADMIN_EMAIL`: administrator email address
- `BEAMERS_ADMIN_PASSWORD`: unique password of at least 12 characters
- `BEAMERS_ADMIN_NAME`: optional display name (defaults to `Showroom Administrator`)

When `admin_users` is empty and the email/password variables are set, the admin bootstrap creates the initial administrator. If either is missing, no administrator is created. Keep these values out of source control. For databases initialized by an earlier version, replace or remove the former `admin@beamers.com` account and its default password.

## Dynamic BMW car details

The New Cars catalogue and `php/car-details.php?slug=...` pages use the shared `cars` table. Car slugs are generated from the model name and made unique automatically when a car is created or renamed in Admin → BMW Cars. Existing showcase vehicles are migrated into the catalogue once, so they use the same dynamic detail pages as cars added later.

The dynamic details template uses the selected car's database record for its cinematic hero, performance figures, technical specifications, editorial statement, highlights, gallery, offers and brochure. Optional sections appear only when the selected car has data for them. The page is arranged as an immersive vehicle introduction followed by sticky section navigation, overview/pricing, editorial design stories, performance data, highlights, gallery, offers and enquiry actions. A hero video is optional; a reduced-motion preference pauses it, and visitors can pause or resume it. The on-page test-drive form submits through the existing enquiry handler and keeps the selected car ID.

In Admin → BMW Cars, staff can manage the hero subtitle and optional MP4/WEBM background video (`videos/filename.mp4` or an HTTPS video URL), editorial statement, overview, specifications, highlights and offers. Add editorial stories one per line in the format `Heading | Description | images/story-image.jpg`; the image is optional. Enter gallery paths/URLs one per line, or upload up to 10 JPG, PNG, WEBP or GIF gallery images at once (maximum 5 MB each); uploaded images are saved under `images/cars/` and appended to the gallery. The existing hero/listing image upload remains separate.

## Firebase / Google authentication

Customer authentication uses Firebase as the identity source. Both email/password and Google sign-in obtain a Firebase ID token; PHP verifies it with Firebase Identity Toolkit before looking up or synchronizing the customer by the unique `Firebase_UID` in the existing `register` table. The application does not store Firebase passwords. Existing customer rows are preserved and linked by verified Firebase email or after the customer proves their existing MySQL password during the one-time migration.

Configure Firebase Console before testing:

1. Enable **Email/Password** and **Google** under Authentication → Sign-in method.
2. Add `localhost` for local testing and the production domain under Authentication → Settings → Authorized domains.
3. Keep the existing Firebase web-app configuration aligned in `js/firebase-config.js` and the API key used by `php/config.php`. These web values are public identifiers; never put Firebase service-account credentials or private keys in frontend files.
4. Review Authentication → Templates → Password reset to set the sender, subject, and reset-email content users receive.
5. Use HTTPS in production so Firebase ID tokens and PHP session cookies are transported securely.
6. Allow the PHP server outbound HTTPS access to `identitytoolkit.googleapis.com` so it can verify ID tokens.
7. On Windows/WAMP, configure `curl.cainfo` in the active `php.ini` to point at a current trusted CA bundle (for example, the bundle from `https://curl.se/ca/cacert.pem`) and restart Apache. Do not disable TLS certificate verification.
8. `php/config.php` automatically migrates existing `register` tables by adding nullable `Firebase_UID`, `Photo_URL`, and `Auth_Provider` fields and a unique Firebase UID index. Fresh installs receive the same fields from `database.sql`.

### Authentication verification steps

1. Register a new email/password customer. Confirm Firebase Authentication contains the account and phpMyAdmin shows one `register` row with the same Firebase UID, email, and profile name.
2. Sign out, then sign in with that email/password. Confirm the same MySQL row is reused.
3. Use **Continue with Google** for a new Google account, then repeat sign-in. Confirm Firebase UID matching prevents duplicate MySQL rows.
4. For a legacy MySQL customer without a Firebase UID, use their existing MySQL password during email registration/sign-in to authorize one-time linking. A matching verified Google email may also link the legacy row. Confirm its `id` is preserved and only its Firebase UID/profile fields are added.
5. If Google and email/password providers already share a Firebase email, use the **Link Google to this account** action and the existing Firebase password.
6. Update the profile name, then sign out/in and confirm it remains synchronized. Confirm a Google profile image appears when Firebase provides one.
7. Sign out and confirm the Firebase client and PHP session are both cleared. Admin authentication remains separate.
8. To verify failure handling, submit an invalid/expired Firebase token or temporarily make the database unavailable in a non-production test setup. The UI should show a specific retry/configuration message; detailed backend errors are written to the PHP error log.

## Project structure

```text
BMW/
├── index.php
├── database.sql
├── README.md
├── css/
├── images/
├── videos/
├── js/
├── php/
│   ├── backend/
│   └── admin/
└── docs/
```

## Verification performed

- All PHP files pass `php -l` syntax validation.
- All JavaScript files pass `node --check` syntax validation.
- Main showroom/auth/legal pages were served successfully through PHP's built-in server.
- Local media/script references were checked for missing files.
- Custom cursor code and CSS references were removed.

The local existing MySQL schema migration and browser auth-page/endpoint smoke checks were performed. Real Firebase sign-up/sign-in and provider linking still need a configured test account in Firebase; no real customer account was created during implementation.

## Admin car image uploads

The BMW Cars section in the admin dashboard uses a normal file chooser for the hero/listing image. Select a JPG, PNG, WEBP or GIF image directly from your computer (maximum 5 MB). The application automatically stores it in `images/cars/` and saves the correct relative path in the `cars.image` column. Gallery images can be uploaded separately in batches of up to 10.

When editing a car, selecting a new image replaces the old one. Leaving the image picker empty keeps the existing image. Use **Remove current image** only when you want to delete the existing image.

## Customer notifications

The notification centre includes test-drive updates, customer feedback alerts for administrators, and store restock alerts. Signed-in customers can request a restock alert on an out-of-stock product in the Lifestyle Shop. When an administrator makes that product active with stock greater than zero in **Admin → BMW Store**, each subscribed customer receives one notification. If the product later goes out of stock, its subscription is reset so the next restock can notify the customer again.

The `notifications`, `notification_preferences`, and `stock_notifications` tables are created by the runtime schema setup and are also included in `database.sql` for fresh installations. Store stock notifications can be enabled or disabled in **Admin → Notifications**.

The Lifestyle Shop hero, announcement bar, category tiles, product visibility, featured products, prices, inventory and product images are managed in **Admin → BMW Store**. Existing cart interactions continue to save selections in the browser; a payment or order-management workflow is not configured.
