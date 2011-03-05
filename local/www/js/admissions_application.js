/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

$(document).ready(function() {
        var $formSteps = $("div[id='formNavigation'] > ul > li > a");
        $.each($formSteps, function(index, value){
            $formSteps[index].href = '#';
            $(this).click(function(event){
                event.preventDefault();
                var $number;
                switch(index){
                    case 0:
                        $number = "One";
                        break;
                    case 1:
                        $number = "Two";
                        break;
                    case 2:
                        $number = "Three";
                        break;
                    case 3:
                        $number = "Four";
                        break;
                    case 4:
                        $number = "Five";
                        break;
                    case 5:
                        $number = "Six";
                        break;
                }
                $("input[name='__button_ApplicationPage" + $number + "']").click();
            })
        })
})