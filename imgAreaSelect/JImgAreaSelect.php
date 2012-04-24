<?php
/**
 * Created by JetBrains PhpStorm.
 * User: yiqing
 * Date: 12-4-23
 * Time: 下午5:40
 *------------------------------------------------------------
 *                 _            _
 *                (_)          (_)
 *        _   __  __   .--. _  __   _ .--.   .--./)
 *       [ \ [  ][  |/ /'`\' ][  | [ `.-. | / /'`\;
 *        \ '/ /  | || \__/ |  | |  | | | | \ \._//
 *      [\_:  /  [___]\__.; | [___][___||__].',__`
 *       \__.'            |__]             ( ( __))
 *
 *--------------------------------------------------------------
 * To change this template use File | Settings | File Templates.
 */
class JImgAreaSelect extends CWidget
{
    /**
     * @static
     * @param bool $hashByName
     * @return string
     * return this widget assetsUrl
     */
    public static function getAssetsUrl($hashByName = false)
    {
        // return CHtml::asset(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'assets', $hashByName);
        return Yii::app()->getAssetManager()->publish(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'assets', $hashByName, -1, YII_DEBUG);
    }


    /**
     * @var string
     */
    public $baseUrl;

    /**
     * @var bool
     */
    public $debug = YII_DEBUG;

    /**
     * @var \CClientScript
     */
    protected $cs;

    /**
     * @var array|string
     * -------------------------
     * the options will be passed to the underlying plugin
     *   eg:  js:{key:val,k2:v2...}
     *   array('key'=>$val,'k'=>v2);
     * -------------------------
     */
    public $options = array();

    /**
     * @var bool
     * makes the selection area border animated
     */
    public $selectionAreaBorderAnimated = false;

    /**
     * @var string
     */
    public $selector;

    /**
     * @var string
     * used to assecc the api
     * @see  http://odyniec.net/projects/imgareaselect/usage.html
     */
    public $apiVarName;

    /**
     * @var string
     * specify a container for preview the selection area
     * this is for funny  carefully use it  (:
     */
    public $previewContainer;

    /**
     * @return JImgAreaSelect
     */
    public function publishAssets()
    {
        if (empty($this->baseUrl)) {
            $assetsPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'assets';
            if ($this->debug == true) {
                $this->baseUrl = Yii::app()->assetManager->publish($assetsPath, false, -1, true);
            } else {
                $this->baseUrl = Yii::app()->assetManager->publish($assetsPath);
            }
        }
        return $this;
    }


    /**
     * @return mixed
     */
    public function init()
    {

        parent::init();

        $this->cs = Yii::app()->getClientScript();
        // publish assets and register css/js files
        $this->publishAssets();
        // register necessary js file and css files
        $this->cs->registerCoreScript('jquery');

        if ($this->debug == true) {
            $this->registerScriptFile('scripts/jquery.imgareaselect.js', CClientScript::POS_HEAD);
        } else {
            $this->registerScriptFile('scripts/jquery.imgareaselect.pack.js', CClientScript::POS_HEAD);
        }

        if ($this->selectionAreaBorderAnimated == true) {
            $this->registerCssFile('css/imgareaselect-animated.css');
        } else {
            $this->registerCssFile('css/imgareaselect-default.css');
        }

        if (empty($this->selector)) {
            //just register the nessisary css and js files ; you want use it mannually
            return;
        }

        $apiVar = '';
        if (isset($this->apiVarName)) {
            $this->cs->registerScript(__CLASS__ . '#api_' . $this->getId(), " var {$this->apiVarName} ; //used for the imgAreaSelect ", CClientScript::POS_HEAD);
            $apiVar = " {$this->apiVarName} =  ";
            if (is_array($this->options)) {
                $this->options['instance'] = true;
            } else {
                $firstCurlyBracePos = strpos($this->options, '{');
                // $this->options = str_replace('{', '{ instance:true , ', $this->options);
                $this->options = substr_replace($this->options, ' instance:true , ', $firstCurlyBracePos + 1, 0);
                // die( "why".$this->options);
            }
        }
        //handle the selection preview
        $this->handleSelectionPreview();

        $options = empty($this->options) ? '' : CJavaScript::encode($this->options);

        $jsSetup = <<<JS_INIT
          {$apiVar} $("{$this->selector}").imgAreaSelect({$options});
JS_INIT;
        $this->cs->registerScript(__CLASS__ . '#' . $this->getId(), $jsSetup, CClientScript::POS_READY);

    }

    /**
     * @return JImgAreaSelect
     * @throws CException
     */
    public function handleSelectionPreview()
    {
        if (isset($this->previewContainer)) {

            $jsCode = <<<JS
  jQuery.fn.fitToParent = function () {
            this.each(function () {
                var width = $(this).width();
                var height = $(this).height();
                var parentWidth = $(this).parent().width();
                var parentHeight = $(this).parent().height();

                if (width / parentWidth < height / parentHeight) {
                    newWidth = parentWidth;
                    newHeight = newWidth / width * height;
                } else {
                    newHeight = parentHeight;
                    newWidth = newHeight / height * width;
                }
                margin_top = (parentHeight - newHeight) / 2;
                margin_left = (parentWidth - newWidth ) / 2;

                $(this).css({
                    'margin-top':margin_top + 'px',
                    'margin-left':margin_left + 'px',
                    'height':newHeight + 'px',
                    'width':newWidth + 'px'
                });
            });
        };
        var JImageAreaSelect = {
            previewImage : function (targetImg, selection) {
                var scaleX = 100 / (selection.width || 1);
                var scaleY = 100 / (selection.height || 1);

                $(targetImg).css({
                    width:Math.round(scaleX * 400) + 'px',
                    height:Math.round(scaleY * 300) + 'px',
                    marginLeft:'-' + Math.round(scaleX * selection.x1) + 'px',
                    marginTop:'-' + Math.round(scaleY * selection.y1) + 'px'
                });
            }

        };
JS;

            $img = CHtml::image('', 'img preview', array());
            $jsPreview = <<<JS_PREVIEW
               var \$previewImg = $('{$img}');
               \$previewImg.attr("src",$("{$this->selector}").attr("src"));
              $("{$this->previewContainer}").append(\$previewImg);
              // need fit to parent ?
JS_PREVIEW;

            if (is_array($this->options)) {
                if (!isset($this->options['onSelectChange'])) {
                    $this->options['onSelectChange'] = "js:function(sourceImg,selection){
                    var \$targetImg = $('{$this->previewContainer}').find('img');
                    JImageAreaSelect.previewImage(\$targetImg,selection);
                 } ";
                } else {
                    //   => js:function(){}|someHandlerName
                    /**
                     * donn't know how to settle these two situations ^_^
                     */
                }

            } else {
                throw new CException(" cann't handle the preview functionality ,
                 please do not use the string as options , array is ok!");
            }

            $this->cs->registerScript(__CLASS__ . '#jsPlugin', $jsCode, CClientScript::POS_END)
                ->registerScript(__CLASS__ . '#preview_' . $this->getId(), $jsPreview, CClientScript::POS_READY);

        }
        return $this;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        try {
            //shouldn't swallow the parent ' __set operation
            parent::__set($name, $value);
        } catch (Exception $e) {
            $this->options[$name] = $value;
        }
    }

    /**
     * @param $fileName
     * @param int $position
     * @return \JImgAreaSelect
     * @throws InvalidArgumentException
     */
    protected function registerScriptFile($fileName, $position = CClientScript::POS_END)
    {
        if (is_string($fileName)) {
            $jsFiles = explode(',', $fileName);
        } elseif (is_array($fileName)) {
            $jsFiles = $fileName;
        } else {
            throw new InvalidArgumentException('you must give a string or array as first argument , but now you give' . var_export($fileName, true));
        }
        foreach ($jsFiles as $jsFile) {
            $jsFile = trim($jsFile);
            $this->cs->registerScriptFile($this->baseUrl . '/' . ltrim($jsFile, '/'), $position);
        }
        return $this;
    }

    /**
     * @param $fileName   you can give many css files : 'xx.css','xx2.css',
     * @return \JImgAreaSelect
     * @throws InvalidArgumentException
     */
    protected function registerCssFile($fileName)
    {
        $cssFiles = func_get_args();
        foreach ($cssFiles as $cssFile) {
            if (is_string($cssFile)) {
                $cssFiles2 = explode(',', $cssFile);
            } elseif (is_array($cssFile)) {
                $cssFiles2 = $cssFile;
            } else {
                throw new InvalidArgumentException('you must give a string or array as first argument , but now you give' . var_export($cssFiles, true));
            }
            foreach ($cssFiles2 as $css) {
                $this->cs->registerCssFile($this->baseUrl . '/' . ltrim($css, '/'));
            }
        }
        // $this->cs->registerCssFile($this->assetsUrl . '/vendors/' .$fileName);
        return $this;
    }


}