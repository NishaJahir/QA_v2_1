/*
 * This module is used for real time processing of
 * Novalnet payment module of customers.
 * This free contribution made by request.
 * 
 * If you have found this script useful a small
 * recommendation as well as a comment on merchant form
 * would be greatly appreciated.
 *
 * @author       Novalnet AG
 * @copyright(C) Novalnet
 * All rights reserved. https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 */
 
var $ = jQuery.noConflict();
var paymentName = $('#paymentKey').val();
var lowerPaymentName = paymentName.toLowerCase();
var splittedPaymentName = lowerPaymentName.split('_');

$(document).ready( function () {
    //~ Save card details process
    if ($("#toggleForm").length <= 0 || $("#toggleForm").is(':checked') ) {
        $("#newCardDetails").show();
    } else {
        $("#newCardDetails").hide();
    }
    
    if( $("input[name='nn_radio_option']").length > 0  ) { 
            var token = $("input[name='nn_radio_option']:first").val(); 
            if(token){
               $('#'+ splittedPaymentName[0] + splittedPaymentName[1] + 'token').val(tokenValue);
            } else {
               $('#'+ splittedPaymentName[0] + splittedPaymentName[1] + 'token').val('');
            }
        }
    
    $("input[name='radioOption']").on('click', function () {
        var tokenValue = $(this).val();
            if(tokenValue){
                $('#'+ splittedPaymentName[0] + splittedPaymentName[1] + 'token').val(tokenValue);
            } else {
                $('#'+ splittedPaymentName[0] + splittedPaymentName[1] + 'token').val('');
            }
        
            if($(this).attr('id') == 'toggleForm') {
                $("#newCardDetails").show();
                $("#newForm").val('1');
            } else {
                $("#newCardDetails").hide();
            }
   });

    $("input[name='radioOption']:first").attr("checked","checked");

    // For credit card payment form process
    if (paymentName == 'NOVALNET_CC') {
        loadNovalnetCcIframe();
        jQuery('#novalnetForm').submit( function (e) {
                if($('#nnCcPanHash').val().trim() == '') {
                    NovalnetUtility.getPanHash();
                    e.preventDefault();
                    e.stopImmediatePropagation();
                }
            });
    }
    
    // For Direct Debit SEPA payment form process
    if(paymentName == 'NOVALNET_SEPA') {
        $('#nnSepaIban').on('input',function ( event ) {
        let iban = $(this).val().replace( /[^a-zA-Z0-9]+/g, "" ).replace( /\s+/g, "" );
            $(this).val(iban);      
        });

        $('#novalnetForm').on('submit',function(){
          $('#novalnetFormBtn').attr('disabled',true);      
        });
    }
});

function loadNovalnetCcIframe()
{
     var ccCustomFields = $('#nnCcFormFields').val() != '' ? JSON.parse($('#nnCcFormFields').val()) : null;
     var ccFormDetails= $('#nnCcFormDetails').val() != '' ? JSON.parse($('#nnCcFormDetails').val()) : null;
    
    // Set your Client key
    NovalnetUtility.setClientKey((ccFormDetails.client_key !== undefined) ? ccFormDetails.client_key : '');

     var requestData = {
        'callback': {
          on_success: function (result) {
            $('#nnCcPanHash').val(result['hash']);
            $('#nnCcUniqueId').val(result['unique_id']);
            $('#nnCc3dRedirect').val(result['do_redirect']);
            jQuery('#novalnetForm').submit();
            return true;
          },
          on_error: function (result) {
           if ( undefined !== result['error_message'] ) {
              alert(result['error_message']);
              return false;
            }
          },

           // Called in case the challenge window Overlay (for 3ds2.0) displays
          on_show_overlay:  function (result) {
            $( '#nnIframe' ).addClass( '.overlay' );
          },

           // Called in case the Challenge window Overlay (for 3ds2.0) hided
          on_hide_overlay:  function (result) {
            $( '#nnIframe' ).removeClass( '.overlay' );
          }
        },

         // You can customize your Iframe container style, text etc.
        'iframe': {

         // Passed the Iframe Id
          id: "nnIframe",

          // Display the inline form if the values is set as 1
          inline: (ccFormDetails.inline_form !== undefined) ? ccFormDetails.inline_form : '0',
         
          // Adjust the creditcard style and text 
          style: {
            container: (ccCustomFields.novalnet_cc_standard_style_css !== undefined) ? ccCustomFields.novalnet_cc_standard_style_css : '',
            input: (ccCustomFields.novalnet_cc_standard_style_field !== undefined) ? ccCustomFields.novalnet_cc_standard_style_field : '' ,
            label: (ccCustomFields.novalnet_cc_standard_style_label !== undefined) ? ccCustomFields.novalnet_cc_standard_style_label : '' ,
          },
          
          text: {
            lang : (ccFormDetails.lang !== undefined) ? ccFormDetails.lang : 'en',
            error: (ccCustomFields.credit_card_error !== undefined) ? ccCustomFields.credit_card_error : '',
            card_holder : {
              label: (ccCustomFields.novalnetCcHolderLabel !== undefined) ? ccCustomFields.novalnetCcHolderLabel : '',
              place_holder: (ccCustomFields.novalnetCcHolderInput !== undefined) ? ccCustomFields.novalnetCcHolderInput : '',
              error: (ccCustomFields.novalnetCcError !== undefined) ? ccCustomFields.novalnetCcError : ''
            },
            card_number : {
              label: (ccCustomFields.novalnetCcNumberLabel !== undefined) ? ccCustomFields.novalnetCcNumberLabel : '',
              place_holder: (ccCustomFields.novalnetCcNumberInput !== undefined) ? ccCustomFields.novalnetCcNumberInput : '',
              error: (ccCustomFields.novalnetCcError !== undefined) ? ccCustomFields.novalnetCcError : ''
            },
            expiry_date : {
              label: (ccCustomFields.novalnetCcExpiryDateLabel !== undefined) ? ccCustomFields.novalnetCcExpiryDateLabel : '',
              place_holder: (ccCustomFields.novalnetCcExpiryDateInput !== undefined) ? ccCustomFields.novalnetCcExpiryDateInput : '',
              error: (ccCustomFields.novalnetCcError !== undefined) ? ccCustomFields.novalnetCcError : ''
            },
            cvc : {
              label: (ccCustomFields.novalnetCcCvcLabel !== undefined) ? ccCustomFields.novalnetCcCvcLabel : '',
              place_holder: (ccCustomFields.novalnetCcCvcInput !== undefined) ? ccCustomFields.novalnetCcCvcInput : '',
              error: (ccCustomFields.novalnetCcError !== undefined) ? ccCustomFields.novalnetCcError : ''
            }
          }
        },

         // Add Customer data
        customer: {
          first_name: (ccFormDetails.first_name !== undefined) ? ccFormDetails.first_name : '',
          last_name: (ccFormDetails.last_name !== undefined) ? ccFormDetails.last_name : ccFormDetails.first_name,
          email: (ccFormDetails.email !== undefined) ? ccFormDetails.email : '',
          billing: {
            street: (ccFormDetails.street !== undefined) ? ccFormDetails.street : '',
            city: (ccFormDetails.city !== undefined) ? ccFormDetails.city : '',
            zip: (ccFormDetails.zip !== undefined) ? ccFormDetails.zip : '',
            country_code: (ccFormDetails.country_code !== undefined) ? ccFormDetails.country_code : ''
          },
          shipping: {
            same_as_billing: (ccFormDetails.same_as_billing !== undefined) ? ccFormDetails.same_as_billing : 0,
          },
        },
        
         // Add transaction data
        transaction: {
          amount: (ccFormDetails.amount !== undefined) ? ccFormDetails.amount : '',
          currency: (ccFormDetails.currency !== undefined) ? ccFormDetails.currency : '',
          test_mode: (ccFormDetails.test_mode !== undefined) ? ccFormDetails.test_mode : '0',
        }
      };

      NovalnetUtility.createCreditCardForm(requestData);
}

function removePaymentDetails(token)
{
    var removeSavedPaymentParams = { 'token' : token };
    savedPaymentRequestHandler(removeSavedPaymentParams);
}

// Remove the save card details based on the customer input
function savedPaymentRequestHandler(removeSavedPaymentParams) {
    if ('XDomainRequest' in window && window.XDomainRequest !== null) {
        var xdr = new XDomainRequest(); // Use Microsoft XDR
        var removeSavedPaymentParams = $.param(removeSavedPaymentParams);
        xdr.open('POST', $('#removalProcessUrl').val());
        xdr.onload = function (result) {
            $('#remove_'+removeSavedPaymentParams['token']).remove();
                alert($('#removeCardDetail').val());
                window.location.reload();
        };
        xdr.onerror = function () {
            _result = false;
        };
        xdr.send(removeSavedPaymentParams);
    } else {
        $.ajax(
            {
                url      : $('#removalProcessUrl').val(),
                type     : 'post',
                dataType : 'html',
                data     : removeSavedPaymentParams,
                success  : function (result) {
                    $('#remove_'+removeSavedPaymentParams['token']).remove();
                    alert($('#removeCardDetail').val());
               
                }
            }
        );
    }
}
