<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Captcha
 *
 * @copyright   ZhangTianJie All rights reserved.
 * @license     GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;

/**
 * 验证码 - Geetest 插件
 * Class PlgCaptchaGeetest
 */
class PlgCaptchaGeetest extends JPlugin
{
    protected $autoloadLanguage = true;
    protected $app;

    /**
     * 初始化验证码
     * @param string $id 默认的验证码 id
     * @return bool
     * @throws Exception 公钥或者私钥 不存在时 抛出异常
     */
    public function onInit($id = 'dynamic_geetest_1')
    {
        $document = JFactory::getDocument();
        JHtml::_('jquery.framework');
        //获取参数
        $public_key = $this->params->get('public_key', '');//公钥，ID
        $private_key = $this->params->get('private_key', '');//私钥，KEY
        $https = $this->params->get('https', 'false');//是否使用https请求
        $product = $this->params->get('product', 'embed');//展现形式，只对 PC 有效
        $lang = $this->params->get('lang', 'zh-cn');//界面显示语言
        $sandbox = $this->params->get('sandbox', 'false');//是否让验证码处在沙盒中，主要用于应对product为float时的情况
        $holdform = $this->params->get('holdform', 'false');//是否阻止表单提交
        //验证公钥和私钥
        if (empty($public_key) || empty($private_key)) {
            throw new Exception(JText::_('PLG_GEETEST_ERROR_NO_PUBLIC_KEY'));
        }
        //实例化库
        include_once __DIR__ . '/class.geetestlib.php';
        $GtSdk = new GeetestLib($public_key, $private_key);
        $status = $GtSdk->pre_process(array());
        $result = $GtSdk->get_response();
        $_SESSION['gtserver'] = $status;
        //定义js
        $js = '
        jQuery(document).ready(function (){
        if(typeof initGeetest==="function"){
            initGeetest({
                gt : "' . $result['gt'] . '",
                challenge : "' . $result['challenge'] . '",
                product : "' . $product . '",
                offline : !' . $result['success'] . ',
                lang : "' . $lang . '",
                https : ' . $https . ',
                sandbox : ' . $sandbox . ',
            },' . $id . '_Geetest_Callback);
            function ' . $id . '_Geetest_Callback(captchaObj){
                //将验证码加到 html 的元素里
                captchaObj.appendTo("#' . $id . '");
                //是否阻止表单提交
                if(' . $holdform . '){
                    var form_ele=jQuery("#' . $id . '").parentsUntil("form").parent();
                    //阻止表单提交
                    var form_submit=function(){return false;};
                    //节点生成完毕
                    captchaObj.onReady(function(){
                        jQuery("body").on("submit", form_submit, form_submit);
                    });
                    //验证成功
                    captchaObj.onSuccess(function(){
                        jQuery("body").off("submit", form_submit, form_submit);
                    });
                }
            }
        }
        });
       ';
        $document->addScriptDeclaration($js);
        $document->addScript('//static.geetest.com/static/tools/gt.js');
        return true;
    }

    /**
     * 设置验证码html表单
     * @param null $name
     * @param string $id 默认的验证码 id
     * @param string $class
     * @return string
     */
    public function onDisplay($name = null, $id = 'dynamic_geetest_1', $class = '')
    {
        return '<div id="' . $id . '"></div>';
    }

    /**
     * 进行验证码校验
     * @param null $code
     * @return bool 校验成功返回 true，校验失败返回 false
     * @throws Exception 钥或者私钥 不存在时 抛出异常
     */
    public function onCheckAnswer($code = null)
    {
        //获取参数
        $public_key = $this->params->get('public_key', '');//公钥，ID
        $private_key = $this->params->get('private_key', '');//私钥，KEY
        $geetest_challenge = $this->app->input->get('geetest_challenge', '', 'string');
        $geetest_validate = $this->app->input->get('geetest_validate', '', 'string');
        $geetest_seccode = $this->app->input->get('geetest_seccode', '', 'string');
        //验证公钥和私钥
        if (empty($public_key) || empty($private_key)) {
            throw new Exception(JText::_('PLG_GEETEST_ERROR_NO_PUBLIC_KEY'));
        }
        //实例化库
        include_once __DIR__ . '/class.geetestlib.php';
        $GtSdk = new GeetestLib($public_key, $private_key);
        if (isset($_SESSION['gtserver']) && $_SESSION['gtserver'] == 1) {
            $result = $GtSdk->success_validate($geetest_challenge, $geetest_validate, $geetest_seccode, array());
            if ($result == true) {
                $return = true;
            } else if ($result == true) {
                $this->_subject->setError(JText::_('PLG_GEETEST_ERROR_INVALID'));
                $return = false;
            } else {
                $this->_subject->setError(JText::_('PLG_GEETEST_ERROR_UNKNOWN'));
                $return = false;
            }
        } else {
            $result = $GtSdk->success_validate($geetest_challenge, $geetest_validate, $geetest_seccode, array());
            if ($result == true) {
                $return = true;
            } else {
                $this->_subject->setError(JText::_('PLG_GEETEST_ERROR_INVALID'));
                $return = false;
            }
        }
        return $return;
    }
}
