<?php

function debugWriteImage($res, $image, $charCol, $charRow, $file=false) {
    // 写回颜色
    for($x=0; $x<120; $x++)
        for($y=0; $y<60; $y++)
            imagesetpixel($res, $x, $y, $image[$x][$y]);
    // 画边框
    for($i=0; $i<4; $i++) {
        imageline($res, $charCol[$i][0]-1, 0, $charCol[$i][0]-1, 60, 0);
        imageline($res, $charCol[$i][1], 0, $charCol[$i][1], 60, 0);
        imageline($res, $charCol[$i][0], $charRow[$i][0]-1, $charCol[$i][1], $charRow[$i][0]-1, 0);
        imageline($res, $charCol[$i][0], $charRow[$i][1], $charCol[$i][1], $charRow[$i][1], 0);
    }

    if($file)
        imagegif($res, 'code-out.gif');
    else {
        header("Content-Type:image/gif");
        imagegif($res);
        imagedestroy($res);
    }
}