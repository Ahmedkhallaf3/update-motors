<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Intervention\Image\Facades\Image;

class ImageService
{
    /**
     * Convert an image (UploadedFile or absolute path) to WebP and save it into a public subdirectory.
     * Returns the relative public path (e.g. "ads/filename.webp").
     */
    public static function toWebp(UploadedFile|string $source, string $publicSubdir, ?string $baseName = null, int $quality = 85): string
    {
        $publicDir = public_path(trim($publicSubdir, '/\\'));
        if (!is_dir($publicDir)) {
            mkdir($publicDir, 0755, true);
        }

        // Determine original name and real path
        if ($source instanceof UploadedFile) {
            $originalBase = pathinfo($source->getClientOriginalName(), PATHINFO_FILENAME);
            $realPath = $source->getRealPath();
        } else {
            $originalBase = pathinfo($source, PATHINFO_FILENAME);
            $realPath = $source;
        }

        $timestamp = time();
        $safeBase = preg_replace('/[^A-Za-z0-9_-]+/', '_', $baseName ?: $originalBase);
        $fileName = $safeBase . '_' . $timestamp . '.webp';
        $absTarget = $publicDir . DIRECTORY_SEPARATOR . $fileName;

        $img = Image::make($realPath);
        // Ensure RGB color space for webp, and strip profiles/metadata
        $img->encode('webp', $quality)->save($absTarget);

        return trim($publicSubdir, '/\\') . '/' . $fileName;
    }

    /**
     * Convert an existing absolute image path to WebP in-place (same directory),
     * remove the original, and return the new relative path under public/ if possible.
     */
    public static function replaceWithWebp(string $absPath, int $quality = 85): ?string
    {
        if (!file_exists($absPath)) {
            return null;
        }
        $dir = dirname($absPath);
        $base = pathinfo($absPath, PATHINFO_FILENAME);
        $webpAbs = $dir . DIRECTORY_SEPARATOR . $base . '.webp';

        $img = Image::make($absPath);
        $img->encode('webp', $quality)->save($webpAbs);

        // Remove original if different extension
        $origExt = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
        if ($origExt !== 'webp') {
            @unlink($absPath);
        }

        // Try to compute relative path from public
        $publicPath = public_path();
        if (str_starts_with($webpAbs, $publicPath)) {
            return ltrim(str_replace($publicPath, '', $webpAbs), '/\\');
        }
        return $webpAbs;
    }
}
