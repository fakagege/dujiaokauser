@extends('kphyper.layouts.default')
@section('content')
<style>
@media (max-width: 767.98px){
    .page-title-box .page-title-right {
        width: 100%;
    }
    .page-title-right {
        margin-bottom: 17px;
    }
    .app-search {
        width: 100%;
    }
    .phone {
        display: none;
    }
}

.text-body:hover {
    color: blue !important; /* 在鼠标悬停时改变颜色 */
}

 .link-no-decor {
        color: inherit; /* 使链接颜色继承自父元素，避免默认蓝色 */
        text-decoration: none; /* 移除下划线 */
    }
    .link-no-decor:hover {
        color: #007bff; /* 鼠标悬停时变为蓝色 */
    }
.notice img {
    max-width: 288px;
    height: auto;


}
</style>
<style>
    .purchase-info-container {
    overflow: hidden;
    white-space: nowrap;
}

.purchase-info {
    display: inline-block;
    animation: slideLeft 20s linear infinite;
}
.purchase-info div {
    color: red;
    font-weight: bold;
    font-size: 18px;
}


@keyframes slideLeft {
    0% {
        transform: translateX(100%);
    }
    100% {
        transform: translateX(-100%);
    }
}

</style>

<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <div class="page-title-right">
                
            </div>
            <h4 class="page-title d-none d-md-block">{{ __('hyper.home_title') }}</h4>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <!-- 公告按钮 -->
                <button type="button" class="btn btn-outline-primary mb-3" id="notice-toggle">
                    <i class="uil-comment-alt-notes me-1"></i>
                    {{ __('hyper.notice_announcement') }}
                </button>
                <!-- 公告内容，初始状态为隐藏 -->
                <div class="notice" id="notice-content" style="display: none;">
                    {!! dujiaoka_config_get('notice') !!}
                </div>
            </div>
        </div>
    </div>
</div>
       @if(dujiaoka_config_get('is_open_xn') == \App\Models\BaseModel::STATUS_OPEN)  
<div class="purchase-info-container">
    <div class="purchase-info">
        @foreach ($purchaseInfos as $info)
            <div>{{ $info['email'] }} 在 {{ $info['time'] }} 购买了 {{ $info['quantity'] }} 件 {{ $info['product'] }}</div>
        @endforeach
    </div>
</div>
    @endif
 <div class="tab-content" id="myTabContent">
                    @foreach($data as $group)
                     @if(count($group['goods']) > 0)
                   <div class="row category">
                     <div class="col-md-12"> 
                    <h3>{{-- 分类名称1 --}}
                       <span class="badge badge-info">
                     {{$group['gp_name']}}</span>
             </h3> 
            </div>
        <div class="col-md-12"> 
        <div class="card pl-1 pr-1"> 
        <table class="table table-centered mb-0"> 
        <thead> 
        <tr>{{-- 名称 --}}
        <th width="47%">
            {{__('hyper.home_product_name')}}</th>
        {{-- 类型 --}}
        <th width="15%" class="phone">
            {{__('hyper.home_product_class')}}</th>
        {{-- 库存 --}}
        <th width="15%" class="phone">
            {{__('hyper.home_in_stock')}}
            </th>
        {{-- 价格 --}}
        <th width="10%">
            {{__('hyper.home_price')}}
            </th>
        {{-- 操作 --}}
        <th width="80px" class="text-center">
        {{__('hyper.home_place_an_order')}}</th> 
        </tr>
        </thead> 
        <tbody> @foreach($group['goods'] as $goods) <tr class="category"> 
        <td class="d-none">{{$group['gp_name']}}-{{$goods['gd_name']}}</td>
        <td class="table-user">
            {{-- 商品名称 --}}
           @if($goods['in_stock'] > 0)
                <a href="{{ url("/goods/{$goods['id']}.html") }}" class="text-body" >
                    <img src="{{ picture_ulr($goods['picture']) }}" class="mr-2 avatar-sm">
                    {{ $goods['gd_name'] }}
                </a>
            @else
                <span class="text-body">
                    <img src="{{ picture_ulr($goods['picture']) }}" class="mr-2 avatar-sm">
                    {{ $goods['gd_name'] }}
                </span>
            @endif
                                @if($goods['wholesale_price_cnf'])
                                    {{-- 折扣 --}}
                                    <span class="badge badge-outline-warning">{{ __('hyper.home_discount') }}</span>
                                @endif
                                     @if ($goods['open_rebate'] > 0 && $goods['rebate_rate'] > 0)
               {{-- 返利 --}}
             <span class="badge badge-outline-warning">返利</span>
                 @endif
                            </td>
                            <td class="phone">
                                @if($goods['type'] == \App\Models\Goods::AUTOMATIC_DELIVERY)
                                    {{-- 自动发货 --}}
                                    <span class="badge badge-outline-primary">{{ __('hyper.home_automatic_delivery') }}</span>
                                @else
                                    {{-- 人工发货 --}}
                                    <span class="badge badge-outline-danger">{{ __('hyper.home_charge') }}</span>
                                @endif
                            </td>
                            {{-- 库存 --}}
                            <td class="phone">
                                @if($goods['in_stock'] > 0)
                                    <div class="text-body">{{ $goods['in_stock'] }}</div>
                                @else
                                  <div class="text-body">{{ $goods['in_stock'] }}</div>

                                @endif
                            </td>
                            {{-- 价格 --}}
                                <td><b style="color: red;">{{ $goods['actual_price'] }} {{(dujiaoka_config_get('global_currency')) }}</b></td>
                           
                            <td class="text-center">
                              @if($goods['in_stock'] > 0)
                      {{-- 实际库存大于0，可以购买 --}}
                   <a class="btn btn-outline-primary" href="{{ url("/goods/{$goods['id']}.html") }}">{{ __('hyper.home_buy') }}</a>
               @else
                  {{-- 缺货 --}}
                    <a class="btn btn-outline-secondary disabled" href="javascript:void(0);">{{ __('hyper.home_out_of_stock') }}</a>
                         @endif

                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    
                </table>
               
            </div>
        </div>
    </div>
    @endif
    @endforeach
    </div>
    
@if(dujiaoka_config_get('is_open_wenzhang') == \App\Models\BaseModel::STATUS_OPEN)  
<div class="tab-content" id="myTabContent">
    <!-- All Products Tab Pane -->
        <div class="row category">
            <div class="col-md-12">
                <h3>                
                    <span class="btn badge-info" style="display: block; width: 100%;">文章教程</span>                
                </h3>            
            </div>
            <div class="col-md-12">
                <div class="card pl-1 pr-1">
                    <table class="table table-centered mb-0">
                        <thead>
                            <tr>
                                <th width="80%">文章标题</th>
                               <th width="20%">更新时间</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($articles->shuffle()->take(6) as $article)
                            <tr class="category">
                                <td class="table-user">
                                    <a href="article/{{ !empty($article['link']) ? $article['link'] : $article['id'] }}.html" class="text-body">
                                        <img src="{{ picture_ulr($article['picture']) }}" class="mr-2 avatar-sm">
                                        <span>{{ $article['title'] }}</span>
                                    </a>
                                </td>
                        <td class="text-center">
                            <a href="article/{{ !empty($article['link']) ? $article['link'] : $article['id'] }}.html" class="link-no-decor">
                                        {{ $article['updated_at'] }}
                                                 </a>
                                      </td>


                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
</div>
@endif
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var noticeButton = document.getElementById('notice-toggle');
        var noticeContent = document.getElementById('notice-content');

        // 检查用户是否已经访问过网站
        if (!localStorage.getItem('visited')) {
            // 如果用户是第一次访问，显示公告内容
            noticeContent.style.display = 'block';
            // 将用户访问的状态存入localStorage
            localStorage.setItem('visited', 'true');
        }

        // 点击按钮时切换公告内容显示/隐藏
        noticeButton.addEventListener('click', function() {
            if (noticeContent.style.display === 'none' || noticeContent.style.display === '') {
                noticeContent.style.display = 'block';  // 显示公告内容
            } else {
                noticeContent.style.display = 'none';  // 隐藏公告内容
            }
        });
    });
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const purchaseInfoContainer = document.querySelector('.purchase-info-container');
    const purchaseInfo = document.querySelector('.purchase-info');
    const purchaseInfoItems = document.querySelectorAll('.purchase-info div');
    let currentIndex = 0;
    let animationDuration = 0;

    function calculateAnimationDuration() {
        const containerWidth = purchaseInfoContainer.offsetWidth;
        const currentItemWidth = purchaseInfoItems[currentIndex].offsetWidth;
        const distanceToTravel = currentItemWidth + containerWidth;
        const pixelsPerSecond = distanceToTravel / 20; // 假设每秒移动的像素数为容器和当前项的宽度之和的20分之一
        animationDuration = distanceToTravel / pixelsPerSecond * 1000; // 将动画持续时间转换为毫秒
    }

    function showNextInfo() {
        // 计算当前购买信息的动画持续时间
        calculateAnimationDuration();

        // 隐藏所有购买信息
        purchaseInfoItems.forEach((item) => {
            item.style.display = 'none';
        });

        // 显示当前购买信息
        purchaseInfoItems[currentIndex].style.display = 'block';

        // 更新索引，循环显示购买信息
        currentIndex = (currentIndex + 1) % purchaseInfoItems.length;

        // 设置定时器，在动画完成后再滚动到下一条信息
        setTimeout(showNextInfo, animationDuration);
    }

    // 初始化
    showNextInfo();
});

</script>
<script>$(document).ready(function() {
    $("#search").on("input", function() {
        var searchText = $(this).val().toLowerCase();
        $(".category").hide().filter(function() {
            return $(this).text().toLowerCase().indexOf(searchText) > -1;
        }).show();
    });
});
$("#search").on("input", function() {
    console.log("Input event triggered.");
    // 现有的代码
});
</script>
<script>
    function sell_out_tip() {
        $.NotificationApp.send("{{ __('hyper.home_tip') }}","{{ __('hyper.home_sell_out_tip') }}","top-center","rgba(0,0,0,0.2)","info");
    }
</script>
@stop