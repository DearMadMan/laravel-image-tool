<?php namespace Dearmadman\ImageTool;

use Response;

/**
 * imagetool.class.php
 * 图片类型检索，非图片类型pass->开启删除模式
 * 动态创建图片画布
 * 保存模块
 * 压缩模块
 * 自定义图片宽高接口
 * 质量比率接口
 * 水印模块
 * 水印透明度
 * 水印位置
 * 图片显示模式接口
 * 等比例压缩模式 两边留白
 * 独立模块
 */

/**
 * 纯静态类，直接使用 使用方法如下：
 *
 *    $arr=array(
 *            "width"=>$gbs["configs"]['thumb_width'],
 *            "height"=>$gbs["configs"]['thumb_height'],
 *            "jpeg_quality"=>90,
 *            "watermark"=>false
 *        );
 *    imagetool::InitFromArray($arr);
 *    imagetool::GetImageFromString($upload[0],"thumb");
 *        $arr=array(
 *            "width"=>$gbs["configs"]['show_width'],
 *            "height"=>$gbs["configs"]['show_height']
 *        );
 *    imagetool::InitFromArray($arr);
 *    imagetool::GetImageFromString($upload[0],"img");
 */
class imagetool
{
    private  $can_remove = true; //不是图片类型可删除
    private  $width = 350;  //图片宽
    private  $height = 350; //图片高
    private  $jpeg_quality = 95; //jpeg图片质量
    private  $watermark = true; //是否开启水印
    private  $watermark_path = "watermark.png";
    private  $watermark_padding_x = 0;
    private  $watermark_padding_y = 0;   //便宜距离 可为负
    private  $watermark_apache = 70;   //透明度
    private  $com_mode = 3;  //水印方位：0.左上角 1.右上角 2.左下角 3.右下角
    private  $return_success = true; //输出成功数组
    private  $cover_img = false; //覆盖原图片
    
    private static $instance;
    
    private function __construct(){}
    private function __clone(){}

    public static function GetInstance(){
        if(!(self::$instance instanceof self)){
            slef::$instance=new self();
        }
        return self::$instance;
    }
    /**
     * [GetImage 图片压缩]
     * @param [type] $arr [数组或字符串]
     */
    public  function GetImage($arr)
    {
        $returns = array();
        if (is_array($arr)) {
            foreach ($arr as $key => $value) {
                $returns[] = $this->GetImageFromString($value);
            }
        } else {
            $returns = $this->GetImageFromString($arr);
        }
        if ($this->return_success) {
            return $returns;
        }
    }

    /**
     * [ImageInfo 获取img信息数组]
     * @param [type] $path [图片路径]
     */
    public  function ImageInfo($path)
    {

        if (!file_exists($path)) {

            return false;
        }
        $info = getimagesize($path);
        if (!$info) {
            if ($this->can_remove) {
                unlink($path);
            }
            return false;
        }

        $img = array();
        $img['width'] = $info[0];
        $img['height'] = $info[1];
        $img['ext'] = substr($info['mime'], strpos($info['mime'], '/') + 1);
        return $img;
    }

    public  function WaterMarker($d_img, $img)
    {

        $w_img = $this->ImageInfo($this->watermark_path);
        if (!$w_img) {
            return false;
        }

        if ($img['width'] < $w_img['width'] || $img['height'] < $w_img['height']) {
            return false;
        }
        $wfunc = 'imagecreatefrom' . $w_img['ext'];
        if (!function_exists($wfunc)) {
            return false;
        }
        $w_im = $wfunc($this->watermark_path);  //创建水印画布
        $dx = 0;
        $dy = 0;
        $x = true;
        $y = true;
        if ($this->watermark_padding_x + $w_img['width'] > $img['width']) {
            $x = false;
        }
        if ($this->watermark_padding_y + $w_img['height'] > $img['height']) {
            $y = false;
        }
        $temp_x = $img['width'] - $w_img['width'];
        $temp_y = $img['height'] - $w_img['height'];
        switch ($this->com_mode) {
            case 0://左上角
                $x ? $dx = $this->watermark_padding_x : $dx = 0;
                $y ? $dy = $this->watermark_padding_y : $dy = 0;
                break;
            case 1://右上角

                $x ? $dx = $this->watermark_padding_x + $temp_x : $temp_x;
                $y ? $dy = $this->watermark_padding_y : 0;
                break;
            case 2://左下角
                $x ? $dx = $this->watermark_padding_x : 0;
                $y ? $dy = $this->watermark_padding_y + $temp_y : $temp_y;
                break;
            case 3://右下角
                $x ? $dx = $this->watermark_padding_x + $temp_x : $temp_x;
                $y ? $dy = $this->watermark_padding_y + $temp_y : $temp_y;
                break;
            default://居中 不推荐
                break;
        }
        imagecopymerge($d_img, $w_im, $dx, $dy, 0, 0, $w_img['width'], $w_img['height'], $this->watermark_apache);
        imagedestroy($w_im);
        return $d_img;
    }

    /**
     * 独立路径生成水印模块
     * @param [type] $path [description]
     */
    public  function WaterMarkerFromPath($path)
    {
        $img = $this->ImageInfo($path);
        if (!$img) {
            return false;
        }
        $dfunc = 'imagecreatefrom' . $img['ext'];
        $d_img = $dfunc($path);
        $d_img = $this->WaterMarker($d_img, $img);
        $dfc = 'image' . $img['ext'];
        $dfc($path);
        imagedestroy($d_img);
    }

    /**
     * [GetImageFromString 根据一个path压缩一张图片并返回路径]
     * @param [type] $path [description]
     */
    public  function GetImageFromString($path, $thumb_prefix = "thumb")
    {

        $img = $this->ImageInfo($path);
        if (!$img) {
            return false;
        }
        //计算缩放比例
        $calc = min($this->width / $img['width'], $this->height / $img['height']);
        //创建原始画布 动态创建函数
        $imagecreatefromfunc = 'imagecreatefrom' . $img['ext'];

        $s_img = $imagecreatefromfunc($path);
        $d_img = imagecreatetruecolor($this->width, $this->height);
        $white = imagecolorallocate($d_img, 255, 255, 255);
        imagefill($d_img, 0, 0, $white);
        $dwidth = (int)$img['width'] * $calc;
        $dheight = (int)$img['height'] * $calc;
        $paddingx = (int)($this->width - $dwidth) / 2;
        $paddingy = (int)($this->height - $dheight) / 2;
        imagecopyresampled($d_img, $s_img, $paddingx, $paddingy, 0, 0, $dwidth, $dheight, $img['width'], $img['height']);
        if ($this->cover_img) {

            unlink($path);
        } else {

            if (strripos($path, "/")) {
                $str = substr($path, strripos($path, "/") + 1);
                $strp = substr($path, 0, strripos($path, "/") + 1);
                $path = $strp . $thumb_prefix . "_" . $str;
            } else {
                $path = $thumb_prefix . "_" . $path;
            }


        }
        if ($this->watermark) {
            //水印
            $dimg = array();
            $dimg['width'] = $this->width;
            $dimg['height'] = $this->height;
            $d_img = $this->WaterMarker($d_img, $dimg);
        }


        $createfunc = 'image' . $img['ext'];
        $createfunc($d_img, $path);
        imagedestroy($s_img);
        imagedestroy($d_img);
        return $path;


    }

    /**
     * [SetConfig 利用数组的方式初始化静态类]
     * @param [type] $array [description]
     */
    public  function SetConfig($array)
    {

        foreach ($array as $key => $value) {

            if (isset($this->$key)) {
                $this->$key = $value;
            } else {
            }
        }
    }


}

?>