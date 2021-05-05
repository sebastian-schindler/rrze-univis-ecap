"use strict";
 
wp.domReady( 
    function(){
        jQuery(document).on('change', 'input#number', function(e){getUnivISDataForBlockelements('personAll');});
        // jQuery(document).on('change', 'select#univisid', function(e){setTask();});
    });

function getUnivISDataForBlockelements($dataType) {
    var $univisOrgID = jQuery('input#number').val();
    var $output = jQuery('select#univisid');

    if ($univisOrgID){
        $output.html('<option value="">loading...   </option>');
    
        jQuery.post(univis_ajax.ajax_url, { 
            _ajax_nonce: univis_ajax.nonce,
            action: 'GetUnivISDataForBlockelements',
            data: {'univisOrgID':$univisOrgID, 'dataType':$dataType},               
        }, function(result) {
            $output.html(result);
        });
    }
}

// function setTask(){
//     var $univisid = jQuery('select#univisid').val();

//     if ($univisid){
//         jQuery('select#task').val('mitarbeiter-alle');
//     }else{
//         jQuery('select#task').val('mitarbeiter-einzeln');
//     }
// }
