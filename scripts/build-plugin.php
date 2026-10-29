<?php
/**
 * Packs plugins/ecom-pilot-connector into public/downloads/ecom-pilot-connector.zip
 * (the file the Integrations page links to).
 *
 *   php scripts/build-plugin.php
 */
$root   = dirname(__DIR__);
$source = $root . '/plugins/ecom-pilot-connector';
$target = $root . '/public/downloads/ecom-pilot-connector.zip';

if (! is_dir(dirname($target))) {
    mkdir(dirname($target), 0775, true);
}
@unlink($target);

$zip = new ZipArchive();
if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Cannot write $target\n");
    exit(1);
}

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS));
foreach ($files as $file) {
    $relative = 'ecom-pilot-connector/' . str_replace('\\', '/', substr($file->getPathname(), strlen($source) + 1));
    $zip->addFile($file->getPathname(), $relative);
}
$zip->close();

echo "Built $target (" . filesize($target) . " bytes)\n";
