{extends file="parent:frontend/listing/product-box/product-badges.tpl"}

{namespace name="frontend/listing/box_article"}

{block name='frontend_listing_box_article_discount_instock_badge'}
    {if $theme.product_box_badge_instock != 0 AND !$sArticle.esd}
        
        {if $sArticle.attributes.mxc_in_stock->get('in_stock') < 1}
            <div class="product--badge badge--sold">
                {s name="twBadgeSoldText"}Ausverkauft{/s}
            </div>
        {elseif $sArticle.attributes.mxc_in_stock->get('in_stock') le $theme.product_box_badge_instock_threshold}
            <div class="product--badge badge--low-stock">
                {s name="twBadgeLowStockTextPrefix"}Nur{/s} {$sArticle.attributes.mxc_in_stock->get('in_stock')|number} {s name="twBadgeLowStockTextSuffix"}auf Lager!{/s}
            </div>
        {/if}
    {/if}
{/block}
