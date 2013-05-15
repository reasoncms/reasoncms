$( document ).ready(function( $ ) {
    $(".policy.sub_policy").accordion({ 
        event: "click",
        header: "h4.policyName",
        icons: null,
        active: false,
        collapsible: true,
        autoHeight: false
    });
} );