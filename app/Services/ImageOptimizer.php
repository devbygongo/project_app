<?php

namespace App\Services;

use Tinify\Tinify;
use Tinify\Source;
use Exception;

class ImageOptimizer
{
    public function __construct()
    {
        \Tinify\setKey(env('TINIFY_API_KEY'));
    }

    /**
     * Optimize and overwrite the given image
     */
    public function optimize($path)
    {
        try {
            $source = \Tinify\fromFile($path);
            $source->toFile($path);
            return true;
        } catch (Exception $e) {
            \Log::error('TinyJPG optimization failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Optimize and save to new path
     */
    public function optimizeAndSave($inputPath, $outputPath)
    {
        try {
            $source = \Tinify\fromFile($inputPath);
            $source->toFile($outputPath);
            return true;
        } catch (Exception $e) {
            \Log::error('TinyJPG optimization failed: ' . $e->getMessage());
            return false;
        }
    }
}
