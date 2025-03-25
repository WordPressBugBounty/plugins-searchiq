<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// handle enable/disable error log option change before render tabs
if(!empty( $_POST ) && isset($_POST['btnSubmitOptions']) && check_admin_referer($this->updateOptionsNonce)){
    $enable_api_error_log = isset($_POST['enable_api_error_log']) && !empty(sanitize_text_field(wp_unslash($_POST['enable_api_error_log'])));
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
    
    $current_menu_page = get_current_screen()->id;

	$tab1Selected = $current_menu_page == 'toplevel_page_searchiq' ? 'selected': 'notselected';
    $tab2Selected = $current_menu_page == 'searchiq_page_searchiq-options' ? 'selected' : "notselected";
    $tab3Selected = $current_menu_page == 'searchiq_page_searchiq-results-config' ? 'selected' : "notselected";
    $tab4Selected = $current_menu_page == 'searchiq_page_searchiq-autocomplete-config' ? 'selected' : "notselected";
    $tab5Selected = $current_menu_page == 'searchiq_page_searchiq-mobile-config' ? 'selected' : "notselected";
    $tab6Selected = isset( $this->pluginSettings["facets_enabled"] ) && !!$this->pluginSettings["facets_enabled"] && $current_menu_page == 'searchiq_page_searchiq-facets-config' ? "selected" : "notselected";
    $tab7Selected = $current_menu_page == 'searchiq_page_searchiq-api-error-log' ? 'selected' : "notselected";
	
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
			<li id="tab-1" class="<?php echo esc_attr( $tab1Selected );?>">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=searchiq') ); ?>">Configuration</a>
			</li>
			<li id="<?php echo esc_attr( empty($engineCode) ? "" : "tab-2" );?>" class="<?php echo esc_attr( $tab2Selected );?>">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=searchiq-options') ); ?>">Options</a>
			</li>
			<li id="<?php echo esc_attr( empty($engineCode) ? "" : "tab-3" );?>" class="<?php echo esc_attr( $tab3Selected );?>">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=searchiq-results-config') ); ?>">Results Page</a>
			</li>
			<li id="<?php echo esc_attr( empty($engineCode) ? "" : "tab-4" );?>" class="<?php echo esc_attr( $tab4Selected );?>">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=searchiq-autocomplete-config') ); ?>">Autocomplete</a>
			</li>
			<li id="<?php echo esc_attr( empty($engineCode) ? "" : "tab-5" );?>" class="<?php echo esc_attr( $tab5Selected );?>">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=searchiq-mobile-config') ); ?>">Mobile</a>
			</li>
            <?php
                if (isset($this->pluginSettings["facets_enabled"]) && !!$this->pluginSettings["facets_enabled"]) {
                    echo wp_kses( $this->facetsTabHtml("", $tab6Selected, (empty($engineCode) ? "" : "tab-6")), array('li' => array( 'id' => array(), 'class' => array() ), 'a' => array('href'=> array())) );
                }

            // API Error Log
            if ($apiErrorLogEnabled || $apiErrorRecordsCount > 0) {
                ?>
                <li id="<?php echo esc_attr(empty($engineCode) ? "" : "tab-7"); ?>" class="<?php echo esc_attr($tab7Selected); ?>">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=searchiq-api-error-log')); ?>">Error Log</a>
                </li>
            <?php
            }
            ?>
		</ul>
	</div>
	<div class="tabsContent showLoader">
		<div class="tab tab-1 <?php echo esc_attr( $tab1Selected );?>">
			<?php
                if( (!empty( $siq_current_url ) && strpos( $siq_current_url, "&tab" ) === FALSE ) || ( ( !empty( $siq_current_url ) && strpos( $siq_current_url, "&tab" ) !== FALSE ) && ( strpos( $siq_current_url, "&tab=tab-1" ) !== FALSE || strpos( $siq_current_url, "&tab=tab-6" ) !== FALSE ))) {
                    include_once(SIQ_BASE_PATH . '/templates/backend/config.php');
                }
            ?>
		</div>
		<div class="tab tab-2 <?php echo esc_attr( $tab2Selected );?>">
			<?php
                if( !empty( $siq_current_url ) && ( strpos( $siq_current_url, "&tab=tab-2" ) !== FALSE || $current_menu_page == 'searchiq_page_searchiq-options' ) ) {
                    include_once(SIQ_BASE_PATH . '/templates/backend/optionsPage.php');
                }
            ?>
		</div>
		<div class="tab tab-3 <?php echo esc_attr( $tab3Selected );?>">
			<?php
                if( !empty( $siq_current_url ) && (strpos( $siq_current_url, "&tab=tab-3" ) !== FALSE || $current_menu_page == 'searchiq_page_searchiq-results-config')) {
                    include_once(SIQ_BASE_PATH.'/templates/backend/appearance.php');
                }
            ?>
		</div>
		<div class="tab tab-4 <?php echo esc_attr( $tab4Selected );?>">
			<?php
                if( !empty( $siq_current_url ) && (strpos( $siq_current_url, "&tab=tab-4" ) !== FALSE || $current_menu_page == 'searchiq_page_searchiq-autocomplete-config') ) {
                    include_once(SIQ_BASE_PATH.'/templates/backend/appearance-autocomplete.php');
                }
            ?>
		</div>
        <div class="tab tab-5 <?php echo esc_attr( $tab5Selected );?>">
			<?php
                if( !empty( $siq_current_url ) && (strpos( $siq_current_url, "&tab=tab-5" ) !== FALSE || $current_menu_page == 'searchiq_page_searchiq-mobile-config') ) {
                    include_once(SIQ_BASE_PATH.'/templates/backend/appearance-mobile.php');
                }
            ?>
		</div>
        <?php
        if (isset($this->pluginSettings["facets_enabled"]) && !!$this->pluginSettings["facets_enabled"]) {
            ?>
            <div class="tab tab-6 <?php echo esc_attr( $tab6Selected );?>">
                <?php
                    if( !empty( $siq_current_url ) && (strpos( $siq_current_url, "&tab=tab-6" ) !== FALSE || $current_menu_page == 'searchiq_page_searchiq-facets-config') ) {
                        include_once(SIQ_BASE_PATH . '/templates/backend/facets.php');
                    }
                ?>
            </div>
            <?php
        }

        // Error Log Tab
        if (strpos($tab7Selected, 'notselected') === false) {
            ?>
            <div class="tab tab-7 <?php echo esc_attr( $tab7Selected );?>">
                <?php
                if ( !empty( $siq_current_url ) && (strpos( $siq_current_url, "&tab=tab-7" ) !== false || $current_menu_page == 'searchiq_page_searchiq-api-error-log')) {
                    include_once(SIQ_BASE_PATH . '/templates/backend/error-log.php');
                }
                ?>
            </div>
            <?php
        }
        ?>
	</div>
</div>
<script type="text/javascript">
    var adminUrl  		= window.location.href;
    var adminPort 		= '<?php echo !empty($_SERVER['SERVER_PORT']) ? esc_attr( sanitize_text_field( wp_unslash($_SERVER['SERVER_PORT']) ) ) : ""; ?>';
    var adminAjax 		= '<?php echo esc_url( admin_url( 'admin-ajax.php' ) );?>';
    var adminBaseUrl 	= '<?php echo esc_url( admin_url( 'admin.php?page=searchiq' ) );?>';
    if(adminUrl.indexOf(adminPort) > -1 && adminAjax.indexOf(adminPort) == -1){
        adminAjax 		= adminAjax.replace(/\/wp-admin/g, ':'+adminPort+'/wp-admin');
        adminBaseUrl 	= adminBaseUrl.replace(/\/wp-admin/g, ':'+adminPort+'/wp-admin');
    }
    var siq_admin_nonce = "<?php  echo esc_html( wp_create_nonce( $this->adminNonceString ) ); ?>";
    var searchEngineText = 'You already have search engines created for this domain. ';
    (function($){
        $(document).on('click', '.clearColor', function(){
            $(this).prev('.color').val("").attr("style", "").attr("value", "");
        });
        var updateNotice = '<?php do_action('_siq_settings_update_notice'); ?>';
        if ( updateNotice != ''){
            $(updateNotice).insertBefore('.backendTabbed');
        }
    })(jQuery);
</script>
