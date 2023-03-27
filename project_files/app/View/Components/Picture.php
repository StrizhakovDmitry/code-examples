<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Picture extends Component
{
    private $name;
    private $x;
    private $y;
    private $alt;
    private $class;
    /**
     * Create a new component instance.
     *
     * @param $name
     * @param $x
     * @param null $y
     * @param string $alt
     * @param null $class
     */
    public function __construct($name, $x, $y = null , $alt = 'картинка', $class = null)
    {
        $this->name = $name;
        $this->x = $x;
        $this->y = $y;
        $this->alt = $alt;
        $this->class = $class;
    }

    public function render()
    {
        $sourceName = $this->name;
        $filenameSegments = explode('.', $this->name);
        array_pop($filenameSegments);
        $filenameWithoutExt = implode('.', $filenameSegments);
        $webpName = $filenameWithoutExt.'.webp';
        $xSegment = 'x=' . $this->x;
        $ySegment = $this->y ?'&y=' . $this->y : '';
        $dontCacheSegmentSrc = $dontCacheSegmentSrcSet = '';
        try{
            if(request()->segment(1)==='admin'){
                $pref = '&timestamp=';
                $dontCacheSegmentSrc = $pref.fileatime(public_path('/images/'.$sourceName));
                $dontCacheSegmentSrcSet = $pref.fileatime(public_path('/images/'.$webpName));
            }
        }    catch (\Throwable $exception){

        }
        $srcset = '/resize-images/'.$webpName.'?'.$xSegment.$ySegment.$dontCacheSegmentSrcSet;
        $src = '/resize-images/'.$sourceName.'?'.$xSegment.$ySegment.$dontCacheSegmentSrc;
        $alt = $this->alt;
        $class = $this->class;
        $attributeX = !empty($this->x) ? 'width="' . $this->x . '"':'';
        $attributeY = !empty($this->y) ? 'height="' . $this->y . '"':'';
        return view('components.picture',
            [
                'srcset' => $srcset,
                'src' => $src,
                'alt' => $alt,
                'class' => $class,
                'attributeX' => $attributeX,
                'attributeY' => $attributeY,
            ]
        );
    }


}
