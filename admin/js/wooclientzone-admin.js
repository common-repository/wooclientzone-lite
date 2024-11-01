/**
 * This is the script that manages all back end JavaScript.
 *
 * @summary   Manages back end JavaScript.
 *
 * @link	   http://blendscapes.com
 * @since     1.0.0
 * @requires jQuery
 * 
 */
(function( $ ) {
	'use strict';

	/**
	 * All of the code for our admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * We use jQuery inside the function that specifies that the DOM is ready.
	 * 
	 * $(function() {
	 *
	 * });
	 *
	 */

	// ADMIN SIDE CODE -- note that admin_js_options is enqueued by class Wooclientzone_Admin

	$(function() {

		// UTILITIES

		/**
		 * @summary Loads the content of the client zone editor.
		 *
		 * @since 1.0.0
		 */
		function get_tinymce_content(){
			if ($("#wp-content-wrap").hasClass("tmce-active")){
				return tinyMCE.activeEditor.getContent();
			} else {
				return $('#adminmessagetextarea').val();
			}
		}

		// CLIENT ZONE DATA RETRIEVAL

		var firstTimeLoadingCommunications = true;

		/**
		 * The timestamp of the earlier file displayed.
		 * 
		 * It is initially set with a very high number, which is then compared with the actual files timestamps.
		 * 
		 * @type Number
		 */
		var first_file_timestamp = 9999999999;
		
		/**
		 * The object defining the basic Ajax parameters for loading communications; is not used as such, but is extended.
		 *
		 * @since 1.0.0
		 * @property    string    url    The ajax url.
		 * @property    string    type   The http methods.
		 * @property    object    data   Data sent with the Ajax call.
		 * @property    string    dataType   The expected response's data type.
		 */
		var adminLoadCommunications_AjaxObject =
		{
			url:    admin_js_options.ajaxurl,
			type:   "POST",
			data:   {
				action: 'admin_load_communications',
				security: admin_js_options.nonce_load_communications,
				userid: admin_js_options.userid,
				orderid: admin_js_options.orderid
			},
			dataType:	"json"
		};

		/**
		 * The object defining all Ajax parameters for loading initial communications.
		 *
		 * Extends adminLoadCommunications_AjaxObject with a loading_initial_communications parameter,
		 * within the data object, which is set to true.
		 * 
		 * @since 1.1
		 * @property    object    data   Data sent with the Ajax call.
		 */
		var adminLoadInitialCommunications_AjaxObject = $.extend(true, {}, adminLoadCommunications_AjaxObject, {data: {loading_initial_communications: true}});

		/**
		 * The object defining all Ajax parameters for refreshing communications.
		 *
		 * Extends adminLoadCommunications_AjaxObject with a refresh parameter,
		 * within the data object, which is set to true.
		 * 
		 * @since 1.0.0
		 * @property    object    data   Data sent with the Ajax call.
		 */
		var adminRefreshCommunications_AjaxObject = $.extend(true, {}, adminLoadCommunications_AjaxObject, {data: {refreshing: true}});

		/**
		 * The object defining all Ajax parameters for loading previous communications.
		 *
		 * Extends adminLoadCommunications_AjaxObject with a loading_previous_communications parameter,
		 * within the data object, which is set to true.
		 * 
		 * @since 1.1
		 * @property    object    data   Data sent with the Ajax call.
		 */
		var adminLoadPreviousCommunications_AjaxObject = $.extend(true, {}, adminLoadCommunications_AjaxObject, {data: {loading_previous_communications: true}});

		/**
		 * @summary    Loads the returned div elements from the Ajax call into the communications placeholder.
		 *
		 * This is used in the callback functions of the Ajax call
		 *
		 * @since 1.0.0
		 * 
		 * @fires    event    wooclientzone:unseenElements    Used to communicate with the action box header for client email notifications
		 *
		 * @param    object    response    This is the returned json-format data from the Ajax call to load/refresh communications.
		 */			
		var setCommunicationsDivs = function(response) {

			if (firstTimeLoadingCommunications) {
				firstTimeLoadingCommunications = false;
				$(".loader-admin").hide();
			}
			if (response.loading_previous_communications) {
				$("#loadPreviousCommunications a").text(admin_js_options.load_more_previous_communications);
			}

			// trigger appropriate custom events for empty or non empty zones (currently used by action to hide moving zone)
			if (response.new_divs) {
				$(document).trigger('wooclientzone:clientZoneHasContent', [true]);
			} else if (!response.refreshing) {
				$(document).trigger('wooclientzone:clientZoneHasContent', [false]);
			}

			// if previous communications are available, which have not been fetched yet, trigger appropriate event to manage the load-previous link
			$(document).trigger('wooclientzone:clientZoneHasPreviousCommunicationsAvailable', (response.previous_communications_available ? [true] : [false]));
			
			// console.log(response);
			if (response.new_divs) {
				$('#errorMessage').hide();
				$('#successMessage').hide();
				if (response.loading_previous_communications) {
					$(response.new_divs).hide().prependTo('#adminCommunicationsPlaceholder').fadeIn(1000);
				} else {
					$(response.new_divs).hide().appendTo('#adminCommunicationsPlaceholder').fadeIn(1000);
				}
			} else {
				// set correct message element depending on the error level
				if (response.errorlevel === 'info') {
					$("#successMessage").html(response.errorstring).fadeIn();
				} else if (response.errorlevel === 'warning') {
					$("#errorMessage").html(response.errorstring).fadeIn();
				}
			}

			// loop through files and read the file timestamp, which we need to set the first file read, and also to
			// show 'seen' divs based on client last access (note the server only placed a seen div under the admin bubbles)
			var unseen_elements = false;
			$('.filediv').each(function(index) {
			   var data = $(this).data();
			   // get first file timestamp
			   if (data.timestamp < first_file_timestamp) {
				   first_file_timestamp = data.timestamp;
			   }
			   // check if the current file was seen
			   if (data.timestamp <= response.client_lastaccess)
				   $(this).find('.bubble-footer-seen').show();
			   else
				   unseen_elements = true;
			});
			// trigger custom event (used by client notification action box)
			$(document).trigger('wooclientzone:unseenElements', [unseen_elements]);
		};

		/**
		 * Ajax load (and subsequently refresh) files and messages in the Client Zone.
		 *
		 * @since	1.0.2
		 */
		var adminGetCommunications = function() {

			var ajaxObject;
			
			if (firstTimeLoadingCommunications) {
				ajaxObject = $.extend(true, {}, adminLoadInitialCommunications_AjaxObject, {data: {first_file_timestamp: first_file_timestamp}});
			}
			else {
				ajaxObject = $.extend(true, {}, adminRefreshCommunications_AjaxObject, {data: {first_file_timestamp: first_file_timestamp}});
			}
			
			$.ajax(ajaxObject)
			.done(function(response) {
				// manage display of divs (and trigger subsequent refreshing)
				setCommunicationsDivs(response);
			})
			.always(function(){
				// recursive call after timeout of refresh rate; by placing this inside
				// .always() this is guaranteed to be performed after .done() is complete
				setTimeout(adminGetCommunications, admin_js_options.refresh_rate);
			});
		};
	
		/**
		 * Load files and messages in the Client Zone the first time.
		 *
		 * @since	1.0.0
		 */
		if ($("#adminCommunicationsPlaceholder").length) {
			adminGetCommunications();
		}

		// PREVIOUS COMMUNICATIONS

		// we check if the current client zone has previous communications available
		$(document).on('wooclientzone:clientZoneHasPreviousCommunicationsAvailable', function(event, clientZoneHasPreviousCommunicationsAvailable) {
			if (clientZoneHasPreviousCommunicationsAvailable) {
				$("#loadPreviousCommunications").show();
			} else {
				$("#loadPreviousCommunications").hide();
			}
		});
		
		// attach ajax action to load previous communications
		if ($("#loadPreviousCommunications").length) {
			
			$("#loadPreviousCommunications").hover(function() {
				$("#loadPreviousCommunications").toggleClass("load-previous-communications-hovering");
			});
			
			$("#loadPreviousCommunications").click(function() {
				
				$("#loadPreviousCommunications a").text(admin_js_options.loading_previous_communications);
				
				var ajaxObject = $.extend(true, {}, adminLoadPreviousCommunications_AjaxObject, {data: {first_file_timestamp: first_file_timestamp}});

				$.ajax(ajaxObject)
				.done(function(response) {
					// manage display of divs
					setCommunicationsDivs(response);
				});
			});
		}

		// ADMIN ACTIONS

		/**
		 * Manages opening/closing of action panels.
		 * 
		 * This is for all the box panels opening the div elements that contain
		 * the various actions available from the admin Client Zone.
		 * 
		 * @since	1.0.0
		 * @listens    click event
		 */
		$(".wooclientzone_actions_header").click(function() {
			$(this).find(".wooclientzone_actions_header_icon").toggleClass('open');
			$(this).next().slideToggle();
		});

		/**
		 * Manages admin actions feedback events.
		 * 
		 * Acts on custom event triggered by the Ajax callback response
		 * to manage the display of error/success feedback messages.
		 * 
		 * @since	1.0.0
		 * @listens    wooclientzone:adminActionsFeedback    Custom event fired by the action panels
		 */
		$(document).on("wooclientzone:adminActionsFeedback", function(event, feedbackElement, errorType, errorText) {
			if (errorType == "error") {
				feedbackElement.removeClass("action_message_success");
				feedbackElement.addClass("action_message_error");
			} else if (errorType == "success") {
				feedbackElement.removeClass("action_message_error");
				feedbackElement.addClass("action_message_success");
			}
			feedbackElement.html(errorText).fadeIn();
		});

		/**
		 * Manages client notification action box header when it needs to alert of unseen communications.
		 * 
		 * This is for all the box panels opening the div elements that contain
		 * the various actions available from the admin Client Zone.
		 * 
		 * @since	1.0.0
		 * @listens    wooclientzone:unseenElements    Custom event fired from within the setCommunicationsDivs function
		 */
		$(document).on('wooclientzone:unseenElements', function(event, unseenElements) {
			if (unseenElements) {
				$(this).find("#wooclientzoneNotifyClientDiv").find(".wooclientzone_actions_header").addClass("action_required");
			} else {
				$(this).find("#wooclientzoneNotifyClientDiv").find(".wooclientzone_actions_header").removeClass("action_required");
			}
		});

		/**
		 * Manages sending client notification emails via Ajax.
		 * 
		 * @since	1.0.0
		 * @listens    click    event
		 * @fires    wooclientzone:adminActionsFeedback    Custom event listened by the function managing feedback messages
		 */
		$("#wooclientzoneNotifyClientButton").on('click', function() {
			// just in case the user is sending an email immediately after another one
			var feedbackElement = $("#feedbackMessageNotifyClient");
			feedbackElement.slideUp();
			var email_subject = $("#wooclientzoneNotifyClientSubject").val();
			var email_text = $("#wooclientzoneNotifyClientTextarea").val();
			var button = this;
			$(this).blur();
			$(this).val(admin_js_options.sending_email_string);
			$.ajax(
			{
				url:    admin_js_options.ajaxurl,
				type:   "POST",
				data:   {
					action: 'admin_notify_client',
					security: admin_js_options.nonce_notify_client,
					userid: admin_js_options.userid,
					orderid: admin_js_options.orderid,
					email_subject: email_subject,
					email_text: email_text
				},
				dataType:	"json"
			})
			.done(function(response) {
				if (response.error) {
					$(document).trigger("wooclientzone:adminActionsFeedback", [feedbackElement, "error", response.errorstring]);
				} else {
					$(document).trigger("wooclientzone:adminActionsFeedback", [feedbackElement, "success", response.feedback]);
				}
			})
			.fail(function(xhr, status, errorThrown) {
				$(document).trigger("wooclientzone:adminActionsFeedback", [feedbackElement, "error", 'Error: ' + errorThrown + '<br>Status: ' + status]);
			})
			.always(function(xhr, status) {
				$(button).val(admin_js_options.notify_client_string);
			});
		});

		/**
		 * Manages select for switching view to other client zones.
		 * 
		 * @since	1.0.0
		 * @listens    change    event
		 */
		$('#selectOtherClientzone').on('change', function() {
			var selectedOption = $('#selectOtherClientzone option:selected');
			var data = selectedOption.data();
			if (typeof data.url != 'undefined') {
				window.location.href = data.url;
			}
		});

		/**
		 * Manages moving files to other client zones via Ajax.
		 * 
		 * @since	1.0.0
		 * @listens    click    event
		 * @fires    wooclientzone:adminActionsFeedback    Custom event listened by the function managing feedback messages
		 */

		// we first check if the current client zone has data, otherwise we hide the moving option altogether
		$(document).on('wooclientzone:clientZoneHasContent', function(event, clientZoneHasContent) {
			if (clientZoneHasContent) {
				$("#adminWooclientzoneMovezoneDiv").show();
			} else {
				$("#adminWooclientzoneMovezoneDiv").hide();
			}
		});

		// we manage the move zone action box
		if ($("#wooclientzoneSelectMoveTable").length) {
			var selectedOption, newOrderid;
			$("#selectMoveClientzone").on('change', function() {
				selectedOption = $("#selectMoveClientzone option:selected");
				newOrderid = selectedOption.val();
				if (newOrderid == -1) {
					$("#wooclientzoneMoveButton").slideUp();
					return;
				}
				$("#wooclientzoneMoveButton").slideDown();
				//console.log('REQUESTED new order ID ' + newOrderid);
			});
			$("#wooclientzoneMoveButton").on('click', function() {
				var button = this;
				$(this).blur();
				$(this).val(admin_js_options.moving_clientzone_string);
				//console.log('orderid = ' + admin_js_options.orderid);
				$.ajax(
				{
					url:    admin_js_options.ajaxurl,
					type:   "POST",
					data:   {
						action: 'admin_move_clientzone',
						security: admin_js_options.nonce_move_clientzone,
						userid: admin_js_options.userid,
						orderid: admin_js_options.orderid,
						newOrderid: newOrderid,
					},
					dataType:	"json"
				})
				.done(function(response) {
					if (response.error) {
						$("#errorMessageMoveClientzone").html(response.errorstring).fadeIn();
					} else {
						$("#errorMessageMoveClientzone").hide();
						$(button).slideUp();
						// redirect to new client zone (with nonced url)
						var data = selectedOption.data();
						if (typeof data.url != 'undefined') {
							window.location.href = data.url;
						}
					}
				})
				.fail(function(xhr, status, errorThrown) {
					$(document).trigger("wooclientzone:adminActionsFeedback", [feedbackElement, "error", 'Error: ' + errorThrown + '<br>Status: ' + status]);
				})
				.always(function(xhr, status) {
					$(button).val(admin_js_options.move_clientzone_string);
				});
			});
		}

		// CLIENTZONE ACTIONS

		/**
		 * Manages the opening/closing of the adminAddCommunicationDiv element.
		 * 
		 * @since	1.0.0
		 * @listens    click event
		 */
		if ($("#adminAddCommunicationHeader").length) {
			$("#adminAddCommunicationHeader").click(function() {
				$(this).find("#adminAddCommunicationHeaderIcon").toggleClass('open');
				$(this).next().slideToggle();
			});
		}

		/**
		 * Manages sending of messages via Ajax.
		 * 
		 * @since	1.0.0
		 * @listens    click event
		 */
		if ($('#adminMessageFormPlaceholder').length) {

			$('#adminMessageSubmitButton').click(function() {
//					console.log('TEXT with content: ' + $('#adminmessagetextarea').val()); // we have disabled the text editor with quicktags => false
				var message = tinyMCE.activeEditor.getContent();
				//console.log('About to send message with content: ' + message);
				if (!message) {
					return;
				}
				$.ajax(
				{
					url:    admin_js_options.ajaxurl,
					type:   "POST",
					data:   {
						action: 'admin_submit_message',
						security: admin_js_options.nonce_submit_message,
						userid: admin_js_options.userid,
						orderid: admin_js_options.orderid,
						data: message
					},
					dataType:	"json"
				})
				.done(function(response) {
					setCommunicationsDivs(response);
					if (!response.error) {
						tinyMCE.activeEditor.setContent('');
					}
				})
				.fail(function(xhr, status, errorThrown) {
					$("#errorMessage").html('Error: ' + errorThrown + '<br>Status: ' + status).fadeIn();
				})
				.always(function(xhr, status) {
					tinyMCE.activeEditor.focus();
				});
			});
		}

		// ADMIN WIDGET NOTIFICATIONS

		var dashboardWidgetNotificationsPlaceholder = $("#dashboardWidgetNotificationsPlaceholder");

		/**
		 * @summary    Calls via Ajax the functionality to create the dashboard widget with the notifications of unseen communications.
		 *
		 * @since 1.0.0
		 */			
		var adminDashboardLoadNotifications = function() {

			$("#dashboardWidgetNotificationsRefreshIcon").addClass('refreshing');
			$.ajax(
			{
				url:    admin_js_options.ajaxurl,
				type:   "POST",
				data:   {
					action: 'admin_dashboard_notifications_get_content',
					security: admin_js_options.nonce_admin_widget_notifications
				},
				dataType:	"json"
			})
			.done(function(response) {

				$("#dashboardWidgetNotificationsRefreshIcon").removeClass('refreshing');

				// console.log(response);
				if (response.error) {
					// set correct message element depending on the error type (if defined)
					if (response.errorType == 'info') {
						dashboardWidgetNotificationsPlaceholder.addClass('admin_widget_notifications_info');
					}
					else {
						dashboardWidgetNotificationsPlaceholder.addClass('admin_widget_notifications_error');
					}
					dashboardWidgetNotificationsPlaceholder.html(response.errorstring).fadeIn();
				} else {
					// place content into the placeholder
					dashboardWidgetNotificationsPlaceholder.removeClass('admin_widget_notifications_info');
					dashboardWidgetNotificationsPlaceholder.removeClass('admin_widget_notifications_error');
					dashboardWidgetNotificationsPlaceholder.html(response.content).fadeIn();
				}
			})
			.fail(function(xhr, status, errorThrown) {
				dashboardWidgetNotificationsPlaceholder.removeClass('admin_widget_notifications_info');
				dashboardWidgetNotificationsPlaceholder.addClass('admin_widget_notifications_error');
				dashboardWidgetNotificationsPlaceholder.html('Error: ' + errorThrown + '<br>Status: ' + status).fadeIn();
			});
		};

		/**
		 * Loads notifications when loading the admin dashboard page.
		 * 
		 * @since	1.0.0
		 * @listens    click event
		 */
		if (dashboardWidgetNotificationsPlaceholder.length) {
			adminDashboardLoadNotifications();
		}

		/**
		 * Reloads notifications when refreshing the widget content
		 * 
		 * @since	1.0.0
		 * @listens    click event
		 */
		$("#dashboardWidgetNotificationsRefreshIcon").on('click', function() {
			adminDashboardLoadNotifications();
		});

		// FOOTER STRINGS AND LINKS
		
		if ($("#adminWooclientzoneWrapper").length || $("#wooclientzone_root_folder").length) {
			
			// we are either inside the WooClientZone settings page or inside an admin Client Zone
			var footerText;
			var documentationText = '<a href="' + admin_js_options.wooclientzone_docs_link + '" target="_blank">'+ admin_js_options.wooclientzone_docs_text + '</a>';
			if (admin_js_options.rating_required) {
				footerText = admin_js_options.rating_string + '&nbsp;&nbsp;' + documentationText;
			} else {
				footerText = admin_js_options.thankyou_using_wooclientzone + '&nbsp;&nbsp;' + documentationText;
			}
			if ($("#adminWooclientzoneWrapper").length) {
				// we add an upgrade link to the footer of admin Client Zones
				footerText += '&nbsp;|&nbsp;' + admin_js_options.upgrade_string;
			}
			$("#footer-left").html(footerText);

			// when clicking on rating link send ajax request to store rating_done option, and switch string
			$('a.wooclientzone-rating-link').click( function() {
				$.post(admin_js_options.ajaxurl, { action: 'admin_rating_link_clicked' });
				$( this ).parent().html(admin_js_options.thankyou_using_wooclientzone + '&nbsp;&nbsp;' + documentationText);
			});
			
		}
		
	});
		
})( jQuery );
