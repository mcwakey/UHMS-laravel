You are a senior Laravel, Inertia/Vue, documentation, and Playwright automation expert.

We need to fix outdated screenshots in `UHMS_USER_MANUAL.md`.

The manual text has been updated to reflect the new UHMS workflows, but the existing screenshots are old and predate the redesign.

Do not manually edit PNG files.

Implement a repeatable screenshot capture system using Playwright.

Tasks:

2. Create a folder:

```text
docs/user-manual/screenshots
```

3. Create:

```text
scripts/capture-user-manual-screenshots.mjs
```

4. The script should:

   * launch Chromium
   * open the UHMS app using `UHMS_URL` env variable or default `http://127.0.0.1:8000`
   * optionally login using `admin@uhms.local` and `password`
   * capture screenshots of important manual screens
   * save screenshots into `docs/user-manual/screenshots`
   * use consistent viewport size
   * wait for network idle before capture
   * fail gracefully if a route does not exist

5. Add this npm script:

```json
"screenshots": "node scripts/capture-user-manual-screenshots.mjs"
```

6. Capture these pages if routes exist:

```text
/login
/dashboard
/visits/create
/triage
/consultations
/invoices
/investigations/catalogue
/inventory/stock-movements
/inventory/stock-adjustments
/purchase-orders
/stock-transfers
/pharmacy
```

7. Update `UHMS_USER_MANUAL.md` screenshot references to point to the new screenshots.

8. Add a note at the top of the manual:

```md
> Screenshot note: Screenshots are generated from the running UHMS app using the Playwright capture script. If the UI changes, rerun `npm run screenshots` to refresh them.
```

Important rules:

* Do not commit real user credentials.
* Use environment variables for login.
* Do not capture sensitive real patient data.
* Use seed/demo data only.
* Do not block documentation update if some routes are missing; log missing routes and continue.

Deliverables:

* Playwright installed or confirmed.
* Screenshot script created.
* NPM script added.
* Screenshot folder created.
* Manual screenshot references updated.
* Clear instructions in the README or manual for regenerating screenshots.
