<?php
/**
 * Product Video - ThirtyBeeS CMS module.
 *
 * Lets you attach a video (YouTube / Vimeo / Dailymotion / direct MP4/WebM/OGV)
 * to every product and displays it inside the product page image block,
 * as an extra "slide" next to the product images in the gallery.
 *
 * Features:
 *  - BO: video field on the product page (extra tab on AdminProducts)
 *  - FO: video rendered in the product image block via displayProductImages hook
 *  - Responsive oEmbed (iframe) for YouTube/Vimeo/Dailymotion, HTML5 <video> for direct files
 *
 * @author Your Name <you@example.com>
 * @license MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Product_Video extends Module
{
    /** Allowed video hosts / types */
    const TYPE_YOUTUBE = 'youtube';
    const TYPE_VIMEO = 'vimeo';
    const TYPE_DAILYMOTION = 'dailymotion';
    const TYPE_DIRECT = 'direct'; // .mp4/.webm/.ogv file

    public function __construct()
    {
        $this->name = 'product_video';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Your Name';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.0', 'max' => _PS_VERSION_);
        $this->bootstrap = false;

        parent::__construct();

        $this->displayName = $this->l('Product Video');
        $this->description = $this->l('Displays a video inside the product image block on the product page.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module? All product video settings will be lost.');
    }

    /* ------------------------------------------------------------------ */
    /*                          Install / Uninstall                       */
    /* ------------------------------------------------------------------ */

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        // Custom table that stores one video per product per shop
        if (!$this->executeSql('
            CREATE TABLE IF NOT EXISTS `' . pSQL($this->_getTableName()) . '` (
                `id_product` INT(11) UNSIGNED NOT NULL,
                `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 1,
                `video_url` VARCHAR(512) NOT NULL DEFAULT "",
                PRIMARY KEY (`id_product`, `id_shop`)
            ) ENGINE ' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;
        ')) {
            return false;
        }

        Configuration::updateValue('PRODUCT_VIDEO_POSITION', 'gallery'); // gallery | below

        return $this->registerHook('displayProductImages')
            && $this->registerHook('displayAdminProductsExtra')
            && $this->registerHook('actionAdminProductsControllerSaveBefore')
            && $this->registerHook('actionProductDelete')
            && $this->registerHook('header');
    }

    public function uninstall()
    {
        $this->executeSql('DROP TABLE IF EXISTS `' . pSQL($this->_getTableName()) . '`');
        Configuration::deleteByName('PRODUCT_VIDEO_POSITION');

        return parent::uninstall();
    }

    protected function _getTableName()
    {
        return $this->table; // product_video
    }

    protected function executeSql($sql)
    {
        return Db::getInstance()->execute($sql);
    }

    /* ------------------------------------------------------------------ */
    /*                     Back-office configuration                      */
    /* ------------------------------------------------------------------ */

    public function getContent()
    {
        $output = null;

        if (Tools::isSubmit('submitProductVideoSettings')) {
            $position = Tools::getValue('PRODUCT_VIDEO_POSITION');
            if (!in_array($position, array('gallery', 'below'))) {
                $position = 'gallery';
            }
            Configuration::updateValue('PRODUCT_VIDEO_POSITION', $position);
            $output .= $this->displayConfirmation($this->l('Settings updated.'));
        }

        return $output . $this->renderForm();
    }

    protected function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Product Video settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'select',
                        'label' => $this->l('Video position'),
                        'name' => 'PRODUCT_VIDEO_POSITION',
                        'desc' => $this->l('"In gallery" adds the video as a slide next to the product images. "Below images" places it under the image block.'),
                        'options' => array(
                            'query' => array(
                                array('id' => 'gallery', 'name' => $this->l('In gallery (as image slide)')),
                                array('id' => 'below', 'name' => $this->l('Below the images')),
                            ),
                            'id' => 'id',
                            'name' => 'name',
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitProductVideoSettings';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->fields_value['PRODUCT_VIDEO_POSITION'] = Configuration::get('PRODUCT_VIDEO_POSITION');

        return $helper->generateForm(array($fields_form));
    }

    /* ------------------------------------------------------------------ */
    /*          Per-product video form (tab on the AdminProducts page)    */
    /* ------------------------------------------------------------------ */

    /**
     * Adds a "Product Video" section to the product page in the back office.
     * Hook: displayAdminProductsExtra
     */
    public function hookDisplayAdminProductsExtra($params)
    {
        $id_product = (int) Tools::getValue('id_product');
        if ($id_product <= 0 && isset($params['id_product'])) {
            $id_product = (int) $params['id_product'];
        }

        $video = $this->getProductVideo($id_product);

        $this->context->smarty->assign(array(
            'pv_id_product' => $id_product,
            'pv_video_url' => $video['video_url'],
        ));

        return $this->fetch('views/templates/admin/video_tab.tpl');
    }

    /**
     * Save the video when the product form is submitted.
     * Hook: actionAdminProductsControllerSaveBefore
     */
    public function hookActionAdminProductsControllerSaveBefore($params)
    {
        if (!Tools::isSubmit('pv_save_product_video')) {
            return;
        }

        $id_product = (int) Tools::getValue('id_product');
        $url = trim((string) Tools::getValue('pv_video_url'));

        if ($id_product > 0 && $url !== '' && !$this->validateVideoUrl($url)) {
            $this->errors[] = sprintf(
                $this->l('Invalid video URL: %s. Use YouTube, Vimeo, Dailymotion or a direct .mp4/.webm/.ogv link.'),
                htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
            );
            return;
        }

        $this->setProductVideo($id_product, $url);
    }

    /* ------------------------------------------------------------------ */
    /*                         Data access layer                          */
    /* ------------------------------------------------------------------ */

    public function getProductVideo($id_product)
    {
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(sprintf('
            SELECT video_url
            FROM `%s`
            WHERE id_product = %d AND id_shop = %d
        ', pSQL($this->_getTableName()), (int) $id_product, (int) $this->context->shop->id));

        return array(
            'video_url' => $row ? $row['video_url'] : '',
        );
    }

    public function setProductVideo($id_product, $url)
    {
        $db = Db::getInstance();
        $url = pSQL($url);
        $exists = $db->getValue(sprintf(
            'SELECT COUNT(*) FROM `%s` WHERE id_product = %d AND id_shop = %d',
            pSQL($this->_getTableName()),
            (int) $id_product,
            (int) $this->context->shop->id
        ));

        if ($exists) {
            return $db->execute(sprintf(
                'UPDATE `%s` SET video_url = "%s" WHERE id_product = %d AND id_shop = %d',
                pSQL($this->_getTableName()), $url, (int) $id_product, (int) $this->context->shop->id
            ));
        }

        return $db->execute(sprintf(
            'INSERT INTO `%s` (id_product, id_shop, video_url) VALUES (%d, %d, "%s")',
            pSQL($this->_getTableName()), (int) $id_product, (int) $this->context->shop->id, $url
        ));
    }

    public function hookActionProductDelete($params)
    {
        $id_product = isset($params['id_product']) ? (int) $params['id_product'] : 0;
        if ($id_product) {
            Db::getInstance()->execute(sprintf(
                'DELETE FROM `%s` WHERE id_product = %d',
                pSQL($this->_getTableName()), $id_product
            ));
        }
    }

    /* ------------------------------------------------------------------ */
    /*                          URL parsing helpers                       */
    /* ------------------------------------------------------------------ */

    /**
     * Detects the video type from a URL. Returns false if unsupported.
     *
     * @param string $url
     *
     * @return string|false
     */
    public function getVideoType($url)
    {
        $url = strtolower(trim($url));

        if (preg_match('~^https?://(www\.)?(youtube\.com/watch|youtu\.be/|youtube\.com/embed/)~', $url)) {
            return self::TYPE_YOUTUBE;
        }
        if (preg_match('~^https?://(www\.)?vimeo\.com/\d+~', $url)) {
            return self::TYPE_VIMEO;
        }
        if (preg_match('~^https?://(www\.)?(dailymotion\.com/video|dai\.ly)/~', $url)) {
            return self::TYPE_DAILYMOTION;
        }
        if (preg_match('~^https?://.*\.(mp4|webm|ogv)(\?.*)?$~', $url)) {
            return self::TYPE_DIRECT;
        }

        return false;
    }

    public function validateVideoUrl($url)
    {
        return $this->getVideoType($url) !== false;
    }

    /** Extract the video id for embed providers. */
    protected function extractYoutubeId($url)
    {
        if (preg_match('~(?:v=|youtu\.be/|embed/)([a-zA-Z0-9_-]{6,})~', $url, $m)) {
            return $m[1];
        }

        return null;
    }

    protected function extractVimeoId($url)
    {
        if (preg_match('~vimeo\.com/(\d+)~', $url, $m)) {
            return $m[1];
        }

        return null;
    }

    protected function extractDailymotionId($url)
    {
        if (preg_match('~(?:video/|dai\.ly/)([a-zA-Z0-9]+)~', $url, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Builds the embed HTML for a given video URL.
     *
     * @param string $url
     * @param int    $width
     * @param int    $height
     *
     * @return string
     */
    public function renderVideoHtml($url, $width = 600, $height = 338)
    {
        switch ($this->getVideoType($url)) {
            case self::TYPE_YOUTUBE:
                $vid = $this->extractYoutubeId($url);
                if (!$vid) {
                    return '';
                }

                return sprintf(
                    '<iframe class="pv-embed" width="%d" height="%d" src="https://www.youtube-nocookie.com/embed/%s" frameborder="0" allow="accelerometer; autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>',
                    (int) $width, (int) $height, pSQL($vid)
                );

            case self::TYPE_VIMEO:
                $vid = $this->extractVimeoId($url);
                if (!$vid) {
                    return '';
                }

                return sprintf(
                    '<iframe class="pv-embed" width="%d" height="%d" src="https://player.vimeo.com/video/%s" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>',
                    (int) $width, (int) $height, pSQL($vid)
                );

            case self::TYPE_DAILYMOTION:
                $vid = $this->extractDailymotionId($url);
                if (!$vid) {
                    return '';
                }

                return sprintf(
                    '<iframe class="pv-embed" width="%d" height="%d" src="https://geo.dailymotion.com/player.html?video=%s" frameborder="0" allowfullscreen></iframe>',
                    (int) $width, (int) $height, pSQL($vid)
                );

            case self::TYPE_DIRECT:
                return sprintf(
                    '<video class="pv-video" controls preload="metadata" width="%d" height="%d" src="%s"></video>',
                    (int) $width, (int) $height, pSQL($url)
                );

            default:
                return '';
        }
    }

    /* ------------------------------------------------------------------ */
    /*                        Front-office hooks                          */
    /* ------------------------------------------------------------------ */

    /**
     * Injects the video into the product image block.
     * Hook: displayProductImages — fired by ThirtyBeeS inside the
     * product-images container of the product page.
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayProductImages($params)
    {
        $id_product = isset($params['id_product']) ? (int) $params['id_product'] : 0;
        if ($id_product <= 0) {
            return '';
        }

        $video = $this->getProductVideo($id_product);
        if (empty($video['video_url']) || !$this->validateVideoUrl($video['video_url'])) {
            return '';
        }

        $this->context->controller->addCSS($this->_path . 'views/css/product_video.css', 'all');

        $this->context->smarty->assign(array(
            'pv_video_html' => $this->renderVideoHtml($video['video_url']),
            'pv_video_position' => Configuration::get('PRODUCT_VIDEO_POSITION'),
        ));

        return $this->display(__FILE__, 'views/templates/hook/product_video.tpl');
    }

    /**
     * Loads the shared CSS on product & category pages.
     * Hook: header
     *
     * @return string
     */
    public function hookHeader($params)
    {
        $controller = get_class($this->context->controller);
        if (strpos($controller, 'ProductController') !== false
            || strpos($controller, 'CategoryController') !== false) {
            $this->context->controller->addCSS($this->_path . 'views/css/product_video.css', 'all');
        }

        return '';
    }
}
