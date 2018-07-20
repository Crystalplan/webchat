var baseUrl = "localhost";
var serverIp = "127.0.0.1";
var cashPrefix = "chat_";
var curTime = new Date().getTime();

const AES_KEY = "aaaaaaaaaaaaaaaa"; //16位
const AES_IV = "8888888888888888"; //16位
const SIGN_KEY = "aaaaaaaaaaaaaaaa8888888888888888"; //32位

checkLogin(); // 检查登录

/**
 * 自定义提示框
 */
function myAlert(id, msg, time) {
  $obj = $("#" + id);
  $obj.show();
  $obj.html(
    '<div class="alert alert-danger text-center" role="alert">' + msg + "</div>"
  );
  time = time || 3000;
  setTimeout('$("#' + id + '").hide("slow")', time);
}

/**
 *  AES加密
 * @param plainText
 * @returns {string}
 */
function aesEncrypt(plainText) {
  var encrypted = CryptoJS.AES.encrypt(
    plainText,
    CryptoJS.enc.Utf8.parse(AES_KEY),
    { iv: CryptoJS.enc.Utf8.parse(AES_IV) }
  );
  return CryptoJS.enc.Base64.stringify(encrypted.ciphertext);
}

/**
 * AES解密
 * @param ciphertext
 * @returns {string}
 */
function aesDecrypt(ciphertext) {
  var decrypted = CryptoJS.AES.decrypt(
    ciphertext,
    CryptoJS.enc.Utf8.parse(AES_KEY),
    { iv: CryptoJS.enc.Utf8.parse(AES_IV) }
  );
  return decrypted.toString(CryptoJS.enc.Utf8);
}

/**
 * md5
 * @param {string} word
 */
function md5Encode(word) {
  return CryptoJS.MD5(word).toString();
}

/**
 * 检查是否已登录
 */
function checkLogin() {
  var pathname = window.location.pathname;
  var tmpArr = pathname.split("/");
  if (tmpArr[1] !== "home" && tmpArr[1] !== "admin") {
    return false;
  }
  var url = baseUrl + tmpArr[1] + "/login.html";

  if (typeof sessionStorage == "undefined") {
    alert("浏览暂不支持sessionStorage");
    window.location.href = url;
    return false;
  }
  if (pathname == "/home/login.html" || pathname == "/admin/login.html") {
    return true;
  }
  if (window.sessionStorage.getItem(cashPrefix + "userInfo") === null) {
    window.location.href = url;
    return false;
  }
  $.ajax({
    type: "post",
    headers: {
      "Access-Token": JSON.parse(
        sessionStorage.getItem(cashPrefix + "userInfo")
      ).access_token
    },
    url: baseUrl + tmpArr[1] + "/login/checkLogin",
    data: {},
    success: function(result) {
      if (result.errcode !== 0) {
        window.location.href = url;
        return false;
      }
    },
    dataType: "json"
  });
  return true;
}

/**
 * localStorage设置
 * @param string key 键名
 * @param int/string value 键值
 * @param int exp 过期时间（s）
 */
function localSetex(key, value, exp) {
  localStorage.setItem(
    key,
    JSON.stringify({ value: value, expireTime: curTime + exp * 1000 })
  );
}

/**
 * localStorage获取
 * @param string key 键名
 */
function localGet(key) {
  var data = localStorage.getItem(key);
  if (data !== null) {
    var dataObj = JSON.parse(data);
    if (dataObj.expireTime - new Date().getTime() <= 0) {
      console.log("信息已过期");
      localStorage.removeItem(key);
      return false;
    } else {
      return JSON.parse(dataObj.value);
    }
  }
  return false;
}

/**
 * 生成数据签名
 * @param paramObj 参数对象
 * @param key
 * @return string
 */
function makeSign(paramObj, key = SIGN_KEY) {
  var str = "";
  paramObj = objKeySort(paramObj);
  var keysArr = Object.keys(paramObj);
  for (var i = 0; i < keysArr.length; i++) {
    if ("sign" !== keysArr[i] && "" !== paramObj[keysArr[i]]) {
      str += keysArr[i] + "=" + paramObj[keysArr[i]] + "&";
    }
  }
  str += "key=" + key;
  console.log(str);
  var sign = md5Encode(str);
  return sign;
}

/**
 * 对象按属性字典序排序
 * 先用Object内置类的keys方法获取要排序对象的属性名，再利用Array原型上的sort方法对获取的属性名进行排序，newkey是一个数组
 * @param {Object} obj
 */
function objKeySort(obj) {
  var newkey = Object.keys(obj).sort();
  var newObj = {};
  for (var i = 0; i < newkey.length; i++) {
    newObj[newkey[i]] = obj[newkey[i]];
  }
  return newObj;
}
