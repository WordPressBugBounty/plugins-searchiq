<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// handle enable/disable error log option change before render tabs
if(!empty( $_POST ) && isset($_POST['btnSubmitOptions']) && check_admin_referer($this->updateOptionsNonce)){
    $enable_api_error_log = isset($_POST['enable_api_error_log']) && !empty(sanitize_text_field($_POST['enable_api_error_log']));
	if ($enable_api_error_log) {
		$this->enableAPIErrorLog();
	} else {
		$this->disableAPIErrorLog();
	}
}

	$settings 		= $this->getPluginSettings();
	$code 			= isset( $settings['auth_code'] ) ? $settings['auth_code']: '';
	$engine 		= isset( $settings['engine_name'] ) ? stripslashes($settings['engine_name']): '';
	$engineCode		= isset( $settings['engine_code'] ) ? $settings['engine_code']: '';
	$indexed 		= isset( $settings['index_posts'] ) ? $settings['index_posts']: '';
	$siq_current_url = $this->siq_get_current_admin_url();
    $apiErrorRecordsCount = $this->getAPIErrorRecordsCount();
    $apiErrorLogEnabled = $this->isAPIErrorLogEnabled();
    
	$tab1Selected = !empty( $siq_current_url ) && strpos( $siq_current_url, "&tab" ) !== FALSE ? ( ( strpos( $siq_current_url, "&tab=tab-1" ) !== FALSE )? 'selected': 'notselected') : "selected";
	$tab2Selected = !empty( $siq_current_url ) && strpos( $siq_current_url, "&tab" ) !== FALSE ? ( ( strpos( $siq_current_url, "&tab=tab-2" ) !== FALSE )? 'selected': 'notselected') : "notselected";
	$tab3Selected = !empty( $siq_current_url ) && strpos( $siq_current_url, "&tab" ) !== FALSE ? ( ( strpos( $siq_current_url, "&tab=tab-3" ) !== FALSE )? 'selected': 'notselected') : "notselected";
	$tab4Selected = !empty( $siq_current_url ) && strpos( $siq_current_url, "&tab" ) !== FALSE ? ( ( strpos( $siq_current_url, "&tab=tab-4" ) !== FALSE )? 'selected': 'notselected') : "notselected";
    $tab5Selected = !empty( $siq_current_url ) && strpos( $siq_current_url, "&tab" ) !== FALSE ? ( ( strpos( $siq_current_url, "&tab=tab-5" ) !== FALSE )? 'selected': 'notselected') : 'notselected';
	$tab6Selected = isset( $this->pluginSettings["facets_enabled"] ) && !!$this->pluginSettings["facets_enabled"] && isset($_GET['tab']) && strpos( $siq_current_url, "&tab=tab-6" ) !== FALSE ? "selected" : "notselected";
    $tab7Selected = !empty( $siq_current_url ) && strpos( $siq_current_url, "&tab" ) !== FALSE ? ( ( strpos( $siq_current_url, "&tab=tab-7" ) !== FALSE )? 'selected': 'notselected') : 'notselected';
    if ( !empty( $siq_current_url ) && strpos( $siq_current_url, "&tab" ) && strpos( $siq_current_url, "&tab=tab-6" ) !== FALSE && $tab6Selected != "selected") {
        $tab1Selected = "selected";
    }

	$tab2Selected .= ($code == "" && $engineCode=="" && ($indexed == "" || $indexed == 0)) ? " hide": "";
	$tab3Selected .= ($code == "" && $engineCode=="" && ($indexed == "" || $indexed == 0)) ? " hide": "";
	$tab4Selected .= ($code == "" && $engineCode=="" && ($indexed == "" || $indexed == 0)) ? " hide": "";
    $tab5Selected .= ($code == "" && $engineCode=="" && ($indexed == "" || $indexed == 0)) ? " hide": "";
    $tab6Selected .= ( (isset($this->pluginSettings["facets_enabled"]) && !$this->pluginSettings["facets_enabled"] ) || empty($code) || empty($engineCode) || empty($indexed)) ? " hide" : "";
    $tab7Selected .= ($tab7Selected === 'notselected' && $apiErrorRecordsCount === 0 && !$apiErrorLogEnabled) ? " hide": "";
?>
<div class="backendTabbed" id="searchIqBackend">
	<div class="tabsHeading">
		<ul>
			<li id="tab-1" class="<?php esc_html_e( $tab1Selected );?>">
				<a href="<?php _e( add_query_arg("tab", "tab-1", esc_url( admin_url( 'admin.php?page=dwsearch') ) ) );; ?>">Configuration</a>
			</li>
			<li id="<?php esc_html_e( empty($engineCode) ? "" : "tab-2" );?>" class="<?php esc_html_e( $tab2Selected );?>">
				<a href="<?php _e( add_query_arg("tab", "tab-2", esc_url( admin_url( 'admin.php?page=dwsearch') ) ) ); ?>">Options</a>
			</li>
			<li id="<?php esc_html_e( empty($engineCode) ? "" : "tab-3" );?>" class="<?php esc_html_e( $tab3Selected );?>">
				<a href="<?php _e( add_query_arg("tab", "tab-3", esc_url( admin_url( 'admin.php?page=dwsearch') ) ) ); ?>">Results Page</a>
			</li>
			<li id="<?php esc_html_e( empty($engineCode) ? "" : "tab-4" );?>" class="<?php esc_html_e( $tab4Selected );?>">
				<a href="<?php _e( add_query_arg("tab", "tab-4", esc_url( admin_url( 'admin.php?page=dwsearch') ) ) ); ?>">Autocomplete</a>
			</li>
			<li id="<?php esc_html_e( empty($engineCode) ? "" : "tab-5" );?>" class="<?php esc_html_e( $tab5Selected );?>">
				<a href="<?php _e( add_query_arg("tab", "tab-5", esc_url( admin_url( 'admin.php?page=dwsearch') ) ) ); ?>">Mobile</a>
			</li>
            <?php
            if (isset($this->pluginSettings["facets_enabled"]) && !!$this->pluginSettings["facets_enabled"]) {
            	_e( wp_kses( $this->facetsTabHtml("", $tab6Selected, (empty($engineCode) ? "" : "tab-6")), array('li' => array( 'id' => array(), 'class' => array() ), 'a' => array('href'=> array())) ) );
            }

            // API Error Log
            ?>
            <li id="tab-7" class="<?php esc_html_e( $tab7Selected );?>">
                <a href="<?php _e (add_query_arg("tab", "tab-7", esc_url( admin_url( 'admin.php?page=dwsearch') ) ) ); ?>">Error Log</a>
            </li>
            <?php
            ?>
		</ul>
	</div>
	<div class="tabsContent showLoader">
		<div class="tab tab-1 <?php esc_html_e( $tab1Selected );?>">
			<?php
                if( (!empty( $siq_current_url ) && strpos( $siq_current_url, "&tab" ) === FALSE ) || ( ( !empty( $siq_current_url ) && strpos( $siq_current_url, "&tab" ) !== FALSE ) && ( strpos( $siq_current_url, "&tab=tab-1" ) !== FALSE || strpos( $siq_current_url, "&tab=tab-6" ) !== FALSE ))) {
                    include_once(SIQ_BASE_PATH . '/templates/backend/config.php');
                }
            ?>
		</div>
		<div class="tab tab-2 <?php esc_html_e( $tab2Selected );?>">
			<?php
                if( !empty( $siq_current_url ) && strpos( $siq_current_url, "&tab=tab-2" ) !== FALSE ) {
                    include_once(SIQ_BASE_PATH . '/templates/backend/optionsPage.php');
                }
            ?>
		</div>
		<div class="tab tab-3 <?php esc_html_e( $tab3Selected );?>">
			<?php
                if( !empty( $siq_current_url ) && strpos( $siq_current_url, "&tab=tab-3" ) !== FALSE ) {
                    include_once(SIQ_BASE_PATH.'/templates/backend/appearance.php');
                }
            ?>
		</div>
		<div class="tab tab-4 <?php esc_html_e( $tab4Selected );?>">
			<?php
                if( !empty( $siq_current_url ) && strpos( $siq_current_url, "&tab=tab-4" ) !== FALSE ) {
                    include_once(SIQ_BASE_PATH.'/templates/backend/appearance-autocomplete.php');
                }
            ?>
		</div>
        <div class="tab tab-5 <?php esc_html_e( $tab5Selected );?>">
			<?php
                if( !empty( $siq_current_url ) && strpos( $siq_current_url, "&tab=tab-5" ) !== FALSE ) {
                    include_once(SIQ_BASE_PATH.'/templates/backend/appearance-mobile.php');
                }
            ?>
		</div>
        <?php
        if (isset($this->pluginSettings["facets_enabled"]) && !!$this->pluginSettings["facets_enabled"]) {
            ?>
            <div class="tab tab-6 <?php esc_html_e( $tab6Selected );?>">
                <?php
                    if( !empty( $siq_current_url ) && strpos( $siq_current_url, "&tab=tab-6" ) !== FALSE ) {
                        include_once(SIQ_BASE_PATH . '/templates/backend/facets.php');
                    }
                ?>
            </div>
            <?php
        }

        // Error Log Tab
        if (strpos($tab7Selected, 'notselected') === false) {
            ?>
            <div class="tab tab-7 <?php esc_html_e( $tab7Selected );?>">
                <?php
                if ( !empty( $siq_current_url ) && strpos( $siq_current_url, "&tab=tab-7" ) !== false ) {
                    include_once(SIQ_BASE_PATH . '/templates/backend/error-log.php');
                }
                ?>
            </div>
            <?php
        }
        ?>
	</div>
	<script type="text/javascript">

	</script>
</div>
<script type="text/javascript">
    var adminUrl  		= window.location.href;
    var adminPort 		= '<?php esc_html_e( sanitize_text_field( $_SERVER['SERVER_PORT'] ) ); ?>';
    var adminAjax 		= '<?php _e( esc_url( admin_url( 'admin-ajax.php' ) ) );?>';
    var adminBaseUrl 	= '<?php _e( esc_url( admin_url( 'admin.php?page=dwsearch' ) ) );?>';
    if(adminUrl.indexOf(adminPort) > -1 && adminAjax.indexOf(adminPort) == -1){
        adminAjax 		= adminAjax.replace(/\/wp-admin/g, ':'+adminPort+'/wp-admin');
        adminBaseUrl 	= adminBaseUrl.replace(/\/wp-admin/g, ':'+adminPort+'/wp-admin');
    }
    var siq_admin_nonce = "<?php  _e( esc_html( wp_create_nonce( $this->adminNonceString ) ) ); ?>";
    var searchEngineText = 'You already have search engines created for this domain. ';
    $jQu	= jQuery;
    $jQu(document).on('click', '.clearColor', function(){
        $jQu(this).prev('.color').val("").attr("style", "").attr("value", "");
    });
</script>
