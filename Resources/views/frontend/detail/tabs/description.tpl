{extends file="frontend/detail/tabs/vdescription.tpl"}

{* Anführungszeichen im Titel der Produktbeschreibung entfernen *}
{block name='frontend_detail_description_title'}
    <div class="content--title">
        {s name="DetailDescriptionHeader"}{/s} {$sArticle.articleName}
    </div>
    <br>
{/block}

{block name='frontend_detail_description_links_title'}
<br>
{/block}

