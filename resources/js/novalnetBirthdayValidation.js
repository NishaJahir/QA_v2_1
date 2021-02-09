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
 
$(document).ready( function() {
    var currentDate = new Date();      
    var maxYear = currentDate.getFullYear() - 18;
    var minYear = currentDate.getFullYear() - 91;    
    var  userAgent = navigator.userAgent || navigator.vendor || window.opera;
    
    $("#nnBirthdayDate").on("keypress textInput",function (e)
    {
        var keyCode = e.which || e.originalEvent.data.charCodeAt(0);
        var expr = String.fromCharCode(keyCode);  
        if ( isNaN( expr ) || ( /android/i.test(userAgent) && $(this).val().length > 1 ) )
        {			
          e.preventDefault();
        }
            var dayVal = $('#nnBirthdayDate').val();
            if( dayVal.length == 1 ) {
                    if( (expr > -1 && dayVal.charAt(0) > 3) || (expr == 0 && dayVal.charAt(0) == 0) || (expr > 1 && dayVal.charAt(0) == 3) )  {
                    return false;
                }
            }        
    });
    
    $('#nnBirthdayDate').on('blur', function() {
		var date, updatedDate;
		updatedDate = date = $('#nnBirthdayDate').val();
		if (date != '0' && date != '' && date.length < 2) {
			 updatedDate = "0"+ date;         
		} else if (date == '0') {
			updatedDate = date.replace('0', '01');        
		} 
		$('#nnBirthdayDate').val(updatedDate);
    });      
    
    $("#nnBirthdayYear").on("input", function(e) {      
        var yearVal = $(this).val();
        var yearLen = yearVal.length;
        let maximumYear = parseInt( maxYear.toString().substring( 0 ,yearLen ) );
        let minimumYear = parseInt( minYear.toString().substring( 0 ,yearLen ) );        
        let userVal = yearVal.substring( 0, yearLen );               
        if( e.keyCode != 8 || e.keyCode != 46 ) {        
                    if( userVal > maximumYear || userVal <  minimumYear || isNaN(userVal) )  {        
                $(this).val( yearVal.substring( 0, yearLen - 1 ) );
                e.preventDefault();
              e.stopImmediatePropagation();
              return false;
          }  
        }
        
        });

    
    function yearAutocomplete(inputVal, arrayYear) {
 
      var currentFocus;
  
      inputVal.addEventListener("input", function(e) {
      var a, b, i, val = this.value;
     
      closeAllLists();
      if (!val || val.length < 2) { return false;}
      currentFocus = -1;
      
      a = document.createElement("div");
      a.setAttribute("id", this.id + "autocomplete-list");
      a.setAttribute("class", "autocomplete-items");
      
      this.parentNode.appendChild(a);
      var count = 1;
      for (i = 0; i < arrayYear.length; i++) {     
        var regex = new RegExp( val, 'g' );
        if (arrayYear[i].match(regex)) {   
      if( count == 10 ) {
       break;
      }
          b = document.createElement("div");
          b.innerHTML = arrayYear[i].replace( val,"<strong>" + val + "</strong>" );          
          b.innerHTML += "<input type='hidden' class='yearActive' value='" + arrayYear[i] + "'>";
          b.addEventListener("click", function(e) {
              inputVal.value = this.getElementsByTagName("input")[0].value;
              closeAllLists();
          });
          a.appendChild(b);
      count++;
        }
      }
  });
  
      inputVal.addEventListener("keydown", function(e) {
          var x = document.getElementById(this.id + "autocomplete-list");
          if (x) x = x.getElementsByTagName("div");
          if (e.keyCode == 40) {
            currentFocus++;            
            addActiveValue(x);
          } else if (e.keyCode == 38) { 
            currentFocus--;
            addActiveValue(x);
          } else if (e.keyCode == 13) {
            e.preventDefault();
            if (currentFocus > -1) {
              if (x) x[currentFocus].click();
            }
          }
      });
      function addActiveValue(x) {
        if (!x) return false;
        removeActiveValue(x);
        if (currentFocus >= x.length) currentFocus = 0;
        if (currentFocus < 0) currentFocus = (x.length - 1);
        x[currentFocus].classList.add("autocomplete-active");
    var elements = $(x[currentFocus]);      
        $('#nnBirthdayYear').val( $('.yearActive', elements).val() );
      }
      function removeActiveValue(x) {
        for (var i = 0; i < x.length; i++) {
          x[i].classList.remove("autocomplete-active");
        }
      }
      function closeAllLists(elmnt) {
        var x = document.getElementsByClassName("autocomplete-items");
        for (var i = 0; i < x.length; i++) {
          if (elmnt != x[i] && elmnt != inputVal) {
            x[i].parentNode.removeChild(x[i]);
          }
        }
      }

      document.addEventListener("click", function (e) {
          closeAllLists(e.target);
      });
    }

    var yearRange = [];
    
    for( var year = maxYear; year >= minYear; year-- ) {              
        yearRange.push('' + year + '');
    }

    yearAutocomplete(document.getElementById("nnBirthdayYear"), yearRange);
    
    
    $('#novalnetForm').on('submit', function() {
    $('#novalnetFormBtn').attr('disabled',true);
        if ( $("#nnBirthdayYear").val() == '' || $("#nnBirthdayDate").val() == '' ) {
        alert($("#nnDobEmpty").val());
        $('#novalnetFormBtn').attr('disabled',false);
        return false;
        }
        
        if($("#nnBirthdayMonth").val() == '0' ) {
        alert($("#nnDobInvalid").val());
        $('#novalnetFormBtn').attr('disabled',false);
            return false;
        }
    
        return isActualDate($("#nnBirthdayMonth").val(), $("#nnBirthdayDate").val(), $("#nnBirthdayYear").val());
        });
    
        function isActualDate (month, day, year) {
            var tempDate = new Date(year, --month, day);
            if( month !== tempDate.getMonth() || $("#nnBirthdayYear").val().length < 4) {
                alert($("#nnDobInvalid").val());
                $('#novalnetFormBtn').attr('disabled',false);
                return false;
            }
            return true;
        }
    
});

 
