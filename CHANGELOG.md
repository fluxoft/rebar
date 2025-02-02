# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.1.2] - 2025-02-01
### Fixed
- Fixed issue with mapper classes where comparisons to null values needs special handling. When creating a
query that includes a null comparison, the filter SQL is written as "IS NULL" or "IS NOT NULL" and the null
parameter is not included in the params. This specifically fixes an issue that arose while checking for a
null value for a property when used with Postgres, but this is a more correct handling of null comparisons
across the board now.

## [1.1.1] - 2025-01-20
### Fixed
- Fixed issue with `Config` class where nested arrays were replaced instead of merged when multiple configuration sources were loaded. Arrays are now merged recursively rather than shallow-merged.

## [1.1.0] - 2025-01-15
### Deprecated
- `Controller::set()` has been deprecated. Use `$this->response->SetData()` instead.
- `Controller::Display()` has been deprecated. The router now directly invokes `$response->Send()` to handle rendering.
- These methods are excluded from test coverage and will be removed in version 1.3.0.

### Added
- Centralized response data management in the `Response` class with `SetData()`, `GetData()`, and `ClearData()`.
- Introduced `Response::SetPresenter()` for managing presenters directly on the response object.
- Enhanced `Response::Send()` to handle rendering and sending the response, replacing the need for `Controller::Display()`.
- Support for assigning presenters by path in the `Router`:
  - Allows different response formats (e.g., JSON, HTML) for specific URL patterns.
  - Presenters are set on the `Response` object before middleware processing to enable consistent rendering for middleware-handled responses.
- Routing logic enhanced to respect path specificity:
  - Ensures more specific paths take precedence over general ones when determining the presenter.

### Fixed
- Improved separation of concerns between controllers and response rendering logic.
- Updated `Router::Route` to handle unmatched paths gracefully by setting the `Presenter` and `Status` on the `Response` object consistently.
- Improved test coverage for `Router`, including error handling and presenter assignment logic.

## [1.0.0] - 2025-01-01
### 🎉 Official Release (For Real This Time)
Unlike [0.24.0], this isn't just a release candidate for masochists. It's ready for prime time!
- Rebar is now stable and production-ready with its **1.0.0** release!
- Extensive refactor and modernization of the codebase.
- Achieved **99.71% test coverage** across the entire codebase.
- Introduced clear design philosophies prioritizing:
  - Simplicity: Lightweight with minimal abstractions.
  - Flexibility: A skeletal structure reinforced with abstract/concrete classes.
  - Stability: Dependable core components and comprehensive test coverage.
- Enhanced support for database mappers, covering:
  - **MySQL**
  - **MariaDB**
  - **PostgreSQL**
  - **SQLite**
  - **Oracle**
  - **SQL Server**
- Improved test suite and refined query-building logic.
- Updated dependencies for stability, including support for `firebase/php-jwt` v6+.

---

## [0.25.3] - 2023 (Beta)
### Notes
- This version marked the final "beta" release before stabilizing for 1.0.0.
- Numerous features were explored, tested, and refined leading up to this release.

[1.1.2]: https://github.com/fluxoft/rebar/releases/tag/1.1.2
[1.1.1]: https://github.com/fluxoft/rebar/releases/tag/1.1.1
[1.1.0]: https://github.com/fluxoft/rebar/releases/tag/1.1.0
[1.0.0]: https://github.com/fluxoft/rebar/releases/tag/1.0.0
[0.25.3]: https://github.com/fluxoft/rebar/releases/tag/0.25.3
[0.24.0]:https://github.com/fluxoft/rebar/releases/tag/0.24.0
