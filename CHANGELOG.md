Version: $Id$ ($Date$)

# History

## 1.5.0-dev @2025 May xx

- **update**: `Route::method` property now defaults to **ALL** http
  commands.

- **update**: **QueryString** handling getting an overhaul and new
  features.

- **fix**: proper uri request to match fix in **inanepain/http**

## 1.4.0 (2024 Dec 25)

- **RouteMatch**: this long awaited class finally joins

## 0.1.3 (2024 Sep 20)

- **router**: config parser can now also take an Options object

- **Throw** `InvalidRouteException` when parsing invalid config

- **update**: added example `.htaccess` file to README

- **update**: route match now includes the route object as well as the
  uri

## 0.1.2 (2022 Aug 13)

- **router**: addRoutes now also takes route config arrays

- **router**: renamed `generateUrl` to `url`

- **route**: fix `hasParams` returns int instead of `true`

- **route**: update default parameter parsing regex to include the
  period `.` char

- **README**: added usage examples

## 0.1.1 (2022 Aug 03)

- **fix**: http method parsing.
