'use strict';
// 适配js
(function() {

  var screen = 100;

  var sUserAgent = navigator.userAgent.toLowerCase();
  var bIsIpad = sUserAgent.match(/ipad/i) == "ipad";
  var bIsIphoneOs = sUserAgent.match(/iphone os/i) == "iphone os";
  var bIsMidp = sUserAgent.match(/midp/i) == "midp";
  var bIsUc7 = sUserAgent.match(/rv:1.2.3.4/i) == "rv:1.2.3.4";
  var bIsUc = sUserAgent.match(/ucweb/i) == "ucweb";
  var bIsAndroid = sUserAgent.match(/android/i) == "android";
  var bIsCE = sUserAgent.match(/windows ce/i) == "windows ce";
  var bIsWM = sUserAgent.match(/windows mobile/i) == "windows mobile";
  //console.log('宽：'+window.innerWidth + '高：'+window.innerHeight)
  var ifWidth = window.innerWidth;
  if (bIsIpad || bIsIphoneOs || bIsMidp || bIsUc7 || bIsUc || bIsAndroid || bIsCE || bIsWM) {
      //console.log("您的浏览设备为：phone")
      if(ifWidth >= 360 && ifWidth <= 375){
          screen = 100;
          //console.log('大于等于360 小于等于375 字体 100');
      }else if(ifWidth >= 320 &&ifWidth <= 359){
          //console.log('大于等于320 小于等于359 字体 100');
          screen = 100;
      }else if( ifWidth >= 600 ){
          //console.log('大于等于600 字体 180');
          screen = 100;
      }
  } else {
      //console.log("您的浏览设备为：pc")
      screen = 100;
  }

  function get() {
      var size = window.getComputedStyle(document.documentElement, null).getPropertyValue("font-size");
      return parseFloat(size);
  }

  function set(size) {
      document.documentElement.style.fontSize = size + "px";
  }

  var hW = window.innerWidth > 750 ? 750 : window.innerWidth;
  // 750是设计稿尺寸，与100为基数window.innerWidth
  var size = screen * (hW / 750);

  set(size);
  // 校正html字体大小
  function fix() {
      var target = 100 * (hW / 750).toFixed(4);
      var current = get().toFixed(4);
      if (current != target) {
          var size = target * (target / current);
          set(size)
      }
  }
  fix();

})();



/**
 * 节流函数
 * @param {*} func 
 * @param {*} wait 
 * @param {*} mustRun 
 */
function throttle(func, wait, mustRun) {
  var timeout,
    startTime = new Date();

  return function () {
    var context = this,
      args = arguments,
      curTime = new Date();

    clearTimeout(timeout);
    // 如果达到了规定的触发时间间隔，触发 handler
    if (curTime - startTime >= mustRun) {
      func.apply(context, args);
      startTime = curTime;
      // 没达到触发间隔，重新设定定时器
    } else {
      timeout = setTimeout(func, wait);
    }
  };
};

/**
 * 隐藏下载广告
 */
$('.delApp').click(function(){
    $('.Download').hide();
    $('.QrCode').css('padding-bottom','0');
});

/*解决IOS：active效果*/
document.body.addEventListener('touchstart', function() {});