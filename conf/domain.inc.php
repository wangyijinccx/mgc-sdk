<?php
define("HTTP", 'http://');
define("HTTPS", 'https://');
define("DOCDOMAIN",'xiguamei.com');
define("DOMAIN", '.'.DOCDOMAIN);
define("WEBSITE", HTTP.'guild'.DOMAIN);
define("MOBILESITE", HTTPS.'m'.DOMAIN);
define("SDKSITE", HTTPS.'api'.DOMAIN);
define("ADMINSITE", HTTPS.'manager'.DOMAIN);
define("OPENSITE", HTTP.'tg'.DOMAIN);
define("PAYSITE", HTTP.'pay'.DOMAIN);
define("DOWNSITE", HTTP.'cdn'.DOMAIN.'/download/sdkgame/');
define("DOWNIOSSITE", "itms-services://?action=download-manifest&url=https://down".DOMAIN.'/download/sdkgame/');
define("DOWNIP", HTTP.'down'.DOMAIN.'/download');
define("AGENTSITE", HTTP.'tg'.DOMAIN);
define("BBSSITE", HTTP.'guild'.DOMAIN);
define("STATICSITE", HTTPS.'statics'.DOMAIN);
define("JZJHSITE", HTTP.'guild'.DOMAIN."/index.php/Index/jzjh.html");
