var OBJ = OBJ || {};
var baseUrl = 'http://chat.crystalsky.top/';

const AES_KEY = "aaaaaaaaaaaaaaaa"; //16位
const AES_IV = "8888888888888888";  //16位

/**
 * 自定义提示框
 */
function myAlert(id, msg, time) {
    $obj = $('#' + id);
    $obj.show();
    $obj.html('<div class="alert alert-danger text-center" role="alert">' + msg + '</div>');
    time = time || 3000;
    setTimeout('$("#' + id + '").hide("slow")', time);
}

/**
 *  AES加密
 * @param plainText
 * @returns {string}
 */
function aes_encrypt(plainText) {
    var encrypted = CryptoJS.AES.encrypt(plainText, CryptoJS.enc.Utf8.parse(AES_KEY), {iv: CryptoJS.enc.Utf8.parse(AES_IV)});
    return CryptoJS.enc.Base64.stringify(encrypted.ciphertext);
}

/**
 * AES解密
 * @param ciphertext
 * @returns {string}
 */
function aes_decrypt(ciphertext) {
    var decrypted = CryptoJS.AES.decrypt(ciphertext, CryptoJS.enc.Utf8.parse(AES_KEY), {iv: CryptoJS.enc.Utf8.parse(AES_IV)});
    return decrypted.toString(CryptoJS.enc.Utf8);
}