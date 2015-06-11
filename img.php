<?php
set_time_limit(0);
require 'Denoise.php';

function deal($fileName='code.gif') {
    $char = processImage($fileName);
    $theta = readTheta();
    $pattern = '0123456789abcdefghigklmnopqrstuvwxyz';
    $ret = '';
    for($i=0; $i<4; $i++) {
        $maxn = 0;
        for($patternIter=0; $patternIter<36; $patternIter++) {
            $sum = 0;
            for($mult=0; $mult<625; $mult++) {
                $sum += $theta[$mult][$patternIter] * $char[$i][$mult];
            }
            $sigmoid = 1 / (1 + exp(-$sum));
            if($maxn < $sigmoid) {
                $maxn = $sigmoid;
                $maxc = $pattern[$patternIter];
            }
        }
        $ret .= $maxc;
    }
    return $ret;
}

function parseImage($n) {
    $file = fopen('./autocode/data.txt', 'a');
    for($i=0;$i<$n;$i++) {
        $image = processImage("./autocode/code-{$i}.gif");
        saveImageData($file, $image);
    }
    fclose($file);
}

function processImage($fileName) {
    $backgroundColor = array(
        212, 218, 219, // 灰 绿 绿
        252, 209, 216 // 黄条
        );
    $sizeX = 120;
    $sizeY = 60;
    $res = imagecreatefromgif($fileName);
    $image = array();
    // 读颜色 去背景色
    for($x=0; $x<$sizeX; $x++) {
        for($y=0; $y<$sizeY; $y++) {
            $colorIndex = imagecolorat($res, $x, $y);
            $image[$x][$y] = $colorIndex;
            if(in_array($colorIndex, $backgroundColor)) {
                $image[$x][$y] = 255;
            }
            // else {
            //     echo "[$colorIndex]";
            //     $color = imagecolorsforindex($res, $colorIndex);
            //     // print_r($color);
            //     $r = $color['red'];
            //     $g = $color['green'];
            //     $b = $color['blue'];
            //     echo "[$r $g $b] ";
            // }
        }
    }

    // 去噪点
    $image = Denoise::imageDenoise($image, 120, 60);

    $columnPx = array();    // 某列像素个数
    $charCol = array();     // 字符的开始列和结束列
    $charFound = 0;         // 当前正在查找的字符
    $finding = FALSE;       // 扫描状态

    // 从左向右扫描 字符左右位置
    for($x = 0; $x<$sizeX; $x++) {
        $columnPx[$x] = 0;
        for($y=0; $y<$sizeY; $y++)
            if($image[$x][$y] != 255) {
                $columnPx[$x] ++;
            }

        // 如果正在扫描字符，此列没有出现点则结束正在扫描状态，
        // 记录结束列。如果不是在扫描状态，如果出现点，则记录
        // 字符开始列并转换状态。
        if($finding) {
            if($columnPx[$x] == 0) {
                $finding = FALSE;
                $charCol[$charFound++][1] = $x;
            }
        } elseif($columnPx[$x] > 0) {
            $finding = TRUE;
            $charCol[$charFound][0] = $x;
        }
    }

    // 是否有粘连
    if(count($charCol) < 4) {
        for($charNum=0; $charNum<4; $charNum++)
            if($charCol[$charNum][1]-$charCol[$charNum][0] > 30) { // 字符宽大于30则分裂
                for($col=3; $col>$charNum; $col--)
                    $charCol[$col] = $charCol[$col-1];
                // 分裂直接对半分
                $avgWidth = ceil(($charCol[$charNum][0] + $charCol[$charNum][1]) / 2);
                $charCol[$charNum+1][0] = $avgWidth;
                $charCol[$charNum][1] = $avgWidth;
            }
    }

    $charRow = array();
    // 从上到下扫描 字符上下位置
    for($charNum=0; $charNum<4; $charNum++) {
        $charRow[$charNum] = array(-1,-1);
        for($y=0; $y<$sizeY; $y++) {
            for($x=$charCol[$charNum][0]; $x<$charCol[$charNum][1]; $x++)
                if($image[$x][$y] != 255) {
                    // 如果此行有点，上界没有值则存上界，已经有值则存下界
                    if($charRow[$charNum][0] == -1)
                        $charRow[$charNum][0] = $y;
                    else
                        $charRow[$charNum][1] = $y;
                    break;
                }
        }
        $charRow[$charNum][1] ++; // 下界多加1，为与右界保持一致都是多1
    }

    // 二值化
    for($x=0; $x<$sizeX; $x++)
        for($y=0; $y<$sizeY; $y++)
            if($image[$x][$y] == 255)
                $image[$x][$y] = 0;
            else
                $image[$x][$y] = 1;

    // 取出四个字符
    $char = array_fill(0, 4, array_fill(0, 625, 0));
    for($charNum=0; $charNum<4; $charNum++) {
        $height = $charRow[$charNum][1] - $charRow[$charNum][0];
        $width = $charCol[$charNum][1] - $charCol[$charNum][0];
        // 最大尺寸25x25
        if($height > 25) {
            $charRow[$charNum][1] = $charRow[$charNum][0] + 25;
        }
        if($width > 25) {
            $charCol[$charNum][1] = $charCol[$charNum][0] + 25;
        }
        for($y=0; $y<$height; $y++)
            for($x=0; $x<$width; $x++)
                $char[$charNum][$y*25+$x] = $image[$x+$charCol[$charNum][0]][$y+$charRow[$charNum][0]];
    }

    return $char;

    // 写回颜色
    // for($x=0; $x<$sizeX; $x++)
    //     for($y=0; $y<$sizeY; $y++)
    //         imagesetpixel($res, $x, $y, $image[$x][$y]);
    // // 画边框
    // for($i=0; $i<4; $i++) {
    //     imageline($res, $charCol[$i][0]-1, 0, $charCol[$i][0]-1, 60, 0);
    //     imageline($res, $charCol[$i][1], 0, $charCol[$i][1], 60, 0);
    //     imageline($res, $charCol[$i][0], $charRow[$i][0]-1, $charCol[$i][1], $charRow[$i][0]-1, 0);
    //     imageline($res, $charCol[$i][0], $charRow[$i][1], $charCol[$i][1], $charRow[$i][1], 0);
    // }

    // imagegif($res, 'code-out.gif');
    // header("Content-Type:image/gif");
    // imagegif($res);
    // imagedestroy($res);
}

function readTheta() {
    $file = fopen('./train/h2000.txt', 'r');
    $theta = array();
    while($row = fgets($file)) {
        $rowArr = split(' ', trim($row));
        $theta[] = $rowArr;
    }
    fclose($file);
    return $theta;
}

function saveImageData($file, $image) {
    $output = '';
    $len = count($image);
    for($num=0; $num<$len; $num++) {
        for($i=0; $i<625; $i++)
            $output .= $image[$num][$i] . ' ';
        $output .= "\n";
    }
    fwrite($file, $output);
}

function getCode($fileName) {
    $content = file_get_contents('http://csujwc.its.csu.edu.cn/sys/ValidateCode.aspx');
    $file = fopen($fileName, 'w');
    fwrite($file, $content);
    fclose($file);
}