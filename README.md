# Qbnox Systems – SMTP Plugin

Enterprise-grade SMTP plugin with OAuth2, SES/Brevo webhooks and analytics.

Here is your content **cleanly converted to Markdown (MD)**, suitable for a README or docs file.

---

## Install Required Tools

### PHP Formatter (WordPress-safe)

Install PHP CodeSniffer and WordPress Coding Standards:

```bash
composer require --dev squizlabs/php_codesniffer wp-coding-standards/wpcs
```

#### Register WordPress Coding Standards

```bash
./vendor/bin/phpcs --config-set installed_paths vendor/wp-coding-standards/wpcs
```

---

### JavaScript Formatter

Install Prettier:

```bash
npm install --save-dev prettier
```

---

## Add Formatter Configs

### `.phpcs.xml` (WordPress Standards)

```xml
<?xml version="1.0"?>
<ruleset name="Qbnox Coding Standards">
  <rule ref="WordPress" />
  <rule ref="WordPress-Core" />
  <rule ref="WordPress-Extra" />
  <file>includes</file>
  <file>admin</file>
</ruleset>
```

---

### `.prettierrc`

```json
{
  "semi": true,
  "singleQuote": true,
  "trailingComma": "es5",
  "printWidth": 80
}
```
Here is the text **formatted cleanly in Markdown**, consistent with the earlier sections:

---

## What Developers Do Locally

CI will **not** auto-fix formatting issues. Developers should format code locally before committing.

### PHP

Run PHP Code Beautifier and Fixer:

```bash
vendor/bin/phpcbf
```

---

### JavaScript

Run Prettier to auto-format JavaScript files:

```bash
npx prettier --write "**/*.js"
```

---

After formatting, commit the changes:

```bash
git commit -m "Apply code formatting"
```

This is the **correct and recommended workflow** when using CI-based code quality enforcement.

