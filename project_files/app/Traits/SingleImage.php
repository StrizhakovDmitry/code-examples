<?php

namespace App\Traits;
use App\View\Components\Picture;
use http\Env\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Storage;

trait SingleImage
{
    public function getImageUrlAttribute($imageNumber = 1, $ext = false){
        $imageNumber = $imageNumber??1;
        $nameWithoutPref = (class_basename($this)).'_'.$this->id.'_'.$imageNumber;
        $pathWithoutPref = Storage::disk('images')->path($nameWithoutPref);
        $acceptedExtensions = ['png','jpg','jpeg','webp','svg'];
        $result = false;
        if($ext){
            $path = $pathWithoutPref.'.'.$ext;
           if(is_file($path)){
               $result = true;
           }
        }
        if($result !== true){
            foreach ($acceptedExtensions as $ext){
                if(is_file($pathWithoutPref.'.'.$ext)){
                    $result = true;
                    $path = $pathWithoutPref.'.'.$ext;
                    break;
                }
            }
        }
        if($result){
            $return = Storage::disk('images')->url(basename($path));
        }else{
            $return = null;
        }
        return $return;
    }
    public function storeImage(UploadedFile $file, $imageNumber){
        if(!$this->exists){
            $this->save();
        }
        $nameWithoutPref = (class_basename($this)).'_'.$this->id.'_'.$imageNumber;
        $command = 'find '.(Storage::disk('images')->path('')).' -name \''.$nameWithoutPref.'\'.* -delete';
        exec($command);
        $cacheImageFilesPattern = Storage::disk('images')->path('cache');
        $command = 'rm -rf '.$cacheImageFilesPattern.'/*';
        exec($command);
        Storage::disk('images')->putFileAs(false, $file, $nameWithoutPref.'.'.$file->extension());
    }

    public function deleteImage($imageNumber = 1){
        $nameWithoutPref = (class_basename($this)).'_'.$this->id.'_'.$imageNumber;
        $path = Storage::disk('images')->path($nameWithoutPref);
        foreach (glob($path.'.*') as $path){
            unlink($path);
        }
    }

    public function saveImageFromRequest($imageNumber){
        if(request()->exists('delete_image_'.$imageNumber)){
            $this->deleteImage();
        }else{
            if($image = request()->file('image_'.$imageNumber)){
                $this->storeImage($image, $imageNumber);
            }
        }
    }

    public function uploadImageTool($imageNumber, $cellLabel = 'Изображение'){
        $entity = $this;
        return view('admin.common.upload-image', get_defined_vars());
    }
    /**
     * @param integer $imageNumber
     * @param integer|array $size
     * @param string $format
     */
    public function picture($imageNumber, $size, $format = 'png'){
        if(is_array($size)){
            list($x,$y) = $size;
        }else{
            list($x,$y) = [$size,null];
        }
        $path = class_basename($this).'_'.$this->id.'_'.$imageNumber.'.'.$format;
        $picture = new Picture($path,$x,$y,$this->name??'картинка');
        return $picture->render();
    }

    public static function boot()
    {
        parent::boot();
        static::deleted(function ($entity) {
            $entity->deleteImage();
        });
    }
}
