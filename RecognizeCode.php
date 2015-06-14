<?php
require 'Denoise.php';

class RecognizeCode {
    private static $thetaFile = './train/h.txt';
    private static $imageData = './autocode/';
    private $res;
    private $dataFileHandle;

    function __construct() {
        if(!is_dir(self::$imageData))
            mkdir(self::$imageData);
    }

    /**
     * 识别验证码
     * @param  string $fileName 图片地址
     * @return string           识别结果
     */
    function deal($fileName) {
        $char = $this->processImage($fileName);
        $theta = $this->readTheta();
        $pattern = '0123456789abcdefghigklmnopqrstuvwxyz';
        $ret = '';
        // 计算36个分类器的分类结果
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

    /**
     * 解析处理图片成二进制数据并存入文件
     * @param  integer $n        图片个数
     * @param  string  $fileName 图片文件名
     */
    function parseImage($n, $fileName='code') {
        $this->dataFileHandle = fopen(self::$imageData . 'data.txt', 'a');
        for($i=0;$i<$n;$i++) {
            $image = $this->processImage(self::$imageData . "{$fileName}-{$i}.gif");
            $this->saveImageData($image);
        }
        fclose($this->dataFileHandle);
    }

    /**
     * 处理图片 去噪 二值化
     * @param  string $fileName 验证码图片路径
     * @return array            四个字符的二进制矩阵
     */
    function processImage($fileName) {
        $backgroundColor = array(
            212, 218, 219, // 灰 绿 绿
            252, 209, 216 // 黄条
            );
        $sizeX = 120;
        $sizeY = 60;
        $res = imagecreatefromgif($fileName);
        $this->res = $res;
        $image = array();
        // 读颜色 去背景色
        for($x=0; $x<$sizeX; $x++) {
            for($y=0; $y<$sizeY; $y++) {
                $colorIndex = imagecolorat($res, $x, $y);
                $image[$x][$y] = $colorIndex;
                if(in_array($colorIndex, $backgroundColor)) {
                    $image[$x][$y] = 255;
                }
            }
        }

        // 去噪点
        $image = Denoise::imageDenoise($image, $sizeX, $sizeY);

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
                        if(isset($charCol[$col-1]))
                            $charCol[$col] = $charCol[$col-1];
                        else
                            $charCol[$col] = array();
                    // 按照颜色分割字符
                    $avgWidth = ceil(($charCol[$charNum][0] + $charCol[$charNum][1]) / 2);
                    $divide = false;
                    for($col=$avgWidth-5; $col<$avgWidth+5; $col++) {
                        $color1 = $this->averageRGB($image[$col]);
                        $color2 = $this->averageRGB($image[$col+1]);
                        // 色差大于100则不同字符
                        if($this->colorDistance($color1, $color2) > 100) {
                            $charCol[$charNum+1][0] = $col + 1;
                            $charCol[$charNum][1] = $col + 1;
                            $divide = true;
                            break;
                        }
                    }
                    // 还没被分割则直接对半分
                    if(!$divide) {
                        $charCol[$charNum+1][0] = $avgWidth;
                        $charCol[$charNum][1] = $avgWidth;
                    }
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
    }

    /**
     * 读取theta参数
     * @return array theta
     */
    function readTheta() {
        $file = fopen(self::$thetaFile, 'r');
        $theta = array();
        while($row = fgets($file)) {
            $rowArr = split(' ', trim($row));
            $theta[] = $rowArr;
        }
        fclose($file);
        return $theta;
    }

    /**
     * 下载验证码保存
     * @param  string $fileName 文件名
     */
    static function downloadCodeImage($fileName) {
        $content = file_get_contents('http://csujwc.its.csu.edu.cn/sys/ValidateCode.aspx');
        $file = fopen($fileName, 'w');
        fwrite($file, $content);
        fclose($file);
    }

    /**
     * 写入图片二进制数据到文件
     * @param  array  $image 图片二进制矩阵
     */
    private function saveImageData($image) {
        $output = '';
        $len = count($image);
        for($num=0; $num<$len; $num++) {
            for($i=0; $i<625; $i++)
                $output .= $image[$num][$i] . ' ';
            $output .= "\n";
        }
        fwrite($this->dataFileHandle, $output);
    }

    /**
     * 色差 RGB空间距离
     * @param  array  $color1 RGB颜色
     * @param  array  $color2 RGB颜色
     * @return integer        色差
     */
    private function colorDistance($color1, $color2) {
        $rd = $color1['red']-$color2['red'];
        $gd = $color1['green']-$color2['green'];
        $bd = $color1['blue']-$color2['blue'];
        return sqrt($rd*$rd + $gd*$gd + $bd*$bd);
    }

    /**
     * 列中非空白颜色RGB平均值
     * @param  array $line 一行或一列
     * @return array       RGB颜色
     */
    private function averageRGB($line) {
        $len = count($line);
        $avgColor = null;
        for($i=0; $i<$len; $i++)
            if($line[$i] != 255) {
                $color = imagecolorsforindex($this->res, $line[$i]);
                if($avgColor) {
                    $avgColor['red'] = ($avgColor['red'] + $color['red']) / 2;
                    $avgColor['green'] = ($avgColor['green'] + $color['green']) / 2;
                    $avgColor['blue'] = ($avgColor['blue'] + $color['blue']) / 2;
                } else {
                    $avgColor = $color;
                }
        }
        return $avgColor;
    }
}