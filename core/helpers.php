<?php

$NNL = new DB;
require_once __DIR__ . '/RSACrypt.php';
$rsa = new RSACrypt();
function encryptData($data)
{
    global $rsa;
    $rsa->setPrivateKey(__DIR__ . '/clientPrivate.pem');
    $rsa->setPublicKey(__DIR__ . '/serverPublic.pem');
    return $rsa->encryptWithPublicKey($data);
}
function decodecryptData($data)
{
    global $rsa;
    $rsa->setPrivateKey(__DIR__ . '/serverPrivate.pem');
    $rsa->setPublicKey(__DIR__ . '/clientPublic.pem');
    return $rsa->decryptWithPrivateKey($data);
}


//thông tin user theo id
function getUser($id, $row)
{
    global $NNL;
    return $NNL->get_row("SELECT * FROM `users` WHERE `id` = '$id' ")[$row];
}
function format_date($time)
{
    return date("H:i:s d/m/Y", $time);
}
function redirect($url)
{
    header("Location: {$url}");
    exit();
}
function CreateToken($username)
{
    $token = md5($username . 'xyz306VY' . "sieuthicode" . 'asbtfih49123ht2310t1ht4120th2130gh218rg210gr231gr23g1r20fbgcmcxzvlxzhblkhxzlkbxzbhcxzlkhbzxobuoquewortqirqtmsdfhoafidhbaofbnapquewrofwafonsadgsambalbn');
    return $token;
}

function admin($data)
{
    if ($data == 'admin') {
        $show = '<span class="badge badge-success">Admin</span>';
    } else {
        $show = '<span class="badge badge-danger">Thành viên</span>';
    }
    return $show;
}

function BASE_URL($url)
{
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"];
    if ($base_url == 'http://localhost') {
        $base_url = 'http://localhost';
    }
    return $base_url . '/' . $url;
}
function gettime()
{
    return date('Y/m/d H:i:s', time());
}
function xss($data)
{
    // Fix &entity\n;
    $data = str_replace(array('&amp;', '&lt;', '&gt;'), array('&amp;amp;', '&amp;lt;', '&amp;gt;'), $data);
    $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
    $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
    $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

    // Remove any attribute starting with "on" or xmlns
    $data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

    // Remove javascript: and vbscript: protocols
    $data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
    $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
    $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

    // Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
    $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
    $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
    $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

    // Remove namespaced elements (we do not need them)
    $data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

    do {
        // Remove really unwanted tags
        $old_data = $data;
        $data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
    } while ($old_data !== $data);

    // we are done...
    $nhatloc = htmlspecialchars(addslashes(trim($data)));

    return $nhatloc;
}
function check_string($data)
{
    return trim(htmlspecialchars(addslashes($data)));
    //return str_replace(array('<',"'",'>','?','/',"\\",'--','eval(','<php'),array('','','','','','','','',''),htmlspecialchars(addslashes(strip_tags($data))));
}
function format_cash($price)
{
    return str_replace(",", ".", number_format($price));
}
function curl_get($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);

    curl_close($ch);
    return $data;
}
function curl_post($data, $url)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}
function random($string, $int)
{
    return substr(str_shuffle($string), 0, $int);
}
function pheptru($int1, $int2)
{
    return $int1 - $int2;
}
function phepcong($int1, $int2)
{
    return $int1 + $int2;
}
function phepnhan($int1, $int2)
{
    return $int1 * $int2;
}
function phepchia($int1, $int2)
{
    return $int1 / $int2;
}
function parse_order_id($des, $MEMO_PREFIX)
{
    $re = '/' . $MEMO_PREFIX . '\d+/im';
    preg_match_all($re, $des, $matches, PREG_SET_ORDER, 0);
    if (count($matches) == 0) {
        return null;
    }
    $orderCode = $matches[0][0];
    $prefixLength = strlen($MEMO_PREFIX);
    $orderId = substr($orderCode, $prefixLength); // KHÔNG dùng intval
    return $orderId;
}

function check_img($img)
{
    $filename = $_FILES[$img]['name'];
    $ext = explode(".", $filename);
    $ext = end($ext);
    $valid_ext = array("png", "jpeg", "jpg", "PNG", "JPEG", "JPG", "gif", "GIF");
    if (in_array($ext, $valid_ext)) {
        return true;
    }
}

function nnl_error_alert($text)
{
    return die('<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2" viewBox="0 0 16 16" role="img" aria-label="Warning:">
    <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
  </svg>
   ' . $text . '
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>');
}
function nnl_error($text)
{
    return die('<script type="text/javascript">
    cuteToast({
        type: "error",
        message: "' . $text . '",
        timer: 5000
    });
    </script>');
}
function nnl_success($text)
{
    return die('<script type="text/javascript">
    cuteToast({
        type: "success",
        message: "' . $text . '",
        timer: 5000
    });
    </script>');
}

function nnl_success_time($text, $url, $time)
{
    return die('<script type="text/javascript">
    cuteToast({
        type: "success",
        message: "' . $text . '",
        timer: 5000
    });
    setTimeout(function(){ location.href = "' . $url . '" },' . $time . ');</script>');
}
function nnl_error_time($text, $url, $time)
{
    return die('<script type="text/javascript">
    cuteToast({
        type: "error",
        message: "' . $text . '",
        timer: 5000
    });
    setTimeout(function(){ location.href = "' . $url . '" },' . $time . ');</script>');
}
// display online

