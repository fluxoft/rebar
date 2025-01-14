# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.1.0] - 2025-01-13
### Deprecated
- `Controller::set()` has been deprecated. Use `$this->response->SetData()` instead.
- `Controller::Display()` has been deprecated. The router now directly invokes `$response->Send()` to handle rendering.
- These methods are excluded from test coverage and will be removed in version 1.3.0.

### Added
- Centralized response data management in the `Response` class with `SetData()`, `GetData()`, and `ClearData()`.
- Introduced `Response::SetPresenter()` for managing presenters directly on the response object.
- Enhanced `Response::Send()` to handle rendering and sending the response, replacing the need for `Controller::Display()`.

### Fixed
- Improved separation of concerns between controllers and response rendering logic.

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

[1.1.0]: https://github.com/fluxoft/rebar/releases/tag/1.1.0
[1.0.0]: https://github.com/fluxoft/rebar/releases/tag/1.0.0
[0.25.3]: https://github.com/fluxoft/rebar/releases/tag/0.25.3
[0.24.0]:https://github.com/fluxoft/rebar/releases/tag/0.24.0
