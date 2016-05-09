<html>
<head>
	<style type="text/css">
		p {
			background-color: #f0f0f0;
			border: 1px solid black;
			padding:5px;
		}
	</style>
</head>

<body>

	<p>
		Single variable<br>
		{$name}
	</p>
	
	<p>
		HTML Code<br>
		{$htmlcode}
	</p>
	
	<p>
		Unfiltered HTML Code
		<br>
		{$htmlcoderaw}
	</p>
	
	<p>
		Loop<br>
		{foreach from=$numbers item=number key=index}
			Number {$number}{if $index+1 < count($numbers)}, {/if}
		{/foreach}
	</p>
	
	{capture name="capturetest"}
	<p>
		Captured content
	</p>
	{/capture}
	
	<p>
		File inclusion<br>
		{include file="example-inc.tpl"}
	</p>
	
	{$capturetest}
	
	<p>
		If/Else Condition<br>
		{if $name == "Waldo"}
			$name is Waldo
		{else}
			$name is not Waldo
		{/if}
	</p>
	
	{assign var="quantity" value=count($numbers)}
	<p>
		Variable assignment<br>
		{$quantity}
	</p>
	
</body>
</html>