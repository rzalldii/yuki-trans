<?php
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('resources/views'));
foreach ($files as $file) {
    if ($file->getExtension() === 'php') {
        $content = file_get_contents($file->getRealPath());
        
        $newContent = preg_replace_callback(
            '/<i\s+([^>]*class="[^"]*\bbx\b[^"]*"[^>]*)>/i',
            function($matches) {
                $tagContent = $matches[1];
                if (stripos($tagContent, 'aria-hidden="true"') === false) {
                    return '<i ' . $tagContent . ' aria-hidden="true">';
                }
                return $matches[0];
            },
            $content
        );

        if ($content !== $newContent) {
            file_put_contents($file->getRealPath(), $newContent);
            echo "Fixed " . $file->getRealPath() . "\n";
        }
    }
}
