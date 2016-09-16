<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Payment extends CI_Model {
    var $uid = -1;
    var $suptotal = 0;
    var $ordertotal = 0;
    var $pay_last = 0;
    var $shipping_datas = array();
    var $arrPromotions = array();
    var $arr_items_shippings = array();
    var $arr_Manufacturers = array();

    var $tax = 0;
    var $tax_persen = 0;
    var $tax_array = array();

    var $shippingfee = 0;
    var $okey = '';
    var $order_number = 0;
    var $r_ordernum = 'XXXXX';
    var $roles = array();
    var $total_commission = 0;
    var $sale_rep_obj;
    var $customerProfileId = '';
    var $customerPaymentProfileId = '';
    var $customerAddressId = '';
    var $mail_body_admin = '';
    var $mail_body_client = 'Thank you for ordering at Bellavie Network. Listed below is your order information. Please save this email until you receive your order.';
    var $schedule_delivery = array();
    var $check_commission = 0;

    var $card4digis = '';

    var $arrCharities_notruct = array();
    var $arrCharities_truct = array();

    var $check_donate = false;
    var $check_voucher = false;

    var $mail_bella = '';
    var $sendname_bella = '';

    var $qrcodeName = '';
    var $qrcodeVoucher = '';
    var $arr_pdf_vouchers = array();

    var $billing_fName = '';
    var $billing_lName = '';
    var $billing_Email = '';
    var $billing_Address = '';
    var $billing_City = '';
    var $billing_State = '';
    var $billing_Zip = '';
    var $billing_Country= 'US';
    var $billing_Phone = '';

    var $shipping_Name = '';
    var $shipping_Address = '';
    var $shipping_City = '';
    var $shipping_State = '';
    var $shipping_Zip = '';
    var $shipping_Country = 'US';
    var $shipping_Phone = '';
    var $shipping_Email = '';

    var $shipping_key = '';
    var $service_free_ship = '';

    var $non_member = 0;
    var $note  = "";

    var $today = '';
    var $paypal_settings = array();
    var $last_general_settup = array();
    var $honestgreen_setup = array();
    var $honestgreen_sent = 0;

    var $coupon_id = 0;
    var $coupon_code = '';
    var $coupon_amount = 0;

    function __construct($uid = NULL) {
        parent::__construct();

        $this->load->library("general");
        $this->last_general_settup = $this->general->getLastGeneralSetting();

        $this->today = gmdate("Y-m-d H:i:s");
        $this->getPaypal_settings();

        $this->getPOSTInfor();

        $this->load->library('shopcart');

        $this->checkProductType();

        $this->mail_bella = $this->lib->getMailInfor('site_info', 'email');
        $this->sendname_bella = $this->lib->getMailInfor('site_info', 'signature');

        $this->getMailBodyClient();

        if (isset($_POST["cc_Card_Number"]) && $_POST["cc_Card_Number"] != '') {
            $this->card4digis = substr($_POST["cc_Card_Number"], -4);
        }

        $this->getArrCharities();

        $this->roles = $this->author->objlogin->role;
        if (isset($this->author->objlogin->login) && $this->author->objlogin->login > 0) {
            $this->uid = $this->author->objlogin->uid;
        }

        $this->check_commission = (isset($_POST['check_commission']) && $_POST['check_commission'] == 1) ? 1 : 0;

    }

    function getPOSTInfor(){
        if(isset($_POST['billing_Email'])) $this->billing_Email = trim($_POST['billing_Email']);
        if(isset($_POST['billing_Name'])) $this->billing_fName = trim($_POST['billing_Name']);
        if(isset($_POST['billing_LastName'])) $this->billing_lName = trim($_POST['billing_LastName']);
        if(isset($_POST['billing_Address'])) $this->billing_Address = trim($_POST['billing_Address']);
        if(isset($_POST['billing_City'])) $this->billing_City = trim($_POST['billing_City']);
        if(isset($_POST['billing_State'])) $this->billing_State = trim($_POST['billing_State']);
        if(isset($_POST['billing_Zip'])) $this->billing_Zip = trim($_POST['billing_Zip']);
        if(isset($_POST['billing_Country'])) $this->billing_Country = trim($_POST['billing_Country']);
        if(isset($_POST['billing_Phone'])) $this->billing_Phone = trim($_POST['billing_Phone']);

        if(isset($_POST['shipping_Name'])) $this->shipping_Name = trim($_POST['shipping_Name']);
        if(isset($_POST['shipping_Address'])) $this->shipping_Address = trim($_POST['shipping_Address']);
        if(isset($_POST['shipping_City'])) $this->shipping_City = trim($_POST['shipping_City']);
        if(isset($_POST['shipping_State'])) $this->shipping_State = trim($_POST['shipping_State']);
        if(isset($_POST['shipping_Zip'])) $this->shipping_Zip = trim($_POST['shipping_Zip']);
        if(isset($_POST['shipping_Country'])) $this->shipping_Country = trim($_POST['shipping_Country']);
        if(isset($_POST['shipping_Phone'])) $this->shipping_Phone = trim($_POST['shipping_Phone']);
        if(isset($_POST['shipping_Email'])) $this->shipping_Email = trim($_POST['shipping_Email']);

        if(isset($_POST['tax_persen']) && is_numeric($_POST['tax_persen']) && $_POST['tax_persen'] > 0) $this->tax_persen = floatval($_POST['tax_persen']);
        if(isset($_POST['tax_array']) && is_array($_POST['tax_array'])) $this->tax_array = $_POST['tax_array'];

        if(isset($_POST['note'])) $this->note = $this->lib->escape($_POST['note']);

        if(isset($_POST['shipping_key'])) $this->shipping_key = $_POST['shipping_key'];
        if(isset($_POST['service_free_ship'])) $this->service_free_ship = $_POST['service_free_ship'];
        //    if(isset($_POST['shipping_fee']) && is_numeric($_POST['shipping_fee']) && $_POST['shipping_fee'] > 0) $this->shippingfee = floatval($_POST['shipping_fee']);

        if(isset($_POST['coupon_code'])) $this->coupon_code = $this->lib->escape($_POST['coupon_code']);
    }

    function checkProductType(){
        $PRODUCT_REGULAR = false;
        $PRODUCT_VOUCHER = false;
        $PRODUCT_SERVICE = false;

        $arr_vendors = isset($_SESSION['__manufacturers__']) ? $_SESSION['__manufacturers__'] : array();
        foreach($arr_vendors as $vendor){
            for ($j = 0; $j < count($vendor['items']); $j++) {
                if($vendor['items'][$j]['product_type'] == PRODUCT_REGULAR){
                    $PRODUCT_REGULAR = true;
                    break;
                }
                if($vendor['items'][$j]['product_type'] == PRODUCT_VOUCHER){
                    $PRODUCT_VOUCHER = true;
                }
                if($vendor['items'][$j]['product_type'] == PRODUCT_SERVICE){
                    $PRODUCT_SERVICE = true;
                }
            }
        }
        if(!$PRODUCT_REGULAR){
            if($PRODUCT_VOUCHER) $this->check_voucher = true;
            elseif($PRODUCT_SERVICE) $this->check_donate = true;
        }
    }

    function getMailBodyClient() {
        $data = array(
            'first_name' => $this->billing_fName,
            'last_name' => $this->billing_lName
        );
        $this->mail_body_client = $this->system->parse_templace("shop/order_mails_body_client.htm", $data, true);
    }

    function pay() {
        //$__payment_name__ = $this->paypal_settings['payment_name'];
        $this->checkItemAvailable();

        $this->verifyCoupon();

        if ($this->roles['rid'] == Sale_Representatives) {
            $this->sale_rep_obj = new sale_rep($this->uid);
            if ($this->check_commission == 1) { // Paid by Wallet
                $this->total_commission = $this->sale_rep_obj->getTotalEarning();

                if($this->total_commission > $this->pay_last) $this->total_commission = $this->pay_last;

                $this->pay_last -= $this->total_commission;

                if($this->pay_last < 0) $this->pay_last = 0;
            }
        }

        $this->okey = generate_orderkey(10);

        if ($this->pay_last <= 0) {
            return $this->saveOrder();
        } else {
            return $this->authorizePayment();
        }
    }

    private function verifyCoupon(){
        if($this->coupon_code != ''){
            $this->load->library('coupon_lib');
            $coupon_array = $this->coupon_lib->verify_coupon($this->coupon_code, $this->suptotal);
            if(isset($coupon_array['error']) && $coupon_array['error'] != ''){
                $this->coupon_code = '';
            }else{
                $coupon_discount = (isset($coupon_array['coupon_discount']) && is_numeric($coupon_array['coupon_discount']) && $coupon_array['coupon_discount'] > 0) ? $coupon_array['coupon_discount'] : 0;
                $coupon_discount_type = isset($coupon_array['coupon_discount_type']) ? $coupon_array['coupon_discount_type'] : 0;
                if($coupon_discount > 0){
                    $this->coupon_id = isset($coupon_array['coupon_id']) ? $coupon_array['coupon_id'] : 0;

                    if($coupon_discount_type == 1){
                        $this->coupon_amount = $coupon_discount;
                    }else{
                        $this->coupon_amount = round($coupon_discount * $this->suptotal / 100, 2);
                    }
                    $this->pay_last -= $this->coupon_amount;
                    if($this->pay_last < 0) $this->pay_last = 0;
                }
            }
        }
    }

    function printError($error){
        echo "incorrect^".$error; exit;
    }

    function authorizePayment() {
        $auth_net_login_id = $this->paypal_settings['auth_login_id'];
        $auth_net_tran_key = $this->paypal_settings['auth_tran_key'];

        $shipToList_firstName = '';
        $shipToList_lastName = '';

        // Post credit Card info to authorize server
        if (!($this->check_donate || $this->check_voucher)) {
            if ($this->shipping_Name != '') {
                $shipping_Name = str_replace("  ", " ", $this->shipping_Name);
                $arr_ship_name = explode(" ", $shipping_Name);
                $shipToList_firstName = isset($arr_ship_name[0]) ? $arr_ship_name[0] : '';
                for ($i = 1; $i < count($arr_ship_name); $i++) {
                    $shipToList_lastName .= $arr_ship_name[$i] . ' ';
                }
                $shipToList_lastName = trim($shipToList_lastName);
            }
        }
        if(!class_exists("gwapi")) require('application/libraries/apimetrics.class.php');
        $gw = new gwapi;
        $gw->setLogin($auth_net_login_id, $auth_net_tran_key);
        $gw->setBilling(
            $this->billing_fName,       // First Name
            $this->billing_lName,       // Last Name
            "",                                 // Company
            $this->billing_Address,    // Address 1
            "",                                 // Address 2
            $this->billing_City,       // City
            $this->billing_State,      // State
            $this->billing_Zip,        // Zip code
            $this->billing_Country,    // Country
            $this->billing_Phone,      // Phone
            "",                                 // Fax
            $this->billing_Email,               // Email
            ""                                  // Website
        );

        $gw->setShipping(
            $shipToList_firstName, // First Name
            $shipToList_lastName,  // Last Name
            "",                                                 // Company
            $this->shipping_Address,                   // Address 1
            "",                                                 // Address 2
            $this->shipping_City,                       // City
            $this->shipping_State,                      // State
            $this->shipping_Zip,                       // Zipcode
            $this->shipping_Country,                   // Country
            $this->shipping_Email
        );
        $gw->setOrder($this->okey, "", $this->tax, $this->shippingfee, "", "");
        $r = $gw->doSale($this->pay_last, $_POST['cc_Card_Number'], sprintf('%02d', $_POST['cc_Card_Month']).substr($_POST['cc_Card_Year'], -2));

        $error = '';

        if($r == "1"){
            $this->r_ordernum = $gw->responses['transactionid'];
            return $this->saveOrder();
        }else{
            $error = "Your card is invalid! Please check your card information!";

            if(isset($gw->responses['response_code'])){
                switch($gw->responses['response_code']){
                    case '221':
                        $error = "Your card is invalid! Please check your card information!";
                        break;
                    default:
                        if(isset($gw->responses['responsetext']) && $gw->responses['responsetext'] != '') $error = $gw->responses['responsetext'];
                        break;
                }
            }
        }
        $this->printError($error);
    }

    function getManMail() {
        $re_1 = $this->db->query("SELECT items.uid FROM order_detais join items join orders on order_detais.itemid = items.itm_id and orders.orderid = order_detais.orderid  where orders.okey = '" . $this->okey . "'  order by order_detais.id ASC limit 0,1");
        $row = $re_1->row_array();
        $query = $this->db->query("SELECT users.* from users join manufacturers on manufacturers.uid = users.uid where users.uid= " . $row['uid']." limit 0,1");
        $row__ = $query->row_array();
        return $row__;
    }

    function saveOrder() {
        $this->generateQrcode();

        $this->savingOrder();
        if(!($this->check_donate && $this->check_voucher)) $this->save_service_tax();

        $this->generateOrder();

        $this->sendMailToClient();
        $this->sendMailToVendor();

        $data = array(
            'okey' => $this->okey,
            'billing_name' => $this->billing_fName . ' ' . $this->billing_lName,
            'signature' => $this->sendname_bella,
            'root_server' => __ROOT_SERVER__
        );
        $strContent = $this->system->parse_templace("shop/payment_complete.htm", $data, true);
        return $strContent;
    }

    function sendMailToVendor(){
        $this->load->library('email');
        $this->load->model('shop/invoices_model', 'invoices');

        for ($manu = 0; $manu < count($this->arr_Manufacturers); $manu++) {
            $this->invoices->setUid($this->arr_Manufacturers[$manu]['uid']);
            $data = $this->invoices->loadInvoicesData($this->okey);

            $template = '';

            switch($data['order_type']){
                case PRODUCT_REGULAR:
                    $template = 'shop/send_order_details_vendor.htm';
                    break;
                case PRODUCT_VOUCHER:
                    $template = 'shop/print_order_details_voucher.htm';
                    break;
                case PRODUCT_SERVICE:
                    $template = 'shop/print_donate_details.htm';
                    break;
            }

            $mailContent = $this->system->parse_templace($template, $data, true);

            $this->email->from($this->mail_bella, $this->sendname_bella);
            $this->email->bcc($this->mail_bella);

            $this->email->subject('Order from BellaVieNetwork, LLC.');
            $this->email->message($mailContent);

            if (isset($this->arr_Manufacturers[$manu]['receive_order_mail']) && $this->arr_Manufacturers[$manu]['receive_order_mail'] == 1) {
                $this->email->to($this->arr_Manufacturers[$manu]['mail']);
                $this->email->send();
            }
            if (isset($this->arr_Manufacturers[$manu]['account']) && is_array($this->arr_Manufacturers[$manu]['account']) && count($this->arr_Manufacturers[$manu]['account']) > 0) {
                foreach ($this->arr_Manufacturers[$manu]['account'] as $mail_employees) {
                    $this->email->to($mail_employees);
                    $this->email->send();
                }
            }

        }
    }

    function sendMailToClient(){
        $this->load->library('email');

        $this->load->model('shop/invoices_model', 'invoices');
        $data = $this->invoices->loadInvoicesData($this->okey);

        $template = '';

        switch($data['order_type']){
            case PRODUCT_REGULAR:
                $template = 'shop/print_order_details.htm';
                break;
            case PRODUCT_VOUCHER:
                $template = 'shop/print_order_details_voucher.htm';
                break;
            case PRODUCT_SERVICE:
                $template = 'shop/print_donate_details.htm';
                break;
        }

        $mailContent = $this->system->parse_templace($template, $data, true);

        $this->email->from($this->mail_bella, $this->sendname_bella);
        $this->email->to($this->billing_Email);
        if(strcasecmp($this->shipping_Email, $this->billing_Address) != 0) $this->email->cc($this->shipping_Email);
        $this->email->bcc($this->mail_bella);

        $this->email->subject('Order from BellaVieNetwork, LLC.');
        $this->email->message($mailContent);

        if(count($this->arr_pdf_vouchers) > 0){
            foreach($this->arr_pdf_vouchers as $file){
                if(is_file($file)) $this->email->attach($file);
            }
        }

        $this->email->send();
    }

    function save_service_tax(){
        $data = array(
            'okey' => $this->okey,
            'tax' => $this->tax,
            'date_save' => gmdate('Y-m-d H:i:s')
        );
        $this->db->insert("taxservice",$data);
    }

    function generateQrcode(){
        $this->load->library("qrcode");
        $this->qrcode->setOrderId($this->okey);

        $data = $this->encryption->encrypt('0|'.$this->okey);

        $this->qrcode->setData($data);
        $this->qrcodeName = $this->qrcode->createQrCode();
    }

    function generateQrcodeVoucher($voucher_key){
        $data = array(
            'vkey' => $voucher_key,
            'mkey' => isset($this->author->objlogin->repid) ? $this->author->objlogin->repid : $this->author->objlogin->ukey
        );

        $str = $this->encryption->encrypt(json_encode($data));

        $this->load->library("qrcode");
        $this->qrcode->setOrderId($voucher_key);
        $this->qrcode->setData($str);
        $this->qrcodeVoucher = $this->qrcode->createQrCode();
    }

    function getParentNameSelected($arr_parent){
        $st = '';

        if(is_array($arr_parent)){
            foreach($arr_parent as $parent){
                $arr = explode("|", $parent);
                $st .= '<br>'.$arr[1];
            }
        }

        return $st;
    }

    private function getPaypal_settings(){
        $sql = "select * from paypal_settings limit 0,1";
        $re = $this->db->query($sql);
        if ($re->num_rows() > 0) {
            $this->paypal_settings = $re->row_array();
        }

        $sql = "select * from honestgreen_setup limit 0,1";
        $re = $this->db->query($sql);
        if ($re->num_rows() > 0) {
            $this->honestgreen_setup = $re->row_array();
        }
    }

    private function saveOrderTXT($manufacturer_info){
        $this->db->update("orders", array('honestgreen'=>1), "orderid = ".$this->order_number);

        $address_2 = stristr($this->shipping_Address, "suite");
        if ($address_2 == false) {
            $address_2 = stristr($this->shipping_Address, "ste");
            if ($address_2 == false){
                $address_2 = stristr($this->shipping_Address, "Apt");
                if ($address_2 == false){
                    $address_2 = stristr($this->shipping_Address, "#");
                }
            }
        }

        $address_1 = str_replace($address_2, "", $this->shipping_Address);

        $customer_id = $manufacturer_info['PHI_warehouse'] == 1 ? $this->honestgreen_setup['id'] : $this->honestgreen_setup['id_hva'];

        $customer_order_info = $customer_id.',,'.$this->okey.$manufacturer_info['PHI_warehouse'].',,FIL,';
        $fulfillment = $this->shipping_Name.','.trim($address_1).','.trim($address_2).','.$this->shipping_City.','.$this->shipping_State.','.$this->shipping_Zip.','.$this->shipping_Phone.',A,5001';

        $products = '';
        foreach ($manufacturer_info['items'] as $item) {//1
            $products .= $item['itm_model'].',,'.$item['sum'].',,,'."\n";
        }

        $content_file = '1'."\n".$customer_order_info."\n".$fulfillment."\n".$products.'***EOF***';

        $src_file = FCPATH.'data/ftp_orders/'.$this->okey.$manufacturer_info['PHI_warehouse'].".txt";

        $fp = @fopen($src_file, "w");
        @fputs($fp, $content_file);
        @fclose($fp);

        if(is_file($src_file)){
            $activate_ship = (isset($this->last_general_settup['activate_ship']) && is_numeric($this->last_general_settup['activate_ship'])) ? intval($this->last_general_settup['activate_ship']) : 0;
            if($activate_ship == 0){
                if(!class_exists("upload_file")) include(FCPATH."application/libraries/ftp.inc.php");

                $order_user = $this->honestgreen_setup['order_user_HVA'];
                $order_pass = $this->honestgreen_setup['order_pass_HVA'];
                $order_url = $this->honestgreen_setup['order_url_HVA'];

                if($manufacturer_info['PHI_warehouse'] == 1){
                    $order_user = $this->honestgreen_setup['order_user'];
                    $order_pass = $this->honestgreen_setup['order_pass'];
                    $order_url = $this->honestgreen_setup['order_url'];
                }

                $myftp = new upload_file($order_user, $order_pass, $order_url);
                if($myftp->conn && $myftp->error == ''){
                    $status = $myftp->ftp_transaction ( $src_file, $this->okey.$manufacturer_info['PHI_warehouse'].".txt" );
                    $myftp->_close_connection ();
                    if($status){

                        //honestgreen_sent:: PHI:1 HVA:2 both:3
                        if($this->honestgreen_sent == 0){
                            if($manufacturer_info['PHI_warehouse'] == 1){
                                $this->honestgreen_sent = 1;
                            }else{
                                $this->honestgreen_sent = 2;
                            }
                        }else{
                            $this->honestgreen_sent = 3;
                        }

                        $this->db->update("orders", array('honestgreen_sent'=>$this->honestgreen_sent), "orderid = ".$this->order_number);
                    }
                }
            }
        }
    }

    function generateOrder() {
        $ups_rate_key = 'UPS-03';

        $this->load->library("address");

        $this->load->model('shop/save_commission', 'commission_obj');
        $this->commission_obj->setOrder($this->uid, $this->order_number);
        $this->commission_obj->save_commission_monthly();

        $templace = "shop/order_mails.htm";
        if ($this->check_donate) {
            $templace = "shop/order_donate.htm";
        } elseif ($this->check_voucher){
            $templace = "shop/order_mails_voucher.htm";
        }

        $data = array(
            'order_number'      => $this->okey,
            'order_date'        => gmdate("m/d/Y"),

            'billingName'       => $this->billing_fName . ' ' . $this->billing_lName,
            'billingAddress'    => $this->address->formatAddress($this->billing_Address, $this->billing_City, $this->billing_State, $this->billing_Zip, $this->billing_Country),
            'billingPhone'      => $this->billing_Phone,

            'shippingEmail'     => $this->shipping_Email,
            'shippingName'      => $this->shipping_Name,
            'shippingAddress'   => $this->address->formatAddress($this->shipping_Address, $this->shipping_City, $this->shipping_State, $this->shipping_Zip, $this->shipping_Country),
            'shippingPhone'     => $this->shipping_Phone,

            'card_number'       => $this->check_commission == 1 ? "My Wallet" : "XXXXX" . $this->card4digis,
            'qrcode'            => $this->system->URL_server__()."data/qrcode/".$this->qrcodeName
        );

        $strContent = $this->system->parse_templace($templace, $data, true);

        $arr = $this->lib->partitionString("<!--startRows-->", "<!--endRows-->", $strContent);
        $strHeader = $arr[0];
        $strRow = $arr[1];
        $strFooter = $arr[2];

        $shipping_label = (isset($this->shipping_datas['label']) && $this->shipping_datas['label'] != '') ? $this->shipping_datas['label'] : 'Shipping fee';

        for ($manu = 0; $manu < count($this->arr_Manufacturers); $manu++) {
            $subtotal_manu = 0;
            $ship_rate = 0;

            $honestgreen = 0;
            if($this->arr_Manufacturers[$manu]['honestgreen'] == 1){
                $honestgreen = 1;
            }

            $total_qty = 0;
            foreach ($this->arr_Manufacturers[$manu]['items'] as $item) {//1
                if ($item['product_type'] == PRODUCT_SERVICE || $item['product_type'] == PRODUCT_VOUCHER) {
                    continue;
                }
                $total_qty += $item['sum'];
            }
            $handling_per_item = $total_qty > 0 ? $this->arr_Manufacturers[$manu]['handling_fee'] / $total_qty : 0;

            foreach ($this->arr_Manufacturers[$manu]['items'] as $item) {//1
                $obj_cart_key = array();

                if($item['sample_product'] == 1){
                    if($this->checkSampleProductOrder($item['itm_id'])){
                        $this->printError('Limit 1-order per customer');
                    }
                }

                if($this->check_donate){
                    $obj_cart_key = isset($item['key']) && !empty($item['key']) ? json_decode($this->encryption->decrypt($item['key']), true) : array();
                }
                else $obj_cart_key = isset($item['k']) && !empty($item['k']) ? json_decode($this->encryption->decrypt($item['k']), true) : array();

                $product_type = $item['product_type'];

                $t_qty = $item['sum'];
                $qty_free = $item['free'];
                $item['sum'] -= $qty_free;

                $itm_key = $item['key'];
                $arr_pickup_id = (isset($item['pickup']) && is_array($item['pickup'])) ? $item['pickup'] : array();
                $arr_pickups = $this->shopcart->getPickupName($arr_pickup_id, $itm_key);

                $current_cost = (is_numeric($item['current_cost']) && $item['current_cost'] > 0) ? (float) $item['current_cost'] : 0;

                $attributes_str = '';
                $arr_attributes = isset($obj_cart_key['attributes']) ? $obj_cart_key['attributes'] : array();
                $attributes = $this->shopcart->loadAttributes($arr_attributes, $itm_key);

                if (count($attributes) > 0) {
                    for($at = 0; $at < count($attributes); $at++){
                        $attri = $attributes[$at];
                        $attribute_itm = '';
                        if(count($attri['value']) == 1 && $attri['value'][0]['qty'] == 1){
                            $attribute_itm = str_replace("\n", "<br>", $attri['value'][0]['name']);
                            if($attri['value'][0]['price'] > 0){
                                $current_cost += $attri['value'][0]['price'] * $attri['value'][0]['qty'];
                                $attribute_itm .= '&nbsp;(+$' . number_format($attri['value'][0]['price'], 2) . ')';
                            }
                        }else{

                            $attribute_itm = '<table class="table_attribute">';
                            foreach($attri['value'] as $value){
                                $price_att = '';
                                if($value['price'] > 0){
                                    $current_cost += $value['price'] * intval($value['qty']);
                                    $price_att = '&nbsp;(+$' . number_format($value['price'], 2) . ')';
                                }

                                $parent = '';
                                if(isset($value['parent']) && is_array($value['parent'])) $parent = $this->getParentNameSelected($value['parent']);

                                $attribute_itm .= '<tr><td align="left">'.$value['name'].$price_att.$parent.'</td><td align="right">'.$value['qty'].' Items</td></tr>';
                            }
                            $attribute_itm .= '</table>';
                        }
                        if($attribute_itm != '') $attributes_str .= '<br><b>' . $attri['label'] . ':</b><br>'.$attribute_itm;

                        $attributes[$at]['string'] = $attribute_itm;
                    }
                }

                $itm_price = $current_cost;

                $this->commission_obj->loadMarkup($item['key'], $item['itm_id']);
                $itm_price += $current_cost * $this->commission_obj->markup_percentage / 100;

                $file_ = $this->lib->__loadFileProduct__($item['itm_id'], 'thumb');
                $_filename = $file_['file'];

                $new_price = $itm_price = round($itm_price, 2);
                $new_current_cost = $current_cost = round($current_cost, 2);

                $default_product_rate_ = floatval($item['default_product_rate']);
                $default_product_rate_ += $handling_per_item;

                $default_product_rate_for_freeproduct = ($qty_free / $t_qty) * $default_product_rate_;

                $last_shipping = $default_product_rate_last = $default_product_rate_current = ($item['sum'] / $t_qty) * $default_product_rate_;

                $promotions_ = '';

                foreach ($this->arrPromotions as $promotions) {
                    if ($promotions['promo_type'] == 2 && $promotions['product_key'] == $itm_key) {

                        $promotions['trigger_qty'] = $item['sum'];
                        $this->saveOrderPromotion($promotions);

                    }
                    if ($promotions['itm_key'] == $itm_key) {//0
                        switch ($promotions['promo_type']) {
                            case 1:
                                $discount_str = '';
                                if ($promotions['discount_type'] == 0) {
                                    $new_price -= $itm_price * $promotions['discount'] / 100;
                                    if($promotions['apply_for'] == 1) $new_current_cost -= $current_cost * $promotions['discount'] / 100;
                                    $discount_str = number_format($promotions['discount']) . '%';
                                } else {
                                    $new_price -= round($promotions['discount'], 2);
                                    if($promotions['apply_for'] == 1) $new_current_cost -= round($promotions['discount'], 2);
                                    $discount_str = '$' . number_format($promotions['discount'], 2);
                                }

                                if($promotions['apply_for'] == 1){  // apply for vendor
                                    $promotions_ .= $this->system->parse_templace("shop/product_discount_temp.htm", array(
                                        'discount_str'  => $discount_str
                                    ), true);
                                }

                                $this->saveOrderPromotion($promotions);
                                break;
                            case 3:
                                if($this->shipping_key == $this->service_free_ship){
                                    $discount_str = '';
                                    if ($promotions['discount_type'] == 0) {
                                        $default_product_rate_last -= $default_product_rate_current * $promotions['discount'] / 100;

                                        if($promotions['apply_for'] == 1) $last_shipping -= $default_product_rate_current * $promotions['discount'] / 100;

                                        $discount_str = number_format($promotions['discount']) . '%';
                                    } else {
                                        $default_product_rate_last -= round($promotions['discount'], 2);

                                        if($promotions['apply_for'] == 1) $last_shipping -= round($promotions['discount'], 2);

                                        $discount_str = '$' . number_format($promotions['discount'], 2);
                                    }
                                    if($promotions['apply_for'] == 1){  // apply for vendor
                                        $promotions_ .= $this->system->parse_templace("shop/discount_shipping_temp.htm", array(
                                            'discount_str'  => $discount_str
                                        ), true);
                                    }

                                    $this->saveOrderPromotion($promotions);
                                }
                                break;
                            case 4:
                                $check_ok = false;
                                if($this->shipping_key == $this->service_free_ship){
                                    for ($i = 0; $i < count($promotions['countries']); $i++) {
                                        if ($promotions['countries'][$i]['code'] == $this->shipping_Country) {
                                            if (count($promotions['countries'][$i]['states']) > 0) {
                                                foreach ($promotions['countries'][$i]['states'] as $state_code) {
                                                    if ($state_code == $this->shipping_State) {
                                                        $check_ok = true;
                                                        break;
                                                    }
                                                }
                                            } else {
                                                $check_ok = true;
                                            }
                                            break;
                                        }
                                    }
                                }
                                if ($check_ok == true) {
                                    $default_product_rate_last = 0;
                                    if($promotions['apply_for'] == 1){
                                        $last_shipping = 0;
                                        $promotions_ .= $this->system->parse_templace("shop/free_shipping_temp.htm", array(), true);
                                    }

                                    $this->saveOrderPromotion($promotions);
                                }
                                break;
                        }
                    }//0
                }

                if ($new_price < 0) $new_price = 0;
                $new_price = round($new_price, 2);

                if ($new_current_cost < 0) $new_current_cost = 0;
                $new_current_cost = round($new_current_cost, 2);

                $str_pickup = '';
                if(count($arr_pickups) > 0){
                    $str_pickup .= '<br><fieldset class="my_fieldset"><legend>Pickup Locations</legend>';
                    foreach($arr_pickups as $pickup){
                        $str_pickup .= '<p><b>'.$pickup['name'].'</b><br>'.$pickup['location'].'</p>';
                    }
                    $str_pickup .= '</fieldset>';
                }

                // Row for manufactures
                $item_url = $this->system->parseURL("index.php/".SHOPURL.'/'.$item['itm_id'].'-'.$this->lib->seoname($item["itm_name"]));
                $desc_ = '<div style="clear:both"><p><a href="' . $item_url . '" style="font-weight:bold">' . $item["itm_name"] . '</a></p>';
                $desc_ .= '<p><b>Model: </b>' . $item["itm_model"].'</p>'. $attributes_str .$promotions_.$str_pickup. '</div>';
                $price_manu = round($new_current_cost * $item['sum'], 2);

                $t_strList = $strRow;
                $t_strList = str_replace("@img@", $this->system->URL_server__() . '/gallerypic/data/img/thumb/' . $_filename, $t_strList);
                $t_strList = str_replace("<!--Qty-->", number_format($item['sum']), $t_strList);
                $t_strList = str_replace("<!--desc-->", $desc_, $t_strList);
                $t_strList = str_replace("<!--price-->", number_format($current_cost, 2), $t_strList);
                $t_strList = str_replace("<!--total-->", number_format($price_manu, 2), $t_strList);

                $this->arr_Manufacturers[$manu]['rows'] .= $t_strList;

                $subtotal_manu += $price_manu;
                if ($item['product_type'] == PRODUCT_SERVICE || $item['product_type'] == PRODUCT_VOUCHER) {
                    $last_shipping = 0;
                    $default_product_rate_current = 0;
                    $default_product_rate_last = 0;
                }

                $vendor_shipping = $last_shipping;

                $last_shipping_for_free = $default_product_rate_for_freeproduct;

                if($item['fulfilled'] == 1){    // BVN ship
                    if($item['fulfilled_percent'] < 100){
                        $last_shipping -= $last_shipping * $item['fulfilled_percent'] / 100;
                        $last_shipping_for_free -= $last_shipping_for_free * $item['fulfilled_percent'] / 100;
                    }else{
                        $last_shipping = 0;
                        $last_shipping_for_free = 0;
                    }
                }else{  // Vendor Ship
                    if($item['fulfilled_percent'] < 100){
                        $last_shipping -= $last_shipping * (100 - $item['fulfilled_percent']) / 100;
                        $last_shipping_for_free -= $last_shipping_for_free * (100 - $item['fulfilled_percent']) / 100;
                    }
                }

                if($default_product_rate_last <= 0) $default_product_rate_last = 0;

                if($last_shipping <= 0) $last_shipping = 0;
                if($last_shipping_for_free <= 0) $last_shipping_for_free = 0;

                $ship_rate += $last_shipping;

                if($item['sum'] > 0){
                    $this->db->insert(
                        'order_detais', array(
                            'orderid' => $this->order_number,
                            'itemid' => $item['itm_id'],
                            'itemprice' => $new_price,
                            'last_itemprice' => $itm_price,
                            'current_cost' => ($item['nocost'] == 1 ? 0 : $new_current_cost),
                            'last_cost' => ($item['nocost'] == 1 ? 0 : $current_cost),
                            'quality' => $item['sum'],
                            'shipping_fee' => (is_numeric($default_product_rate_last) && $default_product_rate_last > 0) ? $default_product_rate_last : 0,
                            'last_shipping' => $last_shipping,
                            'vendor_shipping' => ($vendor_shipping > 0 ? $vendor_shipping : 0),
                            'tax_persend' => (isset($item['tax_persen']) && $item['tax_persen'] > 0) ? $item['tax_persen'] : 0,
                            'product_type' => $product_type,
                            'vender_uid' => $this->arr_Manufacturers[$manu]['uid'],
                            'honestgreen' => $honestgreen
                        )
                    );
                    $odetail = $this->db->insert_id();

                    if(is_numeric($odetail) && $odetail > 0){
                        $this->saveOrderPickup($odetail, $arr_pickups);

                        $this->createVoucher($item, $itm_price);

                        $this->updateInventory($item['itm_id'], $item['sum']);

                        $this->saveOrderAttribute($attributes, $odetail);

                        //-----Save commission -----//
                        $this->commission_obj->setOrderDetail($odetail);
                        $this->commission_obj->saveMemberCommission();
                        $this->commission_obj->savePersonalDiscount();
                        $this->commission_obj->saveCommissionCharity($this->arrCharities_notruct);
                        $this->commission_obj->saveCommissionTrustCharity($this->arrCharities_truct);
                        $this->commission_obj->saveCommissionEmployees();
                        $this->commission_obj->saveCommissionVendorRep($item['vendor_rep']);
                        $this->commission_obj->saveCreditMerchant();
                    }else{
                        $this->printError("There was an error in the implementation of this order. Please try again some other time !");
                    }
                }

                if($qty_free > 0){
                    $ship_rate += $last_shipping_for_free;

                    $this->db->insert(
                        'order_detais', array(
                            'orderid'       => $this->order_number,
                            'itemid'        => $item['itm_id'],
                            'itemprice'     => 0,
                            'last_itemprice'    => 0,
                            'current_cost'      => ($item['nocost'] == 1 || $item['apply_for'] == 1) ? 0 : $current_cost,
                            'last_cost'         => ($item['nocost'] == 1 || $item['apply_for'] == 1) ? 0 : $current_cost,
                            'quality'           => $qty_free,
                            'shipping_fee'      => $default_product_rate_for_freeproduct,
                            'last_shipping'     => $last_shipping_for_free,
                            'vendor_shipping'   => $default_product_rate_for_freeproduct,
                            'tax_persend'       => 0,
                            'product_type'      => $product_type,
                            'vender_uid'        => $this->arr_Manufacturers[$manu]['uid'],
                            'honestgreen'       => $honestgreen
                        )
                    );
                    $odetail = $this->db->insert_id();
                    if(is_numeric($odetail) && $odetail > 0) {
                        $this->saveOrderPickup($odetail, $arr_pickups);
//                            $this->createVoucher($item, $itm_price);
                        $this->updateInventory($item['itm_id'], $qty_free);
                    }
                }

            }//1

            if($honestgreen == 1){
                $this->saveOrderTXT($this->arr_Manufacturers[$manu]);
            }

            if ($this->check_donate || $this->check_voucher) {//insert code
                $ship_rate = 0;
            }

            $this->arr_Manufacturers[$manu]['shipping'] = round($ship_rate, 2);
            $this->arr_Manufacturers[$manu]['subtotal'] = round($subtotal_manu, 2);
        }

//        for ($manu = 0; $manu < count($this->arr_Manufacturers); $manu++) {
//            $strFooter_ = $strFooter;
//            $total_price = $this->arr_Manufacturers[$manu]['subtotal'] + $this->arr_Manufacturers[$manu]['tax'] + $this->arr_Manufacturers[$manu]['shipping'];
//            $strFooter_ = str_replace("<!--suptotal-->", number_format($this->arr_Manufacturers[$manu]['subtotal'], 2), $strFooter_);
//            $strFooter_ = str_replace("{order_total}", number_format($total_price, 2), $strFooter_);
//            $strFooter_ = str_replace('<!--Tax-->', number_format($this->arr_Manufacturers[$manu]['tax'], 2), $strFooter_);
//            $strFooter_ = str_replace('<!--ship_label-->', $shipping_label, $strFooter_);
//            $strFooter_ = str_replace('<!--shipping_fee-->', number_format($this->arr_Manufacturers[$manu]['shipping'], 2), $strFooter_);
//
//            $this->unshowMyWalleAccount($strFooter_);
//
//            $mail_content = $strHeader . $this->arr_Manufacturers[$manu]['rows'] . $strFooter_;
//            $mail_content = str_replace("{mail_content}", "Dear " . $this->arr_Manufacturers[$manu]['firstname'] . ' ' . $this->arr_Manufacturers[$manu]['lastname'] . "<br>" . $this->mail_body_admin, $mail_content);
//            if (isset($this->arr_Manufacturers[$manu]['receive_order_mail']) && $this->arr_Manufacturers[$manu]['receive_order_mail'] == 1) {
//                $this->lib->mail_simple($this->arr_Manufacturers[$manu]['mail'], "Customer's order.", $this->mail_bella, $this->sendname_bella, $mail_content);
//            }
//            if (isset($this->arr_Manufacturers[$manu]['account']) && is_array($this->arr_Manufacturers[$manu]['account']) && count($this->arr_Manufacturers[$manu]['account']) > 0) {
//                foreach ($this->arr_Manufacturers[$manu]['account'] as $mail_employees) {
//                    $this->lib->mail_simple($mail_employees, "Customer's order.", $this->mail_bella, $this->sendname_bella, $mail_content);
//                }
//            }
//        }

        unset($_SESSION['__manufacturers__']);
        $this->shopcart->delSessionCart();
    }

    private function saveOrderPickup($odetail, $arr_pickups){
        foreach($arr_pickups as $pickup){
            $orders_pickup = array(
                'order_detail_id' => $odetail,
                'pickup_id' => $pickup['id']
            );
            $this->db->insert("orders_pickup", $orders_pickup);
        }
    }

    private function saveOrderPromotion($promotions){
        $order_promotion = array(
            'order_key'     => $this->okey,
            'promo_key'     => $promotions['promo_code'],
            'promo_type'    => $promotions['promo_type'],
            'product_key'   => $promotions['product_key'],
            'minqty'        => $promotions['minqty'],
            'freeqty'       => $promotions['freeqty'],
            'trigger_qty'   => isset($promotions['trigger_qty']) ? $promotions['trigger_qty'] : 1,
            'manufacturer_id' => $promotions['uid'],
            'itm_key'       => $promotions['itm_key'],
            'discount_type' => $promotions['discount_type'],
            'discount'      => $promotions['discount'],
            'date_purchase' => strtotime($this->today),
            'apply_for'     => $promotions['apply_for']
        );
        $this->db->insert('orders_promotions', $order_promotion);
    }

    private function saveOrderAttribute($attributes, $odetail){
        if (count($attributes) > 0) {
            $dem__ = 0;
            foreach ($attributes as $attri) {
                $orders_attributes = array(
                    'odetail' => $odetail,
                    'label' => $attri['label'],
                    'name' => (isset($attri['string']) ? $this->lib->FCKToSQL($attri['string']) : ''),
                    'price' => 0,
                    'weight' => -$dem__
                );
                $this->db->insert('orders_attributes', $orders_attributes);
                $dem__ ++;
            }
        }
    }

    private function updateInventory($itm_id, $qty){
        $query = $this->db->query("select inventories,inventories_HVA,minimum_quantity from items where itm_id = ".$itm_id." limit 0,1");
        if($query->num_rows() > 0){
            $row = $query->row_array();
            $inventories = intval($row['inventories']);
            $inventories_HVA = intval($row['inventories_HVA']);

            $minimum_quantity = intval($row['minimum_quantity']);
            if($minimum_quantity <= 0) $minimum_quantity = 1;
            $qty *= $minimum_quantity;

            if($inventories < $qty){
                $inventories = 0;
                $qty -= $inventories;
                $inventories_HVA -= $qty;
                if($inventories_HVA < 0) $inventories_HVA = 0;
            }else{
                $inventories -= $qty;
            }

            $this->db->update("items", array('inventories' => $inventories, 'inventories_HVA'=>$inventories_HVA), "itm_id = " . $itm_id);
        }
    }

    private function createVoucher($item, $itm_price){

        $re = $this->db->query("select * from items where itm_id = ".$item['itm_id']." limit 0,1");
        if ($re->num_rows() > 0) {
            $row = $re->row_array();
            if($row['product_type'] == 2){
                $url_img = $this->system->URL_server__().'gallerypic/data/img/thumb_show/';
                $url_file =  $this->lib->__loadFileProduct__($item['itm_id']);
                $url_img .= $url_file['file'];

                $today = strtotime($this->today);
                $expiration_date = (int)$row['expiration_date'];
                $voucher_title = $row['itm_name'];
                $voucher_origin = $row['origin'];
                $voucher_content = $this->lib->SQLToFCK($row['itm_description']);

                $unit = 'day';
                switch ((int)$row['expiration_date_unit']) {
                    case 1:
                        $unit = 'day';
                        break;
                    case 30:
                        $unit = 'month';
                        break;
                    case 365:
                        $unit = 'year';
                        break;
                }
                $exp_date_int = strtotime('+ ' . $expiration_date . ' ' . $unit, $today);
                $voucher_expire = date('m/d/Y', $exp_date_int);

                for ($it = 0; $it < $item['sum']; $it++) {
                    $this->qrcodeVoucher = '';

                    $voucher_id = '2'.$this->lib->GeneralRandomNumberKey(9);
                    $re_key = $this->db->query("select voucher_id from voucher where voucher_id = '$voucher_id' limit 0,1");
                    while($re_key->num_rows() > 0){
                        $voucher_id = '2'.$this->lib->GeneralRandomNumberKey(9);
                        $re_key = $this->db->query("select voucher_id from voucher where voucher_id = '$voucher_id' limit 0,1");
                    }

                    $this->generateQrcodeVoucher($voucher_id);

                    $vc_id = $this->db->insert(
                        'voucher',
                        array(
                            'voucher_id' => $voucher_id,
                            'item_id' => $item['itm_id'],
                            'member_id' => isset($this->author->objlogin->repid) ? $this->author->objlogin->repid : $this->author->objlogin->ukey,
                            'order_id' => $this->order_number,
                            'price' => $itm_price,
                            'qrcode' => $this->qrcodeVoucher,
                            'create_date' => gmdate("Y-m-d H:i:s"),
                            'exp_date' => date('Y-m-d 23:59:59', $exp_date_int)
                        )
                    );
                    $vc_id = $this->db->insert_id();
                    if(is_numeric($vc_id) && $vc_id > 0){
                        $data_parse = array(
                            'order_date' =>  gmdate("m/d/Y"),
                            'member_id' => isset($this->author->objlogin->repid) ? $this->author->objlogin->repid : 'n/a',
                            'member_name' => $this->author->objlogin->firstname.' '.$this->author->objlogin->lastname,
                            'voucher_id' => $voucher_id,
                            'voucher_price' => number_format($itm_price, 2),
                            'qrcode' => '<img src="'.$this->system->URL_server__()."data/qrcode/".$this->qrcodeVoucher.'" border="0">',
                            'expired_date' => $voucher_expire,
                            'voucher_content' => "<b>".$voucher_title."</b><br>".$voucher_content,
                            'voucher_title' => $voucher_title,
                            'url_img' => $url_img,
                            'location' => $this->getLocations($row['itm_key'])
                        );
                        $mailcontent = $this->system->parse_templace("shop/vouchers.htm", $data_parse, true);

                        $file_pdf = 'data/voucher/Voucher_'.$voucher_id.'.pdf';
                        if(!class_exists("mPDF")) include("mpdf/mpdf.php");
                        $mpdf=new mPDF('c','A4');
                        $mpdf->list_indent_first_level = 0;
                        $mpdf->SetDisplayMode('fullpage');
                        $mpdf->WriteHTML($mailcontent);
                        $mpdf->Output($file_pdf);
                        $this->arr_pdf_vouchers[] = $file_pdf;

                        //    $this->lib->mail_simple($this->billing_Email, "Voucher Information.", $this->mail_bella, $this->sendname_bella, $mailcontent);
                    }

                }
            }
        }
    }

    private function getLocations($itm_key){
        $locations = array();
        //$re = $this->db->query("select location from items_locations where ikey = '".$itm_key."' order by location asc limit 0,5");
        $re = $this->db->query("select locations from orders_locations where item_key = '".$itm_key."' and oid  = '".$this->order_number."' order by locations asc");
        if($re->num_rows() > 0){
            foreach($re->result_array() as $row){
                if($row['locations'] != null && $row['locations'] != '') $locations[]['location_info'] = $row['locations'];
            }
        }
        return $locations;
    }

    private function getArrCharities() {
        $query = $this->db->query("select charities.legal_business_id,charities.trust from charities join users on charities.uid = users.uid where users.status = 1");
        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                if ($row['trust'] == 1)
                    $this->arrCharities_truct[] = $row['legal_business_id'];
                else
                    $this->arrCharities_notruct[] = $row['legal_business_id'];
            }
        }
    }

    function unshowMyWalleAccount(&$strFooter) {
        $arr_ = $this->lib->partitionString("{myWalle_account}", "{/myWalle_account}", $strFooter);
        $strFooter = $arr_[0] . $arr_[2];
    }

    function saveOrderDelivery() {
        if (isset($_POST['schedule_delivery']) && is_array($_POST['schedule_delivery']) && count($_POST['schedule_delivery']) > 0) {
            foreach ($_POST['schedule_delivery'] as $date) {
                $data = array(
                    'oid' => $this->order_number,
                    'schedule_date' => $date
                );
                $this->db->insert('orders_auto_delivery', $data);
            }
        }
    }

    function generateTemporary(){
        return '2'.generate_orderkey(9);
    }

    function savingOrder() {
        $data = array(
            'r_ordernum' => $this->r_ordernum,
            'okey' => $this->okey,
            'temporary' => $this->generateTemporary(),
            "shipping_name"     => $this->lib->escape($this->shipping_Name),
            "shipping_address"  => $this->lib->escape($this->shipping_Address),
            "shipping_city"     => $this->lib->escape($this->shipping_City),
            "shipping_state"    => $this->lib->escape($this->shipping_State),
            "shipping_zip"      => $this->lib->escape($this->shipping_Zip),
            "shipping_country"  => $this->lib->escape($this->shipping_Country),
            "shipping_phone"    => $this->lib->escape($this->shipping_Phone),
            'shipping_email'    => $this->lib->escape($this->shipping_Email),

            "billing_fname"     => $this->lib->escape($this->billing_fName),
            "billing_name"      => $this->lib->escape($this->billing_lName),
            "billing_address"   => $this->lib->escape($this->billing_Address),
            "billing_city"      => $this->lib->escape($this->billing_City),
            "billing_state"     => $this->lib->escape($this->billing_State),
            "billing_country"   => $this->lib->escape($this->billing_Country),
            "billing_zip"       => $this->lib->escape($this->billing_Zip),
            "billing_phone"     => $this->lib->escape($this->billing_Phone),
            "billing_email"     => $this->lib->escape($this->billing_Email),

            "order_tax" => $this->tax_persen,
            "shipping_fee" => 0, //$this->shipping_datas['handling_fee'],
            "shipping_key" => $this->shipping_key,
            "order_total" => $this->ordertotal,
            "order_date" => gmdate("Y-m-d H:i:s"),
            "user_id" => $this->uid,
            "card_number" => $this->card4digis,
            "customerPaymentProfileId" => $this->customerPaymentProfileId,
            "customerProfileId" => $this->customerProfileId,
            "customerAddressId" => $this->customerAddressId,
            "cc_month"  => is_numeric($_POST['cc_Card_Month']) ? $_POST['cc_Card_Month'] : 0,
            "cc_year"   => is_numeric($_POST['cc_Card_Year']) ? $_POST['cc_Card_Year'] : 0,
            'com_set_id' => $this->last_general_settup['id'],
            'qrcode'    => $this->qrcodeName,
            "paid_by"   => (($this->check_commission == 1 && $this->total_commission > 0) ? PAID_BY_WALLET : PAID_BY_CREDITCARD),
            "wallet"    => $this->total_commission,
            'non_member' => $this->non_member,
            'note' => $this->note
        );
        $this->db->insert('orders', $data);
        $this->order_number = $this->db->insert_id();

        if(is_numeric($this->order_number) && $this->order_number > 0){
            $qrcode_data = array(
                'type'  => 'order',
                'oid'   => $this->order_number,
                'okey'  => $this->okey,
                'uid'   => $this->uid
            );
            $data = $this->encryption->encrypt(json_encode($qrcode_data));
            $this->db->update("qrcode", array("data"=>$data), "code = '".$this->okey."'");

            if (!$this->check_donate && !$this->check_voucher) {
                $this->saveOrderHandling();
            }
            $this->saveOrderLocations();

            $this->savePayments();
            $this->activeAccount();
            $this->saveCoupon();
        }else{
            $this->printError("There was an error in the implementation of this order. Please try again some other time !");
        }

        return $this->order_number;
    }

    private function saveCoupon(){
        if($this->coupon_code != '' && $this->coupon_amount > 0){
            $coupon_used = array(
                'COUPON_ID' => $this->coupon_id,
                'COUPON_CODE'   => $this->coupon_code,
                'USER_ID'   => $this->uid,
                'ORDER_ID'  => $this->order_number,
                'amount'    => $this->coupon_amount
            );
            $this->db->insert("coupon_used", $coupon_used);
        }
    }

    function saveOrderLocations() {
        $cart = $this->shopcart->getSessionCart();

        $locations = (isset($cart->locations) && is_array($cart->locations)) ? $cart->locations : array();
        if (count($locations) > 0) {
            foreach ($locations as $items) {
                if (count($items) > 0) {
                    foreach ($items as $item) {
                        $orders_locations = array(
                            'item_key' => $item['attributes'],
                            'qty' => $item['quantity'],
                            'locations' => $item['location'],
                            'oid' => $this->order_number
                        );
                        $this->db->insert("orders_locations", $orders_locations);
                    }
                }
            }
        }
    }

    function savePayments() {
        if ($this->total_commission > 0) {
            $legal_business_id = $this->sale_rep_obj->getLegalBusinessID();
            $legal_business_name = $this->sale_rep_obj->getLegalBusinessName();

            $pay_key = $this->lib->GeneralRandomKey(20);
            $re_key = $this->db->query("select id from payments where pkey = '$pay_key' limit 0,1");
            while($re_key->num_rows() > 0){
                $pay_key = $this->lib->GeneralRandomKey(20);
                $re_key = $this->db->query("select id from payments where pkey = '$pay_key' limit 0,1");
            }

            $datas = array(
                'pkey' => $pay_key,
                'role' => Sale_Representatives,
                'legal_business_id' => $legal_business_id,
                'legal_business_name' => $legal_business_name,
                'pay' => $this->total_commission,
                'date_pay' => gmdate("Y-m-d H:i:s"),
                'pay_type' => payByMyWallet
            );
            $this->db->insert("payments", $datas);
        }
    }

    function saveOrderHandling() {
        $arr_uid = array();

        for ($m = 0; $m < count($this->arr_Manufacturers); $m++) {//0
            if(!in_array($this->arr_Manufacturers[$m]['uid'], $arr_uid)){
                $arr_uid[] = $this->arr_Manufacturers[$m]['uid'];
                $orders_handling = array(
                    'oid' => $this->order_number,
                    'uid' => $this->arr_Manufacturers[$m]['uid'],
                    'handling' => $this->arr_Manufacturers[$m]['handling_fee'],
                    'ship_status' => PENDING
                );
                $this->db->insert('orders_handling', $orders_handling);
            }
        }
    }

    function activeAccount() {
        $this->db->update('representatives', array('purchase_active' => 1), "uid = " . $this->uid);
    }

    function loadItemsShippingsAndPromotions() {
        $arr_items = array();

        for ($m = 0; $m < count($this->arr_Manufacturers); $m++) {//0
            for ($it = 0; $it < count($this->arr_Manufacturers[$m]['items']); $it++) {
                $item = $this->arr_Manufacturers[$m]['items'][$it];

                $item['sum'] = $item['sum'] - $item['free'];
                if($item['sum'] <= 0) continue;

                $itm_key = $item['key'];
                $itm_id = $item['itm_id'];

                $amount = floatval($item['current_cost']);
                $amount += round($amount * floatval($item['markup_percentage']) / 100, 2);

                if(isset($arr_items[$itm_id])) $arr_items[$itm_id]['qty'] += $item['sum'];
                else $arr_items[$itm_id] = array(
                    'key' => $itm_key,
                    'qty' => $item['sum'],
                    'uid' => intval($item['uid']),
                    'amount' => $amount,
                    'itm_name' => $item['itm_name'],
                    'itm_id' => $itm_id
                );
            }
        }

        foreach($arr_items as $itm_id => $arr){
            $this->shopcart->loadPromotionsObject($this->arrPromotions, $arr['key'], $arr['qty']);
        }

        $this->shopcart->getBundles($arr_items, $this->arrPromotions);
    }

    function loadShippingData() {
        $USPS_domestic_services =  $this->config->item('USPS_domestic_services');
        if(isset($USPS_domestic_services[$this->shipping_key])) $this->shipping_datas = $USPS_domestic_services[$this->shipping_key];
    }

    function checkItemAvailable() {

        if ($this->check_voucher || $this->check_donate) {//insert code
            $this->loadManufacturers();
        } else {
            $this->loadShippingData();
            $this->loadManufacturers();
        }

        $this->loadItemsShippingsAndPromotions();

        $this->suptotal = 0;
        $this->tax = 0;
        $this->shippingfee = 0;

        for ($m = 0; $m < count($this->arr_Manufacturers); $m++) {//0

            $ship_rate = 0;

            $total_qty = 0;
            foreach ($this->arr_Manufacturers[$m]['items'] as $item) {//1
                if ($item['product_type'] == PRODUCT_SERVICE || $item['product_type'] == PRODUCT_VOUCHER) {
                    continue;
                }
                $total_qty += $item['sum'];
            }
            $handling_per_item = $total_qty > 0 ? $this->arr_Manufacturers[$m]['handling_fee'] / $total_qty : 0;

            for ($it = 0; $it < count($this->arr_Manufacturers[$m]['items']); $it++) {
                $item = $this->arr_Manufacturers[$m]['items'][$it];
                $itm_key = $item['key'];

                $qty_free = $item['free'];
                $item['sum'] = $item['sum'] - $item['free'];

                $default_product_rate = $item['default_product_rate'];
                $default_product_rate += $handling_per_item;

                if ($item['product_type'] == PRODUCT_SERVICE || $item['product_type'] == PRODUCT_VOUCHER) {
                    $default_product_rate = 0;
                }

                $default_product_rate_last = $default_product_rate_current = $default_product_rate;

                if ($qty_free > 0){
                    $ship_rate += $default_product_rate_last;
                }

                if($item['sum'] <= 0) continue;

                $amount = floatval($item['current_cost']);
                $amount += round($amount * floatval($item['markup_percentage']) / 100, 2);

                $obj_cart_key = (isset($item['k']) && !empty($item['k'])) ? json_decode($this->encryption->decrypt($item['k']), true) : array();
                $arr_attributes = isset($obj_cart_key['attributes']) ? $obj_cart_key['attributes'] : array();
                $attributes = $this->shopcart->loadAttributes($arr_attributes, $itm_key);
                foreach($attributes as $attribute){
                    foreach($attribute['value'] as $value){
                        $amount += $value['price'] * $value['qty'];
                    }
                }

                $amount_last = $amount;

                foreach ($this->arrPromotions as $promotions) {//4
                    if ($promotions['itm_key'] == $itm_key) {//5
                        switch ((int) $promotions['promo_type']) {//6
                            case 1:
                                if ($promotions['discount_type'] == 0) {
                                    $amount_last -= $amount * $promotions['discount'] / 100;
                                } elseif ($promotions['discount_type'] == 1) {
                                    $amount_last -= round($promotions['discount'], 2);
                                }
                                break;
                            case 3:
                                if($this->shipping_key == $this->service_free_ship){
                                    if ($promotions['discount_type'] == 0) {
                                        $default_product_rate_last -= $default_product_rate_current * (float) $promotions['discount'] / 100;
                                    } elseif ($promotions['discount_type'] == 1) {
                                        $default_product_rate_last -= round($promotions['discount'], 2);
                                    }
                                }
                                break;
                            case 4:
                                $check_ok = false;
                                if($this->shipping_key == $this->service_free_ship){
                                    for ($i = 0; $i < count($promotions['countries']); $i++) {
                                        if ($promotions['countries'][$i]['code'] == $this->shipping_Country) {
                                            if (count($promotions['countries'][$i]['states']) > 0) {
                                                foreach ($promotions['countries'][$i]['states'] as $state_code) {
                                                    if ($state_code == $this->shipping_State) {
                                                        $check_ok = true;
                                                    }
                                                }
                                            } else {
                                                $check_ok = true;
                                            }
                                            break;
                                        }
                                    }
                                }
                                if ($check_ok == true) {
                                    $default_product_rate_last = 0;
                                }
                                break;
                        }//6
                    }//5
                }//4

                if ($default_product_rate_last <= 0) $default_product_rate_last = 0;

                $ship_rate += $default_product_rate_last;

                if ($amount_last < 0) $amount_last = 0;
                $amount_last = round($amount_last, 2);
                $total_amount_last = round($amount_last * $item['sum'], 2);

                $this->suptotal += $total_amount_last;

                $tax_persen = 0;
                if($item['taxable'] == 1 && $item['product_type'] == PRODUCT_REGULAR && count($item['pickup']) == 0){
                    $tax_persen = $this->tax_persen;
                }
                $this->arr_Manufacturers[$m]['items'][$it]['tax_persen'] = $tax_persen;

            }//1

            $this->shippingfee += $ship_rate;
        }//0

        if ($this->suptotal < 0) $this->suptotal = 0;
        $this->suptotal = round($this->suptotal, 2);

        $this->calTax();

        $this->shippingfee = round($this->shippingfee, 2);

        if ($this->check_voucher || $this->check_donate){
            $this->shippingfee = 0;
            $this->tax = 0;
        }

        $this->ordertotal = round($this->suptotal + $this->tax + $this->shippingfee, 2);

        $this->pay_last = $this->ordertotal;

        return $this->ordertotal;
    }

    private function calTax(){
        foreach($this->tax_array as $tax){
            $this->tax += floatval($tax['tax_item_total']);
        }
        if($this->tax > 0) $this->tax = round($this->tax, 2);
    }

    function loadManufacturers() {
        $this->arr_Manufacturers = (isset($_SESSION['__manufacturers__']) && is_array($_SESSION['__manufacturers__'])) ? $_SESSION['__manufacturers__'] : array();
        for ($i = 0; $i < count($this->arr_Manufacturers); $i++) {
            $re = $this->db->query("select mail,firstname,lastname from users where uid = " . $this->arr_Manufacturers[$i]['uid']." limit 0,1");
            if ($re->num_rows() > 0) {
                $row = $re->row_array();
                $this->arr_Manufacturers[$i]['mail'] = $row['mail'];
                $this->arr_Manufacturers[$i]['firstname'] = $row['firstname'];
                $this->arr_Manufacturers[$i]['lastname'] = $row['lastname'];

                $receive_order_mail = 0;
                $loadAccessUser = $this->author->loadAccessUser($this->arr_Manufacturers[$i]['uid']);
                if($this->author->isAccessPerm("orders", "receive_order_mail", $loadAccessUser)){
                    $receive_order_mail = 1;
                }
                $this->arr_Manufacturers[$i]['receive_order_mail'] = $receive_order_mail;

                $account = array(); // Danh sach employees
                $vendor_id = 0;
                $honestgreen = 0;

                $query_1 = $this->db->query("select mid,data_xml from manufacturers where uid = ".$this->arr_Manufacturers[$i]['uid']." limit 0,1");
                if($query_1->num_rows() > 0){
                    $row_1 = $query_1->row_array();
                    $vendor_id = intval($row_1['mid']);

                    if (!empty($row_1['data_xml'])) {
                        $data_xml = unserialize($row_1['data_xml']);
                        if(isset($data_xml['honestgreen']) && $data_xml['honestgreen'] == 1) $honestgreen = 1;
                    }
                }

                if($vendor_id > 0){
                    $re_2 = $this->db->query("select users.mail,users.uid from users join manufacturers_users on manufacturers_users.uid = users.uid where users.status = 1 and manufacturers_users.vender_id = " . $vendor_id);
                    foreach ($re_2->result_array() as $row_2) {
                        $loadAccessUser = $this->author->loadAccessUser($row_2['uid']);
                        if($this->author->isAccessPerm("orders", "receive_order_mail", $loadAccessUser)){
                            $account[] = $row_2['mail'];
                        }
                    }
                }

                $this->arr_Manufacturers[$i]['honestgreen'] = $honestgreen;

                $this->arr_Manufacturers[$i]['account'] = $account;
                $this->arr_Manufacturers[$i]['rows'] = '';
                $this->arr_Manufacturers[$i]['subtotal'] = 0;
                $this->arr_Manufacturers[$i]['shipping'] = 0;
                $this->arr_Manufacturers[$i]['tax'] = 0;
                $this->arr_Manufacturers[$i]['handling_fee'] = 0;
            }

            for ($j = 0; $j < count($this->arr_Manufacturers[$i]['items']); $j++) {
                if($this->check_voucher || $this->check_donate) $this->arr_Manufacturers[$i]['items'][$j]['default_product_rate'] = 0;
                else $this->arr_Manufacturers[$i]['items'][$j]['default_product_rate'] = $this->get_default_product_rate($this->arr_Manufacturers[$i]['items'][$j]);

                $re = $this->db->query("SELECT itm_name,itm_model,current_cost,markup_percentage,uid,origin,nocost,inventories,inventories_HVA,coming_soon,fulfilled,fulfilled_percent,sample_product,vendor_rep FROM items WHERE itm_key LIKE '" . $this->arr_Manufacturers[$i]['items'][$j]['key'] . "' and itm_status <> -1 limit 0,1");
                if ($re->num_rows() > 0) {
                    $row = $re->row_array();

                    $inventories = intval($row['inventories']);

                    if($inventories < $this->arr_Manufacturers[$i]['items'][$j]['sum']){
                        $inventories = intval($row['inventories_HVA']);
                        if($inventories < $this->arr_Manufacturers[$i]['items'][$j]['sum']){
                            $this->printError('Product "'.$row['itm_name'].'" has sold out. Please remove this product from your cart.');
                        }
                    }

                    $minimum_quantity = $this->arr_Manufacturers[$i]['items'][$j]['minimum_quantity'];
                    if($row['coming_soon'] == 1){
                        $this->printError('Product "'.$row['itm_name'].'" invalid. Please remove this product from your cart.');
                    }else{
                        if($inventories < $minimum_quantity){
                            $this->printError('Product "'.$row['itm_name'].'" invalid. Please remove this product from your cart.');
                        }else{
                            $inventories = intval($inventories / $minimum_quantity);
                        }
                    }

                    $row['inventories'] = $inventories;

                    $row['fulfilled'] = intval($row['fulfilled']);
                    $row['fulfilled_percent'] = floatval($row['fulfilled_percent']);
                    $row['sample_product'] = intval($row['sample_product']);

                    if($row['sample_product'] == 1){
                        if($this->checkSampleProductOrder( $this->arr_Manufacturers[$i]['items'][$j]['itm_id'] )){
                            $this->printError('Limit 1-order per customer');
                        }
                    }

                    $this->arr_Manufacturers[$i]['items'][$j] = array_merge($this->arr_Manufacturers[$i]['items'][$j], $row);
                }
            }
        }
    }

    private function checkSampleProductOrder($itm_id){
        $query = $this->db->query("select order_detais.id from order_detais join orders on order_detais.orderid = orders.orderid where orders.user_id = ".$this->uid." and order_detais.itemid = ".$itm_id." and order_detais.Status NOT IN (".CANCELED.",".REFUNDED.") limit 0,1");
        if($query->num_rows() > 0){
            return true;
        }
        return false;
    }

    function get_default_product_rate($item) {
        $default_product_rate = 0;
        if (isset($item['ship_rate'][$this->shipping_key])){
            $default_product_rate = $item['ship_rate'][$this->shipping_key];
        }

        return $default_product_rate;
    }
    
}
