<?php $this->load->view('header');?>


<script type="text/javascript">
    // var html_table = '';
    // var customer = '';
    // var title = '';
    // var saller = '';
    // var date = '';
    // var orderno = '';
    // var remark = '';
    // var user = '';
    // var html_table = '';
    // var phone = '';
    // var type = frameElement.api.data.pdfUrl.indexOf('invSa') !== -1 ? 2 : 1;
    // var linkman = '';
    // var address = '';
    // var telephone = '';
    // var disAmount = '';
    // var amount = '';
    // var arrears = '';
    // //获取订单信息
    // $.ajax({
    //     url:'/index.php/scm/prints/getOrderPrintData',
    //     method:'POST',
    //     data:{id:frameElement.api.data.taodaData.id,type:type},
    //     success:function(res){
    //         var r = JSON.parse(res);
    //         if(r.status == 'success'){
    //             html_table = r.data.table_html;
    //             customer = r.data.customer;
    //             title = r.data.title;
    //             saller = r.data.saller;
    //             date = r.data.date;
    //             orderno = r.data.orderno;
    //             remark = r.data.remark;
    //             user = r.data.user;
    //             phone = r.data.phone;
    //             user = r.data.user;
    //             linkman = r.data.linkman;
    //             address = r.data.address;
    //             telephone = r.data.telephone;
    //             disAmount = r.data.disAmount;
    //             amount = r.data.amount;
    //             arrears = r.data.arrears;
    //         }
    //     } ,
    //     error:function(){

    //     }
    // });
    var DOMAIN = document.domain;
    var WDURL = "";
    var SCHEME= "<?php echo sys_skin()?>";
    try{
        document.domain = '<?php echo base_url()?>';
    }catch(e){
    }
    //ctrl+F5 增加版本号来清空iframe的缓存的
    $(document).keydown(function(event) {
        /* Act on the event */
        if(event.keyCode === 116 && event.ctrlKey){
            var defaultPage = Public.getDefaultPage();
            var href = defaultPage.location.href.split('?')[0] + '?';
            var params = Public.urlParam();
            params['version'] = Date.parse((new Date()));
            for(i in params){
                if(i && typeof i != 'function'){
                    href += i + '=' + params[i] + '&';
                }
            }
            defaultPage.location.href = href;
            event.preventDefault();
        }
    });
</script>

<style>
.print-settings-wrap{padding: 25px 25px 10px;}
.print-settings-wrap a{text-decoration: underline;}
.print-settings-wrap .num-input{width: 50px;text-align: right;}
.print-settings-wrap .row-btns{margin-top: 30px;}
.print-settings-wrap .taoda-margin{margin-bottom: 5px;}
.print-settings-wrap .settings-ctn{margin-top: 25px;font-size: 14px;}
.print-settings-wrap .settings-ctn .print-tips{color: #dd4e4e;}
.print-settings-wrap .settings-ctn .print-tips a{color: #2383c0;}
.print-settings-wrap .mod-form-rows{margin-top: 20px;}
.taoda-settings .mod-form-rows .label-wrap{width: 110px;}
.taoda-settings .mod-form-rows a{color: #999;margin-left: 10px;}
.taoda-settings .mod-form-rows a:hover{color: #2383c0;}
#printTemp{margin-right: 10px;}
#setDefaultTemp{white-space: nowrap;margin-left: 0}
.template-list .list-item{font-size: 12px;}
</style>

</head>
<body class="ui-dialog-body">
<div class="print-settings-wrap">
  <ul class="ui-tab" id="printSelect">
      <li data-type="pdf" class="cur">PDF打印</li>
<!--      <li data-type="taoda" id="taodaoBtn">专业套打</li>-->
  </ul>
  <div class="settings-ctn" id="printSettings">
      <div class="item">
        <p class="print-tips">为了保证您的正常打印，请先下载安装<a href="http://dl.pconline.com.cn/html_2/1/81/id=1322&pn=0&linkPage=1.html" target="_blank">Adobe PDF阅读器</a></p>
        <ul class="mod-form-rows" id="pdfSettings">
          <li class="row-item">
            <div class="label-wrap">
              <label>打印纸型:</label>
            </div>
            <div class="ctn-wrap">
              <span class="radio-wrap"><input type="radio" name="paperType"  value="" checked="checked"/><labe>A4</label></span>
            </div>
          </li>
          <!--<li class="row-item">
            <div class="label-wrap">
              <label for="pdfStatX">左边距:</label>
            </div>
            <div class="ctn-wrap">
              <input type="text" id="pdfStatX" name="pdfStatX" value="50" class="ui-input margin-input  num-input"/>毫米
            </div>
          </li>-->
          <li class="row-item">
              <div class="label-wrap">
                <label for="pdfEntrysPerNote">每张行数:</label>
              </div>
              <div class="ctn-wrap">
                <input type="text" value="20" class="ui-input num-input" id="pdfEntrysPerNote" name="pdfEntrysPerNote"/>
              </div>
              <div class="tips">
                <ul>
                  <li> 参考设置：</li>
                  <li>A4纸（241X280）：18~20行</li>
                  <li>二等分纸（241X140）：9~11行</li>
                  <li>三等分纸（241X93）：6~8行</li>
                </ul>
              </div>
            </li>
        </ul>
      </div>

      <div class="item taoda-settings" id="taodaSettings" style="display:none;">
          <p class="print-tips" id="taodaTips">为了保证您的正常打印，请先下载安装<a href="/statics/print/CLodop_Setup_for_Win32NT.zip" target="_blank">套打工具</a></p>
          <ul class="mod-form-rows">
              <li class="row-item" style="font-size:15px;">
                  1.在线打印及设计需安装扩展程序，您可点击下方按钮，然后按照提示安装即可。<br>
                  2.系统在初始化时已经带有打印样式，如不满足您的实际需求，请设计报表后保存即可。<br>
                  3.默认纸张可在系统设置中选择，表格数据内容会自动分页，除表格内容外的元素均可修改。<br>
                  4.如您在设计单据的过程中误操作导致表格样式错乱，您可点击下方恢复默认按钮即可恢复。<br>
                  5.请使用谷歌内核浏览器或IE11以上版本，360浏览器、腾讯浏览器等请开启急速模式。<br>
              </li>
              <!--            <li class="row-item">-->
              <!--                <input type="button" value="打印报表" class="ui_state_highlight" onclick="prints()">-->
              <!--                <input type="button" value="设计模板" class="ui_state_highlight" onclick="edit()">-->
              <!--                <input type="button" value="恢复默认" class="ui_state_highlight" onclick="">-->
              <!--            </li>-->
          </ul>
      </div>
  </div>
</div>
<form method="post" id="downloadForm" style="display:none;"></form>
<script>
    var print_text = 'TE9ET1AuUFJJTlRfSU5JVEEoLTEsMCwiMjEwbW0iLCIxMzkuMDFtbSIsIumUgOi0p+WNlSAtIOaJk+WNsCIpOw0KTE9ET1AuU0VUX1BSSU5UX1BBR0VTSVpFKDEsMjEwMCwxMzkwLCIiKTsNCkxPRE9QLlNFVF9QUklOVF9NT0RFKCJQUk9HUkFNX0NPTlRFTlRfQllWQVIiLHRydWUpOw0KTE9ET1AuU0VUX1BSSU5UX01PREUoIlBSSU5UX05PQ09MTEFURSIsMSk7DQpMT0RPUC5BRERfUFJJTlRfVEVYVCgiMC43ODElIiwiMC43MDUlIiwiOTcuOTYlIiwiNi4zMjQlIix0aXRsZSk7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkZvbnRTaXplIiwxNCk7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkFsaWdubWVudCIsMik7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkJvbGQiLDEpOw0KTE9ET1AuU0VUX1BSSU5UX1NUWUxFQSgwLCJJdGVtVHlwZSIsMSk7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkNvbnRlbnRWTmFtZSIsInRpdGxlIik7DQpMT0RPUC5BRERfUFJJTlRfVEVYVCgiMTIuMzYyJSIsIjEuNjEyJSIsIjcuOTg1JSIsIjMuOTI0JSIsIuiBlOezu+S6uu+8miIpOw0KTE9ET1AuU0VUX1BSSU5UX1NUWUxFQSgwLCJGb250U2l6ZSIsMTApOw0KTE9ET1AuU0VUX1BSSU5UX1NUWUxFQSgwLCJJdGVtVHlwZSIsMSk7DQpMT0RPUC5BRERfUFJJTlRfVEVYVCgiMTIuMzYyJSIsIjcuOTA5JSIsIjE2LjI5NyUiLCIzLjkyNCUiLGN1c3RvbWVyKTsNCkxPRE9QLlNFVF9QUklOVF9TVFlMRUEoMCwiRm9udFNpemUiLDEwKTsNCkxPRE9QLlNFVF9QUklOVF9TVFlMRUEoMCwiSXRlbVR5cGUiLDEpOw0KTE9ET1AuU0VUX1BSSU5UX1NUWUxFQSgwLCJDb250ZW50Vk5hbWUiLCJjdXN0b21lciIpOw0KTE9ET1AuQUREX1BSSU5UX1RFWFQoIjguMTcxJSIsIjUxLjIzNCUiLCIxMC4wNzYlIiwiMy45MjQlIiwi5Y2V5o2u5pel5pyf77yaIik7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkZvbnRTaXplIiwxMCk7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkl0ZW1UeXBlIiwxKTsNCkxPRE9QLkFERF9QUklOVF9URVhUKCI4LjE3MSUiLCI1OS4yOTUlIiwiMTAuNTA0JSIsIjMuOTI0JSIsZGF0ZSk7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkZvbnRTaXplIiwxMCk7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkl0ZW1UeXBlIiwxKTsNCkxPRE9QLlNFVF9QUklOVF9TVFlMRUEoMCwiQ29udGVudFZOYW1lIiwiZGF0ZSIpOw0KTE9ET1AuQUREX1BSSU5UX1RFWFQoIjcuOTgxJSIsIjcxLjI1OSUiLCIxMC4wNzYlIiwiMy45MjQlIiwi5Y2V5o2u57yW5Y+377yaIik7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkZvbnRTaXplIiwxMCk7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkl0ZW1UeXBlIiwxKTsNCkxPRE9QLkFERF9QUklOVF9URVhUKCI3Ljk4MSUiLCI3OS4xOTQlIiwiMTguMDYlIiwiMy45MjQlIixvcmRlcm5vKTsNCkxPRE9QLlNFVF9QUklOVF9TVFlMRUEoMCwiRm9udFNpemUiLDEwKTsNCkxPRE9QLlNFVF9QUklOVF9TVFlMRUEoMCwiSXRlbVR5cGUiLDEpOw0KTE9ET1AuU0VUX1BSSU5UX1NUWUxFQSgwLCJDb250ZW50Vk5hbWUiLCJvcmRlcm5vIik7DQpMT0RPUC5BRERfUFJJTlRfVEFCTEUoIjE2LjI0OCUiLCIxLjEyMSUiLCI5Ny45OTclIiwiNjYuNjg2JSIsaHRtbF90YWJsZSk7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIlZvcmllbnQiLDMpOw0KTE9ET1AuU0VUX1BSSU5UX1NUWUxFQSgwLCJDb250ZW50Vk5hbWUiLCJodG1sX3RhYmxlIik7DQpMT0RPUC5BRERfUFJJTlRfVEVYVCgiODcuMTQzJSIsIjEuNzM4JSIsIjguMjM3JSIsIjMuOTI0JSIsIuaKmOaJo+mine+8miIpOw0KTE9ET1AuU0VUX1BSSU5UX1NUWUxFQSgwLCJGb250U2l6ZSIsMTApOw0KTE9ET1AuU0VUX1BSSU5UX1NUWUxFQSgwLCJJdGVtVHlwZSIsMSk7DQpMT0RPUC5BRERfUFJJTlRfVEVYVCgiODcuMTQzJSIsIjguNTM5JSIsIjE3LjAxNSUiLCIzLjkyNCUiLHJlbWFyayk7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkZvbnRTaXplIiwxMCk7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkl0ZW1UeXBlIiwxKTsNCkxPRE9QLlNFVF9QUklOVF9TVFlMRUEoMCwiQ29udGVudFZOYW1lIiwicmVtYXJrIik7DQpMT0RPUC5BRERfUFJJTlRfVEVYVCgiOTEuOTA1JSIsIjI3LjE3OSUiLCIxMC4xMjYlIiwiMy45MjQlIiwi6IGU57O755S16K+dOiIpOw0KTE9ET1AuU0VUX1BSSU5UX1NUWUxFQSgwLCJGb250U2l6ZSIsMTApOw0KTE9ET1AuU0VUX1BSSU5UX1NUWUxFQSgwLCJJdGVtVHlwZSIsMSk7DQpMT0RPUC5BRERfUFJJTlRfVEVYVCgiOTEuNzE0JSIsIjM0Ljk4NyUiLCIyOC4zNSUiLCIzLjkyNCUiLHBob25lKTsNCkxPRE9QLlNFVF9QUklOVF9TVFlMRUEoMCwiRm9udFNpemUiLDEwKTsNCkxPRE9QLlNFVF9QUklOVF9TVFlMRUEoMCwiSXRlbVR5cGUiLDEpOw0KTE9ET1AuU0VUX1BSSU5UX1NUWUxFQSgwLCJDb250ZW50Vk5hbWUiLCJwaG9uZSIpOw0KTE9ET1AuQUREX1BSSU5UX1RFWFQoIjk1LjY3NiUiLCIxLjczOCUiLCIxMi4wMTUlIiwiMy45MjQlIiwi5Yi25Y2V5Lq677yaIik7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkZvbnRTaXplIiwxMCk7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkl0ZW1UeXBlIiwxKTsNCkxPRE9QLkFERF9QUklOVF9URVhUKCI5NS41NDMlIiwiOC4zMTIlIiwiMTcuMDE1JSIsIjMuOTI0JSIsdXNlcik7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkZvbnRTaXplIiwxMCk7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkl0ZW1UeXBlIiwxKTsNCkxPRE9QLlNFVF9QUklOVF9TVFlMRUEoMCwiQ29udGVudFZOYW1lIiwidXNlciIpOw0KTE9ET1AuQUREX1BSSU5UX1RFWFQoIjk1LjczMyUiLCIyNy40NjklIiwiMTEuMzM1JSIsIjMuOTI0JSIsIuWPkei0p+S6uuetvuWtl++8miIpOw0KTE9ET1AuU0VUX1BSSU5UX1NUWUxFQSgwLCJGb250U2l6ZSIsMTApOw0KTE9ET1AuU0VUX1BSSU5UX1NUWUxFQSgwLCJJdGVtVHlwZSIsMSk7DQpMT0RPUC5BRERfUFJJTlRfVEVYVCgiOTUuNTQzJSIsIjYxLjcxMyUiLCIxMC44MzElIiwiMy45MjQlIiwi5a6i5oi3562+5a2X77yaIik7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkZvbnRTaXplIiwxMCk7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkl0ZW1UeXBlIiwxKTsNCkxPRE9QLkFERF9QUklOVF9URVhUKCIxMi41NzElIiwiMjUuMzE1JSIsIjguMTg2JSIsIjMuODElIiwi5Zyw5Z2A77yaIik7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkl0ZW1UeXBlIiwxKTsNCkxPRE9QLkFERF9QUklOVF9URVhUKDY2LDIzNCwzMTgsMjAsc2FsbGVyKTsNCkxPRE9QLlNFVF9QUklOVF9TVFlMRUEoMCwiSXRlbVR5cGUiLDEpOw0KTE9ET1AuU0VUX1BSSU5UX1NUWUxFQSgwLCJDb250ZW50Vk5hbWUiLCJzYWxsZXIiKTsNCkxPRE9QLkFERF9QUklOVF9URVhUKCI5Ni4xOSUiLCIzNy40MDYlIiwiMTcuMDAzJSIsIjMuODElIiwiX19fX19fX19fX19fX19fX19fX18iKTsNCkxPRE9QLkFERF9QUklOVF9URVhUKCI5NS44MSUiLCI2OS43NzMlIiwiMTUuNzQzJSIsIjMuODElIiwiX19fX19fX19fX19fX19fX19fXyIpOw0KTE9ET1AuQUREX1BSSU5UX1RFWFQoIjcuNiUiLCI2LjI3MiUiLCIxOC4wNiUiLCIzLjkyNCUiLCLlrqLmiLcxIik7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkZvbnRTaXplIiwxMCk7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkl0ZW1UeXBlIiwxKTsNCkxPRE9QLkFERF9QUklOVF9URVhUKDQwLDEzLDQ4LDIxLCLlrqLmiLfvvJoiKTsNCkxPRE9QLlNFVF9QUklOVF9TVFlMRUEoMCwiRm9udFNpemUiLDEwKTsNCkxPRE9QLlNFVF9QUklOVF9TVFlMRUEoMCwiSXRlbVR5cGUiLDEpOw0KTE9ET1AuQUREX1BSSU5UX1RFWFQoIjcuODElIiwiMzIuOTk3JSIsIjE2LjM3MyUiLCIzLjgxJSIsIua1i+ivlSIpOw0KTE9ET1AuU0VUX1BSSU5UX1NUWUxFQSgwLCJJdGVtVHlwZSIsMSk7DQpMT0RPUC5BRERfUFJJTlRfVEVYVCgiNy44MSUiLCIyNS40NDElIiwiOS40NDYlIiwiMy44MSUiLCLplIDllK7kurrlkZjvvJoiKTsNCkxPRE9QLlNFVF9QUklOVF9TVFlMRUEoMCwiSXRlbVR5cGUiLDEpOw0KTE9ET1AuQUREX1BSSU5UX1RFWFQoIjEyLjk1MiUiLCI3MS40MTElIiwiMTIuOTcyJSIsIjMuODElIiwi55S16K+d77yaIik7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkl0ZW1UeXBlIiwxKTsNCkxPRE9QLlNFVF9QUklOVF9TVFlMRUEoMCwiSG9yaWVudCIsMyk7DQpMT0RPUC5BRERfUFJJTlRfVEVYVCgiMTIuOTUyJSIsIjc2LjA3MSUiLCIyMC4xNTElIiwiMy44MSUiLCIxNTk3NTMxMjM0Iik7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkl0ZW1UeXBlIiwxKTsNCkxPRE9QLkFERF9QUklOVF9URVhUKCI4Ny4zMzMlIiwiMzcuNjMyJSIsIjExLjcyNSUiLCIzLjkyNCUiLCIiKTsNCkxPRE9QLlNFVF9QUklOVF9TVFlMRUEoMCwiRm9udFNpemUiLDEwKTsNCkxPRE9QLlNFVF9QUklOVF9TVFlMRUEoMCwiSXRlbVR5cGUiLDEpOw0KTE9ET1AuQUREX1BSSU5UX1RFWFQoIjg3LjMzMyUiLCIyNy4wNTMlIiwiMTEuNTExJSIsIjMuOTI0JSIsIuaKmOaJo+WQjumHkemine+8miIpOw0KTE9ET1AuU0VUX1BSSU5UX1NUWUxFQSgwLCJGb250U2l6ZSIsMTApOw0KTE9ET1AuU0VUX1BSSU5UX1NUWUxFQSgwLCJJdGVtVHlwZSIsMSk7DQpMT0RPUC5BRERfUFJJTlRfVEVYVCgiODcuMTQzJSIsIjUwLjQ3OSUiLCIxMC4yNTIlIiwiMy45MjQlIiwi5pys5qyh5pS25qy+77yaIik7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkZvbnRTaXplIiwxMCk7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkl0ZW1UeXBlIiwxKTsNCkxPRE9QLkFERF9QUklOVF9URVhUKDQ1OCw0NjMsMTE0LDIxLCIiKTsNCkxPRE9QLlNFVF9QUklOVF9TVFlMRUEoMCwiRm9udFNpemUiLDEwKTsNCkxPRE9QLlNFVF9QUklOVF9TVFlMRUEoMCwiSXRlbVR5cGUiLDEpOw0KTE9ET1AuQUREX1BSSU5UX1RFWFQoIjg3LjE0MyUiLCI4Mi40NjklIiwiMTEuNDc0JSIsIjMuOTI0JSIsIiIpOw0KTE9ET1AuU0VUX1BSSU5UX1NUWUxFQSgwLCJGb250U2l6ZSIsMTApOw0KTE9ET1AuU0VUX1BSSU5UX1NUWUxFQSgwLCJJdGVtVHlwZSIsMSk7DQpMT0RPUC5BRERfUFJJTlRfVEVYVCgiODcuMTQzJSIsIjc0LjE1NiUiLCIxMS41MTElIiwiMy45MjQlIiwi5pys5qyh5qyg5qy+77yaIik7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkZvbnRTaXplIiwxMCk7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkl0ZW1UeXBlIiwxKTsNCkxPRE9QLkFERF9QUklOVF9URVhUKCI5MS4yOTUlIiwiMS44NjQlIiwiNi41OTklIiwiMy45MjQlIiwi5aSH5rOo77yaIik7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkZvbnRTaXplIiwxMCk7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkl0ZW1UeXBlIiwxKTsNCkxPRE9QLkFERF9QUklOVF9URVhUKCI5MS4xNjIlIiwiNi41NDklIiwiMTkuMDMlIiwiMy45MjQlIiwiIik7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkZvbnRTaXplIiwxMCk7DQpMT0RPUC5TRVRfUFJJTlRfU1RZTEVBKDAsIkl0ZW1UeXBlIiwxKTsNCg==';
</script>
<!--<script src="/statics/js/common/CLprint.js"></script>-->
<script src="/statics/js/common/base64.js"></script>
<script>
 (function($){
    var billType = frameElement.api.data.billType;
    var printMethod = $.cookie('printMethod') || 'pdf';
    var taodaData = frameElement.api.data.taodaData;
    var pdfData = frameElement.api.data.pdfData;
    var pdfUrl = frameElement.api.data.pdfUrl;
    var defaultSelectValue = frameElement.api.data.opt.defaultSelectValue;//必须为数组形式：[key, value]
    init();
    $('#taodaoBtn').click(function(){
        document.writeln("<script src=\"/statics/js/common/CLprint.js\"><\/script>");
    });
    function init(){
      //初始化设置
      initPrintMethod(printMethod);
      initSettings();

      //绑定事件
      $('#printSelect li').on('click', function(){
        if($(this).hasClass('cur')){return ;}
        printMethod = $(this).data('type');
        initPrintMethod(printMethod);
      });

      //模版选择下拉
      $('#printTemp').combo({
        data: '../noteprinttemp?action=findByType&type=' + billType,
        width: 'auto',
        listCls: 'template-list droplist',
        width: 340,
        defaultSelected: (defaultSelectValue?defaultSelectValue:['isDefault', true]),
        text: 'name',
        value: 'id',
        ajaxOptions: {
          formatData: function(data){         
             return data.data.items;
          }
        }
      });

      //限制输入数字
      Public.currencyToNum($('.num-input').val());

      //套打提示
      if (parent.parent.SYSTEM.serviceType == 8) {
        var taodaTips  = '您的帐套为免费版，须购买付费版才能使用套打功能，<a href="http://app.youshang.com/site/buy.jsp" target="_blank">点击购买</a>'
        $('#taodaTips').html(taodaTips);
      }

      //设置默认模版
      $('#setDefaultTemp').on('click', function(e){
          e.preventDefault();
          var templateId = $('#printTemp').getCombo().getValue();
          $.ajax({
            url:'../noteprinttemp?action=setDefault&templateId=' + templateId + '&type=' + billType,
            type: 'post',
            dataType: 'json',
            success: function(data){
              if (data.status == 200) {
            	  parent.parent.Public.tips({content: '设置默认模版成功！'});
              } else {
            	  parent.parent.Public.tips({type: 1,content: data.msg});
              }
            },
            error: function(){
            	parent.parent.Public.tips({type:1, content : '系统繁忙，请重试！'});
            }
          });
      });
      
      /*//监控表单提交
      $('#downloadForm').submit(function(){
    	  window.setTimeout(function(){
   	         frameElement.api.close();
   	      },500);
      });*/
    }


    function doPrint(){
      $.cookie('printMethod',printMethod);
      if (printMethod == 'pdf') {
         pdfPrint();
      } else if(printMethod == 'taoda'){
        tadaoPrint();
      }
    }
    window.doPrint = doPrint;


    function initPrintMethod(Method){
      var obj = $('#printSelect li').filter('[data-type='+printMethod+']');
      obj.addClass('cur').siblings('li').removeClass('cur');
      var idx = obj.index($('#printSelect li'));
      $('#printSettings .item').eq(idx).show().siblings().hide();
    }

    function initSettings(){
      $('#pdfStatX').val($.cookie('pdfMarginLeft') || 50);
      $('#entrysPerNote').val($.cookie('entrysPerNote') || 0);
      $('#pdfEntrysPerNote').val($.cookie('pdfEntrysPerNote') || 20);
      $('#taodaStartX').val($.cookie('taodaStartX') || 0);
      $('#taodaStartY').val($.cookie('taodaStartY') || 0);
      if ($.cookie('isEmptyLinePrint') != null) {
        var checked = $.cookie('isEmptyLinePrint') == 1 ? true : false;
        $('#isEmptyLinePrint').attr('checked', checked);
      }
      if ($.cookie('printFirstLayer') != null) {
        var checked = $.cookie('printFirstLayer') == 1 ? true : false;
        $('#printFirstLayer').attr('checked', checked);
      }
    }
    
    function getBillType(TypeId) {
    	switch(TypeId) {
    	case 0:
    		return 'Voucher';
    	case 10101:
    		return 'PUR';
    	case 10201:
    		return 'SAL';
    	case 10301:
    		return 'SCM_INV_PUR';
    	case 10303:
    		return 'SCM_INV_SALE';
    	default:
    		return '0';
    	}
    }


    function tadaoPrint(){
        prints();
    }

    function pdfPrint(){
      pdfData.marginLeft = $('#pdfStatX').val(); //设置左边距
      pdfData.entrysPerNote = $('#pdfEntrysPerNote').val(); //每张打印行数 //add by michen 20171125 for 打印
      $.cookie('pdfMarginLeft', pdfData.marginLeft, {expires: 365});
      Business.getFile(pdfUrl, pdfData, true, false);
      frameElement.api.close();
    }
   
    //打印运单处理
    if(!pdfUrl){
  	  $('#printSelect li:eq(1)').trigger('click');
  	  $('#printSelect').hide();
  	  $('#setDefaultTemp').hide();
    }
 })(jQuery);
</script>
</body>
</html>


 