# TTE (Tiny Template Engine)

A minimal implementation of the Smarty Template Engine, using less then a fraction of LOC. 
Features:
* Per-default HTML escaping to prevent XSRF and similar attacks. 
* No need for {literal} in 99% of cases. Anything inside curly braces is checked for template syntax and printed unchanged if it doesn't match, to prevent it escape the openeing curly brace. E.g.\{$bla} is not being parsed

**Available Commands**
* Print variables via {$variablename}, access unescaped data via {$variablenameraw}
* Assign variables via {assign var="variablename" value=[php term]}
* Execute functions via {strtoupper($variablename)}
* Conditional Statements via {if [php term]} {/if}
* The use of {else} and {elseif [php term]} is also possible
* Loop through arrays via {foreach from=$array item=item} or {foreach from=$array key="key"}
* Store HTML output for later printing via {capture name="header"}sadf lsadkfjsladkfj slkdf{/capture}   and then {$header}
* Include files via {include file="filename"} 
* Include files and assign variables in one command:
* {include file="filename" foo="bar" test=$var}
* The use of <?php ?> - Tags is possible but discouraged!

