{extends file="parent:frontend/detail/tabs/description.tpl"}

{* Product Short Description *}
{block name='frontend_detail_description_text'}
    <div class="product--description" itemprop="description">
        {$sArticle.mxc_brief_description}
    </div>
{/block}
