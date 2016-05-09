<?php
/* Tiny Template Engine
 *
 * - Assign view data via assign()
 *
 *  View Template Syntax
 *  - Print variables via {$variablename}
 *    - All variable contents are escaped to prevent XSRF, access raw html via {$variablenameraw}
 *
 *  - Assign variables via {assign var="variablename" value=[php term]}
 *  - Execute functions via {strtoupper($variablename)}
 *  - Conditional Statements via {if [php term]} {/if}
 *  - The use of {else} and {elseif [php term]} is also possible
 *  - Loop through arrays via {foreach from=$array item=item} or {foreach from=$array key="key"}
 *  - Store HTML output for later printing via
 *	      {capture name="header"}sadf lsadkfjsladkfj slkdf{/capture}   and then {$header}
 *
 *  - Include files via {include file="filename"}
 *
 *  - It's also possible to include files and assign variables in one command:
 *		{include file="filename" foo="bar" test=$var}
 * 
 *  - The use of <?php ?> - Tags is possible but discouraged!
 *
 *  - Anything inside curly braces is checked for template syntax, to prevent it escape the openeing curly brace. E.g.\{$bla} is not being parsed
 *
 * NOT possible:
 * - PHP Terms inside {} - e.g. {$num+$num} or {test($bla+$bla)}
 * - Curly braces inside a template term. E.g. {assign var="value" value="the {$title} page"} is not possible.
 */
class View {
	private $data = array();
	private $templatedir = "";
	private $compiledir = "";
	private $viewfilename;
	
	function __construct($basepath = ".") {
		$this->templatedir = $basepath . "/templates/";
		$this->compiledir = $basepath . "/templates_c/";
	}

	function setTemplatesDirectory($dir) {
		$this->templatedir= $dir;
	}
	
	function getTemplatesDirectory() {
		return $this->templatedir;
	}
	
	function assign($name, $value, $defaultvalue = null, $unfiltered = false) {
		if (empty($value)) $value = $defaultvalue;
		
		if ($unfiltered ) {
			$this->data[$name] = $value;
		} else {
			$this->data[$name] = $this->escape($value);
			$this->data[$name . "raw"] = $value;
		}
	}
	
	function unsetVar($name) {
		unset($this->data[$name]);
		unset($this->data[$name."raw"]);
	}
	
	
	function escape($value) {
		if (is_array($value)) {
			foreach ($value as &$singular) {
				$singular = $this->escape($singular);
			}
		}
		if (is_string($value)) {
			$value = htmlspecialchars($value);
		}
		return $value;
	}


	function display($viewfilename) {
		$this->load($viewfilename);
	}
	
	function fetch($viewfilename) {
		$this->load($viewfilename, true);
	}
	
	/* Used internally inside templates */
	private function includeFile($viewfilename) {
		$this->load($viewfilename);
	}
	
	protected function load($viewfilename, $fetch = false) {
		$view = $this;
		
		// May be called without the .php extension
		if (!preg_match("/\.tpl$/", $viewfilename)) {
			$viewfilename.= ".tpl";
		}
		
		$this->viewfilename = $viewfilename;
		
		foreach ($this->data as $_varname => $value) {
			$$_varname = $value;
		}
		
		$rawpath = $this->templatedir. $viewfilename;
		$compiledpath = $this->compiledir. str_replace(".tpl", ".php", $viewfilename);
		
		
		if (!file_exists($compiledpath) || filemtime($rawpath) >= filemtime($compiledpath)) {
			$str = file_get_contents($rawpath);
			
			$str = $this->parseTemplate($str);
		
			if (!is_dir($this->compiledir)) {
				mkdir($this->compiledir);
			}
			
			file_put_contents($compiledpath, $str);
		}

		if ($fetch) {
			ob_start();
			include($compiledpath);
			$str = ob_get_contents();
			ob_end_clean();
			return $str;
		} else {
			include($compiledpath);

		}
	}
	
	
    function parseTemplate($content) {
		$content = preg_replace_callback (
			"/(?<!\\\)\{(.*)\}/sU", 
			function($matches) {
				$match = $matches[1];
				
				// {foreach from=$asd item=value key=key}
				if (preg_match("/^foreach\s+/i", $match)) {
					$match = preg_replace_callback("/
						foreach\s+
						from=(\\$[\w\d\"_'\[\]]+)\s+
						item=(\w+)
						(\s+key=(\w+))?
					/x", function($matches) {
						$arrayname = $matches[1];
						$itemname = '$' . $matches[2];
						$keyname = isset($matches[4]) ? '$' . $matches[4] . '=>' : null;
						
						$keyassign = isset($matches[4]) ? "\$view->assign(\"{$matches[4]}\",  \${$matches[4]});" : "";
						
						return "<?php foreach({$arrayname} as {$keyname}{$itemname}) { {$keyassign} \$view->assign(\"{$matches[2]}\",  {$itemname}); ?>";
					}, $match );
					
					return $match;
				}
				
				// {capture name="test"} and {/capture}
				if (preg_match("/^capture\s+name=(?|\"([^\"]*)\"|'([^']*)')/", $match, $capturematches)) {
					$name = $capturematches[1];
					return "<?php \$capturename=\"{$name}\"; ob_start(); ?>";
				}
				if ($match == "/capture") {
					return "<?php \$view->assign(\$capturename, \$\$capturename = ob_get_contents(), null, true); ob_end_clean();  ?>";
				}
				
				// {include file="test"} or {include file='test'}
				if (preg_match("/^include\s+file=(?|\"([^\"]*)\"|'([^']*)')(.*)/s", $match, $includematches)) {
					// "But wait, there's more!"
					
					$variables = "";
					$unsets = "";
					if (isset($includematches[2])) {
						preg_match_all("/\s+(\w+)=((?|\"[^\"]*\"|'[^']*')|(\\$[\w\d\"_'\[\]]+))/", $includematches[2], $variablematches, PREG_SET_ORDER);
						foreach ($variablematches as $variablematch) {
							$name = $variablematch[1];
							$value = $variablematch[2];
							
							$variables .= "\$view->assign(\"{$name}\",  {$value}); ";
							$unsets .= "\$view->unsetVar(\"{$name}\"); ";
						}
					}
					
					$file = $includematches[1];
					return "<?php {$variables} \$view->load('{$file}'); {$unsets}  ?>";
				}
				
				
				// {/foreach}, {else}, {/if}
				if (preg_match("#(/foreach|/if|else)#", $match)) {
					return str_replace(array("/foreach", "/if", "else"), array("<?php } ?>", "<?php } ?>", "<?php } else { ?>"), $match);
				}
				
				// if(sdf) { ... }
				if (preg_match("/^if\s+(.+)/i", $match, $ifmatch)) {
					return "<?php if({$ifmatch[1]})  { ?>";
				}
				
				// {assign var="name" value=$val} or assign var='name' value=$val}
				if (preg_match("/^assign\s+var=(?|\"([^\"]*)\"|'([^']*)')\s+value=(.+)/i", $match, $assignmatch)) {
					
					return "<?php \$view->assign(\"{$assignmatch[1]}\", \${$assignmatch[1]} = {$assignmatch[2]});  ?>";
				}
				
				// {$testi}
				if ($match{0} == '$') {
					//return $this->data[substr($match,1)];
					return "<?php echo {$match}; ?>";
				}
				
				// {testi("sdf")}
				if (preg_match("/^([\w0-9_]+)\(/i", $match)) {
					return "<?php echo {$match} ; ?>";
				}
				
				return $matches[0];
			},
			$content
		);
		
		$content = str_replace("\{", "{", $content);
		
		return $content;
	}
}