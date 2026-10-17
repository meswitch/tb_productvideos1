<?php
/**
 * Hello ThirtyBeeS - Example module for the ThirtyBeeS CMS.
 *
 * Demonstrates the basic structure of a ThirtyBeeS module:
 *  - installation / uninstallation
 *  - registering hooks
 *  - back-office configuration form (HelperForm)
 *  - rendering frontend output with a .tpl template
 *
 * @author Your Name <you@example.com>
 * @copyright ThirtyBeeS example
 * @license MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Hello_Thirtybees extends Module
{
    const HOOK_POSITION = 'displayHome';

    public function __construct()
    {
        $this->name = 'hello_thirtybees';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Your Name';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.0', 'max' => _PS_VERSION_);
        $this->bootstrap = false;

        parent::__construct();

        $this->displayName = $this->l('Hello ThirtyBeeS');
        $this->description = $this->l('Example module that displays a friendly greeting on the homepage.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');
    }

    /**
     * Install the module: register hooks and set default configuration.
     *
     * @return bool
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        // Default configuration value, editable in the module settings page
        Configuration::updateValue('HELLO_THIRTYBEES_MESSAGE', $this->l('Welcome to ThirtyBeeS!'));

        return $this->registerHook('displayHome')
            && $this->registerHook('header');
    }

    /**
     * Uninstall the module: remove configuration and let parent clean up.
     *
     * @return bool
     */
    public function uninstall()
    {
        Configuration::deleteByName('HELLO_THIRTYBEES_MESSAGE');

        return parent::uninstall();
    }

    /**
     * Module configuration page (shown in back office).
     *
     * @return string|null
     */
    public function getContent()
    {
        $output = null;

        if (Tools::isSubmit('submit' . $this->name)) {
            $message = (string) Tools::getValue('HELLO_THIRTYBEES_MESSAGE');

            if (!Validate::isCleanHtml($message)) {
                $output .= $this->displayError($this->l('Invalid message.'));
            } else {
                Configuration::updateValue('HELLO_THIRTYBEES_MESSAGE', $message);
                $output .= $this->displayConfirmation($this->l('Settings updated.'));
            }
        }

        return $output . $this->renderForm();
    }

    /**
     * Build the configuration form using HelperForm.
     *
     * @return string
     */
    protected function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Greeting message'),
                        'name' => 'HELLO_THIRTYBEES_MESSAGE',
                        'size' => 50,
                        'desc' => $this->l('This message is displayed on the homepage.'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit' . $this->name;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->fields_value['HELLO_THIRTYBEES_MESSAGE'] = Configuration::get('HELLO_THIRTYBEES_MESSAGE');

        return $helper->generateForm(array($fields_form));
    }

    /**
     * Hook: displayHome — render the greeting on the homepage.
     *
     * @return string
     */
    public function hookDisplayHome()
    {
        $this->context->smarty->assign(array(
            'message' => Configuration::get('HELLO_THIRTYBEES_MESSAGE'),
        ));

        return $this->display(__FILE__, 'hello_thirtybees.tpl');
    }

    /**
     * Hook: header — inject small stylesheet for the widget.
     *
     * @return string
     */
    public function hookHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/hello_thirtybees.css', 'all');

        return '';
    }
}
