<?php

namespace Very\Image;

/**
 * Captcha Class base on PHP GD Lib
 * User: 蔡旭东 fifsky@dev.ppstream.com
 * Date: 13-3-6
 * Time: 下午5:25.
 */
class Captcha
{
    //定义验证码图片高度
    private $height;
//定义验证码图片宽度
    private $width;
//定义验证码字符个数
    private $textNum;
//定义验证码字符内容
    private $textContent;
//定义字符颜色
    private $fontColor;
//定义随机出的文字颜色
    private $randFontColor;
//定义字体大小
    private $fontSize;
//定义字体
    private $fontFamily;
//定义背景颜色
    private $bgColor;
//定义随机出的背景颜色
    private $randBgColor;
//定义字符语言
    private $textLang;
//定义干扰点数量
    private $noisePoint;
//定义干扰线数量
    private $noiseLine;
//定义是否扭曲
    private $distortion;
//定义扭曲图片源
    private $distortionImage;
//定义是否有边框
    private $showBorder;
//定义验证码图片源
    private $image;

    public function __construct()
    {
        $this->textNum = 4;
        $this->fontSize = 16;
        $this->fontFamily = __DIR__.'/vcodefont.ttf'; //设置中文字体，可以改成linux的目录
        $this->textLang = 'en';
        $this->noisePoint = 30;
        $this->noiseLine = 3;
        $this->distortion = false;
        $this->showBorder = false;
    } // from liehuo.net

    /**
     * 设置图片宽度.
     *
     * @param $w
     */
    public function setWidth($w)
    {
        $this->width = $w;
    }

    /**
     * 设置图片高度.
     *
     * @param $h
     */
    public function setHeight($h)
    {
        $this->height = $h;
    }

    /**
     * 设置字符个数.
     *
     * @param $textN
     */
    public function setTextNumber($textN)
    {
        $this->textNum = $textN;
    }

    /**
     * 设置字符颜色.
     *
     * @param $fc
     */
    public function setFontColor($fc)
    {
        $this->fontColor = sscanf($fc, '#%2x%2x%2x');
    }

//设置字号,已废弃
//    public function setFontSize($n) {
//        $this->fontSize = $n;
//    }

    /**
     * 设置字体.
     *
     * @param $ffUrl
     */
    public function setFontFamily($ffUrl)
    {
        $this->fontFamily = $ffUrl;
    }

    /**
     * 设置字符语言
     *
     * @param $lang
     */
    public function setTextLang($lang)
    {
        $this->textLang = $lang;
    }

    /**
     * 设置图片背景.
     *
     * @param $bc
     */
    public function setBgColor($bc)
    {
        $this->bgColor = sscanf($bc, '#%2x%2x%2x');
    }

    /**
     * 设置干扰点数量.
     *
     * @param $n
     */
    public function setNoisePoint($n)
    {
        $this->noisePoint = $n;
    }

    /**
     * 设置干扰线数量.
     *
     * @param $n
     */
    public function setNoiseLine($n)
    {
        $this->noiseLine = $n;
    }

    /**
     * 设置是否扭曲.
     *
     * @param $b
     */
    public function setDistortion($b)
    {
        $this->distortion = $b;
    }

    /**
     * 设置是否显示边框.
     *
     * @param $border
     */
    public function setShowBorder($border)
    {
        $this->showBorder = $border;
    }

    /**
     * 初始化验证码图片.
     */
    public function initImage()
    {
        if (empty($this->width)) {
            $this->width = floor($this->fontSize) * $this->textNum + 10;
        } else {
            //根据宽度自动计算字体大小
            $this->fontSize = max(floor(($this->width - 20) / $this->textNum), $this->fontSize);
        }

        if (empty($this->height)) {
            $this->height = $this->fontSize * 1.5;
        }

        $this->image = imagecreatetruecolor($this->width, $this->height);
        if (empty($this->bgColor)) {
            $this->randBgColor = imagecolorallocate($this->image, mt_rand(100, 255), mt_rand(100, 255), mt_rand(100, 255));
        } else {
            $this->randBgColor = imagecolorallocate($this->image, $this->bgColor[0], $this->bgColor[1], $this->bgColor[2]);
        }
        imagefill($this->image, 0, 0, $this->randBgColor);
    }

    /**
     * 产生随机字符.
     *
     * @param $type
     *
     * @return string
     */
    public function randText($type)
    {
        $string = '';
        switch ($type) {
            case 'en':
                $str = 'ABCDEFGHJKLMNPQRSTUVWXY3456789';
                for ($i = 0; $i < $this->textNum; ++$i) {
                    $string = $string.','.$str[mt_rand(0, 29)];
                }
                break;
            case 'cn':
                for ($i = 0; $i < $this->textNum; ++$i) {
                    $string = $string.','.chr(rand(0xB0, 0xCC)).chr(rand(0xA1, 0xBB));
                }
                $string = iconv('GB2312', 'UTF-8', $string); //转换编码到utf8
                break;
        }

        return substr($string, 1);
    }

    /**
     * 输出文字到验证码
     */
    public function createText()
    {
        $textArray = explode(',', $this->randText($this->textLang));
        $this->textContent = implode('', $textArray);
        if (empty($this->fontColor)) {
            $this->randFontColor = imagecolorallocate($this->image, mt_rand(0, 200), mt_rand(0, 200), mt_rand(0, 200));
        } else {
            $this->randFontColor = imagecolorallocate($this->image, $this->fontColor[0], $this->fontColor[1], $this->fontColor[2]);
        }

        $font_width = floor($this->fontSize) * $this->textNum;

        $x_pos = 5;

        if ($this->width > $font_width) {
            $x_pos = floor(($this->width - $font_width) / 2) + 2;
        }

        for ($i = 0; $i < $this->textNum; ++$i) {
            $angle = mt_rand(-1, 1) * mt_rand(2, 20);
            imagettftext($this->image, $this->fontSize, $angle, $x_pos + $i * floor($this->fontSize), floor(($this->height - $this->fontSize) / 2) + $this->fontSize - 2, $this->randFontColor, $this->fontFamily, $textArray[$i]);
        }
    }

    /**
     * 生成干扰点.
     */
    public function createNoisePoint()
    {
        for ($i = 0; $i < $this->noisePoint; ++$i) {
            $pointColor = imagecolorallocate($this->image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagesetpixel($this->image, mt_rand(0, $this->width), mt_rand(0, $this->height), $pointColor);
        }
    }

    /**
     * 产生干扰线
     */
    public function createNoiseLine()
    {
        for ($i = 0; $i < $this->noiseLine; ++$i) {
            $lineColor = imagecolorallocate($this->image, mt_rand(0, 255), mt_rand(0, 255), 20);
            imageline($this->image, 0, mt_rand(0, $this->width), $this->width, mt_rand(0, $this->height), $lineColor);
        }
    }

    /**
     * 扭曲文字.
     */
    public function distortionText()
    {
        $this->distortionImage = imagecreatetruecolor($this->width, $this->height);
        imagefill($this->distortionImage, 0, 0, $this->randBgColor);
        for ($x = 0; $x < $this->width; ++$x) {
            for ($y = 0; $y < $this->height; ++$y) {
                $rgbColor = imagecolorat($this->image, $x, $y);
                imagesetpixel($this->distortionImage, (int) ($x + sin($y / $this->height * 2 * M_PI - M_PI * 0.5) * 3), $y, $rgbColor);
            }
        }
        $this->image = $this->distortionImage;
    }

    /**
     * 生成验证码图片.
     *
     * @return mixed
     */
    public function createImage()
    {
        $this->initImage(); //创建基本图片
        $this->createText(); //输出验证码字符
        if ($this->distortion) {
            $this->distortionText();
        } //扭曲文字
        $this->createNoisePoint(); //产生干扰点
        $this->createNoiseLine(); //产生干扰线
        if ($this->showBorder) {
            imagerectangle($this->image, 0, 0, $this->width - 1, $this->height - 1, $this->randFontColor);
        } //添加边框
        imagepng($this->image);
        imagedestroy($this->image);
        if ($this->distortion) {
            imagedestroy($this->distortionImage);
        }

        return $this->textContent;
    }
}

/*
 * example
 *
$token       = isset($_GET['token']) ? trim($_GET['token']) : '';
$width      = isset($_GET['width']) ? (int)$_GET['width'] : 0;
$height     = isset($_GET['height']) ? (int)$_GET['height'] : 0;
$num        = isset($_GET['num']) && (int)$_GET['num'] ? (int)$_GET['num'] : 4;

if (empty($token)) {
    die('token error');
}

//验证token:vcid的合法性
$ver_vcid1 = substr(md5(strrev(substr($token,0,16)).'caixudong'),16);
$ver_vcid2 = substr($token,16);

if($ver_vcid1 !== $ver_vcid2){
    die('token error');
}

header("Content-type:image/png");

$captcha = new Captcha();

if ($width) {
    //设置验证码宽度
    $captcha->setWidth($width);
}

if ($height) {
    //设置验证码高度
    $captcha->setHeight($height);
}

//设置字符个数
$captcha->setTextNumber($num);

//设置字符颜色
//$captcha->setFontColor('#ff9900');

//设置字体
$captcha->setFontFamily(dirname(__FILE__) . '/vcodefont.ttf');

//设置背景颜色
$captcha->setBgColor('#FFFFFF');

//设置干扰点数量
$captcha->setNoisePoint(50);

//设置干扰线数量
$captcha->setNoiseLine(0);

//设置是否扭曲
//$captcha->setDistortion(true);

//设置是否显示边框
//$captcha->setShowBorder(false);

//输出验证码
$code = $captcha->createImage();

$memcache = new memcache_server2('vcode');
$memcache->connect(1);
$memcache->set('vcid_' . $token, $code, false, 60);
$memcache->close();
 */
