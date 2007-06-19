<?php
/**
 * Module file
 * 
 * This file defines abstract class Module whose provides basic modules functionality.
 * @author Paul Bukowski <pbukowski@telaxus.com>
 * @copyright Copyright &copy; 2006, Telaxus LLC
 * @licence SPL
 * @version 1.0
 * @package epesi-base
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

abstract class ModuleInstall {
	/**
	 * Module installation function.
	 * @return true if installation success, false otherwise
	 */
	abstract public static function install();

	/**
	 * Module uninstallation function.
	 * @return true if installation success, false otherwise
	 */
	abstract public static function uninstall();
}

/**
 * This class provides interface for module setup.
 * @package epesi-base
 * @subpackage module
 */
abstract class ModuleInit {
	/**
	 * Returns array that contains information about modules required by this module.
	 * The array should be determined by the version number that is given as parameter.
	 * 
	 * @return array Array constructed as following: array(array('name'=>$ModuleName,'version'=>$ModuleVersion),...)  
	 */
	abstract public static function requires();

	/**
	 * Return array that contains information which modules functionality can be provided by this module.
	 * 
	 * @return array Array constructed as following: array(array('name'=>$ModuleName,'version'=>$ModuleVersion),...)
	 */
	abstract public static function provides();

//	abstract public static function backup();
}

/**
 * This class provides some basic functionality for every epesi module.
 * @package epesi-base
 * @subpackage module
 */
abstract class Module {
	protected $parent = null;
	protected $children = array();
	private $jses = array();
	private $instance;
	private $children_count;
	private $children_count_display;
	private $type;
	private $path;
	private $reload = null;
	private $fast_process = false;
	private $inline_display = false;
	private $displayed = false;
	
	/**
	 * This function is called when module is send to client after it has been repainted.
	 * Overload this function to get control of what happens after reload.
	 */
	/**
	 * Constructor. Should not be called directly using new Module('name').
	 * Use $this->pack_module or $this->init_module (inside other module).
	 * 
	 * @param string module name 
	 */
	public final function __construct($type,$parent,$name) {
		global $base;
		$this->type = $type;
		if($parent) {
			$this->parent = & $parent;
			if(isset($name))
				$this->instance = (string)$name;
			else
				$this->instance = $parent->get_new_child_instance_id();
			$this->parent->register_child($this);
		} elseif(isset($name))
			$this->instance = (string)$name;
		$this->path = null;
		if(!isset($this->instance)) throw new Exception('No instance name or parent specified.');
		$this->children_count = 0;
		$this->children_count_display = 0;
	}
	
	public final function register_child($ch) {
		$type = $ch->get_type();
		$instance = $ch->get_instance_id();
		if(!isset($this->children[$type]))
			$this->children[$type] = array();
		$this->children[$type][$instance] = & $ch;
		$GLOBALS['base']->debug('registering '.$this->get_path().'/'.$type.'|'.$instance.'<br>');
	}
	
	public final function get_new_child_instance_id() {
		return $this->children_count++;
	}
	
	public final function & get_child($id) {
		if($this->fast_process) return false;
		$yy = explode('|',$id);
		return $this->children[$yy[0]][$yy[1]];
	}

	public final function & get_children() {
		if($this->fast_process) return false;
		$ret = array();
		foreach($this->children as $type=>$xx)
			foreach($xx as $inst=>$obj)
					$ret[$obj->get_node_id()] = & $obj;
		return $ret;
	}

	/**
	 * Returns unique path of parent module.
	 * Path contains modules hierarchy information (parent of parent etc.) for the current module.
	 * Each module in the path is described as name and instance id. 
	 * 
	 * @return string
	 */
	public final function get_parent_path() {
		if($this->parent)
			return $this->parent->get_path();
		return false;
	}
	
	public final function get_node_id() {
		return $this->type.'|'.$this->instance;
	}
	
	/**
	 * Returns unique path of calling module.
	 * 
	 * Path contains modules hierarchy information (parent of parent etc.) for current module.
	 * Each module in the path is described as name and instance id. 
	 * 
	 * Example:
	 * Module named Base/Box, instance 1, without parents:
	 * get_path returns '/Base_Box|1'
	 * 
	 * @return string unique module name 
	 */
	public final function get_path() {
		if(!isset($this->path))
			$this->path = $this->get_parent_path().'/'.$this->get_node_id(); 
		return $this->path;
	}
	
	/**
	 * Sets variable that will be available only for module instance that called this function.
	 * Note that after page refresh, this variable will preserve its value in contrary to module field variables.
	 * Module variables are hold separately for every client.
	 * 
	 * @param string key
	 * @param mixed value
	 */
	public final function set_module_variable($name, $value) {
		$session = & $GLOBALS['base']->get_session();
		$session['__module_vars__'][$this->get_path()][$name] = $value;
	}
	
	/**
	 * Returns value of a module variable. If the variable is not set, function will return value given as second parameter.
	 * For details concerning module variables, see set_module_variable. 
	 * 
	 * @param string key
	 * @param mixed default value
	 * @return mixed value
	 */
	public final function & get_module_variable($name, $default) {
		$session = & $GLOBALS['base']->get_session();
		if(isset($default) && !$this->isset_module_variable($name))
			$session['__module_vars__'][$this->get_path()][$name] = & $default;
		return $session['__module_vars__'][$this->get_path()][$name];
	}
	
	/**
	 * Returns href variable. 
	 * 
	 * If unique href variable, given as first parameter, is not set, function will try to return value of module variable by that same name.
	 * If module variable, given as first parameter, is not set, function will return default value given as second parameter.
	 * 
 	 * For details concerning href variables, see create_href.
	 * For details concerning module variables, see set_module_variable. 
	 * 
	 * @param string
	 * @return mixed
	 */
	public final function & get_module_variable_or_unique_href_variable($name, $default_value) {
		$rid = $this->get_module_variable($name, $default_value);
		if($this->isset_unique_href_variable($name))
			$rid = $this->get_unique_href_variable($name);
		if(isset($rid))
			$this->set_module_variable($name, $rid);
		return $rid;
	}

	/**
	 * Checks if variable exists.
	 * For details concerning module variables, see set_module_variable. 
	 * 
	 * @param string key
	 * @return bool true if variable exists, false otherwise
	 */
	public final function isset_module_variable($name) {
		$session = & $GLOBALS['base']->get_session();
		return isset($session['__module_vars__'][$this->get_path()][$name]);
	}
	
	/**
	 * Unset module variable.
	 * For details concerning module variables see set_module_variable. 
	 * 
	 * @param string key
	 */
	public final function unset_module_variable($name) {
		$session = & $GLOBALS['base']->get_session();
		unset($session['__module_vars__'][$this->get_path()][$name]);
	}
	
	/**
	 * Share variable passed as first parameter with module passed as second parameter.
	 * Any change of this variable will be visible in both modules.
	 * 
	 * @param string varaible name
	 * @param object module object
	 * @return bool false if module is invalid, true otherwise
	 */
	public final function share_module_variable($name, & $m, $name2) {
		if(!is_a($m, 'Module'))
			return false;
		
		if(!isset($name2)) $name2=$name;
		$session = & $GLOBALS['base']->get_session();
		
		$session['__module_vars__'][$m->get_path()][$name2] = & $session['__module_vars__'][$this->get_path()][$name];
		return true;
	}
	
	
	public function share_unique_href_variable($name, & $m, $name2) {
		if(!is_a($m, 'Module'))
			return false;
				             
		if(!isset($name2)) $name2=$name;
						             
		$s = & $m->get_module_variable('__shared_unique_vars__',array());
		$s[$name2] = $this->create_unique_key($name);
		return true;
	}
	
	/**
	 * Mark module to force its reload or prevent that.
	 * If this method is not called, module is reloaded by default,
	 * which means that only if output changed, reload proceeds.
	 * 
	 * @param bool true to force reload of whole module, false to suspend reloading
	 */
	 public final function set_reload($b) {
	 	if($this->reload==true) return;
	 	$this->reload = $b;
	 }
	 
	 /**
	  * Returns current reload settings.
	  * 
	  * @return bool true - force reload, false - no reload, null - default (reload changes only if module output changed)
	  */
	 public final function get_reload() {
	 	return $this->reload;
	 }
	 
	public final static function create_href_js(array $variables = array (), $indicator=null) {
		global $base;		
		$ret = str_replace('&amp;','&',http_build_query($variables));
		if(!isset($indicator)) $indicator='loading...';
		return 'if(saja.procOn==0){history_on=0;saja.updateIndicatorText(\''.addslashes($indicator).'\');'.$base->run("process(".$base->get_client_id().",'".$ret."')").'}';
	}
	
	/**
	 * Create onClick action string (with href="javascript:void(0);").
	 * Use variables passed as first parameter, to generate variables accessible by $_REQUEST array.
	 * 
	 * <xmp>
	 * print('<a '.$this->create_href(array('somekey'=>'somevalue'))).'">Link</a>');
	 * </xmp>
	 * 
	 * @param array variables to pass along with href
	 * @return string
	 */
	public final static function create_href(array $variables = array (),$indicator=null) {
		return ' href="javascript:void(0)" onClick="'.self::create_href_js($variables,$indicator).'" ';
	}
	

	public final static function create_confirm_href($confirm, array $variables = array (), $indicator=null) {
		$ret = http_build_query($variables);
		return ' href="javascript:void(0)" onClick="if(confirm(\''.addslashes($confirm).'\')) {'.self::create_href_js($variables,$indicator).'}"';
	}

	/**
	 * Similar to create_href, but variables passed to this function will only be accessible in module that called this function.
	 * Those variables can be accessed with get_unique_href_variable.
	 * 
	 * @param array variables to pass along with href
	 * @return string
	 */
	public final function create_unique_href(array $variables = array (),$indicator=null) {
		$uvars = array('__action_module__'=>$this->get_path());
		foreach ($variables as $a => $b)
			$uvars[$this->create_unique_key($a)] = $b;
		return $this->create_href($uvars,$indicator);
	}
	public final function create_unique_href_js(array $variables = array (),$indicator=null) {
		$uvars = array('__action_module__'=>$this->get_path());
		foreach ($variables as $a => $b)
			$uvars[$this->create_unique_key($a)] = $b;
		return $this->create_href_js($uvars,$indicator);
	}
	public final function create_confirm_unique_href($confirm,array $variables = array (),$indicator=null) {
		$uvars = array('__action_module__'=>$this->get_path());
		foreach ($variables as $a => $b)
			$uvars[$this->create_unique_key($a)] = $b;
		return $this->create_confirm_href($confirm, $uvars,$indicator);
	}
	
	/**
	 * Returns variable passed with create_unique_href.
	 * 
	 * @param string key
	 * @return mixed value
	 */
	public final function get_unique_href_variable($key) {
		$rkey = $this->create_unique_key($key);
		if(isset($_REQUEST[$rkey])) return $_REQUEST[$rkey];
		return null;
	}
	
	/**
	 * Checks if variable given as first parameter was passed with create_unique_href function.
	 * 
	 * @param string key
	 * @return bool true if variable was declared, false otherwise
	 */
	public final function isset_unique_href_variable($key) {
		$rkey = $this->create_unique_key($key);
		return array_key_exists($rkey, $_REQUEST);
	}
	
	/**
	 * Creates link similar to link created with create_href.
	 * 
	 * The link, when used, will lead to calling of function which name is given as first parameter.
	 * Callback returns true if you use this link again after page refresh.
	 * 
	 * WARNING: id of callback is generated using arguments passed to this function, so if you want to create
	 * callback that run on every page reload, with different arguments, use create_callback_href_with_id
	 *
	 * @param mixed function
	 * @return string
	 */
	public final function create_callback_href($func,$args,$indicator=null) {
		$name = 'callback_'.md5(serialize(array($func,$args)));
		return $this->create_callback_href_with_id($name,$func,$args,$indicator);
	}

	public final function create_confirm_callback_href($confirm, $func, $args,$indicator=null) {
		$name = 'callback_'.md5(serialize(array($func,$args)));
		return $this->create_confirm_callback_href_with_id($name, $confirm, $func,$args,$indicator);
	}

	private final function call_callback_href_function($name,$func,$args) {
		$ret = $this->get_module_variable_or_unique_href_variable($name);
		if($ret=='1') {
			if(!is_array($args)) $args = array($args);
			if(!is_callable($func))
				trigger_error(print_r($func,true)." not callable",E_USER_ERROR);
			$r = call_user_func_array($func,$args);
			if(!$r) $this->unset_module_variable($name);
		}	
	}
	/**
	 * Creates link similar to links created with create_href.
	 * 
	 * The link, when used, will lead to calling of function which name is given as first parameter.
	 * Callback returns true if you use this link again after page refresh.
	 * 
	 * @param string callback id (name)
	 * @param mixed function
	 * @return string 
	 */
	public final function create_callback_href_with_id($name, $func, $args,$indicator) {
		$this->call_callback_href_function($name,$func,$args);
		return $this->create_unique_href(array($name=>1),$indicator);
	}
	
	public final function create_confirm_callback_href_with_id($name, $confirm, $func, $args, $indicator) {
		$this->call_callback_href_function($name,$func,$args);
		return $this->create_confirm_unique_href($confirm,array($name=>1),$indicator);
	}
	
	/**
	 * Creates link that will lead back to previous page content.
	 * Use is_back to check it was called.
	 * 
	 * @return string string that should be placed inside html <pre><a></pre> tag. See create_href for example.
	 */
	public final function create_back_href() {
		return $this->create_unique_href(array('back'=>1));
	}

	/**
	 * Sets reload location to previous page display.
	 * Use is_back to control when this method was called.
	 */
	public final function set_back_location() {
		location(array($this->create_unique_key('back')=>1,'__action_module__'=>$this->get_path()));
	}
	
	/**
	 * Checks if set_back_location was used.
	 * 
	 * @return bool true if back link was used, false otherwise
	 */
	public final function is_back() {
		return ($this->get_unique_href_variable('back')=='1');
	}

	/**
	 * Checks access to function which name is passed as first parameter.
	 * 
	 * If you want to restric access to a function just make function named
	 * 'functionname_access' returning false if user should not access this function.
	 * 
	 * This function is called automatically with each pack_module call.
	 * 
	 * @param string function name
	 * @return bool true if access is granted, false otherwise
	 */
	public final function check_access($m) {
		return ModuleCommon::check_access($this->type,$m);
	}
		
	/**
	 * Creates module instance which name is given as first parameter.
	 * 
	 * Created module instance will be child of the module which called this function. 
	 * 
	 * @param string module name
	 * @return mixed if access denied returns null, else child module object
	 */
	public final function & init_module($module_type, $args = null, $name=null) {
		$module_type = str_replace('/','_',$module_type);
		$m = & ModuleManager::new_instance($module_type,$this,$name);

		if($args!==null && !is_array($args)) $args = array($args);

		if(method_exists($m,'construct'))
			call_user_func_array(array($m,'construct'),$args);
		
		return $m;
	}
	
	/**
	 * Call method of the module passed as first parameter, 
	 * which name is passed as second parameter.
	 * You can pass additional arguments as next parameters. 
	 * 
	 * @param module child module
	 * @param string function to call (get output from), if user has enought privileges.
	 * @param mixed variables
	 * @return mixed if access denied returns false, else true
	 */
	public final function display_module(& $m, $args, $function_name = 'body') {
		$ret = $this->get_html_of_module($m,$args,$function_name);
		if($ret===false) return false;
		print($ret);
		return true;
	}
	
	/**
	 * Call method of the module passed as first parameter, 
	 * which name is passed as second parameter.
	 * You can pass additional arguments as next parameters. 
	 * Attention: do not pass the result of this function by one module to another module.
	 * 
	 * @param module child module
	 * @param string function to call (get output from), if user has enought privileges.
	 * @param mixed variables
	 * @return mixed if access denied returns false, else string
	 */
	public final function get_html_of_module(& $m, $args, $function_name = 'body') {
		global $base;
		
		$this_path = $this->get_path();
		
		if(!$m) trigger_error('Arument 0 for display_module is null.',E_USER_ERROR);
		if($this_path!=$m->get_parent_path()) return false;
		
		if (!method_exists($m, $function_name))
			trigger_error('Invalid method name given as argument 2 for display_module.',E_USER_ERROR);
		
		if($m->displayed())
			trigger_error('You can\'t display the same module two times.',E_USER_ERROR);

		if (!$m->check_access($function_name))
			return false;
			//we cannot trigger error here, couse logout doesn't work
			//trigger_error('Method given as argument 2 for display_module inaccessible.<br>$'.$this->get_type().'->display_module(\''.$m->get_type().'\','.$args.',\''.$function_name.'\');',E_USER_ERROR);
		
		$s = & $m->get_module_variable('__shared_unique_vars__',array());
		foreach($s as $k=>$v) {
			$_REQUEST[$m->create_unique_key($k)] = & $_REQUEST[$v];
		}
		
		if(MODULE_TIMES)
			$time = microtime(true);
		//define key in array so it is before its children
		$path = $m->get_path();
		if(!$m->is_inline_display()) 
			$base->content[$path]['span'] = $this_path.'|'.$this->children_count_display.'content';
		$base->content[$path]['module'] = & $m;
		
		$tmp_session = & $base->get_tmp_session();

		if(!$m->is_fast_process() || strpos($_REQUEST['__action_module__'],$path)===0 || !isset($tmp_session['__module_content__'][$path])) {
			if(!is_array($args)) $args = array($args);
			
			ob_start();
			call_user_func_array(array($m,$function_name),$args);
			if(STRIP_OUTPUT)
				$base->content[$path]['value'] = strip_html(ob_get_contents());
			else
				$base->content[$path]['value'] = ob_get_contents();
			ob_end_clean();
			$base->content[$path]['js'] = $m->get_jses();
		} else {
			$base->content[$path]['value'] = $tmp_session['__module_content__'][$path]['value'];
			$base->content[$path]['js'] = $tmp_session['__module_content__'][$path]['js'];
			$base->debug('Fast process of '.$path.'<br>');
		}
		if(MODULE_TIMES)
			$base->content[$path]['time'] = microtime(true)-$time;
					
		$this->children_count_display++;	
		
		$m->mark_displayed();
		
		if($m->is_inline_display()) return $base->content[$path]['value'];
		return '<span id="'.$base->content[$path]['span'].'"></span>';
	}
	
	public final function displayed() {
		return $this->displayed;
	}

	public final function mark_displayed() {
		$this->displayed=true;
	}
	
	public final function is_fast_process() {
		return $this->fast_process;
	}
	
	public final function set_fast_process() {
		$this->fast_process = true;
	}
	
	public final function is_inline_display() {
		return $this->inline_display;
	}
	
	public final function set_inline_display() {
		$this->inline_display = true;
	}

	/**
	 * Creates instance of module given as first parameter as a child of the module that has called this function. 
	 * Also, this function will call newly created module's method, which name is passed as second parameter.
	 * You can pass additional arguments as next parameters.
	 * 
	 * @param string child module name
	 * @param string function to call
	 * @param mixed variables
	 * @return mixed if access denied returns null, otherwise returns child module object
	 */
	public final function & pack_module($module_type, $display_args, $function_name = 'body', $construct_args, $name) {
		if(!is_array($construct_args)) $construct_args = array($construct_args);
		$m = & $this->init_module($module_type,$construct_args,$name);
		
		$args = func_get_args();
		$args[0] = &$m;
		
		if(!is_array($display_args)) $display_args = array($display_args);
		if($this->display_module($m, $display_args, $function_name))
			return $m;
			
		return null;
	}
	
	public final function js($js) {
		$this->jses[] = $js;
	}
	
	public final function get_jses() {
		return $this->jses;
	}

	/**
	 * Returns name(type) of parent module.
	 * 
	 * @return string module name 
	 */
	public final function get_parent_type() {
		if($this->parent)
			return $this->parent->get_type();
		return false;
	}
	
	/**
	 * Returns name(type) of module that called this function.
	 * 
	 * @return string 
	 */
	public final function get_type() {
		return $this->type;
	}

	public final function get_instance_id() {
		return $this->instance;
	}
	
	/**
	 * Returns unique key name, generated from unique name of this module (function get_path) and string parameter.
	 * 
	 * This function is called inside create_unique_href function and should not be used directly.
	 * 
	 * @param string
	 * @return string
	 */
	public final function create_unique_key($name) {
		return $this->get_path() . '_' . $name;
	}

	/**
	 * Returns path to the default data directory for module calling this method.
	 * Use this directory if your module requires to create or operate on a file.  
	 * 
	 * @return string path to the data directory
	 */
	protected final function get_data_dir() {
		return 'data/'.str_replace('_','/',$this->type).'/';
	}

	/**
	 * Returns path to the module directory.  
	 * 
	 * @return string path to the module directory
	 */
	protected final function get_module_dir() {
		return 'modules/'.str_replace('_','/',$this->type).'/';
	}
}

?>
