{namespace name="frontend/listing/box_article"}

{$hasFlavor = $sArticle.mxc_flavor && ! empty($sArticle.mxc_flavor)}

{if $hasFlavor}
	<div class="price--unit" title="Geschmack">
		<strong>Geschmack</strong> {$sArticle.mxc_flavor}
	</div>
{/if}
