{extends file="parent:frontend/detail/config_step.tpl"}

{* Configurator drop down *}
{block name='frontend_detail_group_selection'}
    <div class="field--select select-field{if $groupID gt 0 && empty($sArticle.sConfigurator[$pregroupID].user_selected)} is--disabled{/if}">
        <select{if $groupID gt 0 && empty($sArticle.sConfigurator[$pregroupID].user_selected)} disabled="disabled"{/if} name="group[{$sConfigurator.groupID}]"{if $theme.ajaxVariantSwitch} data-ajax-select-variants="true"{else} data-auto-submit="true"{/if}>

            {* Please select... *}
            {if empty($sConfigurator.user_selected)}
                <option value="" selected="selected">{s name="DetailConfigValueSelect"}{/s}</option>
            {/if}

            {foreach from=$sConfigurator.values item=configValue name=option key=optionID}
                {* FHE, 23.10.2018, add only options which are selectable, i.e. in stock *}
                {* if $configValue.selectable *} {* FHE *}
                    <option {if !$sArticle.notification && !$configValue.selectable}disabled{/if} {if $configValue.selected && $sConfigurator.user_selected} selected="selected"{/if} value="{$configValue.optionID}">
                        {$configValue.optionname}{if $configValue.upprice && !$configValue.reset} {if $configValue.upprice > 0}{/if}{/if}
                        {if !$configValue.selectable}{s name="DetailConfigValueNotAvailable"}{/s}{/if}
                    </option>
                {* /if *} {* FHE *}
            {/foreach}
        </select>
    </div>
{/block}

