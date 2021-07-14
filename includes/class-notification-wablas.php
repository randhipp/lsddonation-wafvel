<?php
use LSDDonation\Notifications;

if (!defined('ABSPATH')) {
    exit;
}

class WhatsappWafvel extends Notifications\Notification_Base
{
    protected $id = 'lsdd_whatsapp_wafvel';
    protected $name = 'Wafvel';
    protected $type = 'whatsapp';
    protected $docs = array(
        'global' => 'https://learn.lsdplugins.com/docs/lsddonation/settings/whatsapp-notifications/',
        'id' => 'https://learn.lsdplugins.com/id/docs/lsddonasi/pengaturan/notifikasi-whatsapp/',
    );

    /**
     * Template when donors create donation
     *
     * @var string
     */
    protected $template_order = 'Kepada YTH Bpk/Ibu *{{donors}}*
Berikut ini Pesanan Anda :
{{program}}

Total Pembayaran :
{{nominal}}

Silahkan Selesaikan Pembayaran
{{payment}}

Salam Hangat
*LSDDonasi*';

    /**
     * Template when donors completed the payment
     *
     * @var string
     */
    protected $template_completed = 'Terimakasih *{{donors}}*
atas donasi yang telah Anda berikan
Donasi {{program}} akan kami sampaikan kepada orang-orang yang membutuhkan

*Semoga menjadi amal ibadah anda dan Tuhan memberi keberkahan*

Salam Hangat
*LSDDonasi*';

    /**
     * Template for following up, abandon donor
     *
     * @var string
     */
    protected $template_followup;


    protected $apikey;
    /**
     * Api Key for Credentials to Wafvel
     */
    protected $server;

    /**
     * Constructing Class
     */
    public function __construct()
    {
        $this->default_settings();

        // Setter Options
        $settings = get_option($this->id);

        $whatsapp_message = isset($settings['messages']) ? $settings['messages'] : array();
        $this->order_message = isset($whatsapp_message['order']) ? $whatsapp_message['order'] : '';
        $this->completed_message = isset($whatsapp_message['completed']) ? $whatsapp_message['completed'] : '';

        $whatsapp_settings = isset($settings['settings']) ? $settings['settings'] : array();
        $this->apikey = isset($whatsapp_settings['apikey']) ? $whatsapp_settings['apikey'] : '';
        $this->server = isset($whatsapp_settings['server']) ? $whatsapp_settings['server'] : 'https://api.wafvel.com';
        $this->server = str_replace( 'http://', '', $this->server );
        $this->server = str_replace( 'https://', '', $this->server );
        
        // Action for Test and Save
        add_action('wp_ajax_lsdd_whatsapp_wafvel_test', array($this, 'testing'));
        add_action('wp_ajax_lsdd_whatsapp_wafvel_save', array($this, 'save'));

        // Action for Templating Notification
        add_action('lsddonation/notification/processing', [$this, 'templating']);

        // $order = array(
        //     'event' => 'order',
        //     'phone' => '08561655028',
        //     'donors' => 'Lasida',
        //     'nominal' => 'Rp 150.000',
        //     'program' => 'Bantu Sesama',
        //     'payment' => 'Transfer Bank - BCA'
        // );
        // $this->templating( $order );

        // $completed = array(
        //     'event' => 'completed',
        //     'phone' => '08561655028',
        //     'donors' => 'Lasida',
        //     'program' => 'Bantu Sesama',
        // );
        // $this->templating( $completed );
    }

    /**
     * Setup Default Values
     *
     * @return void
     */
    public function default_settings()
    {
        /* Empty Settings -> Set Default Data */
        $settings = get_option($this->id);
        if (empty($settings)) {
            $new = array();
            $new['messages']['order'] = $this->template_order;
            $new['messages']['completed'] = $this->template_completed;
            $new['settings']['apikey'] = '';
            $new['settings']['server'] = '';
            update_option($this->id, $new);
        }

        if (empty($settings['messages']['completed'])) {
            $new = array();
            $new['messages']['completed'] = $this->template_completed;
            $new['messages']['order'] = $settings['messages']['order'];
            $new['settings']['apikey'] = $settings['settings']['apikey'];
            $new['settings']['server'] = $settings['settings']['server'];
            update_option($this->id, $new);
        }

        if (empty($settings['messages']['order'])) {
            $new = array();
            $new['messages']['order'] = $this->template_order;
            $new['messages']['completed'] = $settings['messages']['completed'];
            $new['settings']['apikey'] = $settings['settings']['apikey'];
            $new['settings']['server'] = $settings['settings']['server'];
            update_option($this->id, $new);
        }
    }

    /**
     * Templating Message From Hook
     * Get Template based on Event
     * Templating Data
     * Send Message
     *
     * @param array $data
     * @return void
     */
    public function templating( array $object )
    {
        if ($this->status()) {
            
            $object['payment'] = $object['payment_text'];
            unset($object['payment_text']);

            $settings = get_option($this->id);
            $whatsapp_message = isset($settings['messages']) ? $settings['messages'] : array();
            $template = $whatsapp_message[$object['event']];
            $phone = $object['phone'];

            // Check Template
            if(empty($template)){
                $this->log( empty($phone) ? 'Not Set' : $phone, 'On ' . ucfirst($object['event']), __('Tolong atur template terlebih dahulu', 'lsddonation-wafvel'));
                return;
            }
            
            // Checking Receiver
            if(empty($phone)){
                $this->log( empty($phone) ? 'Not Set' : $phone, 'On ' . ucfirst($object['event']), __('Donatur tidak menginput nomor telepon', 'lsddonation-wafvel'));
                return;
            }

            // Templating
            foreach ($object as $key => $item) {
                $template = str_replace("{{" . $key . "}}", $item, $template);
            }

            // Send Message
            if( $object['payment'] != false ){ // Notification Pattern not Palse
                $this->send(array('event' => $object['event'], 'receiver' => $phone, 'message' => $template));
            }
            
       
        }
    }

    /**
     * Log Notification
     * Implement contract form abstract
     *
     * @param string $reciever
     * @param string $event
     * @param string $message
     * @return void
     */
    public function log($reciever, $event, $message)
    {
        $db = get_option($this->id); /// Get Log
        $log = isset($db['log']) ? $db['log'] : array(); // Check Log

        // Auto Reset Log
        if (count($log) >= 30) {
            $log = array();
        }

        $log[] = array(lsdd_current_date(), $reciever, $event, $message); // Push New Log
        $db['log'] = $log; // Set Log

        // Saving Log
        update_option($this->id, $db);
    }

    /**
     * Send Message via REST API
     * Support Text
     * TODO :: Support Media 
     * TyghI8jIpsKbZvNnU4gGssUabv8flHCrqse0I5EepuJb2vycGJe8hIW0MvmU2vod
     * 
     * @since 4.0.0
     * @param array $data
     * @return void
     */
    public function send( array $obj )
    {

        $body = array(
            'token' => $this->apikey,
            'phone' => $obj['receiver'],
            'message' => $obj['message'],
            // 'type' =>'image',
            // 'media_url' => $image_url,
        );
     
        $payload = array(
            'method' => 'POST',
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'httpversion' => '1.0',
            'body' => json_encode($body),
            'cookies' => array(),
        );
        
        $response = wp_remote_post( esc_url( $this->server . "/api/whatsapp/async/send" ) , $payload);
        $response_back = json_decode(wp_remote_retrieve_body($response), TRUE );

        if (isset($response_back['status']) && $response_back['status'] == 200 ) {
            $this->log($obj['receiver'], 'On ' . ucfirst($obj['event']), $response_back['status']." | ".$response_back['queue_id']);
            return true;
        } else {
            $this->log($obj['receiver'], 'Failed !', $obj['message']);
            return false;
        }
    }

    /**
     * Saving Option :: AJAX
     *
     * @return void
     */
    public function save()
    {
        if (!check_ajax_referer('lsdd_admin_nonce', 'security')) {
            wp_send_json_error('Invalid security token sent.');
        }

        $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
        $type = $_REQUEST['type'] == 'order' ? 'order' : 'completed';
        $content = $_REQUEST['content'];

        // Saving Template
        $option = get_option($this->id);
        $option['messages'][$type] = $content;
        update_option($this->id, $option);

        echo 'action_success';
        wp_die();
    }

    /**
     * Testing Method :: AJAX Processing
     * 
     * @return void
     */
    public function testing()
    {
        if (!check_ajax_referer('lsdd_admin_nonce', 'security')) {
            wp_send_json_error('Invalid security token sent.');
        }

        $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
        $phone = esc_attr($_REQUEST['phone']);

        $args = array('event' => 'test', 'receiver' => $phone, 'message' => '*LSDDonation* :: Whatsapp Notification Test using Wafvel' );

        if ($this->send( $args )) {
            echo 200;
        }else{
            echo 400;
        }
        
        wp_die();
    }

    public function manage()
    {
        ?>
        <style>
            /* Action Tab */
            #tab-wafvel-log:checked~.tab-body-wrapper #tab-body-wafvel-log,
            #tab-wafvel-order:checked~.tab-body-wrapper #tab-body-wafvel-order,
            #tab-wafvel-completed:checked~.tab-body-wrapper #tab-body-wafvel-completed,
            #tab-wafvel-followup:checked~.tab-body-wrapper #tab-body-wafvel-followup,
            #tab-wafvel-settings:checked~.tab-body-wrapper #tab-body-wafvel-settings {
                position: relative;
                top: 0;
                opacity: 1;
            }

            .tab-body-wrapper .table-log th{
                display: inline-block;
            }

            .tab-body-wrapper .table-log tr{
                margin-bottom: 0;
            }

            .tab-body-wrapper .table-log tbody tr td{
                display: inline-block;
                padding: 10px;
            }

            .tab-body-wrapper .table-log.table td, .tab-body-wrapper .table-log.table th{
                border-bottom: 0;
            }
        </style>

        <style>
            #lsdd-editor{
                height:100%;
                margin-top: 20px;
            }

            .tab-body-wrapper label.fix{
                margin-top: 3px;font-weight: 600;float: left;padding: 5px 0 !important;font-size: 14px;
            }
        </style>

        <div class="tabs-wrapper">
            <input type="radio" name="wafvel" id="tab-wafvel-log" checked="checked"/>
            <label class="tab" for="tab-wafvel-log"><?php _e('Log', 'lsddonation');?></label>

            <input type="radio" name="wafvel" id="tab-wafvel-order"/>
            <label class="tab" for="tab-wafvel-order"><?php _e('Ketika Donasi', 'lsddonation');?></label>

            <input type="radio" name="wafvel" id="tab-wafvel-completed"/>
            <label class="tab" for="tab-wafvel-completed"><?php _e('Ketika Selesai', 'lsddonation');?></label>
            <!--
            <input type="radio" name="wafvel" id="tab-wafvel-followup"/>
            <label class="tab" for="tab4"><?php //_e('On FollowUp', 'lsddonation');?></label> -->

            <input type="radio" name="wafvel" id="tab-wafvel-settings"/>
            <label class="tab" for="tab-wafvel-settings"><?php _e('Settings', 'lsddonation');?></label>

            <div class="tab-body-wrapper">

                <!------------ Tab : Test and Log ------------>
                <div id="tab-body-wafvel-log" class="tab-body">

                    <a href="https://wafvel.com/" target="_blank" style="margin-top:3px;margin-bottom:15px;background:#b21919;border:none;width:150px;border-radius:20px;" class="btn btn-primary input-group-btn"><?php _e('Daftar Wafvel', "lsddonation");?></a>

                    <div class="divider" data-content="Test Notification"></div>
                    <div class="input-group" style="width:50%;">
                        <input id="lsdd_wafvel_test" style="margin-top:3px;" class="form-input input-md" type="text" placeholder="0812387621812">
                        <button id="lsdd_wafvel_test" style="margin-top:3px;" class="btn btn-primary input-group-btn"><?php _e('Test Notification', "lsddonation");?></button>
                    </div>

                    <br>

                    <div class="divider" data-content="Wafvel Notification Log"></div>
                    <table class="table-log table table-striped table-hover">
                        <tbody>
                        <?php $db = get_option($this->id);?>
                        <?php $log = isset($db['log']) ? $db['log'] : array();?>

                        <?php if ($log): ?>
                            <?php foreach (array_reverse($log) as $key => $value): ?>
                                <tr>
                                    <td><?php echo lsdd_date_format($value[0], 'j M Y, H:i:s'); ?></td>
                                    <td><?php echo json_encode($value[1]); ?></td>
                                    <td><?php echo $value[2]; ?></td>
                                    <td><?php echo $value[3]; ?></td>
                                </tr>
                            <?php endforeach;?>
                        <?php else: ?>
                            <tr><td><?php _e('Log Kosong', 'lsddonation');?></td></tr>
                        <?php endif;?>
                        </tbody>
                    </table>

                </div>

                <!------------ Tab : On Order ------------>
                <div id="tab-body-wafvel-order" class="tab-body">

                    <div class="columns col-gapless">
                        <!-- Email Instruction and Editor -->
                        <div class="column col-3" style="padding:0 10px 0 0;">

                            <div class="option-form">
                                <h6><?php _e('Replace Tag', 'lsddonation');?> : </h6>
                                <small>
                                    {{donors}} <code>John Doe </code><br>
                                    {{nominal}} <code><?php echo lsdd_currency_display("symbol") . lsdd_currency_display("format"); ?></code><br>
                                    {{program}} <code>Redefined Plastic</code>
                                    {{payment}} <code>Payment</code>
                                </small>
                            </div>
                            <br>
                            <button data-type="order" class="btn btn-primary input-group-btn lsdd_wafvel_templates_save"><?php _e('Save', 'lsddonation');?></button>
                        </div>


                        <!-- Email Preview -->
                        <div class="column col-8" style="margin-left:35px;">
                        
                            <?php if (!isset($this->order_message)): ?>
                                <div class="toast toast-error" style="width: 100%;margin: 10px auto;">
                                    <button class="btn btn-clear float-right"></button>
                                    <?php _e('Please adjust replace tag in your notification template', 'lsddonation');?>
                                </div>
                            <?php endif;?>
                            <!-- Migration Alert -->
                            <textarea data-event="order" class="form-input lsdd_wafvel_message_templates" placeholder="Pesan Notifikasi Untuk Donatur ketika Memesan" rows="14"><?php echo esc_attr($this->order_message); ?></textarea>
                        </div>

                    </div>
                </div>

                <!------------ Tab : On Completed ------------>
                <div id="tab-body-wafvel-completed" class="tab-body">

                    <div class="columns col-gapless">
                        <!-- Email Instruction and Editor -->
                        <div class="column col-3" style="padding:0 10px 0 0;">

                            <div class="option-form">
                                <h6><?php _e('Replace Tag', 'lsddonation');?> : </h6>
                                <small>
                                    {{donors}} <code>John Doe </code><br>
                                    {{program}} <code>Redefined Plastic</code>
                                </small>
                            </div>
                            <br>

                            <button data-type="completed" class="btn btn-primary input-group-btn lsdd_wafvel_templates_save"><?php _e('Save', 'lsddonation');?></button>
                        </div>

                        <div class="column col-8" style="margin-left:35px;">
                            <?php if (!isset($this->completed_message)): ?>
                                <div class="toast toast-error" style="width: 100%;margin: 10px auto;">
                                    <button class="btn btn-clear float-right"></button>
                                    <?php _e('Please adjust replace tag in your notification template', 'lsddonation');?>
                                </div>
                            <?php endif;?>
                            <!-- Migration Alert -->
                            <textarea data-event="completed" class="form-input lsdd_wafvel_message_templates" placeholder="Pesan Notifikasi Untuk Donatur Ketika Pembayaran Berhasil" rows="14"><?php echo esc_attr($this->completed_message); ?></textarea>
                        </div>
                    </div>
                    <!-- Content Email ketika Lunas -->
                </div>


                <!------------ Tab : FollowUp ------------>
                <div id="tab-body-wafvel-followup" class="tab-body">
                    <!-- TODO : Follow Up Notification -->
                </div>

                <!------------ Tab : Settings ------------>
                <div id="tab-body-wafvel-settings" class="tab-body">
                    <!-- Content Pengaturan -->
                    <form class="form-horizontal" block="settings">

                        <div class="form-group">
                            <div class="col-3 col-sm-12">
                                <label class="form-label" for="country"><?php _e('Server Wafvel', "lsddonation");?></label>
                            </div>
                            <div class="col-9 col-sm-12">
                                <input class="form-input" type="text" name="server" placeholder="api.wafvel.com" style="width:320px" value="<?php esc_attr_e(isset($this->server) ? $this->server : null);?>">
                                <small>contoh : api.wafvel.com </small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="col-3 col-sm-12">
                                <label class="form-label" for="country"><?php _e('API Key', "lsddonation");?></label>
                            </div>
                            <div class="col-9 col-sm-12">
                                <input class="form-input" type="password" autocompleted="off"  name="apikey" placeholder="B8as91na12m1nn1243nS1n24An1n021" style="width:320px" value="<?php esc_attr_e(isset($this->apikey) ? $this->apikey : null);?>">
                            </div>
                        </div>

                    <button class="btn btn-primary lsdd_admin_option_save" option="<?php echo $this->id; ?>" style="width:120px"><?php _e('Save', "lsddonation");?></button>
                    </form>

                </div>
            </div>

        </div>

        <script>
            // Save Template
            jQuery(document).on("click",".lsdd_wafvel_templates_save",function( e ) {
                jQuery(this).addClass('loading');
                let type    = jQuery(this).attr('data-type');
                let content = jQuery('.lsdd_wafvel_message_templates[data-event="'+ type +'"]').val();
                let that    = this;

                jQuery.post( lsdd_admin.ajax_url, {
                    action  : 'lsdd_whatsapp_wafvel_save',
                    type    : type,
                    content : content,
                    security : lsdd_admin.ajax_nonce,
                    }, function( response ){
                        if( response.trim() == 'action_success' ){
                            jQuery(that).removeClass('loading');
                        }
                    }).fail(function(){
                        alert('Failed, please check your internet');
                        location.reload();
                    }
                );
            });

            // On User Sending Test Email
            jQuery(document).on("click","#lsdd_wafvel_test",function( e ) {
                var wafvel_number = jQuery('#lsdd_wafvel_test').val();
                var that = this;

                if( wafvel_number != '' ){
                    jQuery(this).addClass('loading');
                    jQuery('#lsdd_wafvel_test').css('border', 'none');

                    jQuery.post( lsdd_admin.ajax_url, {
                        action : 'lsdd_whatsapp_wafvel_test',
                        phone  : wafvel_number,
                        security : lsdd_admin.ajax_nonce,
                        }, function( response ){
                            if( response.trim() == 200 ){
                                jQuery(that).removeClass('loading');
                                jQuery(that).text("Success");
                            }else{
                                jQuery(that).removeClass('loading');
                                jQuery(that).text("Failed");
                            }
                        }).fail(function(){
                            alert('Failed, please check your internet');
                        }
                    );

                }else{
                    jQuery('#lsdd_wafvel_test').css('border', '1px solid red');
                }
            });

        </script>
    <?php
    }
}
Notifications\Registrar::register("whatsapp-wafvel", new WhatsappWafvel());
?>