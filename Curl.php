<?php

/**
 * @class Curl
 * @version 1.0.0
 * @author LuminEe
 * @date 2020-07-28
 */
class Curl
{
    /**
     * @param $url
     * @return bool|string
     */
    public static function curlGet($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url); //设置抓取的url
        curl_setopt($curl, CURLOPT_HEADER, 0); //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); //设置获取的信息以文件流的形式返回，而不是直接输出。
        $data = curl_exec($curl); //执行命令
        curl_close($curl); //关闭URL请求
        return $data;
    }

    /**
     * @param $url
     * @param $data
     * @return bool|string
     */
    public static function curlPost($url, $data)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);  //设置url
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);  //设置http验证方法
        curl_setopt($curl, CURLOPT_HEADER, 0);  //设置头信息
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=utf-8'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  //设置curl_exec获取的信息的返回方式
        curl_setopt($curl, CURLOPT_POST, 1);  //设置发送方式为post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);  //设置post的数据
        $data = curl_exec($curl);//运行curl
        curl_close($curl);
        return $data;
    }

    /**
     * @param $url
     * @param $data
     * @return bool|string
     */
    private function curlUpload($url, $data)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);  //设置url
        curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
        curl_setopt($curl, CURLOPT_POST, 1); //设置发送方式为post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); //设置post的数据
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($curl);//运行curl
        curl_close($curl);
        return $data;
    }
}