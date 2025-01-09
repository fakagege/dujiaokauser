@extends('hyper.layouts.default')

@section('content')
<style>
    .equal-height {
        min-height: 160px; /* Adjust the height to your desired value */
    }


.stats-icon {
    width: 50px; /* 确保图标大小 */
    height: 50px;
    border-radius: 50%; /* 圆形 */
    background-color: #f0f0f0;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 10px; /* 增加图标与内容之间的间距 */
}

</style>
<div class="row mt-3">
    <!-- 用户名和ID -->
    <div class="col-sm">
        <div class="card stats-card equal-height">
            <div class="stats-icon"><i class="uil uil-user"></i></div>
            <div class="stats-detail">
                <span>用户名</span>
<div class="stats-member">
    <h6 class="h6 mt-0 stats-email">{{ Auth::user()->email }}</h6>
</div>


                <div class="malus-invite-tips">ID: {{ Auth::user()->id }}</div>
            </div>
                  <div class="malus-invite-tips">这是你的用户名</div>

        </div>
    </div>

    <!-- 账户余额和充值 -->
    <div class="col-sm">
        <div class="card stats-card equal-height">
            <div class="stats-icon stats-icon-time"><i class="uil uil-yen"></i></div>
            <div class="stats-detail">
                <span>余额</span>
                <div class="stats-member">
                    <h6 class="h6 mt-0">{{ Auth::user()->money }}</h6>
                </div>
            </div>
            <button class="btn btn-sm btn-primary btn-pill" data-toggle="modal" data-target="#rechargeModal">充值</button>
            <div class="malus-invite-tips">支付更方便</div>
        </div>
    </div>

    <!-- 返利订单数和邀请 -->
    <div class="col-sm">
        <div class="card stats-card equal-height">
            <div class="stats-icon stats-icon-user"><i class="uil uil-user-plus"></i></div>
            <div class="stats-detail">
                <span>返利</span>
                <div class="stats-member">
                    <h6 class="h6 mt-0">{{ $invite_count }} 单</h6>
                </div>
            </div>
            <a href="{{ url('/user/invite') }}" class="btn btn-sm btn-outline-orange btn-pill">邀请</a>
            <div class="malus-invite-tips">邀请有奖励哦</div>
        </div>
    </div>

    <!-- 修改密码 -->
    <div class="col-sm">
        <div class="card stats-card equal-height">
            <div class="stats-icon stats-icon-password"><i class="uil uil-key-skeleton"></i></div>
            <div class="stats-detail">
                <span>修改密码</span>
                <button class="btn btn-sm btn-warning btn-pill mt-2" data-toggle="modal" data-target="#changePasswordModal">点击修改</button>
                 <div class="malus-invite-tips">在这改密码哦</div>
            </div>
        </div>
    </div>
</div>

<!-- 订单列表 -->
<div class="card vip-center mt-3">
    <div class="card-header">
        <h3 class="card-title">订单列表</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive order-table">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="d-none d-sm-table-cell">订单号</th> <!-- 隐藏在移动设备上 -->
                        <th class="d-none d-sm-table-cell">订单名称</th> <!-- 隐藏在移动设备上 -->
                        <th>支付金额</th>
                        <th>状态</th> <!-- 始终显示状态 -->
                        <th class="d-none d-sm-table-cell">创建时间</th> <!-- 隐藏在移动设备上 -->
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                    <tr>
                        <td class="d-none d-sm-table-cell">{{ $order->order_sn }}</td> <!-- 隐藏在移动设备上 -->
                        <td class="d-none d-sm-table-cell">{{ $order->title }}</td> <!-- 隐藏在移动设备上 -->
                        <td class="text-primary">￥{{ $order->actual_price }}</td>
                        <td>
                            @switch($order->status)
                            @case(\App\Models\Order::STATUS_EXPIRED)
                            <label class="badge badge-secondary">已过期</label>
                            @break
                            @case(\App\Models\Order::STATUS_WAIT_PAY)
                            <label class="badge badge-primary">待支付</label>
                            <a class="badge badge-warning" href="/bill/{{ $order->order_sn }}">重新结算</a>
                            @break
                            @case(\App\Models\Order::STATUS_PENDING)
                            <label class="badge badge-warning">待处理</label>
                            @break
                            @case(\App\Models\Order::STATUS_PROCESSING)
                            <label class="badge badge-success">已处理</label>
                            @break
                            @case(\App\Models\Order::STATUS_COMPLETED)
                            <label class="badge badge-success">已完成</label>
                            @break
                            @case(\App\Models\Order::STATUS_FAILURE)
                            <label class="badge badge-danger">已失败</label>
                            @break
                            @case(\App\Models\Order::STATUS_FAILURE)
                            <label class="badge badge-dark">状态异常</label>
                            @break
                            @endswitch
                        </td>
                        <td class="d-none d-sm-table-cell">{{ $order->created_at }}</td> <!-- 隐藏在移动设备上 -->
                        <td><a class="btn btn-link" href="/search-order-by-sn?order_sn={{ $order->order_sn }}">查看订单</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $orders->links() }}
        </div>
    </div>
</div>



<!-- 充值模态框 -->
<div class="modal fade" id="rechargeModal" tabindex="-1" aria-labelledby="rechargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rechargeModalLabel">余额充值</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="buy-form" action="{{ url('/user/recharge-money') }}" method="post">
                    @csrf
                    <div class="form-group buy-group">
                        <div class="buy-title">充值金额</div>
                        <div class="choose-tag">
                            @if($recharge_promotion)
                            @foreach($recharge_promotion as $key => $item)
                            <div class="tag" data-key="{{ $key }}" data-amount="{{ $item['amount'] }}">
                                充￥{{ $item['amount'] }}<div class="discount-tag">送￥{{ $item['value'] }}</div>
                            </div>
                            @endforeach
                            @endif
                        </div>
                       <input type="number" name="amount" min="1" class="form-control" placeholder="请输入需要充值的金额" autocomplete="off">
                    </div>
                    <div class="form-group buy-group">
                        <input type="hidden" name="payway" value="{{ $payways[0]['id'] ?? 0 }}">
                        <div class="buy-title">支付方式:</div>
                        @foreach($payways as $key => $way)
                        <div class="pay-type @if($key == 0) active @endif" data-type="{{ $way['pay_check'] }}" data-id="{{ $way['id'] }}" data-name="{{ $way['pay_name'] }}"></div>
                        @endforeach
                    </div>
                    <div class="mt-4 text-center">
                        <button type="submit" class="btn btn-danger" id="submit">
                            <i class="mdi mdi-truck-fast mr-1"></i>
                            点击充值
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 修改密码模态框 -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changePasswordModalLabel">修改密码</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ url('/user/change-password') }}" method="post">
                    @csrf
                    <div class="form-group">
                        <label for="current-password">当前密码</label>
                        <input type="password" class="form-control" id="current-password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new-password">新密码</label>
                        <input type="password" class="form-control" id="new-password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm-new-password">确认新密码</label>
                        <input type="password" class="form-control" id="confirm-new-password" name="new_password_confirmation" required>
                    </div>
                    <button type="submit" class="btn btn-primary">提交更改</button>
                </form>
            </div>
        </div>
    </div>
</div>

@stop
@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if(session('success'))
        // 显示成功消息
        $.NotificationApp.send("Success", "{{ session('success') }}", "top-right", "rgba(0,0,0,0.2)", "success");
    @endif

    @if($errors->any())
        // 显示第一个错误消息
        var firstError = "{{ $errors->all()[0] }}"; // 仅示例，可能需要适当调整
        $.NotificationApp.send("Error", firstError, "top-right", "rgba(0,0,0,0.2)", "error");
    @endif
});
</script>


<script>
   $('.tag').each(function () {
        let t = $(this), key = t.data('key');
    }).click(function () {
        $('.tag').removeClass('active');
        $(this).toggleClass("active");
        $('input[name=amount]').val($(this).data('amount'));
    });
</script>
<script>
$(document).ready(function() {
    // 检查初始支付方式
    updateSubmitButtonState();

    // 支付方式点击事件
    $('.pay-type').click(function() {
        $('.pay-type').removeClass('active'); // 移除所有支付方式的active类
        $(this).addClass('active'); // 当前点击的支付方式添加active类
        $('input[name="payway"]').val($(this).data('id')); // 更新隐藏输入字段的值为当前支付方式的ID
        
        updateSubmitButtonState(); // 更新提交按钮的状态
    });

    // 更新提交按钮的状态
    function updateSubmitButtonState() {
        if ($('input[name="payway"]').val() == 0 || $('input[name="payway"]').val() == "") {
            $('#submit').prop('disabled', true).addClass('btn-disabled'); // 禁用充值按钮
        } else {
            $('#submit').prop('disabled', false).removeClass('btn-disabled'); // 启用充值按钮
        }
    }

    // 充值按钮点击事件
    $('#submit').click(function() {
        if ($("input[name='amount']").val() <= 0) {
            $.NotificationApp.send("警告！", "请输入正确的金额~", "top-center", "rgba(0,0,0,0.2)", "info");
            return false; // 阻止表单提交
        }
    });
});
</script>

@stop
