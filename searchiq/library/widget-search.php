<?php
if(file_exists(ABSPATH . 'wp-admin/includes/template.php')){
	require_once ABSPATH . 'wp-admin/includes/template.php';
	if(class_exists('Walker_Category_Checklist')){
		class SIQ_Walker_Category_Checklist_Widget extends Walker_Category_Checklist {

			private $name;
			private $id;

			function __construct( $name = '', $id = '' ) {
				$this->name = $name;
				$this->id = $id;
			}

			function start_el( &$output, $cat, $depth = 0, $args = array(), $id = 0 ) {
				extract( $args );
				if ( empty( $taxonomy ) ) $taxonomy = 'category';
				$class = in_array( $cat->term_id, $popular_cats ) ? ' class="popular-category"' : '';
				$id = $this->id . '-' . $cat->term_id;
				$checked = checked( in_array( $cat->term_id, $selected_cats ), true, false );
				$output .= "\n<li id='{$taxonomy}-{$cat->term_id}'$class>" 
					. '<label class="selectit"><input value="' 
					. $cat->term_id . '" type="checkbox" name="' . $this->name 
					. '[]" id="in-'. $id . '"' . $checked 
					. disabled( empty( $args['disabled'] ), false, false ) . ' /> ' 
					. esc_html( apply_filters( 'the_category', $cat->name ) ) 
					. '</label>';
			  }
		}
	}
}
class SIQ_Search_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'siq_search_widget', // Base ID
			__( 'SearchIQ search box', 'siq_text' ), // Name
			array( 'description' => __( 'A Widget which displays a searchbox in the widget area', 'siq_text' ), ) // Args
		);
	}


	public function widget( $args, $instance ) {
		global $siq_plugin;
		_e( $args['before_widget'] );
		if ( ! empty( $instance['title'] ) ) {
			_e( $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'] );
		}
		_e( wp_kses( $this->getWidgetHtml($instance), $siq_plugin->kses_allowed_html_config ) );
		_e( $args['after_widget'] );
	}

	public function getWidgetHtml($instance = array()){
		global $siq_plugin;
		$strWidget        	= "";
		$searchValue   		= get_search_query();
		$searchText      	= !empty($instance['placeholder']) ? $instance['placeholder'] : "Search";
		$postcatidfilter	= !empty($instance['postcatidfilter']) ? $instance['postcatidfilter'] : '';
		$postTypesArr		= !empty($instance['postTypes']) ? $instance['postTypes'] : array();
		$getCatNames		= (!empty($postcatidfilter) && (empty($instance['postTypes']) || in_array("post", $postTypesArr)) ) ? $siq_plugin->getTaxonomyNames($postcatidfilter) : '';
		$catFilterClause	= !empty($instance['postCatFilterClause']) ? $instance['postCatFilterClause'] : $siq_plugin->categoryFilterClause["default"];
		$filters			= array();
		$filterGlue			= "";
		$filter				= "";

		if(!empty($postcatidfilter) && !empty($getCatNames) && count($getCatNames) > 0){
			$selectedCats 	= "(".$siq_plugin->categoryFilterGlue.'"'.implode('" '.$catFilterClause.' '.$siq_plugin->categoryFilterGlue.'"', $getCatNames).'")';
			$filters[] = $selectedCats;
			$filter = "(";
			foreach( $getCatNames as $catName ){
				$filter .= $siq_plugin->categoryFilterGlue;
				$filter .= '"'.$catName.'"';
				$filter .= " ".$catFilterClause." ";
			}
			$filter = substr($filter, 0, (strlen($catFilterClause) + 2)*-1);
			$filter .= ")";
		}
			
		$strWidget .='<div id="siq-expandwdgt-cont" class="siq-expandwdgt-cont">
		  <form class="siq-expandwdgt" action="'.get_home_url().'">
		    <input type="search" placeholder="'.$searchText.'" value="'.$searchValue.'" name="s" class="siq-expandwdgt-input" aria-label="Field for entering a search query">';
			$postTypes 		= ! empty( $instance['postTypes'] ) ? ( is_array( $instance['postTypes'] ) ? implode(',', $instance['postTypes']) :  $instance['postTypes'] ) : "";
			if(!empty($postTypes)){
				$strWidget .='<input type="hidden"  value="'.$postTypes.'" name="postTypes" />';
			}
			
			if(count($filters) > 0){
				$strWidget 	   .= "<input type='hidden'  value='".esc_attr( $filter )."' name='siqACFilters' />";
			}
		    $strWidget .='<span class="siq-expandwdgt-icon"></span>
		  </form>
		</div>';
		return $strWidget;
	}


	public function form( $instance ) {
		global $siq_plugin; 
		$title 					= ! empty( $instance['title'] ) ? $instance['title'] : __( '', 'siq_text' );
		$placeholder 			= ! empty( $instance['placeholder'] ) ? $instance['placeholder'] : __( 'Search', 'siq_text' );
		$postCatFilterClause	= ! empty( $instance['postCatFilterClause'] ) ? $instance['postCatFilterClause'] : $siq_plugin->categoryFilterClause["default"];
		$postcatidfilter		= ! empty( $instance['postcatidfilter'] ) ? $instance['postcatidfilter'] : array();
		?>
		<p>
			<label for="<?php esc_attr_e( $this->get_field_id( 'title' ) ); ?>"><b><?php esc_attr_e( 'Title:' ); ?></b></label>
			<input class="widefat" id="<?php esc_attr_e( $this->get_field_id( 'title' ) ); ?>" name="<?php esc_attr_e( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php esc_attr_e( $title ); ?>">
		</p>

		<p>
			<label for="<?php esc_attr_e( $this->get_field_id( 'placeholder' ) ); ?>"><b><?php _e( esc_attr( 'Placeholder Text:' ) ); ?></b></label>
			<input class="widefat" id="<?php esc_attr_e( $this->get_field_id( 'placeholder' ) ); ?>" name="<?php esc_attr_e( $this->get_field_name( 'placeholder' ) ); ?>" type="text" value="<?php esc_attr_e( $placeholder ); ?>">
		</p>
		<?php 
			$postTypeForSearch = $siq_plugin->getPostTypesForSearchOnWidget();
			if(is_array($postTypeForSearch) && count($postTypeForSearch) > 0){
				$postTypes = ! empty( $instance['postTypes'] ) ? ( !is_array( $instance['postTypes'] ) ? explode(',', $instance['postTypes']) : $instance['postTypes'] ) : array();
		?>
			<p>
				<label><b><?php _e( esc_attr( 'Post types for search:' ) ); ?></b></label><br/>
				<?php 
					foreach($postTypeForSearch as $k => $v) { 
						$checked = in_array($v, $postTypes) ? "checked=checked" : "";
				?>
						<input <?php esc_attr_e( $checked );?> type="checkbox"  id="<?php esc_attr_e( $this->get_field_id( 'postTypes' ."_".$v) ); ?>" name="<?php esc_attr_e( $this->get_field_name( 'postTypes' ) ); ?>[]" value="<?php esc_attr_e( $v );?>" />
						<label  for="<?php esc_attr_e( $this->get_field_id( 'postTypes' ."_".$v) ); ?>" ><?php esc_attr_e( $v );?></label><br/>
				<?php } ?>
			</p>
		<?php
			}  
			if(class_exists('SIQ_Walker_Category_Checklist_Widget')){ ?>
				<div class="siq_group">
					 <div class="siq_group_inner limitheight">
						<label for="<?php esc_attr_e( $this->get_field_id( 'postcatidfilter' ) ); ?>"><b><?php esc_attr_e( 'Filter results by category:' ); ?></b></label>
						<ul class="siq_widget_list siq_widget_catlist">
							<?php   $walker = new SIQ_Walker_Category_Checklist_Widget(
													$this->get_field_name( 'postcatidfilter' ), 
													$this->get_field_id( 'postcatidfilter' )
												);
									wp_category_checklist(0,0,$postcatidfilter,false,$walker,false); ?>
						</ul>
					</div>
					<p>
						<label for="<?php esc_attr_e( $this->get_field_id( 'postCatFilterClause' ) ); ?>"><b><?php esc_attr_e( 'Clause to be used in filter:' ); ?></b></label>
						<select name="<?php esc_attr_e( $this->get_field_name( 'postCatFilterClause' ) ); ?>">
						<?php foreach($siq_plugin->categoryFilterClause["clauses"] as $k => $v){ ?>
								<option value='<?php esc_attr_e($v);?>' <?php esc_attr_e( ($postCatFilterClause == $v ? "selected='Selected'": "") ) ?>><?php esc_attr_e( $siq_plugin->createFilterLabel($v) );?></option>
						<?php } ?>
						</select>
					</p>
				</div>
			<?php
			}
	}


	public function update( $new_instance, $old_instance ) {
		$instance = array();
		
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? wp_strip_all_tags(trim($new_instance['title'])) : '';
		$instance['placeholder'] = ( ! empty( $new_instance['placeholder'] ) ) ? wp_strip_all_tags( $new_instance['placeholder'] ) : '';
		$instance['postTypes'] = ( ! empty( $new_instance['postTypes'] ) ) ? array_map( 'wp_strip_all_tags', $new_instance['postTypes'] ) : array();
		$instance['postcatidfilter'] = ( !empty($new_instance['postcatidfilter']) && (empty($new_instance['postTypes']) || ( is_array( $new_instance['postTypes'] ) && in_array("post", $new_instance['postTypes'])) ) ) ? array_map( 'wp_strip_all_tags', $new_instance['postcatidfilter'] ) : array();
		$instance['postCatFilterClause'] = (!empty($new_instance['postCatFilterClause']) && !empty($instance['postcatidfilter'])) ? $new_instance['postCatFilterClause'] : '';
		
		return $instance;
	}

}
