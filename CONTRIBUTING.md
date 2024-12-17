# Contributing to Rebar

Thank you for your interest in contributing to Rebar! Contributions are what make open-source projects thrive. Whether it’s reporting bugs, suggesting features, or submitting code, we appreciate your efforts.

## How to Report Issues
If you encounter a bug or have an idea for improvement:

1. Check the [Issues page](https://github.com/fluxoft/rebar/issues) to ensure it hasn’t already been reported.
2. Open a new issue and include the following:
   - A clear, descriptive title.
   - The PHP version and environment you're using.
   - Steps to reproduce the issue.
   - Expected behavior vs. actual behavior.

## How to Submit Code Changes
To contribute code, follow these steps:

1. **Fork the Repository**

   Head to [Rebar on GitHub](https://github.com/fluxoft/rebar) and fork the repository to your GitHub account.

2. **Create a Branch**

   Create a feature branch for your changes:
   ```bash
   git checkout -b feature/your-feature-name
   ```

3. **Follow Coding Standards**

   Please follow the ruleset defined in `ruleset.xml` with these additional preferences:
   - **PascalCase** for public properties/functions.
   - **camelCase** for private and protected properties/functions.

   Ensure your changes pass code style checks using PHP_CodeSniffer:
   ```bash
   vendor/bin/phpcs --standard=ruleset.xml src/ tests/
   ```

4. **Write Unit Tests**

   If you add a new feature or fix a bug, write or update tests to confirm the behavior. To run tests:
   ```bash
   phpdbg -qrr vendor/bin/phpunit -c phpunit.xml.dist
   ```
   Generally speaking, test coverage should always remain **>95%** (the higher the better).

5. **Submit a Pull Request**

   - Push your changes to your fork:
     ```bash
     git push origin feature/your-feature-name
     ```
   - Open a pull request against the `1.0` branch.
   - Add a clear description of the change and reference any related issues.

## Asking for Help

If you're unsure about anything, feel free to open an issue or email me at [joe@fluxoft.com](mailto:joe@fluxoft.com).
