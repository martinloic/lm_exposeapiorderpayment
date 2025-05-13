<?php

if (!defined('_PS_VERSION_')) {
  exit;
}

class Lm_ExposeApiOrderPayment extends Module {
    
    public function __construct() {
        $this->name = 'lm_exposeapiorderpayment';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Loïc MARTIN';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('LM - Expose API Order Payment');
        $this->description = $this->l('Extends the order webservice by adding the transaction UUID present in the message (LYRA).');
    }

    public function install() {
        return parent::install();
    }

    public function uninstall() {
        return parent::uninstall();
    }
}
