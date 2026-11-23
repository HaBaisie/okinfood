# Okin Food Supermarket - online store

Static site for Netlify. No server code, no build step.

- Products: edit `products.json` (add `"image": "images/rice.jpg"` for photos).
- Store settings (phone, bank details, delivery fee): top of `app.js`.
- Orders: submitted with Netlify Forms (form name `order`) and emailed to info@okinfood.com.ng.

## Deploy
1. Netlify -> Add new site -> Import from GitHub -> `HaBaisie/okinfood` (no build command, publish directory `.`).
2. Site configuration -> Forms -> Form notifications -> Add -> Email -> `info@okinfood.com.ng`.
3. Domain management -> Add custom domain -> `okinfood.com.ng`.
