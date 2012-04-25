<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Forum select
 *
 * This file must be in your /system/third_party/forum_select directory of your ExpressionEngine installation
 *
 * @package             Forum Select (EE2)        
 * @author              Mark Croxton                        
 * @copyright			Copyright (c) 2012 Mark Croxton     
 * @link                http://hallmark-design.co.uk    
 */

class Forum_select_ft extends EE_Fieldtype {

	public $info = array(
		'name'		=> 'Forum Select',
		'version'	=> '1.0.0'
	);
	
	// enable tag pairs
	public $has_array_data = TRUE;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		parent::__construct();	
	}

	/**
	 * Display the settings form for each custom field
	 * 
	 * @param mixed $data
	 * @return string Override the field custom settings with custom html
	 * @access public
	 */
	function display_settings($data)
	{
		$data = $this->_default_settings($data);

		$forum_options = $this->_forum_data();	

		$this->EE->table->add_row(
			'Forum categories',
			$this->_render_select($this->field_id.'_field_settings[categories]', $forum_options, $data['categories'], TRUE)
		);
		
		// Allow multiple selections?
		$this->EE->table->add_row(
			'Allow multiple selections?',
			$this->_build_multi_radios($data['allow_multiple'], 'allow_multiple')
		);
	}

	/**
	 * Save the custom field settings
	 * 
	 * @return boolean Valid or not
	 * @access public
	 */
	function save_settings()
	{
		// remove empty values
		$new_settings = array_filter( $this->EE->input->post('forum_select_field_settings') );
		return $new_settings;
	}
	
	/**
	 * Display Global Settings
	 * 
	 * @return string
	 * @access public
	 */
	function display_global_settings()
	{
		$this->EE->cp->add_to_head('
			<script type="text/javascript">
			$(document).ready(function() {
				$(\'.pageContents input\').attr("value", "OK");
			});				
			</script>');
			
		return '<p>This fieldtype requires the Discussion Forum Module.</p>';
	}	
	
	/**
	 * Save field
     *
	 * @param mixed $data
     * @return string
     * @access public
     */
	function save($data)
	{
		// flatten array if multiple selections are allowed
		if (is_array($data)) 
		{
			$data = implode("\n", $data);
		}
		return $data;
	}
	
	/**
	 * Save cell
     *
	 * @param mixed $data
     * @return string
     * @access public
     */
	function save_cell($data)
	{
		return $this->save($data);
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Normal Fieldtype Display
     *
	 * @param mixed $data
     * @return array
     * @access public
     */
	function display_field($data)
	{	
		$selected = $this->_get_selected_forum_ids($data);
		return $this->_forum_select($selected, $this->field_name, $this->field_id, $this->settings);
	}

	/**
	 * Matrix Cell Display
     *
	 * @param mixed $data
     * @return array
     * @access public
     */
	function display_cell($data)
	{	
		$selected = $this->_get_selected_forum_ids($data);
		return $this->_forum_select($selected, $this->cell_name, $this->field_id, $this->settings);
	}
	
	
	// --------------------------------------------------------------------
	
	/**
	 * Pre-process
	 *
	 * If multiple selections are allowed, this will turn the string of
	 * forum IDs into an array.
     *
	 * @param mixed $data
     * @return array
     * @access public
     */		
	function pre_process($data)
	{
		// Establish Settings
		$settings = $this->_default_settings($this->settings);

		// if multiple selections aren't allowed, just return the forum ID
		if ($settings['allow_multiple'] != 'y') 
		{
			return $data;
		}

		$data = explode("\n", $data);

		foreach ($data as &$forum)
		{
			$forum = array('forum_id' => $forum);
		}

		return $data;
	}
	
	/**
	 * Replace Tag
	 *
	 * If only a single forum selection is allowed, this will just return
	 * the selected forum ID. Otherwise, it'll loop through the tag pair,
	 * parsing the {forum_id} single variable tags.
     *
	 * @param mixed $data
	 * @param array $params
	 * @param string $tagdata
     * @return string
     * @access public
     */	
	function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		// Establish Settings
		$settings = $this->_default_settings($this->settings);

		// if multiple selections aren't allowed, just return the forum ID
		if ($settings['allow_multiple'] != 'y') 
		{
			return $data;
		}
		
		// check for tagdata, if no tagdata, spit out a pipe separated list of the category ids
		if ($tagdata === FALSE)
		{
			$forums = array();
			
			foreach ($data as $array) 
			{
				$forums[] = $array['forum_id'];
			}
			
			return implode('|', $forums);
		}
		
		// fallback for Matrix
		if (is_string($data)) 
		{ 
			$data = $this->pre_process($data); 
		}
		
		// loop through the tag pair for each selected forum,
		// parsing the {forum_id} tags
		$parsed = $this->EE->TMPL->parse_variables($tagdata, $data);

		// backspace= param
		if (isset($params['backspace']) && $params['backspace'])
		{
			$parsed = substr($parsed, 0, -$params['backspace']);
		}

		return $parsed;
	}
	
	/*
	================================================================
    Utility methods
	================================================================
	*/
	
	/**
	 * Ensure we have fallback values for field settings
	 * 
	 * @param array $data
	 * @return array
	 * @access private
	 */
	private function _default_settings($data)
	{
		return array_merge(array(
			'categories'		=> array(),
			'allow_multiple'	=> 'n',
		), $data);
	}
	
	/**
     * Builds a string of yes/no radio buttons
     *
	 * @param string $data
	 * @param string $name
     * @return string
     * @access private
     */
	private function _build_multi_radios($data, $name)
	{
		$name = $this->field_id.'_field_settings['.$name.']';
		
		return form_radio($name, 'y', ($data == 'y') ) . NL
			. 'Yes' . NBS.NBS.NBS.NBS.NBS . NL
			. form_radio($name, 'n', ($data == 'n') ) . NL
			. 'No';
	}
	
	/**
     * Get selected forum IDs
     *
	 * @param mixed $data
     * @return mixed
     * @access private
     */
	private function _get_selected_forum_ids($data)
	{
		$selected = array();

		if ( isset($_POST[$this->field_name]) && $_POST[$this->field_name] )
		{
			$selected = $_POST[$this->field_name];
		}
		
		// make sure we have an array
		if ( is_string($data) )
		{
			$selected = explode("\n", $data);
		}
		
		return $selected;
	}   
	
	/**
     * Forum select
     *
	 * @param array $data
	 * @param string $name
	 * @param mixed $field_id
	 * @param array $settings 
     * @return string select HTML
     * @access private
     */
	private function _forum_select($data, $name, $field_id=false, $settings=array())
	{
		$settings = $this->_default_settings($settings);
		$parents = NULL;
		$multiselect = $settings['allow_multiple'] == 'y' ? TRUE : FALSE;
		
		// filter by category - remove any null entries first
		$settings['categories'] = array_filter($settings['categories'], 'strlen');
		
		if ( ! empty($settings['categories']))
		{		
			$parents = $settings['categories'];
		}

		$forum_options = $this->_forum_data($parents);
		return $this->_render_select($name, $forum_options, $data, $multiselect, TRUE, 'None');
	}
	
	/**
     * Forum data
     *
	 * @param mixed $parents
     * @return array
     * @access private
     */
	private function _forum_data($parents=NULL)
	{
		// limit to selected parents, or NULL by default (i.e. show only top level forums - categories)
		if ( is_array($parents))
		{
			$parents = implode(",", $parents);
			$where = "AND f.forum_parent IN ({$parents}) OR f.forum_id IN ($parents)";
		}
		elseif ( is_int($parents) )
		{
			$where = "AND f.forum_parent = {$parents} OR f.forum_id = {$parents}";
		}
		else
		{
			$where = "AND f.forum_parent IS NULL";
		}
		
		$sql = "SELECT f.forum_id AS `id`, f.forum_name AS `title`, IFNULL(f.forum_parent, 0) AS `parent_id`, fb.board_label AS `board`
		        FROM exp_forums f, exp_forum_boards fb
		        WHERE f.board_id = fb.board_id 
				{$where}
		        ORDER BY fb.board_name, f.forum_order";	
		#echo $sql;
		
		$forums = $this->EE->db->query($sql);	
									
		if ($forums->num_rows())
		{	
			// group cats by parent_id
			$forums_by_parent = $this->_forums_by_parent($forums->result_array());

			// flatten into sorted and indented options
			$this->_forums_select_options($forums_options, $forums_by_parent);
			
			return $forums_options;
		}

		return array();
	}

	/**
     * Group forums by parent 
     *
	 * @param array $forums
     * @return array
     * @access private
     */
	private function _forums_by_parent($forums)
	{
		$forums_by_parent = array();

		foreach ($forums as $forum)
		{
			if (! isset($forums_by_parent[$forum['parent_id']]))
			{
				$forums_by_parent[$forum['parent_id']] = array();
			}

			$forums_by_parent[$forum['parent_id']][] = $forum;
		}

		return $forums_by_parent;
	}

	/**
     * Forum Select Options
     *
	 * @param array $forums
	 * @param array $forums_by_parent
	 * @param string $parent_id
	 * @param string $indent
     * @return array
     * @access private
     */
	private function _forums_select_options(&$forums=array(), &$forums_by_parent, $parent_id='0', $indent='')
	{
		foreach ($forums_by_parent[$parent_id] as $forum)
		{
			$forum['title'] = $indent.$forum['title'];
			$forums[] = $forum;
			if (isset($forums_by_parent[$forum['id']]))
			{
				$this->_forums_select_options($forums, $forums_by_parent, (string)$forum['id'], $indent.NBS.NBS.NBS.NBS);
			}
		}
	}
	
	/**
     * Render select menu
     *
	 * @param array $name
	 * @param array $rows
	 * @param array $selected_ids
	 * @param bool $multi
	 * @param bool $optgroups
	 * @param string $default
	 * @param string $attr
     * @return string
     * @access private
     */
	private function _render_select($name, $rows, $selected_ids, $multi = TRUE, $optgroups = TRUE, $default = "any", $attr='')
	{
		$attr = ' style="width: 230px" '.$attr;
		
		$options = $this->_render_select_options($rows, $selected_ids, $optgroups, $row_count, $default);
		
		if ($multi)
		{
			return '<select name="'.$name.'[]" multiple="multiple" size="'.($row_count < 10 ? $row_count : 10).'"'.$attr.'>'
		       . $options
		       . '</select>';
		}
		else
		{
			return '<select name="'.$name.'"'.$attr.'>'
		       . $options
		       . '</select>';
		}
	}
	
	/**
     * Render select menu options
     *
	 * @param array $rows
	 * @param array $selected_ids
	 * @param bool $optgroups
	 * @param int $row_count
	 * @param string $default
     * @return string
     * @access private
     */
	private function _render_select_options($rows, $selected_ids = array(), $optgroups = TRUE, &$row_count = 0, $default = "any")
	{
		if ($optgroups) $optgroup = '';
		$options = '<option value=""'.($selected_ids || empty($data) ? '' : ' selected="selected"').'>&mdash; '.lang($default).' &mdash;</option>';
		$row_count = 1;

		foreach ($rows as $row)
		{
			if ($optgroups && isset($row['group']) && $row['group'] != $optgroup)
			{
				if ($optgroup) $options .= '</optgroup>';
				$options .= '<optgroup label="'.$row['group'].'">';
				$optgroup = $row['group'];
				$row_count++;
			}

			$selected = in_array($row['id'], $selected_ids) ? 1 : 0;
			$options .= '<option value="'.$row['id'].'"'.($selected ? ' selected="selected"' : '').'>'.$row['title'].'</option>';
			$row_count++;
		}

		if ($optgroups && $optgroup) $options .= '</optgroup>';

		return $options;
	}
}

// END forum_select_ft class

/* End of file ft.forum_select.php */
/* Location: ./system/expressionengine/third_party/forum_select/ft.forum_select.php */