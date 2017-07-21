<?php
require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');

class Genesis_Club_Fonts_Table extends WP_List_Table {

	private $fonts;
	private $slug;
	
 	function __construct($admin_url, $fonts) {
		$this->fonts = $fonts;
 		 $this->slug = $admin_url; 
 		 parent::__construct( array('plural' => 'fonts') );
 	}

 	function get_url($action, $id='') {
   		return $this->slug.'&amp;action=' . $action . (!empty($id) ? ('&amp;font_id='.$id) : '');
 	}

 	function ajax_user_can() {
  		return true; //current_user_can( 'manage_options' );
 	}

	function prepare_items() {
		$search = isset( $_REQUEST['s'] ) ? $_REQUEST['s'] : '';
		$per_page = 50;
		$paged = $this->get_pagenum();
		$this->items = array();
		if (($families = $this->fonts->get_option('families'))
		&& is_array($families)) {
         foreach ($families as $key => $values)
			   $this->items[] = json_decode(json_encode(array('font_id' => $key)+$values), FALSE);
		} else {
         $families = array();
		}
		$this->set_pagination_args( array( 'total_items' => count($families), 'per_page' => $per_page) );
 	}

 	function no_items() {
  		_e( 'No fonts found.' );
 	}

 	function get_bulk_actions() {
  		$actions = array();
  		$actions['delete'] = __( 'Delete' );
  		return $actions;
 	}

 	function get_columns() {
 		return array('cb' => ''
   			,'font_id' => __( 'Font Name' )  
   			,'variants' => __( 'Font Variants' )
			);
 	}

 	function get_sortable_columns() {
 		 return array(
 		  	'font_id' => 'font_id',
  			);
 	}

 	function display_rows() {
		$columns = $this->get_columns();
		$hidden = array();
		$alt = 0; 
   		foreach ( $this->items as $font ) {
   			$font_id = esc_attr( $font->font_id);
   			$font_name = esc_attr( $font->family);
   			$style = ( $alt++ % 2 ) ? '' : ' class="alternate"';
   			$edit_font = $this->get_url('edit', $font_id);
			   $delete_font = $this->get_url('delete', $font_id) . '&noheader=true&_wpnonce=' . wp_create_nonce( 'delete_font_' . $font_id ) ;   
			   echo('<tr id="font-'.$font_id.'" valign="middle"'.$style.'>');
   			foreach ( $columns as $column_name => $column_display_name ) {
               $class = "class='column-$column_name'";
               $style = in_array( $column_name, $hidden ) ? ' style="display:none;"' : '';
               $attributes = $class . $style;
               switch ( $column_name ) {
			 		   case 'cb':
						   echo '<th scope="row" class="check-column"><input type="checkbox" name="cb[]" value="'. esc_attr( $font_id ) .'" /></th>';
						   break;    
                  case 'font_id':
                     echo ('<td $attributes><strong><a class="row-title" href="'.$edit_font.'" title="' . 
    	  					   esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;' ), $font_name ) ) . 
    	  					   '">'.$font_name.'</a></strong><br />');
                     $actions = array();
                     $actions['edit'] = '<a href="' . $edit_font . '">' . __( 'Edit' ) . '</a>';
                     $actions['delete'] = sprintf('<a class="submitdelete" href="%1$s" onclick="return confirm(\'You are about to remove the font %2$s\n Press `Cancel` to stop, `OK` to delete.\' );">%3$s</a>', $delete_font, $font_name, __( 'Remove' ) );
                     echo $this->row_actions( $actions );
                     echo '</td>';
                     break;
                  case 'variants':
                     printf ('<td %1$s>%2$s</td>', $attributes, implode(',', $font->$column_name));
                     break;

                  default:
    	   				printf ('<td %1$s>%2$s</td>', $attributes, $font->$column_name);
    	   				break;
               }
   			} //end column
  			echo('</tr>');
  		} //end each row
 	} //end function	
} //end class
