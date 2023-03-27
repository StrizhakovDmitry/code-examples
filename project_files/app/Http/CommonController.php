<?php

namespace App\Http\Controllers;

use App\Models\CodeGenerator;
use Illuminate\Support\Facades\Response;
use Intervention\Image\ImageManager;


class CommonController extends Controller
{

    function codeMakerRender()
    {
        return '<form method="POST" action="' . route('admin.code-maker-execute') . '"><input type="hidden" name="_token" value="' . csrf_token() . '"><input type="text" name="entity_name" placeholder="entity name"><button>submit</button></form>';
    }

    function codeMakerExecute()
    {
        $modelName = \request('entity_name');
        try {
            $codeGenerator = new CodeGenerator($modelName);
            $codeGenerator->generate();
        } catch (\Throwable $exception) {
            dump($exception);
            $codeGenerator->rollback();
            exit;
        }
        return 'Модель ' . $modelName . ' успешно сренегирирована, <a href="' . route('admin.index') . '">Назад</a>';
    }

    function resizeImage($name)
    {
        $cacheFolder = public_path('images/cache');
        $filenameSegments = explode('.', $name);
        $ext = array_pop($filenameSegments);
        $acceptedExtension = ['png', 'jpg', 'jpeg', 'webp', 'tiff', 'bmp'];
        if (!in_array($ext, $acceptedExtension)) {
            throw new \Exception('this image format is not supported');
        }
        $filenameWithoutExt = implode('.', $filenameSegments);
        $x = \request('x');
        $y = \request('y');
        if ($x > 4000 || $y > 4000) {
            throw new \Exception('to large size (max 4000)');
        }
        if ($y) {
            $cachedFileName = $filenameWithoutExt . '_' . $x . '_' . $y . '.' . $ext;
        } else {
            $cachedFileName = $filenameWithoutExt . '_' . $x . '.' . $ext;
        }
        $cacheFilePath = $cacheFolder . '/' . $cachedFileName;
        if (is_file($cacheFilePath)) {
            $fileContent = file_get_contents($cacheFilePath);
            $mime = mime_content_type($cacheFilePath);
            $response = Response::make($fileContent, 200, ['Read-From-Cache' => 'true', 'Content-Type' => $mime, 'cache-control' => 'private, max-age=999999']);
        } else {
            $sourceFolder = public_path('images/');
            $sourceFilePath = $sourceFolder . $name;
            $manager = new ImageManager(['driver' => 'imagick']);
            if (!is_file($sourceFilePath)) {
                $attemptFilePattern = $sourceFolder . $filenameWithoutExt . '.*';
                $findResult = glob($attemptFilePattern);
                if (count($findResult) > 0) {
                    $analogFile = current($findResult);
                    $imageResourceForConvert = $manager->make($analogFile);
                    $imageResourceForConvert->save($sourceFilePath, null, $ext);
                }
            }

            if (!is_file($sourceFilePath)) {
                $sourceFilePath = $sourceFolder . 'empty-image.png';
            }

            $imageResource = $manager->make($sourceFilePath);
            $imageResource = $imageResource->resize($x, $y, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize(false);
            });
            $imageResource->save($cacheFilePath);
            $fileContent = file_get_contents($cacheFilePath);
            $mime = mime_content_type($cacheFilePath);
            $response = Response::make($fileContent, 200, ['Read-From-Cache' => 'false', 'Content-Type' => $mime, 'cache-control' => 'private, max-age=999999']);
        }
        return $response;
    }

}
