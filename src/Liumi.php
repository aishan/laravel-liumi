<?php
/**
 * 流米充值服务类
 * User: aishan
 * Date: 16-8-8
 * Time: 下午4:26
 */
namespace Aishan\LaravelLiumi;
class Liumi{
    private $serverUrl;
    private $appKey;
    private $appSecret;
    private $token;
    private $sign;

    function __construct()
    {
        $this->serverUrl = config('liumi.serverUrl');
        $this->appKey = config('liumi.appKey');
        $this->appSecret = md5(config('liumi.appSecret'));
        //鉴权获取Token
        $this->sign = sha1("appkey" . $this->appKey . "appsecret" . $this->appSecret); //顺序不能变
        $params = [
            "appkey"        => $this->appKey,
            "appsecret"     => $this->appSecret,
        ];
        $tokenInfo = $this->apiRequest($this->serverUrl.'getToken',$this->getParamsWithSign($params),'POST');
        if(empty($tokenInfo) || $tokenInfo['code'] !='000'){
            throw new \Exception("流米鉴权失败,Error Code :".$tokenInfo['code']);
        }else{
            $this->token = $tokenInfo['data']['token'];
        }
    }

    /**
     * 发送请求curl方法
     * @param $url
     * @param $data
     * @param string $method
     * @return mixed
     */
    function apiRequest($url, $data, $method = "GET"){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $method = strtoupper($method);
        if ($method == "POST") {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $content = curl_exec($ch);
        curl_close($ch);
        return json_decode($content,true);
    }

    /**
     * 获取带有签名的参数json
     * @param $signArray
     * @return string
     */
    function getParamsWithSign($signArray){
        ksort($signArray);
        $sign = "";
        foreach ($signArray as $key => $val) {
            if (strlen(trim($val))) {
                $sign .=  $key . $val;
            }
        }
        //sha1
        $newSign = sha1($sign);
        $signArray["sign"] = $newSign;
        $jsonObj = json_encode($signArray);
        return $jsonObj;
    }

    /**
     * 下单,充流量
     * @param $mobile
     * @param $dataStream 20M 100M 200M
     * @return mixed
     * @throws \Exception
     */
    function doRecharge($mobile,$dataStream){
        //根据运营商确定流量包
        $isp = $this->phoneISP($mobile);
        $postPackage = $this->packageConfig($isp,$dataStream);
        $params = [
            "appkey"        => $this->appKey,
            "appsecret"     => "",
            "token"         =>$this->token,
            "appver"        => 'Http',
            "apiver"        => '2.0',
            "fixtime"       =>"",
            "extno"         =>"",
            "mobile"        =>$mobile,
            //"postpackage"   =>$package,
            "des"         =>"0",
        ];
        $errMsg = [];
        $successMsg = [];
        foreach($postPackage as $package){
            $params['postpackage'] = $package;
            $rel = $this->apiRequest($this->serverUrl.'placeOrder',$this->getParamsWithSign($params),'POST');
            if($rel['code'] != '000'){
                $errMsg[] =['package'=>$package,'errCode'=>$rel['code']];
            }else{
                $successMsg[]= ['package'=>$package,'orderNO'=>$rel['data']['orderNO']];
            }
        }
        if(sizeof($errMsg)){
            return ['status'=>0,'errMsg'=>$errMsg,'success'=>$successMsg];
        }else{
            return ['status'=>1,'success'=>$successMsg];
        }
    }

    /**
     * 获取手机号对应的运营商 1.电信 2.移动 3.联通
     * @param $mobile
     * @return int
     * @throws \Exception
     */
    function phoneISP($mobile){
        //电信 133 153 1700 177 180 181 189
        $chinaNet = ['133', '153', '1700', '177', '180', '181', '189'];
        //移动 134 135 136 137 138 139 150 151 152 157 158 159 1705 178 182 183 184 187 188 147
        $cmcc =['134','135','136','137','138','139','150','151','152','157','158','159','1705','178','182','183','184','187','188','147'];
        //联通 130 131 132 155 156 1709 176 185 186 145
        $cucc = ['130','131','132','155','156','1709','176','185','186','145'];
        $mobileKey4 = substr($mobile,0,4);
        $mobileKey3 = substr($mobileKey4,0,3);
        if(in_array($mobileKey3,$cmcc) || in_array($mobileKey4,$cmcc)){
            return 2;//移动
        }elseif(in_array($mobileKey3,$chinaNet) || in_array($mobileKey4,$chinaNet)){
            return 1;//电信
        }elseif(in_array($mobileKey3,$cucc) || in_array($mobileKey4,$cucc)){
            return 3;//联通
        }else{
            throw new \Exception('用户手机号无法识别运营商');
        }
    }

    /**
     * 根据运营商和流量值返回流量包模板
     * @param $isp 1.电信 2.移动 3.联通
     * @param $package @支持 20 100 200
     * @return array
     * @throws \Exception
     */
    function packageConfig($isp,$package){
        $packageConfig = [
            '1' => [
                '20'=>['DX10','DX10'],
                '100'=>['DX100'],
                '200'=>['DX200'],
            ],
            '2' => [
                '20'=>['YD10','YD10'],
                '100'=>['YD30','YD70'],
                '200'=>['YD30','YD70','YD30','YD70'],
            ],
            '3' => [
                '20'=>['LT20'],
                '100'=>['LT100'],
                '200'=>['LT200'],
            ],
        ];
        if(isset($packageConfig[$isp][$package])){
            return $packageConfig[$isp][$package];
        } else{
            throw new \Exception($package.'M 流量包模板未定义');
        }
    }
}