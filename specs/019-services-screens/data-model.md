# Data model: Services screens

No table changes. What each screen reads and writes:

| Concept | Stored in | Notes |
| --- | --- | --- |
| Service | `services` | `name`, `description` per language; `prices` per currency in cents; `is_active` false means archived |
| Step | `service_steps` | `name` per language, `duration` 1 to 1440, `requires_staff`, `position`; at least one step needs staff (D5) |
| Category | `service_categories` | `name` per language, `position` |
| Offer | `location_services` | one per salon and service; `is_active` false means not offered there; own `prices` kept (R3) |
| Who does it | `staff_location_services` | a person doing an offer; only someone active at that salon (`location_staff.is_active`) |

## Form fields

| Field | Rule |
| --- | --- |
| `name[{language}]` | nullable string, 255; at least the first language filled |
| `description[{language}]` | nullable string, 1000 |
| `category` | nullable integer, a category of the company |
| `prices[{currency}]` | nullable, decimal 0 to 2 places, 0 to 1000000; currency of the company |
| `steps[n][name][{language}]`, `[duration]`, `[requires_staff]` | as the onboarding panel |
| `offers[{location}][]` | integer staff ids; key present means the salon is ticked |

## States

- Service: active ⇄ archived (`DeactivateService`, `ActivateService`).
- Offer: absent → offered (`CreateLocationService`) ⇄ paused (`UpdateLocationService`).
