# Installation Testing Plan

This document outlines the testing procedures for verifying the Blogware/Scriptlog installation system works correctly with multi-language support.

## Test Environment

- **PHP Version**: 7.4+ (recommended 8.0+)
- **Database**: MySQL 5.7+ or MariaDB 10.2+
- **Web Server**: Apache/Nginx
- **Test Domain**: http://blogware.site (or local equivalent)

---

## Pre-Installation Checklist

Before running tests, ensure:

- [ ] Web server is running and pointing to `/var/www/blogware/public_html`
- [ ] Database server is running with MySQL/MariaDB
- [ ] PHP extensions installed: `pdo`, `pdo_mysql`, `mbstring`, `xml`, `curl`, `gd`
- [ ] Write permissions on `public/files/`, `public/cache/`, `public/log/`
- [ ] Clean database (drop existing tables if any)

---

## Test Cases

### Test 1: Fresh Installation - English (Default)

**Objective**: Verify clean installation works with default English language

**Steps**:
1. Navigate to `http://blogware.site/install/`
2. Select "English" from language dropdown
3. Click "Continue" through system requirements
4. Fill in database credentials (host, user, password, database name)
5. Fill in admin account details (username, email, password)
6. Complete installation
7. Verify redirect to admin login

**Expected Results**:
- Installation completes without errors
- Admin user created in `tbl_users`
- Default language (English) set in `tbl_languages` and `tbl_settings`
- 41 translation keys populated in `tbl_translations`

**Verification Queries**:
```sql
SELECT * FROM tbl_users WHERE user_login = 'administrator';
SELECT * FROM tbl_languages WHERE lang_is_default = 1;
SELECT COUNT(*) AS translation_count FROM tbl_translations;
```

---

### Test 2: Fresh Installation - Chinese

**Objective**: Verify installation works with Chinese language

**Steps**:
1. Navigate to `http://blogware.site/install/`
2. Select "中文 (Chinese)" from language dropdown
3. Complete installation with Chinese selected
4. Verify admin panel displays in Chinese after login

**Expected Results**:
- Installation completes successfully
- Default language set to Chinese in database
- All UI text appears in Chinese

---

### Test 3: Fresh Installation - Arabic (RTL)

**Objective**: Verify installation works with Arabic (RTL language)

**Steps**:
1. Navigate to `http://blogware.site/install/`
2. Select "العربية (Arabic)" from language dropdown
3. Complete installation
4. Verify RTL layout is applied in admin panel

**Expected Results**:
- Installation completes successfully
- RTL direction set in `tbl_languages`
- Admin panel displays right-to-left

**Verification Query**:
```sql
SELECT lang_direction FROM tbl_languages WHERE lang_code = 'ar';
```

---

### Test 4: Fresh Installation - French

**Objective**: Verify installation works with French language

**Steps**:
1. Navigate to `http://blogware.site/install/`
2. Select "Français (French)" from language dropdown
3. Complete installation
4. Verify French translations are applied

**Expected Results**:
- Installation completes successfully
- French set as default language

---

### Test 5: System Requirements Check

**Objective**: Verify system requirements page displays correctly

**Steps**:
1. Navigate to `http://blogware.site/install/`
2. Observe system requirements check on first page

**Expected Results**:
- All required PHP extensions detected
- Directory permissions validated
- Pass/Fail indicators for each requirement

---

### Test 6: Database Connection Validation

**Objective**: Verify installer validates database connection

**Steps**:
1. Start installation
2. Enter incorrect database credentials
3. Submit form

**Expected Results**:
- Error message displayed: "Cannot connect to database"
- User can correct credentials and retry

---

### Test 7: Admin Account Security

**Objective**: Verify admin password meets security requirements

**Steps**:
1. During installation, enter weak password (e.g., "123")
2. Submit form

**Expected Results**:
- Validation error prevents submission
- Message indicates minimum password strength required

---

### Test 8: Translation Cache Generation

**Objective**: Verify translation cache is generated after installation

**Steps**:
1. Complete installation with any language
2. Check `public/files/cache/translations/` directory

**Expected Results**:
- JSON cache file created (e.g., `en.json`)
- Contains all 41 translation keys

**Verification**:
```bash
ls -la public/files/cache/translations/
cat public/files/cache/translations/en.json
```

---

## Manual Testing Checklist

After successful installation, verify the following:

### Admin Panel
- [ ] Login at `admin/index.php` with credentials (administrator / 4dMin(*)^)
- [ ] Dashboard loads without PHP errors
- [ ] All admin menu items accessible
- [ ] Language switcher works in settings

### Frontend
- [ ] Homepage loads at `/`
- [ ] Blog posts display correctly
- [ ] Theme assets load (CSS, JS, images)
- [ ] No PHP errors in logs

### Database
- [ ] All 12+ tables created in database
- [ ] Default settings populated in `tbl_settings`
- [ ] Default topic created (if applicable)

---

## Troubleshooting

### Common Issues

| Issue | Solution |
|-------|----------|
| "Headers already sent" | Check for BOM in PHP files; ensure no whitespace before `<?php` |
| Database connection fails | Verify MySQL user has permissions for the database |
| 403 Forbidden | Check file permissions (755 for dirs, 644 for files) |
| Translation cache not generated | Ensure `public/files/cache/translations/` is writable |

### Logs

Check these locations for errors:
- `public/log/` - Application logs
- PHP error log (check `php.ini` for path)

---

## Automated Testing (Optional)

Create `tests/integration/InstallationTest.php` for automated verification:

```php
<?php
class InstallationTest extends PHPUnit_Framework_TestCase
{
    public function testDatabaseConnection()
    {
        // Test PDO connection
    }
    
    public function testTranslationKeysExist()
    {
        // Verify all 41 keys present
    }
    
    public function testDefaultLanguage()
    {
        // Verify default language set correctly
    }
}
```

---

## Reporting

After testing, document:

1. **Test Results**: Pass/Fail for each test case
2. **Environment**: PHP version, database version, OS
3. **Issues Found**: Any errors or unexpected behavior
4. **Recommendations**: Suggested improvements

---

## Contact

For issues or questions about the installation process:
- Check `docs/DEVELOPER_GUIDE.md` for development patterns
- Review `AGENTS.md` for working guidelines
