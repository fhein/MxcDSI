{namespace name="frontend/listing/box_article"}

{$hasFlavor = $sArticle.mxcbc_flavor && ! empty($sArticle.mxcbc_flavor)}

{if $hasFlavor}
	<div class="price--unit" title="Geschmack">
		<strong>Geschmack</strong> {$sArticle.mxcbc_flavor}
	</div>
{/if}
