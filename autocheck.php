<?php
require 'RecognizeCode.php';

/**
 * 自动获取图片、识别、提交教务判断识别正误、写入文件
 * @param  integer $times 获取次数
 */
function run($times = 100) {
    $targetFile = fopen('./autocode/target.txt', 'w');
    $good = 0;
    $bad = 0;
    $login = new LoginJw();
    $recognize = new RecognizeCode();

    for($i=0; $i<$times; $i++) {
        $image = $login->getCodeImage();
        $tempImage = './autocode/code.gif';

        $tempFile = fopen($tempImage, 'w');
        fwrite($tempFile, $image);
        fclose($tempFile);

        echo $i.": ";
        $code = $recognize->deal($tempImage);
        // echo '<img src="'.$tempImage.'"><br>';
        // echo $code;
        if($login->login($code)) {
            fwrite($targetFile, $code);
            rename($tempImage, "./autocode/code-{$good}.gif");
            $good ++;
            if($good%25==0)
                fwrite($targetFile, "\n");
        } else {
            rename($tempImage, "./autocode/badcode-{$bad}.gif");
            $bad ++;
        }
        if($i%100 == 99) $login->refreshStatus();
    }
    fclose($targetFile);
    echo "识别率 ".($good/$times*100).'%';
    return $good;
}

/**
 * 登陆教务网站
 */
class LoginJw
{
    private $session;
    private $formStatus = '';
    
    function __construct()
    {
        $this->refreshStatus();
    }

    /**
     * 重新获取表单状态、session
     */
    function refreshStatus() {
        $url = 'http://csujwc.its.csu.edu.cn/_data/index_LOGIN.aspx';
        $curl = curl_init ();
        curl_setopt_array ( $curl, array (
            CURLOPT_URL => $url,
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 4
        ) );
        $result = curl_exec($curl);
        $errno = curl_errno($curl);
        $error = curl_error($curl);
        curl_close ( $curl );
        if($errno)
            throw new Exception("$errno, $error");

        if(preg_match('/SessionId=(\w*);/', $result, $session))
            $this->session = $session[1];
        else
            throw new Exception('session获取失败');

        if(preg_match('/VIEWSTATE" value="(.+?)"/', $result, $status))
            $this->formStatus = urlencode($status[1]);
    }

    /**
     * 获取验证码
     * @return string 验证码
     */
    function getCodeImage() {
        $url = 'http://csujwc.its.csu.edu.cn/sys/ValidateCode.aspx';
        $header = array(
                'Cookie: ASP.NET_SessionId=' . $this->session
            );

        $curl = curl_init ();
        curl_setopt_array ( $curl, array (
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 4
        ) );
        $result = curl_exec($curl);
        $errno = curl_errno($curl);
        $error = curl_error($curl);
        curl_close ( $curl );
        if($errno)
            throw new Exception("$errno, $error");

        return $result;
    }

    /**
     * 教务登陆测试
     * @param  string $code 验证码
     * @return boolean      验证码是否通过测试
     */
    function login($code) {
        $user = '1313131313';
        $password = '123456';
        $encodeUser = $this->md5up($user.$this->md5up($password).'10533');
        $encodeCode = $this->md5up($this->md5up(strtoupper($code)).'10533');
        $url = 'http://csujwc.its.csu.edu.cn/_data/index_LOGIN.aspx';
        $data = "__VIEWSTATE={$this->formStatus}&Sel_Type=STU&txt_sdsdfdsfryuiighgdf={$user}&txt_dsfdgtjhjuixssdsdf={$password}&txt_sftfgtrefjdndcfgerg={$code}&typeName=%D1%A7%C9%FA&sdfdfdhgwerewt={$encodeUser}&cxfdsfdshjhjlk={$encodeCode}";
        $header = array(
                'User-Agent: Mozilla/5.0',
                'Referer: http://csujwc.its.csu.edu.cn/_data/index_LOGIN.aspx',
                'Cookie: ASP.NET_SessionId=' . $this->session
            );

        $curl = curl_init ();
        curl_setopt_array ( $curl, array (
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => 'gzip',
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_TIMEOUT => 4
        ) );
        $result = mb_convert_encoding(curl_exec($curl), 'UTF-8', 'GBK');
        $errno = curl_errno($curl);
        $error = curl_error($curl);
        curl_close ( $curl );
        if($errno)
            return false;

        // 判断登陆是否成功
        if(strpos($result, 'color="Red"') > 0) {
            // 登陆失败
            preg_match('/"Red">(.+?)</', $result, $loginError);
            echo $loginError[1]."<br>\n";
            ob_flush();
            flush();
            if($loginError[1] == '帐号或密码不正确！')
                return true;
            elseif($loginError[1] == '验证码错误！')
                return false;
        }
        echo "error<br>\n";
        ob_flush();
        flush();
        return false;
    }

    private function md5up($s) {
        return strtoupper(substr(md5($s), 0, 30));
    }
}