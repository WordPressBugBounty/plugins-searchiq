<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings 								= $this->getPluginSettings();
$code 										= isset( $settings['auth_code'] ) ? $settings['auth_code'] : '';
$engine 								= isset( $settings['engine_name'] ) ? stripslashes($settings['engine_name']): '';
$engineCode						= isset( $settings['engine_code'] ) ? $settings['engine_code']: '';
$indexed 								= isset( $settings['index_posts'] ) ? $settings['index_posts']: '';
$imageCustomField     	= isset( $settings['image_custom_field'] ) ? $settings['image_custom_field']: '';
$numPostsIndexed 		= isset( $settings['num_indexed_posts'] ) ? $settings['num_indexed_posts']: '';
$showEngineButton 		= "";
if(!empty($settings['siq_engine_not_found'])){
	$showEngineButton = "display:block;";
}

$classVerify 	= ($code!=""  && empty($showEngineButton)) ? "done" : "not-done";
$classIndexed 	= ($indexed!=""  && empty($showEngineButton)) ? "indexed open" : "not-indexed";
$engineCreated	= ($engine!="" && empty($showEngineButton)) ? "engine open" : "no-engine";

$textStep3 		= "Step 2: Submit posts for synchronization";
$textMessageStep3 = "Click <b>\"Full Synchronize Posts\"</b> button to submit all posts for synchronization.";
$classReindex	= "";
if($indexed && empty($showEngineButton)){
	$textStep3 = "Synchronization Settings";
	$classReindex =  "reindex";
	$textMessageStep3 = "Click <b>\"Full Resynchronize Posts\"</b> button to submit posts for re-synchronization or else click <b>\"Delta Resynchronize Posts\"</b> button to submit only updated posts for re-synchronization. <br/><br/><b>Please don't close this tab while sync is running.</b>";
}

$textIndexing	= ($indexed !="" && (int)$indexed > 0 && empty($showEngineButton)) ? "Full Resynchronize Posts" : "Full Synchronize Posts";

$is_partner  = isset($settings['is_partner']) && $settings['is_partner'] == 'yes' ? true : false;
$domain_list = $is_partner ? $settings['domain_list']: array();
$disable_sync = isset($settings['disable_sync']) && $settings['disable_sync'] == "yes" ? true : false;

$class_partner = $is_partner ? 'is-partner': '';
$class_partner = $disable_sync ? $class_partner . " show" : $class_partner;
?>

<div class="wsplugin">
	<h2>SearchIQ: Configuration <a class="helpSign userGuide" target="_blank" style="text-decoration: none" href="<?php _e( esc_url( $this->userGuideLink ) );?>"><img style="vertical-align:bottom" src="<?php _e( esc_url( SIQ_BASE_URL.'/assets/'.SIQ_PLUGIN_VERSION.'/images/help/help-icon.png' ) ); ?>"> User Guide</a></h2>
	<?php if($code == ""){ ?>
		<div class="dwAdminHeading">Get your API key from <a target="_blank" href="<?php _e( esc_url( $this->administerPanelLink ) ); ?>">SearchIQ</a> account.</div>
	<?php } ?>
	<?php if($code != "" || $engine !="" || $indexed != ""){ ?>
		<div class="section section-top">
			<h2>Plugin Settings</h2>
			<div class="data">
				<ul>
					<li class="vcode">
						<label><?php esc_html_e( $this->labels["verificationCode"] );?></label> <span class="info"><?php esc_html_e( $code ); ?></span>
					</li>
					<li class="iposts">
					<label><?php esc_html_e($this->labels["postsIndexed"]); ?>
						</label> <span class="info">
							<?php esc_html_e($numPostsIndexed); ?>
						</span>
					</li>
					<?php if($is_partner): ?>
						<li class="ename">
							<label><?php esc_html_e( $this->labels["engineName"] );?></label> <span class="info"><?php _e( wp_kses( $this->generatePartnerDomainSelectBox($domain_list, $engineCode ), $this->kses_allowed_html_config) ); ?></span>
						</li>
					<?php endif; ?>
					<?php if($is_partner) : ?>
						<?php _e(wp_kses($this->generatePartnerContainerTagHtml(), $this->kses_allowed_html_config)); ?>
					<?php endif; ?>
				</ul>


				</div>
			</div>
	<?php } ?>
	<?php if($code == ""){ ?>
		<div class="section section-1 <?php esc_html_e( $classVerify );?>">
			<h2>Step 1: Plugin Authentication</h2>
			<div class="data">

				<label>Enter API key:</label>
				<input type="text" class="textbox" name="siq_activation" id="siq_activation" value="<?php esc_html_e( $code );?>" />
				<input type="button" name="btnSubmitcode" id="btnSubmitcode" value="Submit" class="btn" />
			</div>
		</div>
	<?php } ?>
	
	<div class="section section-2 <?php esc_html_e( $classVerify );?> <?php esc_html_e( $engineCreated );?>" style="<?php  esc_html_e( $showEngineButton );?>">
		<h2>Create a Search Engine</h2>
		<div class="data">
			<h3>Click on the button to create search engine.<span class="engineExists"></span></h3>
			<input type="button" name="btnSubmitEngine" id="btnSubmitEngine" value="Create Search engine" class="btn" />
		</div>
	</div>
	
	<div class="section section-3 <?php esc_html_e( $classVerify );?> <?php esc_html_e( $classIndexed );?> <?php esc_html_e( $engineCreated );?> <?php esc_html_e( $classReindex );?> <?php esc_html_e( $class_partner) ?>">
		<h2><?php esc_html_e( $textStep3 );?></h2>
		<div class="data">
			<?php _e( wp_kses( $this->getFilterAndPostTypeHTML(), $this->kses_allowed_html_config ) ); ?>
			<?php 
				if(  isset($settings["index_posts"]) && is_null($settings["index_posts"]) || empty($settings["index_posts"]) || $settings["index_posts"] == 0 || !empty($settings['siq_engine_not_found']) ){ 
					_e( wp_kses( $this->getResyncBlock($textIndexing, $textMessageStep3, (isset($settings["index_posts"]) && $settings["index_posts"] && empty($settings['siq_engine_not_found'])) ? 1: 0, $disable_sync ), $this->kses_allowed_html_config ) );
				} 
			?>
		</div>
		<?php if( $disable_sync ): ?>
			<div class="msg error">Sync is disabled. Please select and save <b><?php esc_html_e($this->labels["engineName"]) ?></b> from the list under <b>Plugin Settings</b> <a id="gotoPluginSettings" href="#">here</a> to enable sync.</div>
		<?php endif; ?>
	</div>
	<?php if(isset($settings["index_posts"]) && $settings["index_posts"] >= 1 &&  empty($showEngineButton)){ ?>
		<div class="section section-3-1">
			<div class="data">
				<?php _e( wp_kses( $this->getResyncBlock($textIndexing, $textMessageStep3, $settings["index_posts"]), $this->kses_allowed_html_config ) ); ?>
			</div>
		</div>
	<?php } ?>

	<div class="section section-4 <?php esc_html_e( $classVerify );?> <?php esc_html_e( $classIndexed );?> <?php esc_html_e( $engineCreated );?>">
		<h2><?php esc_html_e( "Regenerate thumbnails (optional)" );?></h2>
		<?php if(!$this->enableThumbnailService){ ?>
			<h3>To optimize the thumbnails click the button below</h3>
			<div class="data dataPaddingBottom">
				<h5>Choose if you want to crop or resize the thumbnails</h5>
				<ul class="options inline">
				<?php
					foreach($this->siqCropResizeOptions as $k => $v){
					$checked = ( isset($settings["siq_crop_resize_thumb"]) && $settings["siq_crop_resize_thumb"] == $k) ? "checked=checked" : "";
					?>
					<li><input <?php esc_html_e( $checked );?> type="radio" name="selectCropResize" value="<?php esc_html_e( $k );?>" id="selectCropResize_<?php esc_html_e( $k );?>" /><label for="selectCropResize_<?php esc_html_e( $k );?>"><?php esc_html_e( $v );?></label></li>
				<?php } ?>
				</ul>
			</div>
			<div class="data">
				<input type="button" name="btnGenerateThumbnails" id="btnGenerateThumbnails" value="Regenerate Thumbnails" class="btn <?php esc_html_e( $classReindex );?>" />

				<div class="progress-wrap progress" data-progress-percent="25">
					<div class="progress-bar progress"></div>
				</div>
				<div class="progressText"></div>
			</div>
		<?php 
			} else { 
				_e( wp_kses( $this->thumbServiceDisabledMsg, $this->kses_allowed_html_config ) ); 
			} 
		?>
	</div>

	<div class="section section-5 <?php esc_html_e( $classVerify );?> <?php esc_html_e( $classIndexed );?> <?php esc_html_e( $engineCreated );?>" style="<?php  esc_html_e( $showEngineButton );?>">
		<input type="button" name="btnResetConfig" id="btnResetConfig" value="Reset Configuration" class="btn" />
		<h3>Resets the configuration but indexed data will remain on SearchIQ server. In order to delete the indexed data from SearchIQ server login to the dashboard and delete the search engine.</h3>
		<div class="data"></div>
	</div>
</div>

