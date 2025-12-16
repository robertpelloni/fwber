<?php
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Hosting Environment Check</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Hosting Environment Check</h1>

    <h2>1. Apache Modules</h2>
    <?php
    if (function_exists('apache_get_modules')) {
        $modules = apache_get_modules();
        $proxy_support = in_array('mod_proxy', $modules) && in_array('mod_proxy_http', $modules);
        
        echo "<ul>";
        foreach ($modules as $module) {
            $style = ($module == 'mod_proxy' || $module == 'mod_proxy_http' || $module == 'mod_rewrite') ? "font-weight:bold" : "";
            echo "<li style='$style'>$module</li>";
        }
        echo "</ul>";

        if ($proxy_support) {
            echo "<p class='success'>✅ mod_proxy is available. You can use .htaccess to proxy to Mercure.</p>";
        } else {
            echo "<p class='error'>❌ mod_proxy is NOT available. You cannot use .htaccess to proxy to Mercure.</p>";
            echo "<p>You may need to enable it in your hosting panel or upgrade to a VPS.</p>";
        }
    } else {
        echo "<p class='warning'>⚠️ apache_get_modules() is disabled. Cannot verify modules programmatically.</p>";
        echo "<p>Try creating the .htaccess file and checking if you get a 500 Internal Server Error.</p>";
    }
    ?>

    <h2>2. Connectivity Check</h2>
    <?php
    $port = 3001;
    $connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 2);
    if (is_resource($connection)) {
        echo "<p class='success'>✅ Can connect to Mercure on port $port.</p>";
        fclose($connection);
    } else {
        echo "<p class='error'>❌ Cannot connect to Mercure on port $port.</p>";
        echo "<p>Ensure start_mercure_shared.sh is running.</p>";
    }
    ?>
</body>
</html>
