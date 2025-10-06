# Offer Suggestion Model

This document describes the offer-suggestion logic used in the Warehouse Information UI, how to obtain suggestions from the client, how to apply them to the server, and an optional server-side API spec.

## Summary

- Purpose: compute a temporary promotional offer for warehouse products to accelerate movement of stock.
- Where implemented: `warehouse-info.js` (function `suggestOffer(id)` and the UI helper `showOfferSuggestion(id)`).
- Output shape: `null` or an object `{ discount: number, startDate: 'YYYY-MM-DD', endDate: 'YYYY-MM-DD' }`.

## Contract (inputs / outputs / error modes)

- Inputs: a product `id` (the `warehouse_products` row id). The function reads the product from the client `inventory` cache. Expected fields on the product object:

  - `quantity` (number)
  - `date_added` (string, inbound stock date, e.g. `2025-10-01`)
  - `expiry_date` (string, optional)
  - `status` (string, e.g. `Completed` or `In progress`)

- Output:

  - `null` — when no suggestion applies.
  - `{ discount, startDate, endDate }` — when a suggestion applies. Dates are formatted as `YYYY-MM-DD`.

- Errors: the client function returns `null` if the product is not present in `inventory` or date values are invalid. The API used to _apply_ the suggestion will return error responses for invalid inputs.

## Decision rules (priority order)

The following rules are the complete set used by `suggestOffer(id)` (evaluated top to bottom):

1. Expiry proximity (urgent clearance)

   - If `expiry_date` exists and falls within 14 days from now (inclusive), suggest a 40% discount that runs from tomorrow until the `expiry_date`.

2. Very old stock (long ageing)

   - If inbound age > 180 days, suggest 35% discount for 45 days starting tomorrow.

3. Extremely high stock (bulk clearance)

   - If `quantity >= 1000`, suggest 30% discount for 30 days starting tomorrow.

4. Small stock (quick sell)

   - If `quantity < 100`, suggest 15% discount for 15 days starting tomorrow.

5. Mid-high stock tier

   - If `301 <= quantity <= 999`, suggest 8% discount for 20 days starting tomorrow.

6. Medium-tier (original rule)

   - If `100 <= quantity <= 300`, suggest 5% discount for 30 days starting tomorrow.

7. In-progress items with very high quantity

   - If `status === 'In progress' && quantity > 500`, suggest 12% for 20 days starting tomorrow.

8. Completed fallback

   - If `status === 'Completed'`, suggest 10% for 20 days starting tomorrow.

9. Default
   - Otherwise, return `null` (no suggestion).

Notes:

- Expiry check outranks other rules to avoid selling expired goods or missing urgent clearances.
- The algorithm uses client local time for date arithmetic and returns dates in `YYYY-MM-DD` (via `toLocaleDateString('en-CA')` with a fallback to `toISOString().slice(0,10)`).

## Client usage (browser console)

- Get suggestion for a single product id:

```js
suggestOffer(7); // returns suggestion object or null
```

- Get suggestions for all visible inventory rows:

```js
inventory.map((i) => ({ id: i.id, suggestion: suggestOffer(i.id) }));
```

- Show suggestion modal for a row (UI helper):

```js
showOfferSuggestion(7); // opens UI modal with suggested values and actions
```

## Apply a suggestion (client → server)

The UI and code re-use the server API action `offer` that your app already exposes. Example programmatic application:

```js
const s = suggestOffer(7);
if (s) {
  api("offer", {
    id: 7,
    discount: s.discount,
    startDate: s.startDate,
    endDate: s.endDate,
  })
    .then((resp) => console.log("Offer saved", resp))
    .catch((err) => console.error("Save failed", err));
}
```

The UI added to `warehouse-info.php` also provides an “Apply Suggestion” button in the suggestion modal which performs the same call.

## Suggested server endpoint (optional)

If you want server-side suggestion computation (recommended for consistency, batch processing, or deterministic results), add an API action `suggest` in `warehouse-info-api.php`.

Request (POST JSON):

```json
{ "id": 123 }
```

Response (JSON):

```json
{
  "ok": true,
  "suggestion": {
    "discount": 15,
    "startDate": "2025-10-07",
    "endDate": "2025-10-22"
  }
}
```

or

```json
{ "ok": true, "suggestion": null }
```

Server-side pseudo-code (PHP):

```php
// read id
$id = (int)($_POST['id'] ?? 0);
// SELECT quantity, inbound_stock_date, expiry_date, request_status FROM warehouse_products WHERE id = $id
// derive status: request_status==1 ? 'In progress' : 'Completed'
// apply the same rules as in the client, using DateTime in UTC for arithmetic
// return json_encode(['ok'=>true,'suggestion'=>$suggestion]);
```

Validation on the server:

- ensure `discount` is 0..100 and dates are valid `Y-m-d` strings
- ensure the `id` exists
- avoid applying an offer if there is an existing active offer that overlaps (either return an error or provide a suggestion to extend)

## Edge cases & recommendations

- Timezones: client code uses local timezone; server endpoint should use UTC to ensure consistent dates.
- Overlapping offers: avoid blindly applying suggestions if a current active offer overlaps the suggested range. Option: return conflict info from the server.
- Invalid/missing dates: when `date_added` or `expiry_date` are missing or malformed, the client fallback returns `daysOld = 0` and skips expiry logic. Server should validate and log anomalies.
- Audit: store suggestion metadata (rule version, timestamp) if you want to audit why a suggestion was made.

## Next steps (pick one)

1. Implement server-side `action=suggest` (I can add it to `warehouse-info-api.php`).
2. Add a badge column to the UI indicating which rows have suggestions available.
3. Add overlap/conflict checks before applying suggestions.

If you want me to implement any of these, tell me which one and I will implement the change.

---

Generated: 2025-10-06
