<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the module this extends
  */
reason_include_once('classes/admin/modules/associator.php');
reason_include_once('function_libraries/util.php');

/**
 * An administrative module that provides an interface for making b-to-a relationships between entities
 */
class ReverseAssociatorModule extends AssociatorModule // {{{
	{
		var $viewer;
		var $filter;
		var $associations;

		function ReverseAssociatorModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		function get_associations() // {{{
		{
			$d = new DBSelector;

			$d->add_table('ar','allowable_relationship' );
		
			$d->add_table( 'allowable_relationship' );
			$d->add_table( 'relationship' );
			$d->add_table( 'entity' );
			
			$d->add_relation( 'allowable_relationship.name = "site_to_type"' );
			$d->add_relation( 'allowable_relationship.id = relationship.type' );
			$d->add_relation( 'relationship.entity_a = '.$this->admin_page->site_id );
			$d->add_relation( 'relationship.entity_b = ar.relationship_b' );
			$d->add_relation( 'entity.id = ar.relationship_a' );
			
			$d->add_field( 'entity' , 'id' , 'e_id' );
			$d->add_field( 'entity' , 'name' , 'e_name' );
			$d->add_field('ar','*');

			$d->add_relation( 'ar.relationship_b = ' . $this->admin_page->type_id );
			if (reason_relationship_names_are_unique())
			{
				$d->add_relation('ar.type = "association"');
			}
			else
			{
				$d->add_relation('ar.name != "owns"');
			}
			$d->add_relation('(ar.custom_associator IS NULL OR ar.custom_associator = "")');
			
			$r = db_query( $d->get_query() , 'Error selecting relationships' );

			$return_me = array();
			while( $row = mysql_fetch_array( $r , MYSQL_ASSOC ) )
				$return_me[ $row[ 'id' ] ] = $row;
			$this->associations = $return_me;
			if( empty( $this->admin_page->rel_id ) )
			{
				reset( $this->associations );
				list( $key , ) = each( $this->associations );

				$this->admin_page->rel_id = $key;
			}
		} // }}}
		function get_viewer($site_id , $type_id , $lister) //{{{
		{
			$this->viewer = new reverse_assoc_viewer;
			$this->viewer->set_page( $this->admin_page );
			$this->viewer->init( $site_id, $type_id , $lister ); 
		} // }}}
		function list_associations() // {{{
		{
			foreach( $this->associations AS $id => $ass )
			{
				if( $id == $this->admin_page->rel_id )
				{
					$start = '<strong>';
					$finish = '</strong>';
				}
				else
				{
					$start = '<a href="' . $this->admin_page->make_link( array( 'rel_id' => $id ) ) . '">';
					$finish = '</a>';
				}
				echo $start . 'Associate with ' . $ass[ 'e_name' ] . $finish . '<br />';
			}
		} // }}}
	} // }}}

?>