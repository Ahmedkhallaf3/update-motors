<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;

class ConvertImagesToWebp extends Command
{
    protected $signature = 'images:webp {--dry-run : Preview changes without writing} {--quality=85 : WebP quality (0-100)}';
    protected $description = 'Convert existing images referenced in the DB to WebP and update records';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $quality = (int) $this->option('quality');
        $quality = max(0, min(100, $quality));

        $this->info('Starting WebP conversion'.($dry ? ' (dry run)' : '')); 

        // Define handlers: table, columns, path builder/mapper
        $handlers = [
            // ads.main_image stores relative path like 'ads/filename.ext'
            [
                'table' => 'ads',
                'columns' => ['main_image'],
                'resolver' => function ($row, $col) { return public_path($row->$col); },
                'updater' => function (&$row, $col, $newRel) { $row->$col = str_replace('\\', '/', $newRel); },
                'preferred_dir' => 'ads',
            ],
            // ad_images.image stores 'ads/filename.ext'
            [
                'table' => 'ad_images',
                'columns' => ['image'],
                'resolver' => function ($row, $col) { return public_path($row->$col); },
                'updater' => function (&$row, $col, $newRel) { $row->$col = str_replace('\\', '/', $newRel); },
                'preferred_dir' => 'ads',
            ],
            // banners.image_ar, image_en store only filename under image_ar/ and image_en/
            [
                'table' => 'banners',
                'columns' => ['image_ar', 'image_en'],
                'resolver' => function ($row, $col) {
                    $dir = $col === 'image_ar' ? 'image_ar' : 'image_en';
                    return public_path($dir.DIRECTORY_SEPARATOR.$row->$col);
                },
                'updater' => function (&$row, $col, $newRel) {
                    // We expect newRel like 'image_ar/filename.webp' or 'image_en/filename.webp', store only filename
                    $row->$col = basename($newRel);
                },
            ],
            // categories.image stores filename under categorys/
            [
                'table' => 'categories',
                'columns' => ['image'],
                'resolver' => function ($row, $col) { return public_path('categorys'.DIRECTORY_SEPARATOR.$row->$col); },
                'updater' => function (&$row, $col, $newRel) { $row->$col = basename($newRel); },
            ],
            // blogs.image stores filename under blog/
            [
                'table' => 'blogs',
                'columns' => ['image'],
                'resolver' => function ($row, $col) { return public_path('blog'.DIRECTORY_SEPARATOR.$row->$col); },
                'updater' => function (&$row, $col, $newRel) { $row->$col = basename($newRel); },
            ],
            // userauths profile_image, cover_image under profile_images/ and cover_images/
            [
                'table' => 'userauths',
                'columns' => ['profile_image', 'cover_image'],
                'resolver' => function ($row, $col) {
                    $dir = $col === 'profile_image' ? 'profile_images' : 'cover_images';
                    return public_path($dir.DIRECTORY_SEPARATOR.$row->$col);
                },
                'updater' => function (&$row, $col, $newRel) { $row->$col = basename($newRel); },
            ],
        ];

        foreach ($handlers as $h) {
            $table = $h['table'];
            $columns = $h['columns'];
            $resolver = $h['resolver'];
            $updater = $h['updater'];

            $this->line("Processing table: {$table}");

            $rows = DB::table($table)->select(['id', ...$columns])->get();
            $updatedCount = 0;
            foreach ($rows as $row) {
                $rowChanged = false;
                foreach ($columns as $col) {
                    $val = $row->$col ?? null;
                    if (!$val) { continue; }

                    // Skip if already webp
                    if (str_ends_with(strtolower($val), '.webp')) { continue; }

                    $abs = $resolver($row, $col);
                    $absFound = $abs;
                    $foundViaSearch = false;
                    if (!is_string($abs) || !file_exists($abs)) {
                        // try to resolve by various strategies
                        $basename = basename($val);
                        $baseNoExt = pathinfo($basename, PATHINFO_FILENAME);

                        // 1) If a matching .webp already exists in ads/ (or same dir), just update DB and skip conversion
                        $existingWebp = $this->findFirstExisting([
                            public_path('ads'.DIRECTORY_SEPARATOR.$baseNoExt.'.webp'),
                            dirname(public_path($val)).DIRECTORY_SEPARATOR.$baseNoExt.'.webp',
                        ]);
                        if ($existingWebp) {
                            $newRel = $this->relativeFromPublic($existingWebp);
                            $updater($row, $col, $newRel);
                            $rowChanged = true;
                            $this->info("Matched existing WEBP for {$val} -> {$existingWebp}");
                            continue;
                        }

                        // Prefer exact directory for ads/ad_images: try starts-with in public/ads
                        if (($h['preferred_dir'] ?? null) === 'ads') {
                            $candidate = $this->findStartsWithInDir(public_path('ads'), $baseNoExt);
                            if ($candidate) {
                                $absFound = $candidate;
                                $foundViaSearch = true;
                                $this->info("Found in ads/ by starts-with: {$baseNoExt} -> {$absFound}");
                            }
                        }

                        if (!$foundViaSearch) {
                            // 2) Search both public/ and storage/app/public by exact basename
                            $alt = $this->findByBasenameMultiRoots($basename);
                            if (!$alt) {
                                // 3) If no extension, try common ones and starts-with matches
                                $alt = $this->findByBaseNameFuzzy($baseNoExt);
                            }
                        }

                        if (!$foundViaSearch && isset($alt) && $alt) {
                            $absFound = $alt;
                            $foundViaSearch = true;
                            $this->info("Found missing by search: {$basename} -> {$absFound}");
                        } else {
                            $this->warn(" - Missing file for {$table}.id={$row->id} {$col}={$val}");
                            continue;
                        }
                    }

                    // decide target directory
                    $dirSource = dirname($absFound);
                    $base = pathinfo($absFound, PATHINFO_FILENAME);
                    $preferredDir = $h['preferred_dir'] ?? null;
                    $dirTarget = $dirSource;
                    if ($preferredDir) {
                        $dirTarget = public_path($preferredDir);
                        if (!is_dir($dirTarget)) { @mkdir($dirTarget, 0755, true); }
                    }
                    $webpAbs = $dirTarget.DIRECTORY_SEPARATOR.$base.'.webp';

                    if ($dry) {
                        $this->info("[DRY] Convert: {$absFound} -> {$webpAbs}");
                        // pretend update
                        $newRel = $this->relativeFromPublic($webpAbs);
                        $updater($row, $col, $newRel);
                        $rowChanged = true;
                        continue;
                    }

                    try {
                        $img = Image::make($absFound);
                        $img->encode('webp', $quality)->save($webpAbs);
                        // remove original if extension different
                        $origExt = strtolower(pathinfo($absFound, PATHINFO_EXTENSION));
                        // only unlink if converting in-place (same dir); if moved to preferred dir, keep original
                        if ($origExt !== 'webp' && !$foundViaSearch || ($foundViaSearch && $dirSource === $dirTarget)) {
                            @unlink($absFound);
                        }

                        $newRel = $this->relativeFromPublic($webpAbs);
                        $updater($row, $col, $newRel);
                        $rowChanged = true;
                        $this->info("Converted: {$absFound} -> {$webpAbs}");
                    } catch (\Throwable $e) {
                        $this->error("Failed to convert {$absFound}: ".$e->getMessage());
                    }
                }

                if ($rowChanged && !$dry) {
                    // Persist changes for this row
                    $data = [];
                    foreach ($columns as $col) { $data[$col] = $row->$col; }
                    DB::table($table)->where('id', $row->id)->update($data);
                    $updatedCount++;
                }
            }

            $this->info("Table {$table}: updated {$updatedCount} rows");
        }

        $this->info('Done.');
        return self::SUCCESS;
    }

    private function relativeFromPublic(string $absPath): string
    {
        $public = public_path();
        if (str_starts_with($absPath, $public)) {
            return ltrim(str_replace($public, '', $absPath), '/\\');
        }
        return $absPath;
    }

    private function findInTreeByBasename(string $root, string $basename): ?string
    {
        if (!is_dir($root)) { return null; }
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            /** @var \SplFileInfo $file */
            if (strcasecmp($file->getFilename(), $basename) === 0) {
                return $file->getPathname();
            }
        }
        return null;
    }

    private function findByBasenameMultiRoots(string $basename): ?string
    {
        $roots = [
            public_path(),
            storage_path('app/public'),
        ];
        foreach ($roots as $root) {
            $found = $this->findInTreeByBasename($root, $basename);
            if ($found) { return $found; }
        }
        return null;
    }

    private function findByBaseNameFuzzy(string $baseNoExt): ?string
    {
        $roots = [public_path(), storage_path('app/public')];
        $exts = ['jpg','jpeg','png','gif','bmp','webp'];
        foreach ($roots as $root) {
            if (!is_dir($root)) { continue; }
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                /** @var \SplFileInfo $file */
                $name = $file->getFilename();
                $nameNoExt = pathinfo($name, PATHINFO_FILENAME);
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (stripos($nameNoExt, $baseNoExt) === 0 && in_array($ext, $exts, true)) {
                    return $file->getPathname();
                }
            }
        }
        return null;
    }

    private function findFirstExisting(array $paths): ?string
    {
        foreach ($paths as $p) {
            if ($p && file_exists($p)) { return $p; }
        }
        return null;
    }

    private function findStartsWithInDir(string $dir, string $baseNoExt): ?string
    {
        if (!is_dir($dir)) { return null; }
        $dh = opendir($dir);
        if (!$dh) { return null; }
        $baseLower = strtolower($baseNoExt);
        while (($entry = readdir($dh)) !== false) {
            if ($entry === '.' || $entry === '..') { continue; }
            $nameLower = strtolower(pathinfo($entry, PATHINFO_FILENAME));
            if (str_starts_with($nameLower, $baseLower)) {
                closedir($dh);
                return $dir.DIRECTORY_SEPARATOR.$entry;
            }
        }
        closedir($dh);
        return null;
    }
}
