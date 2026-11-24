<?php
/**
 * @author : zl
 * @email : 932460566@qq.com
 */
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Prints extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->common_model->checkpurview();
        $this->jxcsys = $this->session->userdata('jxcsys');
    }

    //获取订单打印data
    public function getOrderPrintData(){
        $this->common_model->checkpurview(193);
        $id   = intval($this->input->post('id',TRUE));
        $type = $this->input->post('type',TRUE);
        switch($type){
            case '1':
                $data = $this->data_model->get_order('a.isDelete=0 and a.id='.$id.' and a.billType="SALE"',1);
            break;
            case '2':
                $data = $this->data_model->get_invoice('a.id='.$id.' and a.billType="SALE"',1);
                break;
        }
        $count = count($data);
        if ($count>0) {
            $data['num']    = 8;
            $data['system'] = $this->common_model->get_option('system');
            $postData = unserialize($data['postData']);
            $tableHtml = '<table><thead><tr><th width="6%">序号</th><th width="30%">商品</th><th>单位</th><th width="6%">数量</th><th>销售单价</th><th>折扣</th><th>折扣额</th><th>销售金额</th><th>仓库</th></tr></thead><tbody>';
            $count = count($postData['entries']);
            foreach ($postData['entries'] as $arr=>$row) {
                $v[$arr]['i']               = $arr + 1;
                $v[$arr]['invId']           = intval($row['invId']);
                $v[$arr]['invNumber']       = $row['invNumber'];
                $v[$arr]['invSpec']         = $row['invSpec'];
                $v[$arr]['invName']         = $row['invName'];
                $v[$arr]['goods']           = $row['invNumber'].' '.$row['invName'].' '.$row['invSpec'];
                $v[$arr]['qty']             = (float)abs($row['qty']);
                $v[$arr]['price']           = $row['price'];
                $v[$arr]['mainUnit']        = $row['mainUnit'];
                $v[$arr]['amount']          = $row['amount'];
                $v[$arr]['deduction']       = $row['deduction'];
                $v[$arr]['discountRate']    = $row['discountRate'];
                $v[$arr]['locationName']    = $row['locationName'];
                $tableHtml                 .= '<tr><td>'.$v[$arr]['i'] .'</td><td>'.$v[$arr]['invName'].'</td><td>'.$v[$arr]['invSpec'].'</td><td>'.$v[$arr]['qty'].'</td><td>'.$v[$arr]['amount'].'</td><td>'.$v[$arr]['discountRate'].'</td><td>'.$v[$arr]['deduction'].'</td><td >'.$v[$arr]['price'].'</td><td>'.$v[$arr]['locationName'].'</td></tr>';
//                var_dump($v[$arr]['i'] == $count,$v[$arr]['i'],$count);
                if($count < 9 && $v[$arr]['i'] == $count){
                    $start = $v[$arr]['i'] + 1;
                    $end = 8;
                    $placeholder = '';
                    for($i=$start;$i<=$end;$i++){
                        $placeholder .= '<tr><td>'. $i .'</td><td></td><td></td><td></td><td></td><td></td><td></td><td ></td><td></td></tr>';
                    }
                    $tableHtml .= $placeholder;
                }
            }
            //合计部分
            $tableHtml .= '<tr><td colspan="3" style="text-align:right;">合计：</td><td>'.$data['totalQty'].'</td><td></td><td></td><td></td><td>'.$data['totalAmount'].'</td><td></td></tr>';
            //金额大写部分
            $tableHtml .= '<tr><td colspan="9" style="text-align:left;">合计（大写金额）：'.str_num2rmb(abs($data['totalAmount'])).'</td></tr>';
            $tableHtml .= '</tbody></table><style type="text/css" media="all">table{width:100%;margin: 0 auto;font-size:12px;color:#333;border-width:1px;border-color:#666;border-collapse:collapse}table th{border-width:1px;padding:8px;border-style:solid;border-color:#666;}table td{text-align:center;border-width:1px;padding:8px;border-style:solid;border-color:#666;background-color:#fff}</style>';
            $data['countpage']  = ceil(count($postData['entries'])/($data['num'] > 0 ? $data['num'] : 53)); ;
            $data['list']       = isset($v) ? $v : array();
            if($type == 1){
                $date = $data['deliveryDate'];
            }else{
                $date = $data['billDate'];
            }
            // var_dump($data);exit;
            str_alert('success',
            '数据获取成功！',
            [
                'customer'=>$data['contactName'],
                'saller'=>$data['salesName'],
                'date'=>$date,'orderno'=>$data['billNo'],
                'remark'=>$data['description'],
                'user'=>$data['userName'],
                'table_html'=>$tableHtml,
                'totalAmount'=>$data['totalAmount'],
                'phone'=>$data['system']['phone'],
                'title'=>$type == 1 ? '客户订单' : '销售订单',
                'linkman'=>$data['udf01'],
                'address'=>$data['udf03'],
                'telephone'=>$data['udf02'],
                'disAmount'=>str_money(abs($data['disAmount']),2),
                'amount'=>str_money(abs($data['amount']),2),
                'arrears'=>str_money(abs($data['arrears']),2),
            ]
            );
        }
        str_alert(-1,'单据不存在、或者已删除');
    }
}