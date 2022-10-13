# Changelog: Routing

> $Id$ ($Date$)

## HISTORY

### develop (2022 Oct xx)

 - router: config parser can now also take an Options object
 - Throw `InvalidRouteException` when parsing invalid config

### 0.1.2 (2022 Aug 13)

 - router: addRoutes now also takes route config arrays
 - router: renamed `generateUrl` to `url`
 - route: fix `hasParams` returns int instead of `true`
 - route: update default parameter parsing regex to include the period `.` char
 - README: added usage examples

### 0.1.1 (2022 Aug 03)

 - fix: http method parsing
