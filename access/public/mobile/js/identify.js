/*******输入框******/
$(".inputBox").focus(function () {
    $(".error_box").html("");
});
//忘记密码
$(".submitss>button").click(function () {
    var mem_id         = $("#mem_id").val();
    var realname       = $("#realname").val();
    var idcard         = $("#idcard").val();
    var oldidcard      = $("#oldidcard").val();
    var data           = {};
    var url            = $("#ajaxUrl").val();
    var namecheck      = "";
    var idcardcheck    = "";
    var oldidcardcheck = "";
    if (realname.trim() == "" || idcard.trim() == "") {
        showMsg(".error_box", "姓名及身份证号不能为空...");
        return false;
    }
    namecheck = checkRealname();
    if (namecheck.trim() !== "") {
        showMsg(".error_box", namecheck);
        return false;
    }
    if (idcard.indexOf("*") > 0) {
        idcard = oldidcard;
    }
    idcardcheck = checkIdcard(idcard);
    if (idcardcheck.trim() !== "") {
        showMsg(".error_box", idcardcheck);
        return false;
    }
    var form_data = {
        "mem_id"  : mem_id,
        "realname": realname,
        "idcard"  : idcard
    };
    sendData(url, form_data, succ, err);
    function succ(result) {
        if ('success' == result.state) {
            window.location.href = result.url;
        } else {
            showMsg(".error_box", result.info);
            return false;
        }
    }

    function err(XMLHttpRequest, textStatus, errorThrown) {
        showMsg(".error_box", "网络错误");
        return false;
    }

    //姓名验证
    function checkRealname() {
        var msg      = "";
        var realname = $("#realname").val();
        if (realname == "") {
            msg = "请输入姓名！";
        }
        if (realname.length < 2 || realname.length > 4) {
            msg = "请输入正确的姓名！";
        }
        return msg;
    }

    function checkIdcard(idcard) {
        var msg = "";
        if (idcard == "") {
            msg = "请输入身份证号...";
        }
        idcard = idcard.toUpperCase();
        if (! (/(^\d{15}$)|(^\d{17}([0-9]|X)$)/.test(idcard))) {
            msg = "请输入正确身份证号...";
        }
        var len, re;
        len = idcard.length;
        if (len == 15) {
            re           = new RegExp(/^(\d{6})(\d{2})(\d{2})(\d{2})(\d{3})$/);
            var arrSplit = idcard.match(re);
            //检查生日日期是否正确
            var dtmBirth = new Date('19' + arrSplit[2] + '/' + arrSplit[3] + '/' + arrSplit[4]);
            var bGoodDay;
            bGoodDay     = (dtmBirth.getYear() == Number(arrSplit[2])) && ((dtmBirth.getMonth() + 1) == Number(arrSplit[3])) && (dtmBirth.getDate() == Number(arrSplit[4]));

            if (! bGoodDay) {
                msg = "请输入正确身份证号...";
            } else {
                //将15位身份证转成18位
                //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
                var arrInt = new Array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
                var arrCh  = new Array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
                var nTemp  = 0, i;
                idcard     = idcard.substr(0, 6) + '19' + idcard.substr(6, num.length - 6);
                for (i = 0; i < 17; i ++) {
                    nTemp += idcard.substr(i, 1) * arrInt[i];
                }
            }
        }
        if (len == 18) {
            re           = new RegExp(/^(\d{6})(\d{4})(\d{2})(\d{2})(\d{3})([0-9]|X)$/);
            var arrSplit = idcard.match(re);
            //检查生日日期是否正确
            var dtmBirth = new Date(arrSplit[2] + "/" + arrSplit[3] + "/" + arrSplit[4]);
            var bGoodDay;
            bGoodDay     = (dtmBirth.getFullYear() == Number(arrSplit[2])) && ((dtmBirth.getMonth() + 1) == Number(arrSplit[3])) && (dtmBirth.getDate() == Number(arrSplit[4]));
            if (! bGoodDay) {
                msg = "请输入正确身份证号...";
            } else {
                //检验18位身份证的校验码是否正确。
                //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
                var valnum;
                var arrInt = new Array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
                var arrCh  = new Array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
                var nTemp  = 0, i;
                for (i = 0; i < 17; i ++) {
                    nTemp += idcard.substr(i, 1) * arrInt[i];
                }
                valnum = arrCh[nTemp % 11];
                if (valnum != idcard.substr(17, 1)) {
                    msg = "请输入正确身份证号...";
                }
            }
        }
        return msg;
    }
});