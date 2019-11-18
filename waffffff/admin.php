<?php
define("IN_WAF_PLATFORM", true);

error_reporting(0);

require_once("config.php");

if (!isset($_GET['password']) || hash('sha256', $_GET['password']) !== PASSWORD) {
    die();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ph0en1x Admin Manage</title>
    <link rel="stylesheet" href="layui/css/layui.css" media="all">
</head>

<body>

<script src="layui/layui.js"></script>

<div style="display:none;" class="layui-card-body" id='show_dom'>
  <table class="layui-table" lay-filter='show_form'>
    <colgroup>
      <col width="30%">
      <col width="70%">
    </colgroup>
    <tbody id='show_dom_tbody'>
    </tbody>
  </table>
</div>

<div class="layui-card-body">
    <div class="Reload layui-form layui-form-pane">
        <div class="layui-inline">
            <label class="layui-form-label">时间</label>
            <div class="layui-input-inline">
                <select id="where_time">
                    <option value="all">ALL</option>
                    <?php
                    $tmp = scandir(DATA_PATH);
                    $t = LOG_NAME;
                    foreach ($tmp as $name) {
                        if (preg_match("/{$t}_[0-9]{10}/", $name)) {
                            $time = substr($name, strlen($t)+1, 10);
                            date_default_timezone_set("Asia/Shanghai");
                            $format_time_1 = date("H:i:s", $time);
                            $format_time_2 = date("H:i:s", $time+LOG_TIME_INTERVAL-1);
                            echo "<option value=\"$time\">$format_time_1 - $format_time_2</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            
        </div>

        <div class="layui-inline">
            <label class="layui-form-label">IP</label>
            <div class="layui-input-inline">
                <select id="where_ip">
                    <option value="all">ALL</option>
                    <?php
                    $ipFile = DATA_PATH.'/'.IP_NAME;
                    if (file_exists($ipFile)) {
                        $ips = file($ipFile);
                        foreach ($ips as $ip) {
                            $ip = substr($ip, 0, strlen($ip)-1);
                            echo "<option value=\"$ip\">$ip</option>";
                        }
                    }
                    ?>
                </select>
            </div>
        </div>

        <button class="layui-btn" data-type="reload"><i class="layui-icon">&#xe615;</i>搜索</button>
        <button class="layui-btn" data-type="reload"><i class="layui-icon">&#xe669;</i>刷新</button>
    </div>

    <table id="demo" lay-filter="test"></table>

</div>

<script>
function show(data, type) {
    try {
        var dict = JSON.parse(data);
        console.log(dict);
        if (dict.length === 0) {
            return 0;
        }
        if (type === 1) {
            var tmp = [];
            for (let item in dict) {
                tmp = Object.assign(tmp,dict[item])
            }
            dict = tmp;
        }
        document.getElementById('show_dom_tbody').innerHTML = '';
        var tbody = document.getElementById('show_dom_tbody');
        for (let key in dict) {  
            var tr = document.createElement('tr');
            var td1 = document.createElement('td');
            var td2 = document.createElement('td');
            td1.innerHTML = key;
            td1.setAttribute('style', 'word-wrap:break-word; word-break:break-all;');
            td2.innerHTML = dict[key];
            td2.setAttribute('style', 'word-wrap:break-word; word-break:break-all;');
            tr.appendChild(td1);
            tr.appendChild(td2);
            tbody.appendChild(tr); 
        }
        return 1;
    }
    catch(err) {
        return 0;
    }
};

function gw_now_addzero(temp) {
    if (temp < 10) {
        return "0" + temp;
    }
    else {
        return temp;
    }
}

function format_time(time) {
    var date = new Date(time*1000);
    var hour = gw_now_addzero(date.getHours());
    var minute = gw_now_addzero(date.getMinutes());
    var second = gw_now_addzero(date.getSeconds());
    return hour + ":" + minute + ":" + second;
}

layui.use(['table', 'form'], function() {
    var table = layui.table;
    var form = layui.form;

    table.render({
        elem: '#demo'
        ,url: 'api.php?cmd=load_waf_record'
        ,loading: true
        ,cols: [[
            {field: 'request_time', title: '时间', sort: true, templet: function(res){return format_time(res.request_time)}, align:'center'}
            ,{field: 'user_IP', title: 'IP', sort: true, align:'center'}
            ,{field: 'script_name', title: '请求文件', sort: true, align:'center'}
            ,{field: 'request_method', title: '请求方法', sort: true, align:'center'}
            ,{field: 'headers_data', title: 'Headers', sort: false, event: 'showHEADERS', templet: function(res){if(JSON.parse(res.headers_data).length===0)return '';else return res.headers_data;}, align:'left'}
            ,{field: 'cookie_data', title: 'Cookies', sort: false, event: 'showCOOKIES', templet: function(res){if(JSON.parse(res.cookie_data).length===0)return '';else return res.cookie_data;}, align:'left'}
            ,{field: 'get_data', title: 'GET', sort: false, event: 'showGET', templet: function(res){if(JSON.parse(res.get_data).length===0)return '';else return res.get_data;}, align:'left'}
            ,{field: 'post_data', title: 'POST', sort: false, event: 'showPOST', templet: function(res){if(JSON.parse(res.post_data).length===0)return '';else return res.post_data;}, align:'left'}
            ,{field: 'files_data', title: 'FILES', sort: false, event: 'showFILES', templet: function(res){if(JSON.parse(res.files_data).length===0)return '';else return res.files_data;}, align:'left'}
            ,{field: 'is_wafed', title: '是否被拦截', sort: true, align:'center'}
            ,{field: 'wafed_result', title: '被拦截原因', sort: false, event: 'showResult', align:'left'}
        ]]
        ,id: 'idTest'
    });

    table.on('tool(test)', function(obj) {
        var data = obj.data;
        var $ = layui.$;
        var is_show = 0;    

        switch(obj.event) {
            case 'showHEADERS':
                is_show = show(data.headers_data, 0);
                var title = 'Headers';
            break;
            case 'showCOOKIES':
                is_show = show(data.cookie_data, 0);
                var title = 'Cookies';
            break;
            case 'showGET':
                is_show = show(data.get_data, 0);
                var title = 'GET';
            break;
            case 'showPOST':
                is_show = show(data.post_data, 0);
                var title = 'POST';
            break;
            case 'showFILES':
                is_show = show(data.files_data, 1);
                var title = 'FILES';
            break;

            case 'showResult':
                layer.msg(data.wafed_result, {
                time: 30000,
                btn: ['确定']
            });
            break;
        }

        if (is_show === 1) {
            var index = layer.open({
                type: 1, 
                title: title,
                skin: 'layui-layer-molv',
                area: ['50%', '50%'],
                btn: ['确定'],
                content: $('#show_dom')
            });   
        }
    });

    var $ = layui.$, active = {
        reload: function() {
            var where_ip = $('#where_ip');
            var where_time = $('#where_time');
            table.reload('idTest', {
                where: {
                    ip: where_ip.val()
                    ,time: where_time.val()
                }
            });
        }
    };

    $('.Reload .layui-btn').on('click', function() {
        var type = $(this).data('type');
        active[type]? active[type].call(this): '';
    });

});
</script>
</body>
</html>