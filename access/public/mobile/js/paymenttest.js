//判断机型
var huosdk_deviceType = 'pc';
var sUserAgent        = navigator.userAgent.toLowerCase();
huosdk_deviceType     = sUserAgent.match(/ipad/i) == "ipad" ? 'ipad' : 'pc';
huosdk_deviceType     = sUserAgent.match(/iphone/i) == "iphone" ? 'iphone' : 'pc';
huosdk_deviceType     = sUserAgent.match(/android/i) == "android" ? 'android' : 'pc';
/**************支付方式******************/
var changePay         = {
    canPay  : true,
    topClick: 0,
    payType : null,
    init    : function () {
        var isGamepay = document.getElementById("gamepay").value;
        var me        = this;
        var liList    = document.querySelectorAll(".change_way>.way>li");
        for (var i = 0; i < liList.length; i ++) {
            liList[i].onclick = function () {
                me.payType = this.getAttribute("data-way");
                if (me.payType == null) {
                    me.payType = "alipay";
                }
                if (me.canPay) {
                    var form_data = {
                        paytype : me.payType,
                        orderid : document.getElementById("orderid").value,
                        paytoken: document.getElementById("paytoken").value,
                        randnum : Math.random()
                    };
                    var vurl      = document.getElementById("payform").getAttribute("action");
                    ysSendData(vurl, form_data, preorder_succ, preorder_err);
                } else {
                    for (var j = 0; j < liList.length; j ++) {
                        liList[j].className = '';
                    }
                    this.className = 'active';
                }
            };
        }
        document.querySelector("footer>.pay").onclick = function () {
            if (me.payType == null) {
                me.payType = "alipay";
            }
            if (me.payType !== null || 'gamepay' == isGamepay) {
                if ('gamepay' == isGamepay) {
                    me.payType = 'gamepay';
                }
                var form_data = {
                    paytype : me.payType,
                    orderid : document.getElementById("orderid").value,
                    paytoken: document.getElementById("paytoken").value,
                    randnum : Math.random()
                };
                var vurl      = document.getElementById("payform").getAttribute("action");
                ysSendData(vurl, form_data, preorder_succ, preorder_err);
            } else {
                me.canPay = true;
                showMobileAlert('请选择支付方式', 'null', '确定', 'right');
            }
        };
    }
}
changePay.init();
function ysSendData(url, data, succ, err, type, dataType, conentType) {
    if (! type) {
        type = "POST"
    }
    if (! url) {
        throw new Error("url is not find...");
    }
    if (! dataType) {
        dataType = "JSON"
    }
    if (! conentType) {
        conentType = "application/x-www-form-urlencoded"
    }
    //showMobileAlert(url, 'null', '确定', 'right');
    var params = formatParams(data);

    //创建 - 非IE6 - 第一步
    if (window.XMLHttpRequest) {
        var xhr = new XMLHttpRequest();
    } else { //IE6及其以下版本浏览器
        var xhr = new ActiveXObject('Microsoft.XMLHTTP');
    }

    //接收 - 第三步
    xhr.onreadystatechange = function () {
        showMobileAlert(xhr.readyState, 'null', '确定', 'right');
        if (xhr.readyState == 4) {
            var status = xhr.status;
            showMobileAlert(status, 'null', '确定', 'right');
            if (status >= 200 && status < 300) {
                showMobileAlert(xhr.responseText, 'null', '确定', 'right');
                var responsedata = JSON.parse(xhr.responseText);
                showMobileAlert(responsedata.state, 'null', '确定', 'right');
				//showMobileAlert(responsedata.payinfo, 'null', '确定', 'right');
                succ(responsedata, xhr.responseXML);
            } else {
                err(status);
            }
        }
    }

    //连接 和 发送 - 第二步
    if (type == "GET") {
		// showMobileAlert(url, 'null', '确定1', 'right');
        xhr.open("GET", url + "?" + params, true);
        xhr.send(null);
    } else if (type == "POST") {
		//showMobileAlert(url, 'null', '确定2', 'right');
        xhr.open("POST", url, true);
        //设置表单提交时的内容类型
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.send(params);
    }
}

//格式化参数
function formatParams(data) {
    var arr = [];
    for (var name in data) {
        arr.push(encodeURIComponent(name) + "=" + encodeURIComponent(data[name]));
    }
    arr.push(("v=" + Math.random()).replace(".", ""));
    return arr.join("&");
}

//下单成功时调用原生支付
function preorder_succ(result) {
    if ('success' == result.state) {
        var txt              = result.payinfo;
		 //showMobileAlert(txt.paytype, 'null', '确定', 'right');
		  //showMobileAlert(txt.token, 'null', '确定', 'right');
		// return ;
		// showMobileAlert(txt.token, 'null', '确定', 'right');
        if ('h5_alipay' == txt.paytype) {
            ali_js_post(txt.token);
            return;
        }
        if ('h5_spay' == txt.paytype) {
            window.huosdk.changeTitleStatus('hidden');
            spay_js_post(txt.token);
            return;
        }
        if ('h5_nowpay' == txt.paytype) {
            window.location.href = txt.token;
            return;
        }
        if ('ptbpay' == txt.paytype) {
            windwo.huosdk.huosdk_payNotify(txt.token);
        }
		 showMobileAlert(txt, 'null', '确定', 'right');
        window.huosdk.huoPay(txt);	
		//showMobileAlert('aaaa', 'null', '确定', 'right');
        console.log(txt);
        return;
        var token = JSON.parse(txt);
        if ('gamepay' == token.paytype) {
            showMobileAlert(token.info, '确定', 'null', 'left', ptb_huopay);
        } else {
            window.huosdk.huoPay(txt);
        }
    } else {
        showMobileAlert(result.info, 'null', '确定');
    }
}

function ali_js_post(param) {
    document.write(param);
    document.forms['alipaysubmit'].submit();
}

// todo spay提交
function spay_js_post(param) {
    window.location.href = param;
}

//web调用原生打电话
function huosdk_ringup(phone) {
    var phonestr = '' + phone;
    window.huosdk.openPhone(phonestr);
}

//web调用原生打开QQ
function huosdk_openqq(qq) {
    var qqstr = '' + qq;
    window.huosdk.openQq(qqstr);
}

//web调用原生打开QQ群
function huosdk_openqqgroup(qqgroup, qqkeystr) {
    var qqgroupstr = '' + qqgroup;
    if ('android' == huosdk_deviceType) {
        qqgroupstr = '' + qqkeystr;
    }
    window.huosdk.joinQqgroup(qqgroupstr);
}

//调用原生关闭页面
function closeweb() {
    showMobileAlert('确定退出', '是', '否', '', huosdk_backgame);
}

//调用原生切换账号
function huosdk_changeaccount(type) {
    if ('logout' == type) {
        showMobileAlert('确定退出', '是', '否', '', huosdk_logout);
    } else {
        showMobileAlert('确定切换账号', '是', '否', '', huosdk_logout);
    }
}

//连接失败时弹出提示
function preorder_err(status) {
    showMobileAlert('网络连接错误', 'null', '确定', 'right');
}

//成功回调
function huosdk_backgame(data) {
    window.huosdk.closeWeb();
}

//切换账号
function huosdk_logout() {
    window.huosdk.changeAccount();
}

//成功回调
function ptb_huopay(data) {
    var txt = data + '';
    window.huosdk.huoPay(txt);
}

function GoToSet() {
//	location.href="prefs:root=General&path=ManagedConfigurationList";
    location.href = "prefs:root=General";
}

//web调用原生通知支付结果
function huosdk_payNotify(data) {
    var info = '' + data;
    window.huosdk.payNotify(info);
}
/*可定制的弹出框*/
function showMobileAlert(title, lbtn, rbtn, operation, succ, data, font) {
    var once = false;
    if (! title) {
        title = '确定退出?'
    }
    if (! lbtn && lbtn !== 'null') {
        lbtn = '是'
    }
    if (! rbtn && rbtn !== 'null') {
        rbtn = '否'
    }
    if (lbtn === 'null') {
        lbtn = '';
        once = true;
    }
    if (rbtn === 'null') {
        rbtn = '', once = true
    }
    var flag        = document.createDocumentFragment();
    var style       = document.createElement('style');
    var htmlwidth   = document.querySelector("html").clientWidth;
    var boxWidth    = Math.floor(parseInt(htmlwidth) * 0.9 / 2);
    style.innerHTML = "#mobileAlert{width:100%;height:100%;position:fixed;top:0;left:0;background-color:rgba(0,0,0,.5);display:none}#mobileAlert>.box{width:90%;max-width:400px;background-color:#fff;margin:0 auto;position:absolute;top:200px;left:50%;border-radius:8px;margin-left:-" + boxWidth + "px}#mobileAlert>.box>.title{padding:30px 0;text-align:center;font-size:16px;font-family:'微软雅黑','microsoft YaHei'}#mobileAlert>.box>ul{border-top:1px solid #F3F3F3}#mobileAlert>.box>ul>li{float:left;width:50%;height:40px;line-height:40px;text-align:center;font-size:16px}#mobileAlert>.box>ul>li>a{font-family:'微软雅黑','microsoft YaHei'}#mobileAlert>.box>#show2>li{width:100%}#mobileAlert .box > ul.center>li{width:100%} ";
    flag.appendChild(style);
    var huosdk_div       = document.createElement('div');
    huosdk_div.id        = 'mobileAlert';
    huosdk_div.innerHTML = '<div class="box">\
        <p class="title">确定退出？</p>\
    <ul class=' + (once == false ? "" : "center") + '>\
            <li class="lbtn" style="-webkit-box-sizing: border-box;-moz-box-sizing: border-box;box-sizing: border-box;g:border-box;border-right:1px solid #F3F3F3">\
                <a href="#">是</a>\
                </li>\
        <li class="rbtn"><a href="#">否</a></li>\
        </ul>\
        </div>';

    flag.appendChild(huosdk_div);
    if (document.querySelectorAll('#mobileAlert').length > 0) {
        document.body.removeChild(document.querySelector('#mobileAlert'));
    }
    document.body.appendChild(flag);
    document.querySelector("#mobileAlert").style.fontSize    = font;
    document.querySelector("#mobileAlert .title").innerHTML  = title;
    document.querySelector("#mobileAlert .lbtn a").innerHTML = lbtn;
    document.querySelector("#mobileAlert .rbtn a").innerHTML = rbtn;
    if ('left' == operation) { //单个按钮有事件
        document.querySelector("#mobileAlert .rbtn").style.display = 'none';
        document.querySelector("#mobileAlert .lbtn").onclick       = function () {
            document.querySelector("#mobileAlert").style.display = 'none';
            succ(data);
        };
    } else if ('right' == operation) { //单个按钮无事件
        document.querySelector("#mobileAlert .lbtn").style.display = 'none';
        document.querySelector("#mobileAlert .rbtn").onclick       = function () {
            document.querySelector("#mobileAlert").style.display = 'none';
        };
    } else {
        document.querySelector("#mobileAlert .rbtn").onclick = function () {
            document.querySelector("#mobileAlert").style.display = 'none';
        };
        document.querySelector("#mobileAlert .lbtn").onclick = function () {
            succ(data);
        };

    }
    if (parseInt(htmlwidth) * 0.9 > 400) {
        document.querySelector('#mobileAlert .box').style.marginLeft = '-200px';
    } else {
        document.querySelector('#mobileAlert .box').style.marginLeft = '-' + parseInt(htmlwidth * 0.9 / 2) + 'px';
    }
    document.querySelector('#mobileAlert .box').style.top = (window.innerHeight - 117) / 2 + "px";

    document.getElementById("payform").getAttribute("action");

    document.querySelector("#mobileAlert").style.display = 'block';
}