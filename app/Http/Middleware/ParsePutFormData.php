<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ParsePutFormData
{
    public function handle(Request $request, Closure $next)
    {
        if (
            $request->isMethod('PUT') &&
            str_contains($request->header('Content-Type'), 'multipart/form-data')
        ) {
            $raw = file_get_contents('php://input');

            $boundary = substr($request->header('Content-Type'), strpos($request->header('Content-Type'), 'boundary=') + 9);

            $blocks = preg_split("/-+$boundary/", $raw);
            array_pop($blocks);

            $data = [];
            $files = [];

            foreach ($blocks as $block) {
                if (empty($block)) continue;

                if (strpos($block, 'application/octet-stream') !== false) {
                    preg_match("/name=\"([^\"]*)\".*filename=\"([^\"]*)\".*Content-Type: (.*)\r\n\r\n/s", $block, $matches);

                    if (!isset($matches[1])) continue;

                    $name = $matches[1];
                    $filename = $matches[2];
                    $fileContent = substr($block, strpos($block, "\r\n\r\n") + 4);
                    $fileContent = substr($fileContent, 0, -2);

                    $tmpPath = sys_get_temp_dir() . '/' . uniqid();
                    file_put_contents($tmpPath, $fileContent);

                    $files[$name] = new \Illuminate\Http\UploadedFile(
                        $tmpPath,
                        $filename,
                        null,
                        null,
                        true
                    );
                } else {
                    preg_match('/name=\"([^\"]*)\"\r\n\r\n(.*)\r\n/s', $block, $matches);

                    if (!isset($matches[1])) continue;

                    $data[$matches[1]] = trim($matches[2]);
                }
            }

            $request->merge($data);
            $request->files->add($files);
        }

        return $next($request);
    }
}