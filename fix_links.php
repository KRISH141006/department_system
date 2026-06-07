<?php
$dir = new RecursiveDirectoryIterator(__DIR__ . '/modules');
$iterator = new RecursiveIteratorIterator($dir);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $original_content = $content;

        // Replace in HTML/JS context
        $content = preg_replace('/(href|action|src|fetch)\s*(=|\()[\'"](?:\.\.\/)+public\//i', '$1$2"<?= $base_path ?>/public/', $content);
        $content = preg_replace('/(href|action|src|fetch)\s*(=|\()[\'"](?:\.\.\/)+app\/actions\//i', '$1$2"<?= $base_path ?>/app/actions/', $content);
        $content = preg_replace('/(href|action|src|fetch)\s*(=|\()[\'"](?:\.\.\/)+app\/auth\//i', '$1$2"<?= $base_path ?>/app/auth/', $content);
        
        // Also fix any single quotes that were replaced with double quotes incorrectly
        // The regex above hardcodes `"` after $2. We should be careful. Let's do it safer.
        
        // Let's reset and use a better regex for HTML/JS
        $content = $original_content;
        
        // 1. Double quotes in HTML/JS
        $content = preg_replace('/(href|action|src|fetch)\s*(=|\()\s*"(\.\.\/)+(public|app\/actions|app\/auth)\//i', '$1$2"<?= $base_path ?>/$4/', $content);
        // 2. Single quotes in HTML/JS
        $content = preg_replace('/(href|action|src|fetch)\s*(=|\()\s*\'(\.\.\/)+(public|app\/actions|app\/auth)\//i', '$1$2\'<?= $base_path ?>/$4/', $content);

        // 3. PHP header redirects (double quotes)
        $content = preg_replace('/header\s*\(\s*"Location:\s*(\.\.\/)+(public|app\/actions|app\/auth)\//i', 'header("Location: $base_path/$2/', $content);
        // 4. PHP header redirects (single quotes)
        $content = preg_replace('/header\s*\(\s*\'Location:\s*(\.\.\/)+(public|app\/actions|app\/auth)\//i', 'header(\'Location: \' . $base_path . \'/$2/', $content);
        
        // 5. Bare strings like $file_url = '../../public/...'
        // It's tricky to catch all, but let's try catching assigned strings
        $content = preg_replace('/=\s*[\'"](\.\.\/)+(public|app\/actions|app\/auth)\//i', '= $base_path . "/$2/', $content);

        if ($content !== $original_content) {
            file_put_contents($file->getPathname(), $content);
            echo "Updated: " . $file->getPathname() . "\n";
        }
    }
}
echo "Done.\n";
