<?php

include 'img.php';
run();
// parseImage(76);

function run() {
    $targetFile = fopen('./autocode/target.txt', 'w');
    $num = 0;
    for($i=0; $i<500; $i++) {
        $session = getSession();
        $image = getCodeImage($session);
        $tempImage = './autocode/code.gif';

        $tempFile = fopen($tempImage, 'w');
        fwrite($tempFile, $image);
        fclose($tempFile);

        $code = deal($tempImage);
        // echo '<img src="'.$tempImage.'"><br>';
        // echo $code;
        if(login($session, $code)) {
            fwrite($targetFile, $code);
            rename($tempImage, "./autocode/code-{$num}.gif");
            $num ++;
            if($num%25==0)
                fwrite($targetFile, "\n");
        }
    }
    fclose($targetFile);
    echo "识别率 ".($num/100).'%';
}

function login($session, $code) {
    $user = '1313131313';
    $password = '123456';
    $encodeUser = md5up($user.md5up($password).'10533');
    $encodePsw = md5up(md5up(strtoupper($code)).'10533');
    $url = 'http://csujwc.its.csu.edu.cn/_data/index_LOGIN.aspx';
    $data = "__VIEWSTATE=dDwzMzgyNTExODY7dDw7bDxpPDA%2BO2k8MT47aTwyPjtpPDM%2BOz47bDx0PHA8bDxUZXh0Oz47bDzkuK3ljZflpKflraY7Pj47Oz47dDxwPGw8VGV4dDs%2BO2w8XGU7Pj47Oz47dDw7bDxpPDE%2BOz47bDx0PDtsPGk8MD47PjtsPHQ8cDxsPFRleHQ7PjtsPFw8b3B0aW9uIHZhbHVlPSdTVFUnIHVzcklEPSflrabjgIDlj7cnXD7lrabnlJ9cPC9vcHRpb25cPgpcPG9wdGlvbiB2YWx1ZT0nVEVBJyB1c3JJRD0n5bel44CA5Y%2B3J1w%2B5pWZ5biI5pWZ6L6F5Lq65ZGYXDwvb3B0aW9uXD4KXDxvcHRpb24gdmFsdWU9J1NZUycgdXNySUQ9J%2BW4kOOAgOWPtydcPueuoeeQhuS6uuWRmFw8L29wdGlvblw%2BClw8b3B0aW9uIHZhbHVlPSdBRE0nIHVzcklEPSfluJDjgIDlj7cnXD7pl6jmiLfnu7TmiqTlkZhcPC9vcHRpb25cPgo7Pj47Oz47Pj47Pj47dDxwPHA8bDxUZXh0Oz47bDzpqozor4HnoIHplJnor6%2FvvIFcPGJyXD7nmbvlvZXlpLHotKXvvIE7Pj47Pjs7Pjs%2BPjs%2BbJEaPfguxTuU8HTmL4IvrIkQ5FQ%3D&Sel_Type=STU&txt_sdsdfdsfryuiighgdf={$user}&txt_dsfdgtjhjuixssdsdf={$password}&txt_sftfgtrefjdndcfgerg={$code}&typeName=%D1%A7%C9%FA&sdfdfdhgwerewt={$encodeUser}&cxfdsfdshjhjlk={$encodePsw}";
    $header = array(
            'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/43.0.2357.124 Safari/537.36',
            'Referer: http://csujwc.its.csu.edu.cn/_data/index_LOGIN.aspx',
            'Cookie: ASP.NET_SessionId=' . $session
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
        // throw new Exception("$errno, $error");

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
        // throw new Exception($loginError[1]);
    }
    return false;
}

function md5up($s) {
    return strtoupper(substr(md5($s), 0, 30));
}

function getCodeImage($session) {
    $url = 'http://csujwc.its.csu.edu.cn/sys/ValidateCode.aspx';
    $header = array(
            'Cookie: ASP.NET_SessionId=' . $session
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

function getSession() {
    $headers = get_headers('http://csujwc.its.csu.edu.cn/_data/index_LOGIN.aspx',1);
    if(preg_match('/SessionId=(\w*);/', $headers['Set-Cookie'], $session))
        return $session[1];
    else
        return '';
}