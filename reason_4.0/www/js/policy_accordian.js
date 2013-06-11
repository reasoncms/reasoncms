/**
 * Create open close links on sub_policies
 *
 * @author Matt Ryan and Nathan White
 * modified accordion_list.js for the Policiy type
 */

$(document).ready(function()
{   
    $('div.sub_policy').each(function()
    {
        toggleVisibility(this, true);
        $(this).css('cursor','pointer');
        $(this).click(function() 
        {
            toggleVisibility(this, false);
        });
        $("div.sub_policy", this).click(function()
        {
            return false;
        });
        $("a", this).click(function(e)
        {
            e.stopPropagation();
            return true;
        });
    });
    
    $('div.sub_policy').each(function()
    {
        $(this).css({cursor: 'default'});
    });
    
    /**
     * if there is a document hash in the URL - lets open the corresponding tag.
     */
    if(window.location.hash)
    {
        var hash_value = window.location.hash.replace('#', '');
        $("a#"+hash_value).each(function()
        {
            $(this).closest('div.sub_policy').each(function()
            {
                toggleVisibility(this);
            });
        });
    }
    
    function toggleVisibility(list_item, force_hide)
    {
        if(force_hide)
            $(list_item).children('div.policyContent').hide();
        else
            $(list_item).children('div.policyContent').toggle();
        if ($(list_item).children('div.policyContent').is(':hidden'))
        {
            $(list_item).removeClass('openAccordion').addClass('closedAccordion');
        }
        else
        {
            $(list_item).removeClass('closedAccordion').addClass('openAccordion');
            $("div.policyContent", list_item).css({cursor: 'default'});
        }
    }
});