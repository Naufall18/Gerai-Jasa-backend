# Gerai Jasa — API Versioning & Deprecation Policy

The API serves multiple clients (web admin/vendor panel, Flutter mobile app, and
potentially third parties). This policy keeps those clients from breaking as the
API evolves.

## Versioning scheme

- All endpoints live under a URL version prefix: `/api/v1/...`.
- **v1 is stable.** Within v1 we only make **backward-compatible** changes:
  - ✅ Adding a new endpoint.
  - ✅ Adding a new **optional** request field.
  - ✅ Adding a new field to a response body (clients must ignore unknown fields).
  - ✅ Adding a new `meta` field (e.g. `pagination.last_page`).
- **Breaking changes require a new version** (`/api/v2/...`), e.g.:
  - ❌ Removing/renaming a response field.
  - ❌ Changing a field's type or meaning.
  - ❌ Making a previously-optional request field required.
  - ❌ Changing an identifier (e.g. vendor lookup by `slug` → `id`).

> Example pending breaking change: unifying resource identifiers (some routes use
> `{slug}`, others `{id}`). Because clients depend on the current shape, this is a
> **v2** change and must be coordinated with web + mobile.

## Response contract (stable within v1)

Every endpoint returns the same envelope:

```json
{ "success": true, "message": "...", "data": <payload|null>, "meta": { } }
```

Paginated list endpoints always include:

```json
"meta": { "pagination": { "current_page": 1, "per_page": 20, "total": 0, "last_page": 1 } }
```

Clients **must** tolerate unknown/extra fields and not assume field ordering.

## Deprecation process

1. Announce the deprecation (changelog + this doc) with a target removal date.
2. Mark deprecated responses with a `Deprecation` / `Sunset` HTTP header.
3. Keep the deprecated behavior for at least one minor cycle (recommended ≥ 90 days).
4. Remove only in the next major version (`v2`), never within `v1`.

## Changelog

Material API changes should be recorded here (date — change — compatibility note).

- 2026-06 — `meta.pagination.last_page` now present on all list endpoints (additive).
