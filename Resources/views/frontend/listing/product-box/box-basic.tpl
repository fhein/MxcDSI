{extends file="parent:frontend/listing/product-box/box-basic.tpl"}

{namespace name="frontend/listing/box_article"}

{block name="block name='frontend_listing_box_article_nostock_class"}
    {if $sArticle.attributes.mxc_in_stock->get('in_stock') <= 0 AND !$sArticle.esd} nostock {/if}"
{/block}

{block name='frontend_listing_box_article_unit'}
	{include file="frontend/listing/product-box/product-price-unit.tpl"}
	{include file="frontend/listing/product-box/product-flavor.tpl"}
{/block}

