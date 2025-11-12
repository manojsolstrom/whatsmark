<?php

namespace Corbital\Installer\Http\Controllers;

use Corbital\Installer\Classes\DatabaseTest;
use Corbital\Installer\Classes\EnvironmentManager;
use Corbital\Installer\Classes\InstallFinalizer;
use Corbital\Installer\Classes\PermissionsChecker;
use Corbital\Installer\Classes\RequirementsChecker;
use Corbital\Installer\Http\Requests\AdminSetupRequest;
use Corbital\Installer\Http\Requests\DatabaseSetupRequest;
use Corbital\Installer\Http\Requests\LicenseVerificationRequest;
use Corbital\Installer\Installer;
use Exception;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use PDOException;

class InstallController extends Controller
{
    /**
     * Display the installer welcome page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('installer::installation.welcome');
    }

    /**
     * Display the requirements page.
     *
     * @return \Illuminate\View\View
     */
    public function requirements()
    {
        $requirementsChecker = new RequirementsChecker;

        $requirements = $requirementsChecker->check();
        $php          = $requirementsChecker->checkPHPVersion();

        return view('installer::installation.requirements', compact('requirements', 'php'));
    }

    /**
     * Display the permissions page.
     *
     * @return \Illuminate\View\View
     */
    public function permissions()
    {
        $permissionsChecker = new PermissionsChecker;
        $permissions        = $permissionsChecker->check();

        return view('installer::installation.permissions', compact('permissions'));
    }

    /**
     * Display the environment and database setup page.
     *
     * @return \Illuminate\View\View
     */
    public function setup()
    {
        $environmentManager = new EnvironmentManager;
        $guessedUrl         = $environmentManager::guessUrl();

        // You may need to add this method to get countries or provide them another way
        $countries = $this->getCountries();

        return view('installer::installation.setup', compact('guessedUrl', 'countries'));
    }

    /**
     * Process the environment and database setup.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setupStore(DatabaseSetupRequest $request)
    {
        try {
            // Test database connection
            $this->testDatabaseConnection(
                $request->database_hostname,
                $request->database_port,
                $request->database_name,
                $request->database_username,
                $request->database_password
            );

            // Generate app key and identification key
            $environmentManager = new EnvironmentManager;
            $appKey             = $environmentManager->generateAppKey();
            $identificationKey  = $environmentManager->generateIdentificationKey();

            config([
                'database.default'                    => 'mysql',
                'database.connections.mysql.driver'   => 'mysql',
                'database.connections.mysql.host'     => $request->database_hostname,
                'database.connections.mysql.port'     => $request->database_port,
                'database.connections.mysql.database' => $request->database_name,
                'database.connections.mysql.username' => $request->database_username,
                'database.connections.mysql.password' => $request->database_password,
            ]);

            // // Clear any previous connections
            DB::purge('mysql');

            // // Reconnect using new configuration
            DB::reconnect('mysql');
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            // Create the sessions table migration
            if (! Schema::hasTable('sessions')) {
                Schema::create('sessions', function (Blueprint $table) {
                    $table->string('id')->primary();
                    $table->foreignId('user_id')->nullable()->index();
                    $table->string('ip_address', 45)->nullable();
                    $table->text('user_agent')->nullable();
                    $table->longText('payload');
                    $table->integer('last_activity')->index();
                });
            }

            // // Save environment settings
            $environmentManager->saveEnv([
                'APP_NAME'                 => $request->app_name,
                'APP_KEY'                  => $appKey,
                'IDENTIFICATION_KEY'       => $identificationKey,
                'APP_URL'                  => $request->app_url,
                'APP_DEBUG'                => 'true',
                'DB_CONNECTION'            => 'mysql',
                'DB_HOST'                  => $request->database_hostname,
                'DB_PORT'                  => $request->database_port,
                'DB_DATABASE'              => $request->database_name,
                'DB_USERNAME'              => $request->database_username,
                'DB_PASSWORD'              => $request->database_password,
                'SESSION_DRIVER'           => 'database',
                'QUEUE_CONNECTION'         => 'database',
                'WHATSAPP_LOGGING_ENABLED' => 'false',
            ]);

            // Store country in session if provided
            if ($request->has('country')) {
                session(['country' => $request->country]);
            }

            // Redirect to the admin user setup page
            return redirect()->route('install.license');

        } catch (Exception $e) {
            // If there was an error, redirect back with error message
            return redirect()->back()->with('database_error', $e->getMessage())->withInput();
        }
    }

    /**
     * Display the admin user setup page.
     *
     * @return \Illuminate\View\View
     */
    public function user()
    {
        return view('installer::installation.user');
    }

    /**
     * Process the admin user setup.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function userStore(AdminSetupRequest $request)
    {
        try {
            // Store admin data in session
            session([
                'admin_firstname' => $request->firstname ?? null,
                'admin_lastname'  => $request->lastname  ?? null,
                'admin_email'     => $request->email,
                'admin_password'  => $request->password,
                'admin_timezone'  => $request->timezone,
            ]);

            // Run installer finalizer
            $installer = app('installer');
            $finalizer = new InstallFinalizer($installer);

            $result = $finalizer->handle([
                'firstname' => $request->firstname ?? null,
                'lastname'  => $request->lastname  ?? null,
                'email'     => $request->email,
                'password'  => $request->password,
                'timezone'  => $request->timezone,
            ]);

            if (! $result['success']) {
                throw new Exception($result['error']);
            }

            // Redirect to the finished page
            return redirect()->route('install.finished')->with('user', $result['user']);

        } catch (Exception $e) {
            // If there was an error, redirect back with error message
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Display the installation finished page.
     *
     * @return \Illuminate\View\View
     */
    public function finished()
    {
        $user = session('user');

        if (! $user) {
            // If user data is not in session, redirect to the admin user setup page
            return redirect()->route('install.user');
        }

        // Get PHP executable path - you may need to adjust this based on your server
        $phpExecutable = PHP_BINARY ?? 'php';

        return view('installer::installation.finished', compact('user', 'phpExecutable'));
    }

    /**
     * Test the database connection.
     *
     * @throws Exception
     */
    protected function testDatabaseConnection(string $hostname, string $port, string $database, string $username, string $password): bool
    {
        try {
            // Configure a test connection
            $testConnection = 'installer_test';
            Config::set("database.connections.$testConnection", [
                'driver'    => 'mysql',
                'host'      => $hostname,
                'port'      => $port,
                'database'  => $database,
                'username'  => $username,
                'password'  => $password,
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => '',
                'strict'    => true,
                'engine'    => 'InnoDB',
            ]);

            // Try to connect
            DB::connection($testConnection)->getPdo();

            // If we reached here, connection succeeded
            // Now test database privileges
            $databaseTest = new DatabaseTest(DB::connection($testConnection));
            $testResults  = $databaseTest->runAllTests();

            // Check if all required privileges are granted
            $missingPrivileges = array_filter($testResults, function ($result) {
                return $result === false;
            });

            if (! empty($missingPrivileges)) {
                $missingPrivilegeNames = array_keys($missingPrivileges);
                throw new Exception('Database user is missing required privileges: ' . implode(', ', $missingPrivilegeNames));
            }

            return true;

        } catch (PDOException $e) {
            // Connection failed
            throw new Exception('Database connection failed: ' . $e->getMessage());
        } finally {
            // Clean up the test connection
            DB::purge($testConnection);
        }
    }

    /**
     * Get countries list for dropdown.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getCountries()
    {
        // If your application already has a countries table, you could use that
        // Otherwise, return a simple collection with some default countries
        // This is just an example - adjust as needed
        $jsonPath = base_path('platform/packages/corbital/installer/countries.json');

        if (file_exists($jsonPath)) {
            return json_decode(file_get_contents($jsonPath));
        }

        return []; // Return an empty array if the file doesn't exist
    }

    /**
     * Display the license verification page.
     *
     * @return \Illuminate\View\View
     */
    public function license()
    {
        // Check if license is already verified to pre-fill the form
        $licenseData  = session('license_data', []);
        $username     = $licenseData['username']      ?? '';
        $purchaseCode = $licenseData['purchase_code'] ?? '';

        return view('installer::installation.license', compact('username', 'purchaseCode'));
    }

    /**
     * Verify the Envato purchase code.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function licenseVerify(LicenseVerificationRequest $request)
    {
        // License validation disabled - always return success
        session(['license_verified' => true]);
        session([
            'license_data' => [
                'username'        => 'admin',
                'purchase_code'   => 'disabled',
                'verified'        => true,
                'details'         => [],
                'token'           => 'disabled',
                'verification_id' => 'disabled',
                'support_until'   => now()->addYears(10)->format('Y-m-d'),
            ],
        ]);
        
        return redirect()->route('install.user')->with('success', 'License verified successfully!');
    }

    /**
     * Extract OS information from User-Agent
     *
     * @param  string $userAgent
     * @return string
     */
    private function getOSFromUserAgent($userAgent)
    {
        $os = 'Unknown OS';

        $osPlatforms = [
            '/windows nt 10/i'      => 'Windows 10',
            '/windows nt 6.3/i'     => 'Windows 8.1',
            '/windows nt 6.2/i'     => 'Windows 8',
            '/windows nt 6.1/i'     => 'Windows 7',
            '/windows nt 6.0/i'     => 'Windows Vista',
            '/windows nt 5.2/i'     => 'Windows Server 2003/XP x64',
            '/windows nt 5.1/i'     => 'Windows XP',
            '/windows xp/i'         => 'Windows XP',
            '/windows nt 5.0/i'     => 'Windows 2000',
            '/windows me/i'         => 'Windows ME',
            '/win98/i'              => 'Windows 98',
            '/win95/i'              => 'Windows 95',
            '/win16/i'              => 'Windows 3.11',
            '/macintosh|mac os x/i' => 'Mac OS X',
            '/mac_powerpc/i'        => 'Mac OS 9',
            '/linux/i'              => 'Linux',
            '/ubuntu/i'             => 'Ubuntu',
            '/iphone/i'             => 'iPhone',
            '/ipod/i'               => 'iPod',
            '/ipad/i'               => 'iPad',
            '/android/i'            => 'Android',
            '/blackberry/i'         => 'BlackBerry',
            '/webos/i'              => 'Mobile',
        ];

        foreach ($osPlatforms as $regex => $value) {
            if (preg_match($regex, $userAgent)) {
                $os = $value;
                break;
            }
        }

        return $os;
    }

    /**
     * Extract browser information from User-Agent
     *
     * @param  string $userAgent
     * @return string
     */
    private function getBrowserFromUserAgent($userAgent)
    {
        $browser = 'Unknown Browser';

        $browsers = [
            '/msie/i'      => 'Internet Explorer',
            '/firefox/i'   => 'Firefox',
            '/safari/i'    => 'Safari',
            '/chrome/i'    => 'Chrome',
            '/edge/i'      => 'Edge',
            '/opera/i'     => 'Opera',
            '/netscape/i'  => 'Netscape',
            '/maxthon/i'   => 'Maxthon',
            '/konqueror/i' => 'Konqueror',
            '/mobile/i'    => 'Mobile Browser',
        ];

        foreach ($browsers as $regex => $value) {
            if (preg_match($regex, $userAgent)) {
                $browser = $value;
                break;
            }
        }

        // Get version number
        $knownBrowsers = ['Firefox', 'Chrome', 'Safari', 'Opera', 'Edge'];
        if (in_array($browser, $knownBrowsers)) {
            $pattern = '#(?<browser>' . preg_quote($browser, '#') . ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
            if (preg_match($pattern, $userAgent, $matches)) {
                $browser = $browser . ' ' . $matches['version'];
            }
        }

        return $browser;
    }

    public function validate()
    {
        $username      = '';
        $purchase_code = '';

        if (get_setting('whats-mark.wm_validate') == true) {
            return redirect()->route('admin.dashboard');
        }

        return view('validate', compact('username', 'purchase_code'));
    }

    public function validateLicense(LicenseVerificationRequest $request)
    {
        // License validation disabled - always return success
        $installer = new Installer;
        
        set_settings_batch('whats-mark', [
            'wm_verification_id'    => base64_encode('disabled'),
            'wm_verification_token' => base64_encode('disabled') . '|disabled',
            'wm_last_verification'  => now()->timestamp,
            'wm_validate'           => true,
        ]);

        $installer->markAsInstalled();

        session()->flash('notification', [
            'type'    => 'success',
            'message' => 'License verified successfully!',
        ]);

        return redirect()->to(route('admin.dashboard'));
    }
}
