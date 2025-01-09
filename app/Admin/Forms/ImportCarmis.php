<?php

namespace App\Admin\Forms;

use App\Jobs\CreateCarmiPush;
use App\Models\Carmis;
use App\Models\Goods;
use Dcat\Admin\Widgets\Form;
use Illuminate\Support\Facades\Storage;

class ImportCarmis extends Form
{
     private $info_preg = "";
    /**
     * Handle the form request.
     *
     * @param array $input
     *
     * @return mixed
     */
    public function handle(array $input)
    {
        if (empty($input['carmis_list']) && empty($input['carmis_txt'])) {
            return $this->response()->error(admin_trans('carmis.rule_messages.carmis_list_and_carmis_txt_can_not_be_empty'));
        }
        $carmisContent = "";
        if (!empty($input['carmis_txt'])) {
            $carmisContent = Storage::disk('public')->get($input['carmis_txt']);
        }
        if (!empty($input['carmis_list'])) {
            $carmisContent = $input['carmis_list'];
        }
         $this->info_preg = $input['info_preg'];
        $carmisData = [];
        $tempList = explode(PHP_EOL, $carmisContent);
        foreach ($tempList as $val) {
            if (trim($val) != "") {
                $carmisData[] = [
                    'goods_id' => $input['goods_id'],
                    'carmi' => trim($val),
                    'info' => $this->formatInfo(trim($val)),
                    'status' => Carmis::STATUS_UNSOLD,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }
        }
        if ($input['remove_duplication'] == 1) {
            $carmisData = assoc_unique($carmisData, 'carmi');
        }
        Carmis::query()->insert($carmisData);
        // 发送通知
        CreateCarmiPush::dispatch($input['goods_id'], count($tempList));
        // 删除文件
        Storage::disk('public')->delete($input['carmis_txt']);
        return $this
				->response()
				->success(admin_trans('carmis.rule_messages.import_carmis_success'))
				->location('/carmis');
    }
  
  
   

     
/**
 * 匹配卡密信息
 *
 * @param string $val 卡密
 * @return string|null
 */
private function formatInfo($val){
    // 如果正则表达式为空，返回 NULL
    if (empty($this->info_preg)) return NULL;

    $info = "";
    
    // 使用正则表达式匹配卡密
    if (@preg_match($this->info_preg, $val, $info))
        return $info[0];  // 返回正则表达式匹配到的部分
    
    // 使用正则表达式作为分割符分割卡密信息
    $info = explode($this->info_preg, $val);
    
    // 调换输出，返回分割符之前的部分
    if (count($info) > 1)
        return $info[0];  // 返回分割符之前的部分

    return NULL;
}

    /**
     * Build a form here.
     */
    public function form()
    {
        $this->confirm(admin_trans('carmis.fields.are_you_import_sure'));
        $this->select('goods_id')->options(
            Goods::query()->where('type', Goods::AUTOMATIC_DELIVERY)->pluck('gd_name', 'id')
        )->required();
        $this->textarea('carmis_list')
            ->rows(20)
            ->help(admin_trans('carmis.helps.carmis_list'));
        $this->file('carmis_txt')
            ->disk('public')
            ->uniqueName()
            ->accept('txt')
            ->maxSize(5120)
            ->help(admin_trans('carmis.helps.carmis_list'));
              $this->text('info_preg')->help(admin_trans('卡密匹配规则，111111---xxxx卡密，分割符号是---显示的是1111，卡密不显示'));;
        $this->switch('remove_duplication');
    }

}
