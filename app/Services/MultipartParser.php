<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class MultipartParser
{
    public static function parse(Request $request)
    {
        if (
            !in_array($request->method(), ['PUT', 'PATCH']) ||
            !str_contains($request->header('Content-Type'), 'multipart/form-data')
        ) {
            return;
        }

        $data = [];
        $files = [];

        $input = file_get_contents('php://input');

        preg_match('/boundary=(.*)$/', $request->header('Content-Type'), $matches);
        $boundary = $matches[1] ?? null;

        if (!$boundary) return;

        $blocks = preg_split("/-+$boundary/", $input);
        array_pop($blocks);

        foreach ($blocks as $block) {
            if (empty($block)) continue;

            if (strpos($block, 'filename=') !== false) {
                preg_match('/name="([^"]*)"; filename="([^"]*)"/', $block, $matches);
                $name = $matches[1];

                preg_match('/\r\n\r\n(.*)\r\n$/s', $block, $fileMatches);
                $content = $fileMatches[1];

                $tmpPath = tempnam(sys_get_temp_dir(), 'laravel');
                file_put_contents($tmpPath, $content);

                $files[$name] = new UploadedFile(
                    $tmpPath,
                    $matches[2],
                    null,
                    null,
                    true
                );
            } else {
                preg_match('/name="([^"]*)"/', $block, $matches);
                $name = $matches[1];

                preg_match('/\r\n\r\n(.*)\r\n$/s', $block, $valueMatches);
                $data[$name] = $valueMatches[1];
            }
        }

        $request->merge($data);
        $request->files->add($files);
    }
}