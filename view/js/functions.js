jQuery(document).ready(function(){
	
	jQuery('.edit_field').click(function(){
		jQuery(this).hide();
		jQuery(this).prev().hide();
		jQuery(this).next().show();
		jQuery(this).next().select();
	});
	
	
	jQuery('input.field_name').blur(function() {  
         if (jQuery.trim(this.value) == ''){  
			 this.value = (this.defaultValue ? this.defaultValue : '');  
		 }
		 else{
			jQuery(this).val(this.value);
			 jQuery(this).prev().prev().html(this.value);
		 }
		 
		 jQuery(this).hide();
		 jQuery(this).prev().show();
		 jQuery(this).prev().prev().show();
     });
	  
	  jQuery('input.field_name').keypress(function(event) {
		  if (event.keyCode == '13') {
			event.preventDefault();
			  if (jQuery.trim(this.value) == ''){  
				 this.value = (this.defaultValue ? this.defaultValue : '');  
			 }
			 else
			 {
				jQuery(this).val(this.value);
				jQuery(this).prev().prev().html(this.value);
			 }
			 
			 jQuery(this).hide();
			 jQuery(this).prev().show();
			 jQuery(this).prev().prev().show();
		  }
	  });
		  
});