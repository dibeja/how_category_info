<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * Category Info Plugin
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Plugin
 * @author		Luca Di Bella
 * @link		
 */

$plugin_info = array(
	'pi_name'		=> 'Category Info',
	'pi_version'	=> '1.0',
	'pi_author'		=> 'Luca Di Bella',
	'pi_author_url'	=> '',
	'pi_description'=> 'Fetch Category data based on category id',
	'pi_usage'		=> Category_info::usage()
);


class Category_info {
	
	public $return_data;
	public $cat_id;
    
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
		
		if (!$this->EE->TMPL->fetch_param('cat_id'))
		{
			return;
		} 
		
		$this->cat_id = $this->EE->TMPL->fetch_param('cat_id');

		// fetch category field names and id's
	
		// limit to correct category group
		$gquery = $this->EE->db->query("SELECT group_id FROM exp_categories WHERE cat_id = '".$this->cat_id."'");

		if ($gquery->num_rows() == 0)
		{
			return $this->EE->TMPL->no_results();
		}

		$query = $this->EE->db->query("SELECT field_id, field_name
							FROM exp_category_fields
							WHERE site_id IN ('".implode("','", $this->EE->TMPL->site_ids)."')
							AND group_id = '".$gquery->row('group_id')."'");

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$this->catfields[] = array('field_name' => $row['field_name'], 'field_id' => $row['field_id']);
			}
		}

		$field_sqla = ", cg.field_html_formatting, fd.* ";
		$field_sqlb = " LEFT JOIN exp_category_field_data AS fd ON fd.cat_id = c.cat_id
						LEFT JOIN exp_category_groups AS cg ON cg.group_id = c.group_id ";

		$query = $this->EE->db->query("SELECT c.group_id, c.cat_name, c.parent_id, c.cat_url_title, c.cat_description, c.cat_image {$field_sqla}
							FROM exp_categories AS c
							{$field_sqlb}
							WHERE c.cat_id = '".$this->cat_id."'");

		if ($query->num_rows() == 0)
		{
			return $this->EE->TMPL->no_results();
		}

		$row = $query->row_array();

		$this->EE->load->library('file_field');
		$cat_image = $this->EE->file_field->parse_field($query->row('cat_image'));

		$cat_vars = array('category_name'		=> $query->row('cat_name'),
						  'category_description'	=> $query->row('cat_description'),
						  'category_image'			=> $cat_image['url'],
						  'category_id'				=> $this->cat_id,
						  'category_group'			=> $query->row('group_id'),
						  'parent_id'					=> $query->row('parent_id'));

		// add custom fields for conditionals prep
		foreach ($this->catfields as $v)
		{
			$cat_vars[$v['field_name']] = ($query->row('field_id_'.$v['field_id'])) ? $query->row('field_id_'.$v['field_id']) : '';
		}
		
		//print_r('tagdata: ' . $this->EE->TMPL->tagdata);
		$tagdata = $this->EE->TMPL->tagdata;
		$tagdata = $this->EE->functions->prep_conditionals($tagdata, $cat_vars);
		
		$variables[] = $cat_vars;
		
		$this->return_data = $this->EE->TMPL->parse_variables($tagdata, $variables);
		return;
	}
	
	// ----------------------------------------------------------------
	
	/**
	 * Plugin Usage
	 */
	public static function usage()
	{
		ob_start();
?>

Docs goes here...
<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}
}


/* End of file pi.category_info.php */
/* Location: /system/expressionengine/third_party/category_info/pi.category_info.php */