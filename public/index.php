<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Define paths relative to the current index.php
$basePath = realpath(__DIR__);
$vendorPath = $basePath . '/../vendor';
$autoloadPath = $basePath . '/../vendor/autoload.php';
$envPath = $basePath . '/../.env';
$envExamplePath = $basePath . '/../.env.example';
$maintenancePath = $basePath . '/../storage/framework/maintenance.php';

// Check if Laravel is ready to boot normally
$canBootLaravel = file_exists($autoloadPath) &&
                  file_exists($envPath) &&
                  is_dir($vendorPath);

// If Laravel can boot normally, check if installation is complete
if ($canBootLaravel) {
    // Load environment variables to check installation status
    if (file_exists($envPath)) {
        $envLines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($envLines as $line) {
            if (strpos($line, '=') !== false && !str_starts_with(trim($line), '#')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, '"\'');
                if (!array_key_exists($key, $_ENV)) {
                    $_ENV[$key] = $value;
                    putenv("{$key}={$value}");
                }
            }
        }
    }

    // Check if installation is complete by verifying key components
    $appKeySet = !empty($_ENV['APP_KEY'] ?? '');
    $dbConfigured = !empty($_ENV['DB_DATABASE'] ?? '') && !empty($_ENV['DB_USERNAME'] ?? '');

    // Additional check: see if we can connect to database and if users table exists
    $installationComplete = false;
    if ($appKeySet && $dbConfigured) {
        try {
            $dbConfig = [
                'connection' => $_ENV['DB_CONNECTION'] ?? 'mysql',
                'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
                'port' => $_ENV['DB_PORT'] ?? '3306',
                'database' => $_ENV['DB_DATABASE'],
                'username' => $_ENV['DB_USERNAME'],
                'password' => $_ENV['DB_PASSWORD'] ?? ''
            ];

            $dsn = "{$dbConfig['connection']}:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']}";
            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);

            // Check if users table exists (indicates successful migration)
            $result = $pdo->query("SHOW TABLES LIKE 'users'")->rowCount() > 0;
            if ($result) {
                $installationComplete = true;
            }
        } catch (\Exception $e) {
            // Database connection failed, continue with installer
        }
    }

    // If installation is complete, boot Laravel normally
    if ($installationComplete) {
        // Determine if the application is in maintenance mode...
        if (file_exists($maintenancePath)) {
            require $maintenancePath;
        }

        // Register the Composer autoloader...
        require $autoloadPath;

        // Bootstrap Laravel and handle the request...
        /** @var Application $app */
        $app = require_once __DIR__.'/../bootstrap/app.php';

        $app->handleRequest(Request::capture());
        exit; // Ensure we don't continue to installer code
    }
}

// --- INSTALLER CODE STARTS HERE ---
// This section only runs if Laravel is not ready or installation is incomplete

$installer_message = '';
$installer_status = 'info';
$log_output = [];

/**
 * Helper function to run a shell command and capture output.
 */
function runShellCommand($command, $context) {
    global $log_output;
    $log_output[] = ['message' => "Executing {$context}: `{$command}`", 'status' => 'running'];

    // Change to project root directory
    $projectRoot = realpath(__DIR__);
    $originalDir = getcwd();
    chdir($projectRoot);

    $descriptorspec = [
        0 => ["pipe", "r"],
        1 => ["pipe", "w"],
        2 => ["pipe", "w"]
    ];

    $process = proc_open($command, $descriptorspec, $pipes);

    if (is_resource($process)) {
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($process);

        $output = trim($stdout . "\n" . $stderr);
    } else {
        $status = 1;
        $output = "Failed to execute command";
    }

    // Restore original directory
    chdir($originalDir);

    // Check for errors
    if ($status !== 0 ||
        str_contains($output, 'PHP Fatal error') ||
        str_contains($output, 'Uncaught Exception') ||
        str_contains($output, 'RuntimeException: Composer detected issues') ||
        str_contains($output, 'Failed to download')
    ) {
        $log_output[] = ['message' => "{$context} failed!", 'status' => 'error'];
        $log_output[] = ['message' => $output, 'status' => 'error'];
        return ['success' => false, 'output' => $output];
    } else {
        $log_output[] = ['message' => "{$context} completed successfully.", 'status' => 'success'];
        if (!empty($output)) {
            $log_output[] = ['message' => $output, 'status' => 'info'];
        }
        return ['success' => true, 'output' => $output];
    }
}

/**
 * Check system requirements
 */
function checkSystemRequirements() {
    $requirements = [
        'php_version' => ['status' => false, 'message' => '', 'required' => true],
        'composer_version' => ['status' => false, 'message' => '', 'required' => true],
        'storage_permissions' => ['status' => false, 'message' => '', 'required' => true],
        'bootstrap_permissions' => ['status' => false, 'message' => '', 'required' => true],
    ];

    // Check PHP version (>= 8.3)
    $phpVersion = PHP_VERSION;
    $phpVersionFloat = (float)PHP_VERSION;
    if (version_compare($phpVersion, '8.3.0', '>=')) {
        $requirements['php_version']['status'] = true;
        $requirements['php_version']['message'] = "PHP {$phpVersion} ✓";
    } else {
        $requirements['php_version']['message'] = "PHP {$phpVersion} (Required: >= 8.3.0)";
    }

    // Check Composer version (>= 2.0)
    $composerPaths = ['composer', '/usr/local/bin/composer', '/usr/bin/composer'];
    $composerVersion = null;
    $composerExecutable = null;

    foreach ($composerPaths as $path) {
        $testResult = shell_exec("which {$path} 2>/dev/null");
        if (!empty($testResult)) {
            $composerExecutable = trim($testResult);
            break;
        }
    }

    if (!$composerExecutable) {
        // Try direct execution
        $testComposer = shell_exec('composer --version 2>/dev/null');
        if (!empty($testComposer)) {
            $composerExecutable = 'composer';
        }
    }

    if ($composerExecutable) {
        $composerOutput = shell_exec("{$composerExecutable} --version 2>/dev/null");
        if (preg_match('/Composer version (\d+\.\d+\.\d+)/i', $composerOutput, $matches)) {
            $composerVersion = $matches[1];
            if (version_compare($composerVersion, '2.0.0', '>=')) {
                $requirements['composer_version']['status'] = true;
                $requirements['composer_version']['message'] = "Composer {$composerVersion} ✓";
            } else {
                $requirements['composer_version']['message'] = "Composer {$composerVersion} (Required: >= 2.0.0)";
            }
        } else {
            $requirements['composer_version']['message'] = "Composer found but version detection failed";
        }
    } else {
        $requirements['composer_version']['message'] = "Composer not found";
    }

    // Check storage folder permissions
    $storagePath = realpath(__DIR__) . '/../storage';
    if (is_dir($storagePath)) {
        if (is_writable($storagePath)) {
            // Check subdirectories
            $storageSubdirs = ['app', 'framework', 'logs'];
            $allWritable = true;
            foreach ($storageSubdirs as $subdir) {
                $subdirPath = $storagePath . '/' . $subdir;
                if (is_dir($subdirPath) && !is_writable($subdirPath)) {
                    $allWritable = false;
                    break;
                }
            }
            if ($allWritable) {
                $requirements['storage_permissions']['status'] = true;
                $requirements['storage_permissions']['message'] = "Storage folder writable ✓";
            } else {
                $requirements['storage_permissions']['message'] = "Some storage subdirectories are not writable";
            }
        } else {
            $requirements['storage_permissions']['message'] = "Storage folder is not writable";
        }
    } else {
        $requirements['storage_permissions']['message'] = "Storage folder not found";
    }

    // Check bootstrap/cache permissions
    $bootstrapCachePath = realpath(__DIR__) . '/../bootstrap/cache';
    if (is_dir($bootstrapCachePath)) {
        if (is_writable($bootstrapCachePath)) {
            $requirements['bootstrap_permissions']['status'] = true;
            $requirements['bootstrap_permissions']['message'] = "Bootstrap cache folder writable ✓";
        } else {
            $requirements['bootstrap_permissions']['message'] = "Bootstrap cache folder is not writable";
        }
    } else {
        // Try to create the directory
        if (mkdir($bootstrapCachePath, 0755, true)) {
            $requirements['bootstrap_permissions']['status'] = true;
            $requirements['bootstrap_permissions']['message'] = "Bootstrap cache folder created and writable ✓";
        } else {
            $requirements['bootstrap_permissions']['message'] = "Bootstrap cache folder not found and cannot be created";
        }
    }

    return $requirements;
}

/**
 * Helper function to update/create .env variables.
 */
function updateDotEnv($envValues) {
    global $envPath, $envExamplePath, $log_output;

    try {
        // Create .env from .env.example if it doesn't exist
        if (!file_exists($envPath)) {
            if (file_exists($envExamplePath)) {
                copy($envExamplePath, $envPath);
                $log_output[] = ['message' => '.env file created from .env.example.', 'status' => 'info'];
            } else {
                file_put_contents($envPath, '');
                $log_output[] = ['message' => 'Empty .env file created.', 'status' => 'info'];
            }
        }

        $envContent = file_get_contents($envPath);

        foreach ($envValues as $key => $value) {
            $value = $value ?? '';

            // Properly escape values
            if (preg_match('/[\s#"\'$\\\\]/', $value) || empty($value)) {
                $value = '"' . str_replace(['"', '\\'], ['\"', '\\\\'], $value) . '"';
            }

            $pattern = "/^{$key}=.*$/m";
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, "{$key}={$value}", $envContent);
            } else {
                $envContent .= "\n{$key}={$value}";
            }

            $log_output[] = ['message' => "Updated ENV variable: {$key}", 'status' => 'info'];
        }

        file_put_contents($envPath, $envContent);
        return true;
    } catch (\Exception $e) {
        $log_output[] = ['message' => "Failed to update .env: " . $e->getMessage(), 'status' => 'error'];
        return false;
    }
}

/**
 * Test database connection
 */
function testDatabaseConnection($config) {
    try {
        $dsn = "{$config['connection']}:host={$config['host']};port={$config['port']};dbname={$config['database']}";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        return ['success' => true, 'pdo' => $pdo];
    } catch (\Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// --- Installation Stages ---

// Check system requirements first
$systemRequirements = checkSystemRequirements();
$allRequirementsMet = true;
foreach ($systemRequirements as $req) {
    if ($req['required'] && !$req['status']) {
        $allRequirementsMet = false;
        break;
    }
}

// Stage 0: Check for Composer Dependencies
if (!file_exists($autoloadPath) || !is_dir($vendorPath)) {

    if (isset($_POST['run_composer']) && $allRequirementsMet) {
        $action = $_POST['run_composer'];

        // Find Composer executable
        $composerPaths = ['composer', '/usr/local/bin/composer', '/usr/bin/composer'];
        $composerExecutable = null;

        foreach ($composerPaths as $path) {
            $testResult = shell_exec("which {$path} 2>/dev/null");
            if (!empty($testResult)) {
                $composerExecutable = trim($testResult);
                break;
            }
        }

        if (!$composerExecutable) {
            // Try direct execution
            $testComposer = shell_exec('composer --version 2>/dev/null');
            if (!empty($testComposer)) {
                $composerExecutable = 'composer';
            }
        }

        if (!$composerExecutable) {
            $installer_message = "Composer executable not found. Please install Composer globally or ensure it's in your PATH.";
            $installer_status = 'error';
            goto render_composer_ui;
        }

        $commandSuffix = ($action === 'install')
            ? 'install --no-interaction --prefer-dist --optimize-autoloader'
            : 'update --no-interaction --prefer-dist --optimize-autoloader';

        $composerCommand = "{$composerExecutable} {$commandSuffix}";
        $result = runShellCommand($composerCommand, "Composer {$action}");

        if ($result['success']) {
            $installer_message = "Composer {$action} completed successfully! Reloading...";
            $installer_status = 'success';
            echo "<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>";
        } else {
            $installer_message = "Composer {$action} failed!";
            $installer_status = 'error';
        }
    }

    render_composer_ui:
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Project Setup - System Requirements</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
            .installer-card { background: white; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); padding: 40px; max-width: 700px; width: 90%; }
            .brand { color: #667eea; font-weight: 700; margin-bottom: 30px; }
            .requirement-item { background: #f8f9fa; border-radius: 10px; padding: 15px; margin-bottom: 15px; border-left: 4px solid #dee2e6; }
            .requirement-item.success { border-left-color: #28a745; background: #d4edda; }
            .requirement-item.error { border-left-color: #dc3545; background: #f8d7da; }
            .requirement-icon { font-size: 1.2em; margin-right: 10px; }
            .requirement-icon.success { color: #28a745; }
            .requirement-icon.error { color: #dc3545; }
            .log-container { background: #f8f9fa; border-radius: 10px; padding: 20px; margin: 20px 0; max-height: 300px; overflow-y: auto; font-family: 'SF Mono', Monaco, monospace; font-size: 14px; }
            .log-entry { display: flex; align-items: flex-start; margin-bottom: 8px; }
            .log-entry.success { color: #28a745; }
            .log-entry.error { color: #dc3545; }
            .log-entry.running { color: #007bff; }
            .log-entry.info { color: #6c757d; }
            .btn-modern { border-radius: 12px; padding: 12px 30px; font-weight: 600; border: none; transition: all 0.3s ease; }
            .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
            .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3); }
            .btn-primary:disabled { background: #6c757d; transform: none; box-shadow: none; cursor: not-allowed; }
            .requirements-header { border-bottom: 2px solid #e9ecef; padding-bottom: 15px; margin-bottom: 20px; }
        </style>
    </head>
    <body>
        <div class="installer-card">
            <h1 class="brand text-center">🚀 Project Setup</h1>
            <h3 class="text-center text-muted mb-4">System Requirements Check</h3>

            <?php if (!empty($installer_message)): ?>
                <div class="alert alert-<?php echo $installer_status; ?>" role="alert">
                    <?php echo $installer_message; ?>
                </div>
            <?php endif; ?>

            <div class="requirements-section mb-4">
                <h5 class="requirements-header">📋 System Requirements</h5>

                <?php foreach ($systemRequirements as $key => $req): ?>
                    <div class="requirement-item <?php echo $req['status'] ? 'success' : 'error'; ?>">
                        <div class="d-flex align-items-center">
                            <span class="requirement-icon <?php echo $req['status'] ? 'success' : 'error'; ?>">
                                <?php echo $req['status'] ? '✅' : '❌'; ?>
                            </span>
                            <div>
                                <strong>
                                    <?php
                                    $titles = [
                                        'php_version' => 'PHP Version',
                                        'composer_version' => 'Composer Version',
                                        'storage_permissions' => 'Storage Permissions',
                                        'bootstrap_permissions' => 'Bootstrap Cache Permissions'
                                    ];
                                    echo $titles[$key];
                                    ?>
                                </strong>
                                <div class="small text-muted"><?php echo $req['message']; ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!$allRequirementsMet): ?>
                <div class="alert alert-warning" role="alert">
                    <strong>⚠️ Requirements Not Met</strong><br>
                    Please fix the above requirements before proceeding with installation.

                    <div class="mt-3">
                        <small><strong>Quick Fixes:</strong></small>
                        <ul class="small mb-0 mt-1">
                            <li><strong>Permissions:</strong> Run <code>chmod -R 755 storage bootstrap/cache</code></li>
                            <li><strong>PHP:</strong> Upgrade to PHP 8.3 or higher</li>
                            <li><strong>Composer:</strong> Update Composer to version 2.0 or higher</li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <p class="text-center mb-4">
                <?php if ($allRequirementsMet): ?>
                    ✅ All requirements met! You can now install dependencies.
                <?php else: ?>
                    ❌ Please resolve the requirements above to proceed.
                <?php endif; ?>
            </p>

            <form method="POST" class="text-center">
                <div class="d-grid gap-2">
                    <button type="submit" name="run_composer" value="install" class="btn btn-primary btn-modern" <?php echo !$allRequirementsMet ? 'disabled' : ''; ?>>
                        📦 Install Dependencies
                    </button>
                    <button type="submit" name="run_composer" value="update" class="btn btn-outline-secondary btn-modern" <?php echo !$allRequirementsMet ? 'disabled' : ''; ?>>
                        🔄 Update Dependencies
                    </button>
                </div>
            </form>

            <?php if (!empty($log_output)): ?>
                <div class="log-container">
                    <?php foreach ($log_output as $entry): ?>
                        <div class="log-entry <?php echo $entry['status']; ?>">
                            <span class="me-2">
                                <?php
                                switch($entry['status']) {
                                    case 'success': echo '✅'; break;
                                    case 'error': echo '❌'; break;
                                    case 'running': echo '⏳'; break;
                                    default: echo '➡️'; break;
                                }
                                ?>
                            </span>
                            <span><?php echo htmlspecialchars($entry['message']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Stage 1: Handle Installation Form Submission
if (isset($_POST['install_app']) && $_POST['install_app'] === 'true') {
    // Validation
    $errors = [];
    $required = ['db_database', 'db_username', 'admin_name', 'admin_email', 'admin_password'];

    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
        }
    }

    if (!filter_var($_POST['admin_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid admin email is required.';
    }

    if (strlen($_POST['admin_password']) < 8) {
        $errors[] = 'Admin password must be at least 8 characters.';
    }

    if ($_POST['admin_password'] !== $_POST['admin_password_confirm']) {
        $errors[] = 'Admin passwords do not match.';
    }

    if (!empty($errors)) {
        $installer_message = implode('<br>', $errors);
        $installer_status = 'error';
        goto render_installation_form;
    }

    // Begin installation process
    $log_output[] = ['message' => 'Starting Laravel application installation...', 'status' => 'running'];

    // Generate new APP_KEY
    $appKey = 'base64:' . base64_encode(random_bytes(32));

    // Update .env file
    $envData = [
        'APP_NAME' => 'Laravel',
        'APP_ENV' => 'production',
        'APP_KEY' => $appKey,
        'APP_DEBUG' => 'false',
        'APP_URL' => (($_SERVER['HTTPS'] ?? '') ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'],
        'DB_CONNECTION' => $_POST['db_connection'] ?? 'mysql',
        'DB_HOST' => $_POST['db_host'] ?? '127.0.0.1',
        'DB_PORT' => $_POST['db_port'] ?? '3306',
        'DB_DATABASE' => $_POST['db_database'],
        'DB_USERNAME' => $_POST['db_username'],
        'DB_PASSWORD' => $_POST['db_password'] ?? ''
    ];

    if (!updateDotEnv($envData)) {
        $installer_message = 'Failed to update .env file.';
        $installer_status = 'error';
        goto render_installation_form;
    }

    // Test database connection
    $dbTest = testDatabaseConnection([
        'connection' => $envData['DB_CONNECTION'],
        'host' => $envData['DB_HOST'],
        'port' => $envData['DB_PORT'],
        'database' => $envData['DB_DATABASE'],
        'username' => $envData['DB_USERNAME'],
        'password' => $envData['DB_PASSWORD']
    ]);

    if (!$dbTest['success']) {
        $installer_message = 'Database connection failed: ' . $dbTest['error'];
        $installer_status = 'error';
        goto render_installation_form;
    }

    $pdo = $dbTest['pdo'];

    // Clear caches before migration
    runShellCommand('php artisan config:clear', 'Clearing configuration cache');
    runShellCommand('php artisan cache:clear', 'Clearing application cache');

    // Run migrations with proper error handling
    $migrateResult = runShellCommand('php artisan migrate --force --step', 'Running database migrations');
    if (!$migrateResult['success']) {
        // Try without --step flag as fallback
        $log_output[] = ['message' => 'Step migration failed, trying standard migration...', 'status' => 'info'];
        $migrateResult2 = runShellCommand('php artisan migrate --force', 'Retrying database migrations');
        if (!$migrateResult2['success']) {
            $installer_message = 'Database migrations failed. Check database connection and permissions.';
            $installer_status = 'error';
            goto render_installation_form;
        }
    }

    // Verify that essential tables exist before seeding
    try {
        $log_output[] = ['message' => 'Verifying required tables exist...', 'status' => 'running'];

        // Check for users table
        $usersTableExists = $pdo->query("SHOW TABLES LIKE 'users'")->rowCount() > 0;
        if (!$usersTableExists) {
            throw new Exception('Users table not found after migration');
        }

        // Check for currencies table (required for your seeder)
        $currenciesTableExists = $pdo->query("SHOW TABLES LIKE 'currencies'")->rowCount() > 0;
        if (!$currenciesTableExists) {
            $log_output[] = ['message' => 'Currencies table not found. Your seeder may fail.', 'status' => 'warn'];
        }

        $log_output[] = ['message' => 'Required database tables verified successfully.', 'status' => 'success'];

    } catch (\Exception $e) {
        $log_output[] = ['message' => 'Table verification failed: ' . $e->getMessage(), 'status' => 'warn'];
        $log_output[] = ['message' => 'Proceeding with user creation...', 'status' => 'info'];
    }

    // Create admin user
    try {
        $log_output[] = ['message' => 'Creating admin user...', 'status' => 'running'];

        $adminEmail = $_POST['admin_email'];
        $adminName = $_POST['admin_name'];
        $adminPassword = password_hash($_POST['admin_password'], PASSWORD_BCRYPT);

        // Check if user already exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $checkStmt->execute([$adminEmail]);

        if ($checkStmt->fetchColumn() > 0) {
            $log_output[] = ['message' => "Admin user already exists: {$adminEmail}", 'status' => 'info'];
        } else {
            $insertStmt = $pdo->prepare("
                INSERT INTO users (name, email,role, password, email_verified_at, created_at, updated_at)
                VALUES (?, ?, ?, ?, NOW(), NOW(), NOW())
            ");
            $insertStmt->execute([$adminName, $adminEmail,'admin', $adminPassword]);
            $log_output[] = ['message' => "Admin user created: {$adminEmail}", 'status' => 'success'];
        }
    } catch (\Exception $e) {
        $installer_message = 'Failed to create admin user: ' . $e->getMessage();
        $installer_status = 'error';
        goto render_installation_form;
    }

    // Run additional setup commands
    runShellCommand('php artisan storage:link', 'Creating storage symlink');

    // Check if DatabaseSeeder exists and required SQL files
    $databaseSeederPath = realpath(__DIR__) . '/../database/seeders/DatabaseSeeder.php';
    $sqlFilesPath = realpath(__DIR__) . '/../database/seeders/sql/';
    $requiredSqlFiles = ['flaggroups.sql', 'systemflags.sql', 'languages.sql','pages.sql'];

    $shouldRunSeeder = false;
    $missingFiles = [];

    if (file_exists($databaseSeederPath)) {
        $seederContent = file_get_contents($databaseSeederPath);
        // Check if DatabaseSeeder has actual seeding content
        if (strpos($seederContent, 'Currency::') !== false ||
            strpos($seederContent, 'DB::unprepared') !== false ||
            strpos($seederContent, 'User::factory') !== false ||
            strpos($seederContent, '$this->call') !== false) {

            // Check if required SQL files exist
            foreach ($requiredSqlFiles as $sqlFile) {
                if (!file_exists($sqlFilesPath . $sqlFile)) {
                    $missingFiles[] = $sqlFile;
                }
            }

            if (empty($missingFiles)) {
                $shouldRunSeeder = true;
            } else {
                $log_output[] = ['message' => 'Missing SQL files: ' . implode(', ', $missingFiles), 'status' => 'warn'];
                $log_output[] = ['message' => 'Seeding will be skipped. Add SQL files to database/seeders/sql/', 'status' => 'info'];
            }
        }
    }

    if ($shouldRunSeeder) {
        // Clear any cached config before seeding to ensure fresh environment
        runShellCommand('php artisan config:clear', 'Clearing config before seeding');

        // First check if Currency model/migration exists
        $currencyMigrationExists = !empty(glob(realpath(__DIR__) . '/../database/migrations/*_create_currencies_table.php'));
        if (!$currencyMigrationExists) {
            $log_output[] = ['message' => 'Currency migration not found. Seeding may fail without currencies table.', 'status' => 'warn'];
        }

        // Run seeding with verbose output for better debugging
        $seedResult = runShellCommand('php artisan db:seed --force --verbose', 'Running database seeders');
        if (!$seedResult['success']) {
            // Try without verbose flag as fallback
            $log_output[] = ['message' => 'Verbose seeding failed, trying standard seeding...', 'status' => 'info'];
            $seedResult2 = runShellCommand('php artisan db:seed --force', 'Retrying database seeders');
            if (!$seedResult2['success']) {
                $log_output[] = ['message' => 'Database seeding failed. Common issues:', 'status' => 'error'];
                $log_output[] = ['message' => '1. Missing Currency model or migration', 'status' => 'info'];
                $log_output[] = ['message' => '2. SQL files missing from database/seeders/sql/', 'status' => 'info'];
                $log_output[] = ['message' => '3. Database permissions or syntax errors', 'status' => 'info'];
                $log_output[] = ['message' => 'You can run "php artisan db:seed --force" manually after installation.', 'status' => 'info'];
            }
        } else {
            $log_output[] = ['message' => 'Database seeding completed successfully!', 'status' => 'success'];
        }
    } else {
        if (!file_exists($databaseSeederPath)) {
            $log_output[] = ['message' => 'DatabaseSeeder not found - skipping seeding', 'status' => 'info'];
        } else {
            $log_output[] = ['message' => 'DatabaseSeeder exists but dependencies missing - skipping seeding', 'status' => 'info'];
        }
        $log_output[] = ['message' => 'You can run seeding manually: php artisan db:seed --force', 'status' => 'info'];
    }

    runShellCommand('php artisan config:cache', 'Caching configuration');
    runShellCommand('php artisan route:cache', 'Caching routes');
    runShellCommand('php artisan view:cache', 'Caching views');

    // Installation completed - this will trigger Laravel boot on next request
    $installer_message = 'Installation completed successfully! Your Laravel application is ready. Redirecting...';
    $installer_status = 'success';

    // Redirect to trigger normal Laravel boot
    echo "<script>setTimeout(function(){ window.location.href = '/'; }, 3000);</script>";
    goto render_installation_form;
}

// Stage 2: Render installation form
render_installation_form:

// Load existing .env values if they exist
$envValues = [];
if (file_exists($envPath)) {
    $envLines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        if (strpos($line, '=') !== false && !str_starts_with(trim($line), '#')) {
            [$key, $value] = explode('=', $line, 2);
            $envValues[trim($key)] = trim($value, '"\'');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px 0; }
        .installer-container { max-width: 800px; margin: 0 auto; }
        .installer-card { background: white; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); padding: 40px; margin: 20px 0; }
        .brand { color: #667eea; font-weight: 700; margin-bottom: 30px; }
        .form-control, .form-select { border-radius: 10px; padding: 12px 16px; border: 2px solid #e9ecef; transition: border-color 0.3s ease; }
        .form-control:focus, .form-select:focus { border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25); }
        .btn-modern { border-radius: 12px; padding: 15px 30px; font-weight: 600; border: none; transition: all 0.3s ease; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3); }
        .log-container { background: #f8f9fa; border-radius: 15px; padding: 25px; margin: 25px 0; max-height: 400px; overflow-y: auto; font-family: 'SF Mono', Monaco, monospace; font-size: 14px; }
        .log-entry { display: flex; align-items: flex-start; margin-bottom: 10px; padding: 5px 0; }
        .log-entry.success { color: #28a745; }
        .log-entry.error { color: #dc3545; }
        .log-entry.running { color: #007bff; }
        .log-entry.info { color: #6c757d; }
        .section-divider { border: none; border-top: 2px solid #e9ecef; margin: 30px 0; }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="installer-card">
            <h1 class="brand text-center">🚀 Project Setup</h1>
            <h3 class="text-center text-muted mb-4">Configure Your Application</h3>

            <?php if (!empty($installer_message)): ?>
                <div class="alert alert-<?php echo $installer_status; ?>" role="alert">
                    <?php echo $installer_message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($log_output)): ?>
                <div class="log-container">
                    <h5 class="mb-3">🔧 Installation Progress</h5>
                    <?php foreach ($log_output as $entry): ?>
                        <div class="log-entry <?php echo $entry['status']; ?>">
                            <span class="me-2">
                                <?php
                                switch($entry['status']) {
                                    case 'success': echo '✅'; break;
                                    case 'error': echo '❌'; break;
                                    case 'running': echo '⏳'; break;
                                    default: echo '➡️'; break;
                                }
                                ?>
                            </span>
                            <span><?php echo htmlspecialchars($entry['message']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($installer_status !== 'success' || empty($installer_message)): ?>
                <form method="POST">
                    <input type="hidden" name="install_app" value="true">

                    <div class="mb-4">
                        <h4 class="text-primary mb-3">📊 Database Configuration</h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="db_connection" class="form-label">Database Type</label>
                                <select class="form-select" id="db_connection" name="db_connection" required>
                                    <option value="mysql" <?php echo ($envValues['DB_CONNECTION'] ?? 'mysql') === 'mysql' ? 'selected' : ''; ?>>MySQL</option>
                                    <option value="pgsql" <?php echo ($envValues['DB_CONNECTION'] ?? '') === 'pgsql' ? 'selected' : ''; ?>>PostgreSQL</option>
                                    <option value="sqlite" <?php echo ($envValues['DB_CONNECTION'] ?? '') === 'sqlite' ? 'selected' : ''; ?>>SQLite</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="db_host" class="form-label">Host</label>
                                <input type="text" class="form-control" id="db_host" name="db_host" value="<?php echo htmlspecialchars($envValues['DB_HOST'] ?? '127.0.0.1'); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="db_port" class="form-label">Port</label>
                                <input type="number" class="form-control" id="db_port" name="db_port" value="<?php echo htmlspecialchars($envValues['DB_PORT'] ?? '3306'); ?>" required>
                            </div>
                            <div class="col-md-8">
                                <label for="db_database" class="form-label">Database Name</label>
                                <input type="text" class="form-control" id="db_database" name="db_database" value="<?php echo htmlspecialchars($envValues['DB_DATABASE'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="db_username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="db_username" name="db_username" value="<?php echo htmlspecialchars($envValues['DB_USERNAME'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="db_password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="db_password" name="db_password" value="<?php echo htmlspecialchars($envValues['DB_PASSWORD'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <hr class="section-divider">

                    <div class="mb-4">
                        <h4 class="text-primary mb-3">👤 Admin Account</h4>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="admin_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="admin_name" name="admin_name" placeholder="Administrator" required>
                            </div>
                            <div class="col-md-12">
                                <label for="admin_email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="admin_email" name="admin_email" placeholder="admin@yoursite.com" required>
                            </div>
                            <div class="col-md-6">
                                <label for="admin_password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="admin_password" name="admin_password" minlength="8" required>
                                <div class="form-text">Minimum 8 characters</div>
                            </div>
                            <div class="col-md-6">
                                <label for="admin_password_confirm" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="admin_password_confirm" name="admin_password_confirm" required>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-modern btn-lg">
                            🚀 Complete Installation
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
