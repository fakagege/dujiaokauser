<?php

namespace App\Http\Controllers\Home;

use App\Exceptions\RuleValidationException;
use App\Http\Controllers\BaseController;
use App\Models\Pay;
use App\Models\Articles;
use Germey\Geetest\Geetest;
use Illuminate\Database\DatabaseServiceProvider;
use Illuminate\Database\QueryException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class HomeController extends BaseController
{

    /**
     * 商品服务层.
     * @var \App\Service\PayService
     */
    private $goodsService;

    /**
     * 支付服务层
     * @var \App\Service\PayService
     */
    private $payService;

    public function __construct()
    {
        $this->goodsService = app('Service\GoodsService');
        $this->payService = app('Service\PayService');
}

    /**
     * 首页.
     *
     * @param Request $request
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */

public function index(Request $request)
{
    // 获取当前登录用户
    $user = Auth::user();

    // 获取商品数据，确保返回的数据是数组形式
    $goodsList = $this->goodsService->withGroup();

    // 遍历商品列表，根据用户等级修改价格
    foreach ($goodsList as &$group) {
        foreach ($group['goods'] as &$goods) {
            // 默认使用商品的常规价格
            $goods['actual_price'] = $goods['actual_price'];

            // 如果用户已登录，根据其等级选择对应的价格
            if ($user) {
                switch ($user->grade) {
                    case 1:
                        $goods['actual_price'] = $goods['grade_1'] > 0 ? $goods['grade_1'] : $goods['actual_price'];
                        break;
                    case 2:
                        $goods['actual_price'] = $goods['grade_2'] > 0 ? $goods['grade_2'] : $goods['actual_price'];
                        break;
                    case 3:
                        $goods['actual_price'] = $goods['grade_3'] > 0 ? $goods['grade_3'] : $goods['actual_price'];
                        break;
                }
            }
        }
    }

    // 获取文章数据
    $articles = Articles::select('id', 'link', 'title', 'picture', 'content', 'updated_at')
        ->take(8)
        ->orderBy('updated_at', 'desc')
        ->get();

    // 生成虚拟购买信息
    $purchaseInfos = $this->generatePurchaseInfos();

    // 将商品数据、文章数据和虚拟购买信息一起传递给视图
    return $this->render('static_pages/home', [
        'data' => $goodsList,
        'articles' => $articles,
        'purchaseInfos' => $purchaseInfos
    ], __('dujiaoka.page-title.home'));
}

     
     protected function generatePurchaseInfos()
{
    // 写死的邮箱后缀数组
    $emailSuffixes = ['@163.com', '@qq.com', '@gmail.com', '@189.cn', '@188.com'];
    // 从配置读取的其他数据保持不变
    $products = explode('---', dujiaoka_config_get('xn_products'));
    $quantityRange = explode('~', dujiaoka_config_get('xn_quantities'));
    // 时间范围，按秒计算
    $minSeconds = 60; // 最小分钟数转为秒
    $maxSeconds = 24 * 60 * 60; // 最大小时数转为秒

    $purchaseInfos = [];

  for ($i = 0; $i < 1; $i++) {
    $emailSuffix = $emailSuffixes[array_rand($emailSuffixes)]; // 随机选择一个邮箱后缀
    $emailPrefix = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 10); // 生成随机前缀
    $email = $emailPrefix . $emailSuffix; // 组合前缀和后缀生成邮箱

    // 将邮箱前缀的部分字符替换为星号
    // 假设我们想要保留前缀的前3个字符和后缀的前3个字符，其余替换为星号
    $visiblePrefixLength = 5; // 要保留的前缀字符数
    $hiddenPart = str_repeat('*', strlen($emailPrefix) - $visiblePrefixLength); // 生成与隐藏部分长度相同的星号字符串
    $visiblePrefix = substr($emailPrefix, 0, $visiblePrefixLength); // 获取可见的前缀部分
    $emailWithHiddenPart = $visiblePrefix . $hiddenPart . $emailSuffix; // 组合新的邮箱地址

    $product = $products[array_rand($products)];

    $minQuantity = isset($quantityRange[0]) ? intval($quantityRange[0]) : 1;
    $maxQuantity = isset($quantityRange[1]) ? intval($quantityRange[1]) : 10;
    $quantity = rand($minQuantity, $maxQuantity);

    // 生成随机的秒数，表示购买时间距现在的时长
    $secondsAgo = rand($minSeconds, $maxSeconds);
    // 格式化时间显示
    $time = $this->formatTime($secondsAgo);

    $purchaseInfos[] = [
        'email' => $emailWithHiddenPart, // 使用含有隐藏部分的邮箱地址
        'product' => $product,
        'quantity' => $quantity,
        'time' => $time,
    ];
}


    return $purchaseInfos;
}

protected function formatTime($seconds)
{
    if ($seconds < 60) {
        // 少于60秒，直接显示秒
        return "$seconds 秒之前";
    } elseif ($seconds < 3600) {
        // 60秒到3600秒（1小时）之间，显示分钟
        $mins = floor($seconds / 60);
        return "$mins 分钟之前";
    } else {
        // 超过3600秒，显示小时
        $hours = floor($seconds / 3600);
        return "$hours 小时之前";
    }
}



  
    /**
     * 商品详情
     *
     * @param int $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */

public function buy(int $id)
{
    try {
        // 获取商品详情
        $goods = $this->goodsService->detail($id);
        $this->goodsService->validatorGoodsStatus($goods);

        // 获取当前登录用户
        $user = Auth::user();

          // 检查配置是否要求登录才能购买，如果配置项不存在，默认值为 STATUS_CLOSED（不要求登录）
        $isBuyLoginRequired = dujiaoka_config_get('is_buy_login', \App\Models\BaseModel::STATUS_CLOSE) == \App\Models\BaseModel::STATUS_OPEN;

        // 如果要求登录并且用户未登录，重定向到登录页面
        if ($isBuyLoginRequired && !$user) {
            session()->flash('message', __('您需要登录才能下单'));
            return redirect()->route('login');
        }


        // 默认使用商品的常规价格
        $goods->actual_price = $goods->actual_price;

        // 如果用户已登录，根据其等级选择对应的价格
        if ($user) {
            switch ($user->grade) {
                case 1:
                    $goods->actual_price = $goods->grade_1 > 0 ? $goods->grade_1 : $goods->actual_price;
                    break;
                case 2:
                    $goods->actual_price = $goods->grade_2 > 0 ? $goods->grade_2 : $goods->actual_price;
                    break;
                case 3:
                    $goods->actual_price = $goods->grade_3 > 0 ? $goods->grade_3 : $goods->actual_price;
                    break;
            }
        }

        // 有没有优惠码可以展示
        if (count($goods->coupon)) {
            $goods->open_coupon = 1;
        }

        // 格式化商品信息
        $formatGoods = $this->goodsService->format($goods);

        // 加载支付方式
        $client = Pay::PAY_CLIENT_PC;
        if (app('Jenssegers\Agent')->isMobile()) {
            $client = Pay::PAY_CLIENT_MOBILE;
        }
        $formatGoods->payways = $this->payService->pays($client);

        // 处理支付方式限制
        if ($formatGoods->payment_limit) {
            $formatGoods->payment_limit = json_decode($formatGoods->payment_limit, true);
            if (count($formatGoods->payment_limit)) {
                $formatGoods->payways = array_filter($formatGoods->payways, function($way) use ($formatGoods) {
                    return in_array($way['id'], $formatGoods->payment_limit);
                });
            }
        }

        // 处理预选的卡密（如适用）
        if ($goods->preselection >= 0) {
            $selectableCarmis = $this->goodsService->getSelectableCarmis($id);

            if (!empty($selectableCarmis)) {
                $formatGoods->selectable = $selectableCarmis;
            }
        }

        // 获取文章数据
        $articles = Articles::select('id', 'link', 'title', 'picture', 'content', 'updated_at')
            ->take(8)
            ->orderBy('updated_at', 'desc')
            ->get();

        // 将文章数据合并到 $formatGoods 中
        $formatGoods->articles = $articles;

        // 将商品数据和文章数据一起传递给视图
        return $this->render('static_pages/buy', $formatGoods, $formatGoods->gd_name);
    } catch (RuleValidationException $ruleValidationException) {
        return $this->err($ruleValidationException->getMessage());
    }


 }



    /**
     * 极验行为验证
     *
     * @param Request $request
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function geetest(Request $request)
    {
        $data = [
            'user_id' => @Auth::user()?@Auth::user()->id:'UnLoginUser',
            'client_type' => 'web',
            'ip_address' => \Illuminate\Support\Facades\Request::ip()
        ];
        $status = Geetest::preProcess($data);
        session()->put('gtserver', $status);
        session()->put('user_id', $data['user_id']);
        return Geetest::getResponseStr();
    }

    /**
     * 安装页面
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function install(Request $request)
    {
        return view('common/install');
    }

    /**
     * 执行安装
     *
     * @param Request $request
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function doInstall(Request $request)
    {
        try {
            $dbConfig = config('database');
            $mysqlDB = [
                'host' => $request->input('db_host'),
                'port' => $request->input('db_port'),
                'database' => $request->input('db_database'),
                'username' => $request->input('db_username'),
                'password' => $request->input('db_password'),
            ];
            $dbConfig['connections']['mysql'] = array_merge($dbConfig['connections']['mysql'], $mysqlDB);
            // Redis
            $redisDB = [
                'host' => $request->input('redis_host'),
                'password' => $request->input('redis_password', 'null'),
                'port' => $request->input('redis_port'),
            ];
            $dbConfig['redis']['default'] = array_merge($dbConfig['redis']['default'], $redisDB);
            config(['database' => $dbConfig]);
            DB::purge();
            // db测试
            DB::connection()->select('select 1 limit 1');
            // redis测试
            Redis::set('dujiaoka_com', 'ok');
            Redis::get('dujiaoka_com');
            // 获得文件模板
            $envExamplePath = base_path() . DIRECTORY_SEPARATOR . '.env.example';
            $envPath =  base_path() . DIRECTORY_SEPARATOR . '.env';
            $installLock = base_path() . DIRECTORY_SEPARATOR . 'install.lock';
            $installSql = database_path() . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'install.sql';
            $envTemp = file_get_contents($envExamplePath);
            $postData = $request->all();
            // 临时写入key
            $postData['app_key'] = 'base64:' . base64_encode(
                    Encrypter::generateKey(config('app.cipher'))
                );
            foreach ($postData as $key => $item) {
                $envTemp = str_replace('{' . $key . '}', $item, $envTemp);
            }
            // 写入配置
            file_put_contents($envPath, $envTemp);
            // 导入sql
            DB::unprepared(file_get_contents($installSql));
            // 写入安装锁
            file_put_contents($installLock, 'install ok');
            return 'success';
        } catch (\RedisException $exception) {
            return 'Redis配置错误 :' . $exception->getMessage();
        } catch (QueryException $exception) {
            return '数据库配置错误 :' . $exception->getMessage();
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }


}
