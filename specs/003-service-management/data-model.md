# Data Model: Service Management

Every table has `id` and timestamps. Durations are minutes (a service sums its steps, R3), prices
the smallest currency unit.

## service_categories

| Column | Type | Rule |
| --- | --- | --- |
| company_id | foreign key, cascade | |
| name | string | required, unique per company |
| description | text, nullable | |
| position | unsigned integer | last on create (R4) |

## services

| Column | Type | Rule |
| --- | --- | --- |
| company_id | foreign key, cascade | |
| service_category_id | foreign key, nullable, null on delete | same company |
| name | string | required, unique per company |
| description | text, nullable | |
| price | unsigned integer | zero or more |
| is_active | boolean, default true | |
| position | unsigned integer | last in its category on create (R4) |

## service_steps

| Column | Type | Rule |
| --- | --- | --- |
| service_id | foreign key, cascade | |
| position | unsigned integer | index in the list (R2), unique per service |
| active_duration | unsigned integer | above zero |
| processing_duration | unsigned integer, default 0 | zero or more |

## location_services

| Column | Type | Rule |
| --- | --- | --- |
| location_id | foreign key, cascade | same company as the service; unique with `service_id` |
| service_id | foreign key, cascade | active when created |
| price | unsigned integer, nullable | null takes `services.price`, see `resolvedPrice()` |
| is_active | boolean, default true | |

## team_member_services

| Column | Type | Rule |
| --- | --- | --- |
| user_id | foreign key, cascade | assigned to the location (`user_locations`) |
| location_service_id | foreign key, cascade | |

Unique on `user_id`, `location_service_id`.

## Relationships

- Company has many `ServiceCategory` and `Service`.
- `ServiceCategory` has many `Service`; `Service` belongs to an optional `ServiceCategory`.
- `Service` has many `ServiceStep` ordered by position, and many `LocationService`.
- `Location` has many `LocationService`; `Service` and `Location` belong to many each other through it.
- `LocationService` belongs to many `User` through `team_member_services`.
