<?php
declare(strict_types=1);

// Router for PHP's offline development server; it does not read .htaccess.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
// PHP's built-in server does not canonicalize directory URLs like Apache does.
// Relative admin links/redirects need the trailing slash to stay under /admin/.
if ($path === '/admin') {
    header('Location: /admin/', true, 308);
    exit;
}
$publicScripts = ['index.php', 'index.html', 'program.php', 'submit-feedback.php'];
$adminScripts = ['index.php', 'login.php', 'logout.php', 'programs.php', 'program-edit.php', 'feedback.php', 'export-feedback.php'];
$allowed = $path === '/' || $path === '/admin' || $path === '/admin/'
    || in_array(ltrim($path, '/'), $publicScripts, true)
    || $path === '/assets/vendor/sweetalert2.all.min.js'
    || in_array($path, ['/assets/loading.css', '/assets/loading.js'], true)
    || (str_starts_with($path, '/admin/') && in_array(substr($path, 7), $adminScripts, true))
    || preg_match('~^/uploads/banners/[a-f0-9]{32}\.(jpg|png|webp)$~', $path);
if (!$allowed) {
    http_response_code(404);
    exit('Not found.');
}
return false;
