<?php namespace Very\Filesystem;

class Upload {
    //文件保存目录路径
    public $configs;


    public function init($configs) {

        $default_configs = array(
            'directory' => '',
            'allowed'     => array('jpg', 'gif', 'png', 'jpeg'),//文件后缀
            'max_size'    => '3M',//文件最大大小
            'file_name'   => '',//自定义文件名
        );

        $this->configs = array_merge($default_configs, $configs);

        if (!is_dir($this->configs['directory'])) {
            $this->mkdirs($this->configs['directory'], 0775);
        }

        if (!$this->checkDir($this->configs['directory'])) {
            return array(
                'code' => 400,
                'msg'  => '目录' . $this->configs['directory'] . '创建失败',
            );
        }
        return $this;
	}

    public function valid(array $file) {
        return (isset($file['name']) && isset($file['tmp_name']) && isset($file['size']) && isset($file['type']));
    }

    //验证目录
    public function checkDir($directory) {
        if (is_dir(realpath($directory)) && is_writable($directory)) {
            return true;
        }

        return false;
    }

    //验证类型
    public static function checkType(array $file, array $allowed) {
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        return in_array($file_ext, $allowed);
    }

    public static function checkSize(array $file, $allowed_size) {
        $allowed_size = strtoupper($allowed_size);

        if (!preg_match('#[0-9]++[GMKB]#i', $allowed_size)) {
            return false;
        }

        switch (substr($allowed_size, -1)) {
            case 'G':
                $allowed_size = intval($allowed_size) * pow(1024, 3);
                break;
            case 'M':
                $allowed_size = intval($allowed_size) * pow(1024, 2);
                break;
            case 'K':
                $allowed_size = intval($allowed_size) * pow(1024, 1);
                break;
            case 'B':
                $allowed_size = intval($allowed_size);
                break;
        }

        return ($file['size'] <= $allowed_size);
    }

    public function mkdirs($dir, $mode = 0755) {
        return mkdir($dir, $mode,true);
    }

    public function save(array $file) {
        $configs = $this->configs;
        //有上传文件时
        if ($this->valid($file) === true) {

            //检测扩展名
            if ($this->checkType($file, $configs['allowed']) === false) {
                return array(
                    'code' => 301,
                    'msg'  => '不允许上传该类型的文件，允许的文件有：' . implode(',', $configs['allowed']),
                );
            }

            //检测大小
            if ($this->checkSize($file, $configs['max_size']) === false) {
                return array(
                    'code' => 302,
                    'msg'  => '文件超过了最大' . $configs['max_size'],
                );
            }

            if (@is_uploaded_file($file['tmp_name']) === false) {
                return array(
                    'code' => 401,
                    'msg'  => '上传临时文件不存在',
                );
            }
            $file_path = $this->configs['directory'] . DS;

            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($configs['file_name']) {
                $file_name = $configs['file_name'] . '.' . $file_ext;
            } else {
                $file_name = md5(uniqid() . time()) . '.' . $file_ext;
            }

            //存储文件
            if (move_uploaded_file($file['tmp_name'], $file_path.$file_name) === false) {

                return array(
                    'code' => 403,
                    'msg'  => '存储' . $file_name . '失败',
                );
            }

            return array(
                'code'      => 200,
                'file' => $file_path.$file_name,
                'file_name'=>$file_name,
                'msg'       => '上传成功',
                'ori_name'  => $file['name'],
            );

            //$this->imageWaterMark($file_path,$this->waterImage);
        } else {
            return array(
                'code' => 404,
                'msg'  => '没有上传文件',
            );
        }
    }

//	public function imageWaterMark($groundImage,$waterImage="",$waterPos=5){
//
//		$formatMsg = "暂不支持该文件格式，请用图片处理软件将图片转换为GIF、JPG、PNG格式。";
//		$compressSize = 700;//压缩后的宽度
//		$thumbsize = 200; //缩略图宽度
//
//		//读取水印文件
//		if(!empty($waterImage) && file_exists($waterImage)) {
//			$water_info = getimagesize($waterImage);
//			$water_w   = $water_info[0];//取得水印图片的宽
//			$water_h   = $water_info[1];//取得水印图片的高
//
//			switch($water_info[2])//取得水印图片的格式
//			{
//				case 1:$water_im = imagecreatefromgif($waterImage);break;
//				case 2:$water_im = imagecreatefromjpeg($waterImage);break;
//				case 3:$water_im = imagecreatefrompng($waterImage);break;
//				default:die($formatMsg);
//			}
//		}
//
//		//读取背景图片
//		if(!empty($groundImage) && file_exists($groundImage)) {
//			$ground_info = getimagesize($groundImage);
//			$ground_w   = $ground_info[0];//取得背景图片的宽
//			$ground_h   = $ground_info[1];//取得背景图片的高
//
//			switch($ground_info[2])//取得背景图片的格式
//			{
//				case 1:$ground_im = imagecreatefromgif($groundImage);break;
//				case 2:$ground_im = imagecreatefromjpeg($groundImage);break;
//				case 3:$ground_im = imagecreatefrompng($groundImage);break;
//				default:die($formatMsg);
//			}
//			//压缩图片
//			if($ground_w>$compressSize){
//				$oground_w = $ground_w;
//				$oground_h = $ground_h;
//				$ground_h = floor($ground_h *( 1- (($ground_w-$compressSize)/$ground_w)));
//				$ground_w = $compressSize;
//				$ground_im = $this->ImageResize($groundImage,$ground_im,$ground_w,$ground_h,$oground_w,$oground_h);
//			}
//		}else{
//			//$this->alert("需要加水印的图片不存在！");
//		}
//
//		if( ($ground_w<$water_w) || ($ground_h<$water_h) ) {
//			//$this->alert("需要加水印的图片的长度或宽度比水印图片还小，无法生成水印！");
//			return;
//		}
//		switch($waterPos)
//		{
//			case 0://随机
//				$posX = rand(0,($ground_w - $water_w));
//				$posY = rand(0,($ground_h - $water_h));
//				break;
//			case 1://1为顶端居左
//				$posX = 0;
//				$posY = 0;
//				break;
//			case 2://2为顶端居中
//				$posX = ($ground_w - $water_w) / 2;
//				$posY = 0;
//				break;
//			case 3://3为顶端居右
//				$posX = $ground_w - $water_w;
//				$posY = 0;
//				break;
//			case 4://4为中部居左
//				$posX = 0;
//				$posY = ($ground_h - $water_h) / 2;
//				break;
//			case 5://5为中部居中
//				$posX = ($ground_w - $water_w) / 2;
//				$posY = ($ground_h - $water_h) / 2;
//				break;
//			case 6://6为中部居右
//				$posX = $ground_w - $water_w;
//				$posY = ($ground_h - $water_h) / 2;
//				break;
//			case 7://7为底端居左
//				$posX = 0;
//				$posY = $ground_h - $water_h;
//				break;
//			case 8://8为底端居中
//				$posX = ($ground_w - $water_w) / 2;
//				$posY = $ground_h - $water_h;
//				break;
//			case 9://9为底端居右
//				$posX = $ground_w - $water_w;
//				$posY = $ground_h - $water_h;
//				break;
//			default://随机
//				$posX = rand(0,($ground_w - $water_w));
//				$posY = rand(0,($ground_h - $water_h));
//				break;
//		}
//		if($this->iswater){
//	  //设定图像的混色模式
//			imagealphablending($ground_im, true);
//
//			imagecopy($ground_im, $water_im, $posX, $posY, 0, 0, $water_w,$water_h);//拷贝水印到目标文件
//			imagejpeg($ground_im,$groundImage);
//		}
//
//		if($this->isthumb){
//			//生成缩略图
//			$thumb_h = floor($ground_h *( 1- (($ground_w-$thumbsize)/$ground_w)));
//			$thumb_w = $thumbsize;
//			$thumbImage = $this->save_path .'thumb_'. $this->file_name;
//			$this->ImageResize($thumbImage,$ground_im,$thumb_w,$thumb_h,$ground_w,$ground_h);
//		}
//
//		//释放内存
//		if(isset($water_info)) unset($water_info);
//		if(isset($water_im)) imagedestroy($water_im);
//		if(isset($ground_info)) unset($ground_info);
//		if(isset($ground_im)) imagedestroy($ground_im);
//	}
//	public function ImageResize($groundImage,$pImage, $t_width, $t_height, $s_width, $s_height) {
//		if(function_exists("imagecopyresampled")) {
//			$iCanvas = imagecreatetruecolor($t_width, $t_height);
//			imagecopyresampled($iCanvas, $pImage, 0, 0, 0, 0, $t_width, $t_height, $s_width, $s_height);
//		}
//		else {
//			$iCanvas = imagecreate($t_width, $s_width);
//			imagecopyresized($iCanvas, $pImage, 0, 0, 0, 0, $t_width, $t_height, $s_width, $s_height);
//		}
//		imagejpeg($iCanvas,$groundImage);
//		return $iCanvas;
//	}
}