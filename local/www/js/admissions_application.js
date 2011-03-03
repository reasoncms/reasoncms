/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

$(document).ready(function() {
        $("div[id='formNavigation']").children()[0].children()[0].click(
                function(){
                        alert('testing');
                        $("input[name='__button_ApplicationPageTwo']").click();
                }
        )
})