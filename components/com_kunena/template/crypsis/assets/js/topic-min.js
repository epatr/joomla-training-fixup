jQuery(document).ready(function(a){a(".kspoiler").each(function(b){a(this).click(function(){if(!a(this).find(".kspoiler-content").is(":visible")){a(this).find(".kspoiler-content").show();a(this).find(".kspoiler-expand").hide();a(this).find(".kspoiler-hide").show();}else{a(this).find(".kspoiler-content").hide();a(this).find(".kspoiler-expand").show();a(this).find(".kspoiler-hide").hide();}});});a(".openmodal").click(function(){var b=a(this).attr("href");a(b).css("visibility","visible");});a('[id^="login-link"]').click(function(){a(this).ready(function(){if(a("#userdropdown").is(":visible")){a(this).addClass("kdelay");}else{a("#userdropdown").css("display","inline-block");a("#userdropdown").css("visibility","visible").delay(500).queue(function(){a(this).addClass("kdelay");});}});});a(document).click(function(){a(".kdelay").css("display","none").removeClass("kdelay");});a("#userdropdown").click(function(b){b.stopPropagation();});a(".heading").click(function(){if(!a(this).hasClass("heading-less")){a(this).prev(".heading").show();a(this).hide();a(this).next(".content").slideToggle(500);}else{var b=a(this).next(".heading").show();a(this).hide();b.next(".content").slideToggle(500);}});a("#kmod_topics").change(function(){var b=a(this).val();if(b!=0){a("#kmod_subject").hide();}else{a("#kmod_subject").show();}if(b==-1){a("#kmod_targetid").show();}else{a("#kmod_targetid").hide();}});if(a.fn.jsSocials!=undefined){a("#share").jsSocials({showCount:true,showLabel:true,shares:[{share:"email",label:Joomla.JText._("COM_KUNENA_SOCIAL_EMAIL_LABEL")},{share:"twitter",label:Joomla.JText._("COM_KUNENA_SOCIAL_TWITTER_LABEL")},{share:"facebook",label:Joomla.JText._("COM_KUNENA_SOCIAL_FACEBOOK_LABEL")},{share:"googleplus",label:Joomla.JText._("COM_KUNENA_SOCIAL_GOOGLEPLUS_LABEL")},{share:"linkedin",label:Joomla.JText._("COM_KUNENA_SOCIAL_LINKEDIN_LABEL")},{share:"pinterest",label:Joomla.JText._("COM_KUNENA_SOCIAL_PINTEREST_LABEL")},{share:"stumbleupon",label:Joomla.JText._("COM_KUNENA_SOCIAL_STUMBLEUPON_LABEL")},{share:"whatsapp",label:Joomla.JText._("COM_KUNENA_SOCIAL_WHATSAPP_LABEL")}]});a(".jssocials-share-whatsapp").addClass("visible-phone");}a("#kmod_categories").change(function(){a.getJSON(kunena_url_ajax,{catid:a(this).val()}).done(function(c){var d=a("#kmod_topics option:nth(0)").clone();var b=a("#kmod_topics option:nth(1)").clone();a("#kmod_topics").empty();d.appendTo("#kmod_topics");b.appendTo("#kmod_topics");a.each(c,function(f,e){a.each(e,function(h,g){a("#kmod_topics").append('<option value="'+g.id+'">'+g.subject+"</option>");});});});});});