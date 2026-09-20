<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * safe_zip_extract()
 *
 * Canonical safe ZIP extraction used by the theme and plugin uploaders.
 *
 * Unlike ZipArchive::extractTo(), every archive entry is validated before it is
 * written:
 *  - path traversal segments (".."), absolute paths, drive letters, backslash
 *    traversal and null bytes are rejected (closes the zip-slip class of bugs,
 *    including the Windows-style "..\" variant);
 *  - symbolic-link entries are rejected so extraction never follows a link out
 *    of the destination;
 *  - the resolved destination directory must stay inside the target directory
 *    (defense in depth);
 *  - MAX_FILES / MAX_SIZE / MAX_RATIO are enforced while streaming each entry,
 *    so a zip bomb cannot exhaust memory or disk.
 *
 * Extraction is streamed per file (no extractTo) so partial files are not
 * left behind when a limit is exceeded. On any unsafe condition an
 * InvalidArgumentException is thrown and the caller is responsible for
 * cleaning up the partially written destination.
 *
 * @category function
 * @param string $zipPath      Path to the zip archive to extract
 * @param string $destDir      Directory entries are extracted into
 * @param array  $skipPatterns Optional list of regexes; entries whose name
 *                             matches any pattern are skipped
 * @return bool True when every entry was extracted successfully
 * @throws InvalidArgumentException When the archive is unsafe or unreadable
 */
function safe_zip_extract($zipPath, $destDir, array $skipPatterns = [])
{
    if (!is_dir($destDir)) {
        create_directory($destDir);
    }

    $destReal = realpath($destDir);
    if ($destReal === false) {
        throw new InvalidArgumentException('Invalid extraction destination.');
    }

    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        throw new InvalidArgumentException('Unable to open the uploaded archive.');
    }

    $fileCount = 0;
    $totalSize = 0;
    $count = (version_compare(phpversion(), "7.4.30", ">=")) ? $zip->count() : $zip->numFiles;

    try {
        for ($i = 0; $i < $count; $i++) {
            $entry = $zip->getNameIndex($i);
            $stats = $zip->statIndex($i);

            if ($entry === false || $entry === '') {
                continue;
            }

            foreach ($skipPatterns as $pattern) {
                if (preg_match($pattern, $entry)) {
                    continue 2;
                }
            }

            // Normalize backslashes so Windows-style traversal is caught too
            $normalized = str_replace('\\', '/', $entry);

            if (strpos($normalized, "\0") !== false
                || preg_match('#(^|/)\.\.(/|$)#', $normalized)
                || substr($normalized, 0, 1) === '/'
                || preg_match('#^[a-zA-Z]:/#', $normalized)) {
                throw new InvalidArgumentException('The uploaded archive contains an unsafe path.');
            }

            // Reject symlink entries: extraction must never follow links.
            // statIndex() does not expose a "mode" key on every libzip/PHP
            // build (it is missing on PHP 8.5), so read the Unix permission
            // bits through getExternalAttributesIndex() instead.
            $opsys = 0;
            $attr = 0;
            $isSymlink = false;
            if ($zip->getExternalAttributesIndex($i, $opsys, $attr)) {
                if ($opsys === ZipArchive::OPSYS_UNIX && ($attr >> 16) > 0) {
                    $isSymlink = ((($attr >> 16) & 0170000) === 0120000);
                }
            }
            if ($isSymlink) {
                throw new InvalidArgumentException('The uploaded archive contains a symbolic link.');
            }

            $target = $destReal . DIRECTORY_SEPARATOR . $normalized;

            if (substr($normalized, -1) === '/') {
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
                continue;
            }

            $fileCount++;
            if ($fileCount > MAX_FILES) {
                throw new InvalidArgumentException('The uploaded archive contains too many files.');
            }

            $dir = dirname($target);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            // Defense in depth: the resolved parent must stay inside the dest
            $resolvedDir = realpath($dir);
            if ($resolvedDir === false
                || strpos($resolvedDir . DIRECTORY_SEPARATOR, $destReal . DIRECTORY_SEPARATOR) !== 0) {
                throw new InvalidArgumentException('The uploaded archive escapes the destination directory.');
            }
            $target = $resolvedDir . DIRECTORY_SEPARATOR . basename($normalized);

            $fp = $zip->getStream($entry);
            if ($fp === false) {
                throw new InvalidArgumentException('Unable to read an archive entry.');
            }

            $out = @fopen($target, 'wb');
            if ($out === false) {
                fclose($fp);
                throw new InvalidArgumentException('Unable to write an archive entry.');
            }

            while (!feof($fp)) {
                $chunk = fread($fp, READ_LENGTH);
                if ($chunk === false) {
                    break;
                }

                $totalSize += strlen($chunk);

                if ($totalSize > MAX_SIZE) {
                    fclose($out);
                    fclose($fp);
                    throw new InvalidArgumentException('The uploaded archive exceeds the maximum size.');
                }

                fwrite($out, $chunk);
            }

            // Zip-bomb defense: uncompressed size must stay within ratio
            if ($stats['comp_size'] > 0 && $stats['size'] > 0) {
                $ratio = $stats['size'] / $stats['comp_size'];
                if ($ratio > MAX_RATIO) {
                    fclose($out);
                    fclose($fp);
                    throw new InvalidArgumentException('The uploaded archive exceeds the maximum compression ratio.');
                }
            }

            fclose($out);
            fclose($fp);
        }
    } finally {
        $zip->close();
    }

    return true;
}