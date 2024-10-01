<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ($this->pluginSettings['facets_enabled']) {

    if (isset($_POST) && isset( $_POST['siqFacetSubmit'] ) && check_admin_referer($this->updateFacetsNonce) ) {
        $facets = array();
        $post   =  $this->array_map_deep( $_POST, 'sanitize_text_field' );
        if (isset($post['siqFacetType']) && is_array($post['siqFacetType'])
                && is_array($post['siqFacetLabel'])
                && is_array($post['siqFacetField'])
                && is_array($post['siqFacetDateFormat'])
                && is_array($post['siqFacetTargetField'])) {
            for ($i = 0; $i < count($post['siqFacetType']); ++$i) {
                $facet = array(
                    "postType" => sanitize_text_field($post['siqFacetPostType'][$i]),
                    "type" => sanitize_text_field($post['siqFacetType'][$i]),
                    "label" => sanitize_text_field($post['siqFacetLabel'][$i]),
                    "field" => sanitize_text_field($post['siqFacetField'][$i]),
                    "targetField" => sanitize_text_field($post['siqFacetTargetField'][$i])
                );
                if ($facet["type"] == "date") {
                    $facet["dateFormat"] = stripslashes(sanitize_text_field($post['siqFacetDateFormat'][$i]));
                }
                array_push($facets, $facet);
            }
        }

        $facetsNoticeStatus = get_option(self::FACETS_NOTICE_KEY,0);
        $facetsCurrentValue = $this->pluginSettings["siq_facets"];
        $this->saveFacets($facets);
        $facetsNewValue     = $this->pluginSettings["siq_facets"];
        $preFacetsEnabledAc = $this->getAutocompleteFacetsEnabled();
        $preFacetsEnabledRp = $this->getResultPageFacetsEnabled();

        $this->setAutocompleteFacetsEnabled(isset($post['siq_enable_facets_autocomplete']) && $post['siq_enable_facets_autocomplete'] == "1");
        $this->setResultPageFacetsEnabled(isset($post['siq_enable_facets_result_page']) && $post['siq_enable_facets_result_page'] == "1");

        $postFacetsEnabledAc = $this->getAutocompleteFacetsEnabled();
        $postFacetsEnabledRp = $this->getResultPageFacetsEnabled();

        if( ( ($facetsCurrentValue !== $facetsNewValue ) || $facetsNoticeStatus == -1)  && ($postFacetsEnabledAc == 1 || $postFacetsEnabledRp == 1) && !empty($facetsNewValue)){
            update_option(self::FACETS_NOTICE_KEY, 1);
            apply_filters('_siq_check_facets_error',1);
        }else if(($facetsCurrentValue !== $facetsNewValue) || ($facetsNoticeStatus == 1 && $postFacetsEnabledAc == 0 && $postFacetsEnabledRp == 0)){
                update_option(self::FACETS_NOTICE_KEY, -1);
                apply_filters('_siq_check_facets_error',0);
        }

        $this->_siq_sync_settings();
    }else{
        $getNoticeStatus = get_option(self::FACETS_NOTICE_KEY, 0);
        if($getNoticeStatus > 0) {
            apply_filters('_siq_check_facets_error',1);
        }
    }

    $settings = $this->getPluginSettings();

    $facets = isset($settings["siq_facets"]) ? $settings["siq_facets"] : array();
    $excludeFields = array(
        "externalId", "title", "url", "body", "excerpt", "image", "documentType"
    );
    $postTypes = $this->getAllpostTypes();
    ?>

    <script>
        var SIQ_postTypes = <?php _e( json_encode(array_values($postTypes)) );?>;
    </script>

<div class="wsplugin">
    <h2>Facets</h2>
    <div class="wpAdminHeading">Here you can add facets to display in autocomplete and on result page</div>
    <form action="<?php _e( esc_url( admin_url( 'admin.php?page=dwsearch&tab=tab-6') ) );?>" method="post">
        <div class="section section-0">
            <div class="data">
                <label>Enable facets in autocomplete</label>
                <input type="checkbox" value="1" name="siq_enable_facets_autocomplete" <?php esc_html_e( $this->getAutocompleteFacetsEnabled() ? "checked" : "" );?> />
            </div>
            <div class="data">
                <label>Enable facets on result page</label>
                <input type="checkbox" value="1" name="siq_enable_facets_result_page" <?php esc_html_e( $this->getResultPageFacetsEnabled() ? "checked" : "" );?> />
            </div>

            <div id="siq-facet-form">
                <?php
                if (is_array($facets) && count($facets) > 0) {
                    $documentTypeFields = array();
                    $defaultPostTypes = array("post", "page", "_siq_all_posts");
                    foreach($facets as $key => $facet) {
                        if (!in_array($facet['postType'], $defaultPostTypes) && !array_key_exists($facet['postType'], $documentTypeFields)) {
                            $tmp = $this->getAllCustomFields(array($facet['postType']));
                            $tmp = array_merge($tmp[$facet['postType']]["regular_fields"], $tmp[$facet['postType']]['system_fields']);
                            $tmp = array_map(function($val) {
                                return $this->customFieldPrefix . $val;
                            }, $tmp);
                            $documentTypeFields[$facet['postType']] = array_merge($tmp, array_map(function($val) {
                                return $this->customTaxonomyPrefix . $val;
                            }, $this->getPostTypeTaxonomies($facet['postType'])));
                        }
                    }
                    $detectedPostTypeMappings = $this->getBulkDocumentFieldMapping($documentTypeFields);
                    for($i = 0; $i < count($facets); ++$i) {
                        $facet = $facets[$i];
                        $mapping = null;
                        if (array_key_exists($facet['postType'], $detectedPostTypeMappings) && count($detectedPostTypeMappings[$facet['postType']]) > 0) {
                            $mapping = $detectedPostTypeMappings[$facet['postType']];
                        }
                        $correctFacetType = $this->getCorrectFacetType($facet['field'], $facet['type'], $mapping);
                        $correctFacetDateFormat = $this->getCorrectFacetDateFormat($facet['field'], isset($facet['dateFormat']) ? $facet['dateFormat'] : null, $mapping);
                        $correctFacetTargetField = $this->getTargetField($facet['field'], $mapping);
                        $predefinedField = $this->isPredefinedField($facet['field'], $mapping);
                        ?>
                        <div id="siq-facet-item-<?php esc_html_e( $i );?>" class="siq-facet-item">
                            <table>
                                <tr>
                                    <td><label>Label</label></td>
                                    <td>
                                        <input type="text" name="siqFacetLabel[]" value="<?php esc_html_e( $facet["label"] );?>" required />
                                    </td>
                                    <td><label>Post Type</label></td>
                                    <td>
                                        <select name="siqFacetPostType[]" onchange="SIQ_buildFacetFieldSelectBox(<?php esc_html_e( $i );?>, this);">
                                            <option value="_siq_all_posts">All types</option>
                                            <?php
                                            foreach($postTypes as $postType) {
                                                ?><option value="<?php esc_html_e( $postType );?>" <?php esc_html_e( $facet['postType'] == $postType ? "selected" : "" );?>><?php esc_html_e( $postType );?></option><?php
                                            }
                                            ?>
                                        </select>
                                    </td>
                                    <td><label>Field</label></td>
                                    <td>
                                        <select name="siqFacetField[]" required onchange="SIQ_changeFacetField(<?php esc_html_e( $i );?>);">
                                            <?php _e( wp_kses( $this->buildFacetFieldOptionList($facet['postType'], $mapping, $facet['field']), $this->kses_allowed_html_searchbox ) ); ?>
                                        </select>
                                        <input type="hidden" name="siqFacetTargetField[]" value="<?php esc_html_e( !is_null($correctFacetTargetField) ? $correctFacetTargetField : "" );?>"/>
                                    </td>
                                    <td class="siqFacetType <?php esc_html_e( $predefinedField ? "hidden" : "" );?>"><label>Type</label></td>
                                    <td class="siqFacetType <?php esc_html_e( $predefinedField ? "hidden" : "" );?>">
                                        <select name="siqFacetType[]" required onchange="SIQ_changeFacetType(<?php esc_html_e( $i );?>);">
                                            <option value=""></option>
                                            <option value="string" <?php esc_html_e( $correctFacetType == "string" ? "selected" : "" );?>>String</option>
                                            <option value="number" <?php esc_html_e( $correctFacetType == "number" ? "selected" : "" );?>>Number</option>
                                            <option value="rating" <?php esc_html_e( $correctFacetType == "rating" ? "selected" : "" );?>>Rating</option>
                                            <option value="date" <?php esc_html_e( $correctFacetType == "date" ? "selected" : "" );?>>Date</option>
                                        </select>
                                    </td>
                                    <td class="siqDateFormat <?php esc_html_e( ($correctFacetType != "date" || $facet["field"] == "timestamp") ? "hidden" : "" );?>"><label>Date format</label></td>
                                    <td class="siqDateFormat <?php esc_html_e( ($correctFacetType != "date" || $facet["field"] == "timestamp") ? "hidden" : "" );?>">
                                        <input type="text" name="siqFacetDateFormat[]"
                                               value="<?php esc_html_e( (!is_null($correctFacetDateFormat) && strlen($correctFacetDateFormat) > 0) ? $correctFacetDateFormat : "Y-m-d\\TH:i:s\\.\\0\\0\\0" );?>"
                                               <?php esc_html_e( $correctFacetType == "date" ? "required" : "" );?> />
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="8">
                                        <a href="javascript:SIQ_moveFacetUp(<?php esc_html_e( $i );?>);" class="siq-facet-move-up <?php esc_html_e( $i == 0 ? "hidden" : "" );?>">Move up</a>
                                        <a href="javascript:SIQ_moveFacetDown(<?php esc_html_e( $i );?>);" class="siq-facet-move-down <?php esc_html_e( $i + 1 == count($facets) ? "hidden" : "" );?>">Move down</a>
                                        <a href="javascript:SIQ_removeFacet(<?php esc_html_e( $i );?>);" class="siq-facet-remove">Remove</a>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <?php
                    }
                } else {
                    _e( wp_kses( "<div class='siq-no-facets'>No facet created. Click &laquo;Add facet&raquo; button.</div>", $this->kses_allowed_html_config ) );
                }
                ?>
            </div>

            <div>
                <input type="button" name="btnAddFacet" id="btnAddFacet" value="Add facet" class="btn" onclick="SIQ_addNewFacet();return false;"><br/>
                <input type="submit" name="siqFacetSubmit" class="btn" value="Save"/>
                <?php wp_nonce_field( $this->updateFacetsNonce );?>
            </div>
        </div>
        <div class="section section-1 section-facets-resync">
            <h2>Please wait data synchronization is in progress</h2>
            <div class="data">
                <div class="progress-wrap progress" data-progress-percent="25">
                    <div class="progress-bar progress"></div>
                </div>
                <div class="progressText"></div>
            </div>
        </div>
    </form>
</div>

<?php } ?>