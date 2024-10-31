jQuery(document).ready(function($){
  setTimeout("charCount();", 500);  
  jQuery('#mytwitter_status').val('');
}); //on load, check character count

function charCount() {
	var count = jQuery("#mytwitter_status").val().length;
	var status =jQuery("#mytwitter_status").val();
	if (count > 0) {
		if (count > 140) {
			jQuery('#mytwitter_status').val() = status.substring(0,140);
			jQuery('#mytwitter_characters').html("No characters remaining");
		}
		else {
			jQuery("#mytwitter_characters").html((140 - count) + " characters remaining");
		}
	}
	else {
		jQuery("#mytwitter_characters").html("140 characters remaining.");
	}
} //end function

function isNumeric(fieldText) {
   var ValidChars = "0123456789";
   var IsNumber = true;
   var Char;
 
   for (i = 0; i < fieldText.length && IsNumber == true; i++) { 
      Char = fieldText.charAt(i); 
      if (ValidChars.indexOf(Char) == -1) {IsNumber = false;}
   }
   return IsNumber;   
} //end function

function ValidateForm(form) {
   if (!isNumeric( document.getElementById("elitwee_cache[life]").value )) {	
      alert('Please enter a valid number of seconds for the Cache Life value.') 
      jQuery("elitwee_cache[life]").focus(); 
      return false; 
   }
   else if ( Number(document.getElementById("elitwee_cache[life]").value) < 1 ) {
      alert('Please enter a Cache Life value (seconds) greater than 0.');
      document.getElementById("elitwee_cache[life]").focus();
      return false; 
   }
   else if (document.getElementById("elitwee_twitter[user]").value.length == 0) {
      alert('Please enter a Twitter username or set it to the default of MyTwitt3r.');
      jQuery("#elitwee_twitter[user]").focus(); 
      return false; 
   }
return true;
} //end function 

function ValidateUpdate() {
	if (document.getElementById("elitwee_twitter[user]").value.length == 0) {
      alert('Please enter a Twitter username or set it to the default of MyTwitt3r.');
      document.getElementById("elitwee_twitter[user]").focus(); 
      return false; 
   }
   else if(document.getElementById("elitwee_twitter[pass]").value.length == 0) {
      alert('Please enter your Twitter password in order to post your update.');
      document.getElementById("elitwee_twitter[pass]").focus(); 
      return false; 
   }
   else if(document.getElementById("mytwitter_status").value.length == 0) {
	alert('Please enter something in the "What are you doing?" field in order to post the update.');
      document.getElementById("mytwitter_status").focus(); 
      return false;
   }
   return true;
} //end function

function ajaxTweet() {
  if ( ValidateUpdate() ) {
   elitwee_admin_post_tweet();
  }
}

function elitwee_admin_post_tweet() {
  var ajax_url = jQuery("#elitwee_admin_ajax_url").val();

  var data = {
    action: 'elitwee_post',
    status: jQuery("#mytwitter_status").val()
  }

  jQuery.ajax({
    type: "POST",
    url: ajax_url,
    dataType: "json",
    data: data,
    success: function(response) {
      if(response.success = 1) {
        jQuery('#tweet_submit_status').html("Twitter status updated: <i>" + jQuery('#mytwitter_status').val() + "</i>").addClass("updated").addClass("fade");
        jQuery('#mytwitter_status').val('');
      }
      else if(response.error_message) {
        jQuery('#tweet_submit_status').html("<b>Error:</b>" + response.error_message + "</i>").addClass("error");
      }
      else {
        jQuery('#tweet_submit_status').html("<b>Error:</b> Unable to update Twitter status due to unknown error.").addClass("error");
      }
    },
    error: function(response) {
        jQuery('#tweet_submit_status').html("<b>Error:</b>" + response).addClass("error");
    }
  });
}