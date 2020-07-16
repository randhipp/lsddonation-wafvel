<?php 
/**
 * Class Notification Email
 * 
 * Feature :
 * Logging
 * On Order
 * On Complete
 * Settings
 * 
 */
Class LSDDonationWABLAS Extends LSDD_Notification {
    public $id       = 'lsdd_notification_wablas';
    public $name     = 'WABLAS';
    public $type     = 'whatsapp';
    public $doc_url  = 'https://docs.lsdplugins.com/docs/menggunakan-notifikasi-email/';

    protected $settings         = array();
    protected $log              = array();
    protected $order_message    = null;
    protected $complete_message = null;
    public $server              = null;
    public $apikey              = null;

    public function __construct() {

        // TestCase : Data Empty
        // update_option( 'lsdd_notification_wablas',null );

        if( get_option( 'lsdd_notification_wablas', true ) == null ){
            $new = array();
            $new['order']['message']    = '#f7f7f7';
            $new['complete']['message'] = '#f7f7f7';
            $new['settings']['server']  = '';
            $new['settings']['apikey']  = '';
            update_option( 'lsdd_notification_wablas', $new );
        }

        $this->settings       = get_option( $this->id, true ); 

        $this->order_message    = $this->settings['order']['message'];
        $this->complete_message = $this->settings['complete']['message'];

        $this->server   = $this->settings['settings']['server'];
        $this->apikey   = $this->settings['settings']['apikey'];
        
        add_action( 'wp_ajax_nopriv_lsdd_notification_wablas_test', array( $this, 'wablas_test' ) );
        add_action( 'wp_ajax_lsdd_notification_wablas_test', array( $this, 'wablas_test' ) );
        add_action( 'lsdd_notification_hook', array( $this, 'lsdd_register_wablas_notification') );
    }

    public function lsdd_register_wablas_notification( $data ){
        if( lsdd_get_notification_status('lsdd_notification_wablas') ) {
            $settings = get_option( 'lsdd_notification_wablas' );
            $event = isset($data['notification_event']) ?  $data['notification_event'] : '';
            $phone = isset($data['phone']) ?  $data['phone'] : '';
        
            $template =  get_option('lsdd_wablas_'. $event .'_template', true);
        
            if( $template ){
                $this->send_whatsapp( $phone, $event, $template, $data );
            }else{
                $this->log_wablas( $phone, 'on ' . $event, __('please set template first', 'lsdd') );
            }
        }
    }

    public function log_wablas( $reciever, $event , $message ){
        $db = get_option( $this->id, true ); /// Get Log
        $log = isset( $db['log'] ) ? $db['log'] : array(); // Check Log
        if( count($log) >= 30 ) $log = array(); // Auto Reset Log

        $log[] = array( lsdd_date_now(), $reciever, $event, $message); // Push New Log
        $db['log'] = $log; // Set Log
    
        update_option( $this->id, $db ); // Saving Log
    }

    public function send_whatsapp( $reciever, $event, $message, $data ){

        if( lsdd_get_notification_status('lsdd_notification_wablas') || $data == 'test' ){

            $settings = get_option( $this->id , true );
            $server   = isset( $settings['settings']['server'] ) ? esc_attr( $settings['settings']['server'] ) : 'console.wablas.com';
            $apikey   = isset( $settings['settings']['apikey'] ) ? esc_attr( $settings['settings']['apikey'] ) : '';

            if( $data != 'test' ){
                $payment = esc_attr( $data['gateway'] );
                $message = str_replace("%donors%", esc_attr( ucfirst( $data['name'] ) ), $message);
                $message = str_replace("%program%", get_the_title( $data['program_id'] ), $message);
                $message = str_replace("%total%", lsdd_currency_format( true, $data['total'] ), $message);
                $message = str_replace("%payment%", lsdd_payment_get( $payment, 'name' ) , $message);
                $message = str_replace("%bank_code%", lsdd_payment_get( $payment, 'bankcode' ), $message);
                $message = str_replace("%bank_swift%", lsdd_payment_get( $payment, 'swiftcode' ), $message);
                $message = str_replace("%account%", lsdd_payment_get( $payment, 'account_number' ), $message);
                $message = str_replace("%account_holder%", lsdd_payment_get( $payment, 'account_holder' ), $message);
            }
        
            $body = array(
                'phone'     => $reciever,
                'message'   => $message,
            );
        
            $payload = array(
                'method' => 'POST',
                'timeout' => 15,
                'headers'     => array(
                    'Authorization' => $apikey,
                    'Content-Type'  => 'application/json',
                ),
                'httpversion' => '1.0',
                'body' => json_encode($body),
                'cookies' => array()
            );

            $response = wp_remote_post( esc_url( $server . "/api/send-message" ) , $payload);
            $response_back = json_decode(wp_remote_retrieve_body( $response ), TRUE );

            if( $response_back['status'] == false ){
                $this->log_wablas( $reciever, $event, $response_back['message'] );
            }

            return $response_back;
        }
    }

    public function wablas_test(){
        if ( ! check_ajax_referer( 'lsdd_nonce', 'security' ) )  wp_send_json_error( 'Invalid security token sent.' );

        $_REQUEST   = array_map( 'stripslashes_deep', $_REQUEST );
        $phone      = esc_attr( $_REQUEST['phone'] );

        $this->send_whatsapp( $phone, 'on test', 'LSDDonation : Whatsapp Notification using WABLAS', 'test' );
        echo 'action_success';
        wp_die();
    }

    public function manage(){ ?>
        <div class="tabs-wrapper">
            <input type="radio" name="<?php echo $this->name; ?>" id="log_<?php echo $this->name; ?>" checked="checked"/>
            <label class="tab" for="log_<?php echo $this->name; ?>"><?php _e( 'Log', 'lsdd' ); ?></label>

            <input type="radio" name="<?php echo $this->name; ?>" id="order_<?php echo $this->name; ?>"/>
            <label class="tab" for="order_<?php echo $this->name; ?>"><?php _e( 'On Order', 'lsdd' ); ?></label>

            <input type="radio" name="<?php echo $this->name; ?>" id="complete_<?php echo $this->name; ?>"/>
            <label class="tab" for="complete_<?php echo $this->name; ?>"><?php _e( 'On Complete', 'lsdd' ); ?></label>

            <input type="radio" name="<?php echo $this->name; ?>" id="settings_<?php echo $this->name; ?>"/>
            <label class="tab" for="settings_<?php echo $this->name; ?>"><?php _e( 'Settings', 'lsdd' ); ?></label>

            <div class="tab-body-wrapper">
                 <!------------ Tab : Log ------------>
                <div id="tab-body-<?php echo $this->name; ?>-log" class="tab-body">
                    <table class="table-log table table-striped table-hover">
                        <tbody>
                        <?php 
                            $db = get_option( 'lsdd_notification_wablas', true );
                            $log = isset( $db['log'] ) ? $db['log'] : array();
                        ?>
                        <?php if( $log ) : ?>
                        <?php foreach ( array_reverse( $log ) as $key => $value) : ?>
                            <tr>
                                <td><?php echo lsdd_date_format( $value[0], 'j M Y, H:i:s' ); ?></td>
                                <td><?php echo $value[1]; ?></td>
                                <td><?php echo $value[2]; ?></td>
                                <td><?php echo $value[3]; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php else:  ?>
                            <tr><td><?php _e( 'Empty Log', 'lsdd-qurban' ); ?></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!------------ Tab : On Order ------------>
                <div id="tab-body-<?php echo $this->name; ?>-order" class="tab-body">

                    <div class="columns col-gapless">
                        <!-- Email Instruction and Editor -->
                        <div class="column col-3" style="padding:0 10px 0 0;">
        
                            <div id="instruction">

                                <!-- Marker -->
                                <p id="tag" class="mt-2">
                                    <kbd>%donors%</kbd><code>John Doe</code><br>
                                    <kbd>%program%</kbd><code>Save Our Forest</code> <br>
                                    <kbd>%total%</kbd><code>$5</code> <br>
                                    <kbd>%payment%</kbd><code>Paypal</code> <br>
                                    <kbd>%bank_code%</kbd><code>(014)</code> <br>
                                    <kbd>%bank_swift%</kbd><code>BRINIDJA</code> <br>
                                    <kbd>%account%</kbd><code>lsdplugins@gmail.com</code> <br>
                                    <kbd>%account_holder%</kbd><code>LSDPlugins</code> <br>
                                </p>
                            </div>

                            <button data-type="order" class="btn btn-primary input-group-btn lsdd_notification_email_save"><?php _e( 'Save', 'lsdd' ); ?></button> 
                        </div>

                        <!-- Email Preview -->
                        <div class="column col-8" style="margin-left:35px;">
                            <!-- Migration Alert -->

<?php 
// update_option('lsdd_wablas_order_template', '');
$lsdd_wablas_order_template = '';
if( empty( get_option('lsdd_wablas_order_template') ) ) :
$lsdd_wablas_order_template = 'Kepada YTH Bpk/Ibu %donors%
Berikut ini Pesanan Anda :
%program%
Total Pembayaran :
%total%

Silahkan Lakukan Pembayaran
%payment%
%bank_code% %account%
a.n %account_holder%

Salam Hangat
LSD Plugin';
update_option('lsdd_wablas_order_template', $lsdd_wablas_order_template);
else:
    $lsdd_wablas_order_template = get_option('lsdd_wablas_order_template', true);
endif;
?>
                                 <textarea id="lsdd_wablas_order_template" class="form-input" placeholder="Notifikasi Untuk Donatur" rows="14"><?php echo $lsdd_wablas_order_template; ?></textarea>
                     
                        </div>

                    </div>

                </div>
                <!------------ Tab : On Complete ------------>
                <div id="tab-body-<?php echo $this->name; ?>-complete" class="tab-body">

                    <div class="columns col-gapless">
                        <!-- Email Instruction and Editor -->
                        <div class="column col-3" style="padding:0 10px 0 0;">

                            <div id="instruction">
                                <!-- Subject Email -->
                                <p id="tag" class="mt-2">
                                    <kbd>%donors%</kbd><code>John Doe</code><br>
                                    <kbd>%program%</kbd><code>Save Our Forest</code> <br>
                                </p>
                            </div>

                            <button data-type="complete" class="btn btn-primary input-group-btn lsdd_notification_email_save"><?php _e( 'Save', 'lsdd' ); ?></button> 
                        </div>

                        <div class="column col-8" style="margin-left:35px;">
                            <!-- Migration Alert -->
<?php 
// update_option('lsdd_wablas_complete_template', '');
$lsdd_wablas_complete_template = '';
if( empty( get_option('lsdd_wablas_complete_template') ) ) :
$lsdd_wablas_complete_template = 'Terimakasih Bpk/Ibu %pemesan%

Donasi %donors%,
Sebesar %total%
telah kami terima pembayarannya.

Semoga bisa bermanfaat bagi mereka

Salam Hangat
LSD Plugin';
update_option('lsdd_wablas_complete_template', $lsdd_wablas_complete_template);
else:
$lsdd_wablas_complete_template = get_option('lsdd_wablas_complete_template', true);
endif;
?>
                            <textarea id="lsdd_wablas_complete_template" class="form-input" placeholder="Notifikasi Untuk Donatur Ketika Pembayaran Berhasil" rows="14"><?php echo $lsdd_wablas_complete_template; ?></textarea>
                                

                        </div>
                    </div>
                    <!-- Content Email ketika Lunas -->
                </div>
                <!------------ Tab : Settings ------------>
                <div id="tab-body-<?php echo $this->name; ?>-settings" class="tab-body">
                    <!-- Content Pengaturan -->
                    <form class="form-horizontal" block="settings">

                        <!-- Sender -->
                        <div class="form-group">
                            <div class="col-3 col-sm-12">
                            <label class="form-label" for="country"><?php _e( 'Server', "lsdd-wablas" ); ?></label>
                            </div>
                            <div class="col-9 col-sm-12">
                            <input class="form-input" type="text" name="server" placeholder="console.wablas.com" style="width:320px" value="<?php esc_attr_e( isset( $this->server ) ? $this->server : null ); ?>">
                            </div>
                        </div>

                        <!-- Sender Email -->
                        <div class="form-group">
                            <div class="col-3 col-sm-12">
                            <label class="form-label" for="country"><?php _e( 'API Key', "lsdd-wablas" ); ?></label>
                            </div>
                            <div class="col-9 col-sm-12">
                            <input class="form-input" type="password" autocomplete="off"  name="apikey" placeholder="B8as91na12m1nn1243nS1n24An1n021" style="width:320px" value="<?php esc_attr_e( isset( $this->apikey ) ? $this->apikey : null ); ?>">
                            </div>
                        </div>

                        <button class="btn btn-primary lsdd_admin_option_save" option="<?php echo $this->id; ?>" style="width:120px"><?php _e( 'Save', "lsdd-wablas" ); ?></button> 
                    </form>
      
                    <div class="divider" data-content="Test Wablas Notification"></div>
                    <div class="input-group" style="width:50%;">
                        <input id="lsdd_wablas_test" style="margin-top:3px;" class="form-input input-md" type="text" placeholder="0812387621812">
                        <button id="lsdd_wablas_sendtest" style="margin-top:3px;" class="btn btn-primary input-group-btn"><?php _e( 'Test Notification', "lsdd-wablas" ); ?></button>
                    </div>
                </div>
            </div>

        </div>

        <style>
            #lsdd-editor{
                height:100%;
                margin-top: 20px;
            }

            #lsdd-editor-order img,
            #lsdd-editor-complete img{
                cursor: pointer;
            }

            /* Action Tab */
            #log_WABLAS:checked~.tab-body-wrapper #tab-body-WABLAS-log,
            #order_WABLAS:checked~.tab-body-wrapper #tab-body-WABLAS-order,
            #complete_WABLAS:checked~.tab-body-wrapper #tab-body-WABLAS-complete,
            #settings_WABLAS:checked~.tab-body-wrapper #tab-body-WABLAS-settings {
                position: relative;
                top: 0;
                opacity: 1
            }
            .tab-body-wrapper .table-log th{
                display: inline-block;
            }
            .tab-body-wrapper .table-log tr{
                margin-bottom: 0; 
            }

            .tab-body-wrapper .table-log tbody tr td{
                display: inline-block !important;
                padding: 10px !important;
            }

            .tab-body-wrapper .table-log.table td, .tab-body-wrapper .table-log.table th{
                border-bottom: 0;
            }
            .wp-picker-container{
                display:block;
            }
        </style>

        <script>
            // On Email Editor Save
            jQuery(document).on("click",".lsdd_notification_email_save",function( e ) {
                jQuery(this).addClass('loading');
                let lsdd_email_type = jQuery(this).attr('data-type');
                let that = this;

                jQuery.post( lsdd_adm.ajax_url, { 
                    action : 'lsdd_notification_email_template',
                    email_type : lsdd_email_type,
                    data : jQuery('#lsdd-editor-' + lsdd_email_type ).html(),
                    header_bg : jQuery('#lsdd_header_bg_' + lsdd_email_type ).val(),
                    subject : jQuery('#lsdd_subject_' + lsdd_email_type ).val(),
                    security : lsdd_adm.ajax_nonce,
                    }, function( response ){
                        if( response.trim() == 'action_success' ){
                            jQuery(that).removeClass('loading');
                        }
                    }).fail(function(){
                        alert('Failed, please check your internet');
                    }
                );
            });

  
            // On User Sending Test Email
            jQuery(document).on("click","#lsdd_wablas_sendtest",function( e ) {
                var wablas_number = jQuery('#lsdd_wablas_test').val();

                if( wablas_number != '' ){
                    jQuery(this).addClass('loading');
                    jQuery('#lsdd_wablas_test').css('border', 'none');
                    
                    jQuery.post( lsdd_adm.ajax_url, { 
                        action : 'lsdd_notification_wablas_test',
                        phone  : wablas_number,
                        security : lsdd_adm.ajax_nonce,
                        }, function( response ){
                            if( response.trim() == 'action_success' ){
                                location.reload();
                            }
                        }).fail(function(){
                            alert('Failed, please check your internet');
                        }
                    );

                }else{
                    jQuery('#lsdd_wablas_test').css('border', '1px solid red');
                }
            });

        </script>
    <?php
    }
}
lsdd_notification_register( 'LSDDonationWABLAS' );


?>