<?php
/**
 * 去噪，将噪点抹白
 * 采用DFS
 */
class Denoise {
    private static $gox = array(0,1,0,-1);
    private static $goy = array(-1,0,1,0);
    private static $sizeX;
    private static $sizeY;
    private static $visit;
    private static $denoised;
    private static $image;

    public static function imageDenoise(&$image, $sizeX, $sizeY) {
        // 初始化
        self::$image = $image;
        self::$visit = array_fill(0, 120, array_fill(0, 60, FALSE));
        self::$denoised = self::$visit;
        self::$sizeX = $sizeX;
        self::$sizeY = $sizeY;

        // 去噪
        for($x=0; $x<$sizeX; $x++)
            for($y=0; $y<$sizeY; $y++)
                if(!self::$visit[$x][$y] && self::$image[$x][$y]!=255) {
                    $size = self::getBlockSize($x, $y, 0); // 求块大小
                    if($size < 10) // 根据块大小判断噪点簇
                        self::deletePixel($x, $y);
                }
        return self::$image;
    }

    private static function getBlockSize($x, $y, $size) {
        self::$visit[$x][$y] = TRUE;
        $size ++;
        // 最多递归500层
        // 此时可能会把余下没有递归的一块当作噪点
        // 这种情况字符大小为500<n<510
        if($size > 500) return 500;

        for($i=0; $i<4; $i++) {
            $xp = $x + self::$gox[$i]; // 下一步x坐标
            $yp = $y + self::$goy[$i]; // 下一步y坐标
            // echo "[$xp,$yp]";
            if($xp>=0 && $yp>=0 && $xp<self::$sizeX && $yp<self::$sizeY
                && !self::$visit[$xp][$yp] && self::$image[$xp][$yp]!=255) {
                $size = self::getBlockSize($xp, $yp, $size); // 递归
            }
        }
        return $size;
    }

    private static function deletePixel($x, $y) {
        self::$denoised[$x][$y] = TRUE;
        self::$image[$x][$y] = 255;
        for($i=0; $i<4; $i++) {
            $xp = $x + self::$gox[$i]; // 下一步x坐标
            $yp = $y + self::$goy[$i]; // 下一步y坐标
            if($xp>=0 && $yp>=0 && $xp<self::$sizeX && $yp<self::$sizeY
                && !self::$denoised[$xp][$yp] && self::$image[$xp][$yp]!=255) {
                self::deletePixel($xp, $yp);
            }
        }
    }
}