流米流量充值sdk for laravel
# [laravel-liumi](https://github.com/aishan/laravel-liumi) for Laravel 5.*



## Installation

- Run `composer require aishan/laravel-liumi`


- Add `Aishan\LaravelLiumi\LiumiServiceProvider::class,` to  **providers** in *config/app.php*
- Add `'Liumi' =>\Aishan\LaravelLiumi\Facades\Liumi::class,` to **aliases** in *config/app.php*
- Run `php artisan vendor:publish`



## Usage

Just like this :

````
Liumi::doRecharge('mobile_number',20);//20 是指20M流量包   目前只有 20 100 200 三种流量包配置，设置后，会自动根据手机号识别运营商进行充值
````
充值成功会返回订单号：
````
array:2 [▼
  "status" => 1
  "success" => array:2 [▼
    0 => array:2 [▼
      "package" => "DX10"
      "orderNO" => "586201608202319091085"
    ]
    1 => array:2 [▼
      "package" => "DX10"
      "orderNO" => "586201608202319101086"
    ]
  ]
]
````
*说明 ： 运营商提供的流量包套餐类型有限，我们的流量包需求可能是多种流量套餐组成，比如20M电信流量会由两个“DX10”流量包构成，生成两个订单
## Config

在config/liumi.php中配置流米提供的授权信息：
````
return [

    'serverUrl'=>'http://yfbapi.liumi.com/server/',
    'appKey'=>'',
    'appSecret'=>'',

];
````

