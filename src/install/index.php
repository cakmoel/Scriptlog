<?php
// Clear cache based on PHP version
(version_compare(PHP_VERSION, '7.4', '>=')) ? clearstatcache() : clearstatcache(true);
/**
 * index.php file
 *
 * @category  installation the index.php file
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   0.1
 * @since     Since Release 0.1
 *
 */
// Include necessary files
require dirname(__FILE__) . '/include/settings.php';
require dirname(__FILE__) . '/include/setup.php';
require dirname(__FILE__) . '/install-layout.php';

// Initialize variables
$ca = false; // certificate authority
$current_path = preg_replace("/\/index\.php.*$/i", "", current_url());
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$server_host = $_SERVER['HTTP_HOST'];

// 1. Hard Lock: Prevent re-installation if config.php exists
if (file_exists(__DIR__ . '/../config.php')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $server_host = $_SERVER['HTTP_HOST'];
    $base_url = $protocol . '://' . $server_host . str_replace('install/index.php', '', $_SERVER['PHP_SELF']);

    // Output a clean 'Already Installed' dashboard
    install_header($current_path);
    ?>
    <div class="container py-5 fade-in text-center">
        <div class="card border-0 shadow-lg mx-auto" style="max-width: 600px; border-radius: 20px; overflow: hidden;">
            <div class="card-header bg-navy text-white py-4" style="background: #000080;">
                <i class="fa fa-shield fa-3x mb-3 text-chartreuse"></i>
                <h2 class="h4 mb-0">System Already Installed</h2>
            </div>
            <div class="card-body py-5">
                <p class="lead mb-4 text-muted">A configuration file was found. Scriptlog is already set up and secured.</p>
                <div class="alert alert-info border-0 mb-4" style="background: rgba(0, 0, 128, 0.05); color: #000080;">
                    <small><i class="fa fa-info-circle mr-2"></i> To re-install, you must manually delete or rename <code>config.php</code> in the root directory.</small>
                </div>
                <a href="<?= $base_url; ?>" class="btn btn-primary btn-lg px-5 shadow-sm rounded-pill" style="font-weight: 800; letter-spacing: 1px;">
                    <i class="fa fa-home mr-2"></i> GO TO HOMEPAGE <i class="fa fa-chevron-right ml-2 tiny"></i>
                </a>
            </div>
        </div>
    </div>
    <?php
    install_footer($current_path);
    exit;
}

// 2. CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 3. Sequential Step Tracking - Initialization
if (!isset($_SESSION['install_step_reached'])) {
    $_SESSION['install_step_reached'] = (are_all_requirements_met()) ? 1 : 0;
}

// 4. Initializations
$generated_prefix = generate_table_prefix(6);
$completed = false;
$errors = [];
$installation_path = $protocol . '://' . $server_host . dirname(htmlspecialchars($_SERVER['PHP_SELF'])) . DIRECTORY_SEPARATOR;

// 4b. Step Gatekeeper: Prevent bypassing via direct URL or DOM manipulation
$step_to_show = 1;
if (isset($_SESSION['install_step_reached'])) {
    $step_to_show = min($_SESSION['install_step_reached'] + 1, 4);
}

// Ensure requirements are met to move beyond step 1
if (!are_all_requirements_met() && $step_to_show > 1) {
    $step_to_show = 1;
    $_SESSION['install_step_reached'] = 1;
}

// 5. POST Handler with Security Gatekeepers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header($_SERVER["SERVER_PROTOCOL"] . " 403 Forbidden", true, 403);
        exit("CSRF validation failed! Please refresh and try again.");
    }

    // Step Verification: Ensure they reached Step 4 (Account) via Step 3 (Database)
    if (!isset($_SESSION['install_step_reached']) || $_SESSION['install_step_reached'] < 3) {
        header($_SERVER["SERVER_PROTOCOL"] . " 403 Forbidden", true, 403);
        exit("Security Error: You must complete the database configuration before creating an account.");
    }

    // Sanitize and validate inputs
    $dbhost = isset($_POST['db_host']) ? escapeHTML($_POST['db_host']) : "";
    $dbname = isset($_POST['db_name']) ? escapeHTML($_POST['db_name']) : "";
    $dbport = isset($_POST['db_port']) ? escapeHTML($_POST['db_port']) : "";
    $dbpass = isset($_POST['db_pass']) ? escapeHTML($_POST['db_pass']) : "";
    $dbuser = isset($_POST['db_user']) ? remove_bad_characters($_POST['db_user'], $dbhost, $_POST['db_user'], $dbpass, $dbname, $dbport) : "";
    $username = isset($_POST['user_login']) ? remove_bad_characters($_POST['user_login'], $dbhost, $dbuser, $dbpass, $dbname, $dbport) : "";
    $password = isset($_POST['user_pass1']) ? escapeHTML($_POST['user_pass1']) : "";
    $confirm = isset($_POST['user_pass2']) ? escapeHTML($_POST['user_pass2']) : "";
    $email = filter_input(INPUT_POST, 'user_email', FILTER_SANITIZE_EMAIL);
    $site_language = isset($_POST['site_language']) ? $_POST['site_language'] : 'en';

    // Hard-stop Requirement Validation
    if (!are_all_requirements_met()) {
        $errors['errorSetup'] = "Your server does not meet the minimum requirements. Please fix the items in Step 1.";
    }

    // Validate database credentials
    if (empty($errors) && (empty($dbhost) || empty($dbname) || empty($dbuser) || empty($dbpass))) {
        $errors['errorSetup'] = "Database settings are missing required fields.";
    }

    // Validate port
    if (empty($errors) && !ctype_digit(strval($dbport))) {
        $errors['errorSetup'] = "Database port must be a valid integer.";
    }

    // Validate account details
    if (empty($errors)) {
        if (empty($username) || empty($password) || empty($email)) {
            $errors['errorSetup'] = "Administrator account settings are incomplete.";
        } elseif (strlen($username) < 8 || strlen($username) > 20 || !preg_match('/^(?=.{8,20}$)(?![_.])(?!.*[_.]{2})[a-zA-Z0-9._]+(?<![_.])$/', $username)) {
            $errors['errorSetup'] = "Username must be 8-20 characters long and contain only alphanumerics, underscores, and dots.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['errorSetup'] = "Please enter a valid email address.";
        } elseif ($password !== $confirm || !preg_match('/^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[\W])(?=\S*[A-Z])(?=\S*[\d])\S*$/', $password)) {
            $errors['errorSetup'] = "Password must be at least 8 characters long and include uppercase, lowercase, numbers, and special characters.";
        }
    }

    // If no errors, proceed with installation
    if (empty($errors)) {
        try {
            $completed = true;
            $_SESSION['install'] = true;

            // Generate a secure key
            $key = installation_key(32);

            // Use user-provided prefix or generate random one
            $table_prefix = !empty($_POST['tbl_prefix']) ? trim($_POST['tbl_prefix']) : $generated_prefix;

            // Validate prefix format (alphanumeric with underscore, ending with underscore)
            if (!preg_match('/^[a-zA-Z0-9]+_$/', $table_prefix)) {
                $table_prefix = $generated_prefix;
            }

            $_SESSION['token'] = $key;

            // Create database connection
            $link = make_connection($dbhost, $dbuser, $dbpass, $dbname, $dbport);

            // Install database tables
            if (check_mysql_version($link, "5.7")) {
                // Generate encryption key BEFORE database installation to get the path
                $defuse_key_path = '';
                try {
                    $defuse_key_path = generate_defuse_key();
                } catch (Exception $e) {
                    error_log("Failed to generate defuse key: " . $e->getMessage());
                    $defuse_key_path = dirname(__DIR__, 2) . '/lib/utility/.lts/lts.php';
                }

                install_database_table($link, $protocol, $server_host, $username, $password, $email, $key, $table_prefix, $site_language, $defuse_key_path);

                // Write configuration file - system generates encryption key automatically outside web root
                $configResult = write_config_file($protocol, $server_host, $dbhost, $dbpass, $dbuser, $dbname, $dbport, $email, $key, $ca, $table_prefix);
                
                if (!$configResult) {
                    $errors['errorSetup'] = "Failed to create configuration files. Please check file permissions.";
                } else {
                    // Redirect to finish page
                     # Generate server config file based on web server
                    $server_config = generate_server_config();
                    $_SESSION['server_config'] = $server_config;
                    
                    header("Location:" . $protocol . "://" . $server_host . dirname(htmlspecialchars($_SERVER['PHP_SELF'])) . DIRECTORY_SEPARATOR . "finish.php?status=success&token={$key}", true, 302);
                    exit;
                }
            }
        } catch (mysqli_sql_exception $e) {
            $errors['errorSetup'] = "Database error: " . $e->getMessage();
        }
    }
}

// Display installation form
install_header($current_path);
?>

<!-- Theme Toggle Button -->
<div class="theme-toggle-wrap">
    <button type="button" class="theme-btn" id="themeToggle" title="Toggle Dark/Light Mode">
        <i class="fa fa-moon-o" id="themeIcon"></i>
    </button>
</div>

<div class="container py-5 fade-in">
    <div class="text-center mb-5">
        <div class="install-icon-wrap">
            <img class="install-icon" src="<?= $current_path; ?>assets/img/icon612x612.png" alt="Scriptlog Logo">
        </div>
        <h1 class="h2 mb-1 font-weight-bold">Scriptlog</h1>
        <p class="text-muted small-caps">Installation Management System</p>
    </div>

    <!-- Step Indicator -->
    <div class="step-container mx-auto" style="max-width: 800px;">
        <div class="step-item active" id="step-marker-1">
            <div class="step-number">1</div>
            <div class="step-label">Requirements</div>
        </div>
        <div class="step-item" id="step-marker-2">
            <div class="step-number">2</div>
            <div class="step-label">Preferences</div>
        </div>
        <div class="step-item" id="step-marker-3">
            <div class="step-number">3</div>
            <div class="step-label">Database</div>
        </div>
        <div class="step-item" id="step-marker-4">
            <div class="step-number">4</div>
            <div class="step-label">Account</div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-11">
            <?php if (isset($errors['errorSetup']) && !$completed) : ?>
                <div class="alert alert-danger shadow-sm mb-4 border-0 rounded-lg py-3 px-4 d-flex align-items-center" style="background: rgba(220, 38, 38, 0.1); color: var(--danger);">
                    <i class="fa fa-exclamation-triangle fa-lg mr-3"></i>
                    <div>
                        <strong class="d-block">Installation Error</strong>
                        <span class="small"><?= $errors['errorSetup']; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= $installation_path; ?>" class="needs-validation" id="installForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                <input type="hidden" id="install_step_reached" value="<?= $_SESSION['install_step_reached']; ?>">
                
                <!-- Step 1: Requirements Dashboard -->
                <div class="install-step" id="step-1">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-tasks mr-3 text-success fa-lg"></i>
                                <span>Step 1: System Requirements</span>
                            </div>
                            <span class="badge badge-success px-3 py-2 rounded-pill">Pre-flight Check</span>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-6">
                                    <?= get_sisfo(); ?>
                                    <?= required_settings(); ?>
                                </div>
                                <div class="col-lg-6">
                                    <?php if (strtolower(check_web_server()['WebServer']) != 'nginx') : ?>
                                        <?= check_mod_rewrite(); ?>
                                    <?php endif; ?>
                                    <?= check_dir_file(); ?>
                                </div>
                            </div>
                            
                            <div class="alert alert-info mt-4 border-0" style="background: rgba(0, 0, 128, 0.05); color: var(--primary);">
                                <small><i class="fa fa-info-circle mr-2"></i> If any item above is marked in red, please fix it before proceeding to ensure a stable installation.</small>
                            </div>

                            <div class="text-right mt-5">
                                <?php if (are_all_requirements_met()) : ?>
                                    <button type="button" class="btn btn-primary px-5 shadow-sm" onclick="goToStep(2)">
                                        All Good! Continue <i class="fa fa-chevron-right ml-2"></i>
                                    </button>
                                <?php else : ?>
                                    <button type="button" class="btn btn-secondary px-5 shadow-sm disabled" style="cursor: not-allowed;" title="Please fix requirements to proceed">
                                        Requirements Not Met <i class="fa fa-lock ml-2"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Language & Preferences -->
                <div class="install-step d-none" id="step-2">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header d-flex align-items-center">
                            <i class="fa fa-globe mr-3 text-success fa-lg"></i>
                            <span>Step 2: Language & Region</span>
                        </div>
                        <div class="card-body py-5">
                            <div class="row justify-content-center">
                                <div class="col-md-8 text-center">
                                    <h4 class="mb-4">Choose your preferred language</h4>
                                    <div class="mb-4">
                                        <select class="form-control form-control-lg text-center" id="siteLanguage" name="site_language" style="height: 4rem; font-size: 1.25rem; border-width: 2px;">
                                            <option value="en" selected>English (US)</option>
                                            <option value="id">Bahasa Indonesia</option>
                                            <option value="fr">Français</option>
                                            <option value="es">Español</option>
                                            <option value="ru">Русский</option>
                                            <option value="zh">中文</option>
                                            <option value="ar">العربية</option>
                                        </select>
                                    </div>
                                    <p class="text-muted small">You can change this later in the settings menu.</p>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-5">
                                <button type="button" class="btn btn-link text-muted font-weight-bold" onclick="goToStep(1)">
                                    <i class="fa fa-chevron-left mr-2"></i> Back to Requirements
                                </button>
                                <button type="button" class="btn btn-primary px-5 shadow-sm" onclick="goToStep(3)">
                                    Next: Database <i class="fa fa-chevron-right ml-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Database Settings -->
                <div class="install-step d-none" id="step-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header d-flex align-items-center">
                            <i class="fa fa-database mr-3 text-success fa-lg"></i>
                            <span>Step 3: Database Configuration</span>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-9 mb-4">
                                    <label for="databaseHost" class="font-weight-bold">Database Host</label>
                                    <input type="text" class="form-control form-control-lg" id="databaseHost" name="db_host" placeholder="localhost" value="<?= htmlspecialchars($dbhost ?? 'localhost'); ?>" required>
                                    <span class="help-text">Standard host is usually "localhost".</span>
                                </div>
                                <div class="col-md-3 mb-4">
                                    <label for="databasePort" class="font-weight-bold">Port</label>
                                    <input type="text" class="form-control form-control-lg" id="databasePort" name="db_port" placeholder="3306" value="<?= htmlspecialchars($dbport ?? '3306'); ?>" required>
                                    <span class="help-text">Default: 3306</span>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="databaseName" class="font-weight-bold">Database Name</label>
                                <input type="text" class="form-control form-control-lg" id="databaseName" name="db_name" placeholder="scriptlog_db" value="<?= htmlspecialchars($dbname ?? ''); ?>" required>
                                <span class="help-text">Enter the name of your pre-created SQL database.</span>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label for="databaseUser" class="font-weight-bold">Database Username</label>
                                    <input type="text" class="form-control form-control-lg" id="databaseUser" name="db_user" placeholder="db_user" value="<?= htmlspecialchars($dbuser ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label for="databasePass" class="font-weight-bold">Database Password</label>
                                    <input type="password" class="form-control form-control-lg" id="databasePass" name="db_pass" placeholder="••••••••" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="tablePrefix" class="font-weight-bold">Table Prefix</label>
                                <input type="text" class="form-control form-control-lg" id="tablePrefix" name="tbl_prefix" placeholder="<?= htmlspecialchars($generated_prefix); ?>" value="<?= htmlspecialchars($generated_prefix); ?>">
                                <span class="help-text">For security, we recommend using the generated prefix.</span>
                            </div>

                            <div class="d-flex justify-content-between mt-5">
                                <button type="button" class="btn btn-link text-muted font-weight-bold" onclick="goToStep(2)">
                                    <i class="fa fa-chevron-left mr-2"></i> Back
                                </button>
                                <button type="button" class="btn btn-primary px-5 shadow-sm" id="btn-validate-db" onclick="validateDatabaseStep()">
                                    Next: Account <i class="fa fa-chevron-right ml-2"></i>
                                </button>
                                <button type="button" class="btn btn-primary d-none px-5 shadow-sm" id="btn-next-account" onclick="goToStep(4)">
                                    Continue to Account <i class="fa fa-chevron-right ml-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Administrator Account -->
                <div class="install-step d-none" id="step-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header d-flex align-items-center">
                            <i class="fa fa-user-circle mr-3 text-success fa-lg"></i>
                            <span>Step 4: Administrator Account</span>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <label for="username" class="font-weight-bold">Create Admin Username</label>
                                <input type="text" class="form-control form-control-lg" name="user_login" id="username" placeholder="admin" value="<?= htmlspecialchars($username ?? ''); ?>" required>
                                <span class="help-text">This is what you'll use to log in.</span>
                            </div>

                            <div class="mb-4">
                                <label for="email" class="font-weight-bold">Email Address</label>
                                <input type="email" class="form-control form-control-lg" id="email" name="user_email" placeholder="admin@example.com" value="<?= htmlspecialchars($email ?? ''); ?>" required>
                                <span class="help-text">For recovery and system notifications.</span>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label for="pass1" class="font-weight-bold">Password</label>
                                    <input type="password" class="form-control form-control-lg" name="user_pass1" id="pass1" placeholder="••••••••" required>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label for="pass2" class="font-weight-bold">Confirm Password</label>
                                    <input type="password" class="form-control form-control-lg" name="user_pass2" id="pass2" placeholder="••••••••" required>
                                </div>
                            </div>
                            
                            <div class="alert alert-warning py-3 mb-4 rounded-lg d-flex align-items-center border-0" style="background: rgba(127, 255, 0, 0.05); color: #7FFF00;">
                                <i class="fa fa-shield mr-3 fa-lg"></i>
                                <small>Strong passwords improve your site security. Aim for 8+ characters with a mix of letters and numbers.</small>
                            </div>

                            <input type="hidden" name="setup" value="install">
                            <div class="d-flex justify-content-between mt-5">
                                <button type="button" class="btn btn-link text-muted font-weight-bold" onclick="goToStep(3)">
                                    <i class="fa fa-chevron-left mr-2"></i> Access Database
                                </button>
                                <button type="submit" class="btn btn-success px-5 shadow-sm py-3 text-uppercase">
                                    <i class="fa fa-rocket mr-2"></i> Launch My Blog
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const themeBtn = document.getElementById('themeToggle');
const themeIcon = document.getElementById('themeIcon');

themeBtn.addEventListener('click', () => {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('scriptlog_install_theme', newTheme);
    updateThemeIcon(newTheme);
});

function updateThemeIcon(theme) {
    if (theme === 'dark') {
        themeIcon.className = 'fa fa-sun-o';
    } else {
        themeIcon.className = 'fa fa-moon-o';
    }
}

updateThemeIcon(document.documentElement.getAttribute('data-theme') || 'light');

function goToStep(step) {
    // Client-side Gatekeeper: Prevent skipping steps
    const reachedInput = document.getElementById('install_step_reached');
    let reached = parseInt(reachedInput.value);
    
    if (step > reached + 1) {
        alert("Please complete the previous steps first.");
        return;
    }

    // Successfully moving to step
    document.querySelectorAll('.install-step').forEach(el => el.classList.add('d-none'));
    const target = document.getElementById('step-' + step);
    if(target) {
        target.classList.remove('d-none');
        target.classList.add('fade-in');
    }
    
    // Update progress state if moving forward (but Step 3 requires AJAX validation separately)
    if (step > reached && step < 4) {
        reachedInput.value = step;
        fetch('update-step.php?step=' + step);
    }

    document.querySelectorAll('.step-item').forEach(el => el.classList.remove('active', 'completed', 'blocked'));
    for(let i=1; i<=4; i++) {
        let marker = document.getElementById('step-marker-' + i);
        if(i < step) marker.classList.add('completed');
        if(i === step) marker.classList.add('active');
        if(i > reached + 1) marker.classList.add('blocked');
    }
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

async function validateDatabaseStep() {
    const btn = document.getElementById('btn-validate-db');
    const originalText = btn.innerHTML;
    
    // Hard Error: Check if fields are empty before AJAX
    const host = document.getElementById('databaseHost').value.trim();
    const name = document.getElementById('databaseName').value.trim();
    const user = document.getElementById('databaseUser').value.trim();
    
    if (!host || !name || !user) {
        alert("Hard Error: Database Host, Name, and Username are required.");
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin mr-2"></i> Verifying...';

    const formData = new FormData();
    formData.append('db_host', host);
    formData.append('db_name', name);
    formData.append('db_port', document.getElementById('databasePort').value);
    formData.append('db_user', user);
    formData.append('db_pass', document.getElementById('databasePass').value);
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

    try {
        const response = await fetch('validate-db.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            document.getElementById('install_step_reached').value = "3";
            btn.classList.add('d-none');
            document.getElementById('btn-next-account').classList.remove('d-none');
            goToStep(4);
        } else {
            alert("Database Connection Failed: " + data.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    } catch (error) {
        alert("System Error: Could not reach validation endpoint.");
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

<?php if (isset($errors['errorSetup'])) : ?>
    document.addEventListener('DOMContentLoaded', function() {
        let errText = "<?= $errors['errorSetup']; ?>";
        if (errText.includes('Database')) {
            goToStep(3);
        } else if (errText.includes('Username') || errText.includes('Password') || errText.includes('Email')) {
            goToStep(4);
        }
    });
<?php else : ?>
    document.addEventListener('DOMContentLoaded', function() {
        goToStep(<?= $step_to_show; ?>);
    });
<?php endif; ?>
</script>



<?php
install_footer($current_path);
ob_end_flush();

 


