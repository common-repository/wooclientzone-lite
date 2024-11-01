/**
 * This is the script that manages all front end JavaScript.
 *
 * @summary   Manages front end JavaScript.
 *
 * @link	   http://blendscapes.com
 * @since     1.0.0
 * @requires jQuery
 * 
 */
(function( $ ) {
	'use strict';

	/**
	 * All of the code for our public-facing JavaScript source
	 * should reside in this file.
	 *
	 * We use jQuery inside the function that specifies that the DOM is ready.
	 * 
	 * $(function() {
	 *
	 * });
	 *
	 */

	// PUBLIC SIDE CODE - note that public_js_options is enqueued by class Wooclientzone_Public

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
				return $('#publicmessagetextarea').val();
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
		 * The object defining the basic Ajax parameters for loading communications; it is not used as such, but is extended.
		 *
		 * @since 1.0.0
		 * @property    string    url    The ajax url.
		 * @property    string    type   The http methods.
		 * @property    object    data   Data sent with the Ajax call.
		 * @property    string    dataType   The expected response's data type.
		 */
		var publicLoadCommunications_AjaxObject =
		{
			url:    public_js_options.ajaxurl,
			type:   "POST",
			data:   {
				action:	  'public_load_communications',
				security:  public_js_options.nonce_load_communications,
				orderid:   public_js_options.orderid
			},
			dataType:   "json"
		};

		/**
		 * The object defining all Ajax parameters for loading initial communications.
		 *
		 * Extends publicLoadCommunications_AjaxObject with a loading_initial_communications parameter,
		 * within the data object, which is set to true.
		 * 
		 * @since 1.1
		 * @property    object    data   Data sent with the Ajax call.
		 */
		var publicLoadInitialCommunications_AjaxObject = $.extend(true, {}, publicLoadCommunications_AjaxObject, {data: {loading_initial_communications: true}});

		/**
		 * The object defining all Ajax parameters for refreshing communications.
		 *
		 * Extends publicLoadCommunications_AjaxObject with a refresh parameter,
		 * within the data object, which is set to true.
		 * 
		 * @since 1.0.0
		 * @property    object    data   Data sent with the Ajax call.
		 */
		var publicRefreshCommunications_AjaxObject = $.extend(true, {}, publicLoadCommunications_AjaxObject, {data: {refreshing: true}});

		/**
		 * The object defining all Ajax parameters for loading previous communications.
		 *
		 * Extends publicLoadCommunications_AjaxObject with a loading_previous_communications parameter,
		 * within the data object, which is set to true.
		 * 
		 * @since 1.1
		 * @property    object    data   Data sent with the Ajax call.
		 */
		var publicLoadPreviousCommunications_AjaxObject = $.extend(true, {}, publicLoadCommunications_AjaxObject, {data: {loading_previous_communications: true}});

		/**
		 * @summary    Loads the returned div elements from the Ajax call into the communications placeholder.
		 *
		 * This is used in the callback functions of the Ajax call
		 *
		 * @since 1.0.0
		 *
		 * @param    object    response      This is the returned json-format data from the Ajax call to load/refresh communications.
		 */			
		var setCommunicationsDivs = function(response) {

			if (firstTimeLoadingCommunications) {
				firstTimeLoadingCommunications = false;
				$(".loader-public").hide();
			}
			if (response.loading_previous_communications) {
				$("#loadPreviousCommunications a").text(public_js_options.load_more_previous_communications);
			}

			// if previous communications are available, which have not been fetched yet, trigger appropriate event to manage the load-previous link
			$(document).trigger('wooclientzone:clientZoneHasPreviousCommunicationsAvailable', (response.previous_communications_available ? [true] : [false]));
			
			// console.log(response);
			if (response.new_divs) {
				$('#errorMessage').hide();
				$('#successMessage').hide();
				if (response.loading_previous_communications) {
					$(response.new_divs).hide().prependTo('#publicCommunicationsPlaceholder').fadeIn(1000);
				} else {
					$(response.new_divs).hide().appendTo('#publicCommunicationsPlaceholder').fadeIn(1000);
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
			// show 'seen' divs based on admin last access (note the server only placed a seen div under the client bubbles)
			$('.filediv').each(function(index) {
			   var data = $(this).data();
			   // get first file timestamp
			   if (data.timestamp < first_file_timestamp) {
				   first_file_timestamp = data.timestamp;
			   }
			   // check if the current file was seen
			   if (data.timestamp <= response.admin_lastaccess)
				   $(this).find('.bubble-footer-seen').show();
			});
		};

		/**
		 * Ajax load (and subsequently refresh) files and messages in the Client Zone.
		 *
		 * @since	1.0.2
		 */
		var publicGetCommunications = function() {

			var ajaxObject;
			
			if (firstTimeLoadingCommunications) {
				ajaxObject = $.extend(true, {}, publicLoadInitialCommunications_AjaxObject, {data: {first_file_timestamp: first_file_timestamp}});
			}
			else {
				ajaxObject = $.extend(true, {}, publicRefreshCommunications_AjaxObject, {data: {first_file_timestamp: first_file_timestamp}});
			}
			
			$.ajax(ajaxObject)
			.done(function(response) {
				// manage display of divs (and trigger subsequent refreshing)
				setCommunicationsDivs(response);
			})
			.always(function(){
				// recursive call after timeout of refresh rate; by placing this inside
				// .always() this is guaranteed to be performed after .done() is complete
				setTimeout(publicGetCommunications, public_js_options.refresh_rate);
			});
		};
	
		/**
		 * Load files and messages in the Client Zone the first time.
		 *
		 * @since	1.0.0
		 */
		if ($("#publicCommunicationsPlaceholder").length) {
			publicGetCommunications();
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
				
				$("#loadPreviousCommunications a").text(public_js_options.loading_previous_communications);
				
				var ajaxObject = $.extend(true, {}, publicLoadPreviousCommunications_AjaxObject, {data: {first_file_timestamp: first_file_timestamp}});

				$.ajax(ajaxObject)
				.done(function(response) {
					// manage display of divs
					setCommunicationsDivs(response);
				});
			});
		}

		// MY ACCOUNT NOTIFICATIONS

		// This is the placeholder of the notification of unseen new communications (placed in the My Account dashboard page)
		var myAccountNotificationsPlaceholder = $("#myAccountNotificationsPlaceholder");

		/**
		 * If we are in the My Account page, loads the unseen notifications list.
		 *
		 * @since	1.0.0
		 */
		if (myAccountNotificationsPlaceholder.length) {

			$.ajax(
			{
				url:    public_js_options.ajaxurl,
				type:   "POST",
				data:   {
					action: 'my_account_notifications_get_content',
					security: public_js_options.nonce_my_account_notifications
				},
				dataType:   "json"
			})
			.done(function(response) {

				if (response.error) {
					;// fail silently myAccountNotificationsPlaceholder.html(response.errorstring).fadeIn();
				} else {
					// place content into the placeholder
					myAccountNotificationsPlaceholder.html(response.content).fadeIn();
				}
			})
			.fail(function(xhr, status, errorThrown) {
				;// fail silently myAccountNotificationsPlaceholder.html('Error: ' + errorThrown + '<br>Status: ' + status).fadeIn();
			});
		}

		// NAVIGATION ACTIONS

		/**
		 * Manages opening/closing of action panels.
		 * 
		 * This is for the panel opening the div elements that contains
		 * the select tag element to switch to another Client Zone.
		 * 
		 * @since	1.0.0
		 * @listens    click event
		 */
		$(".wooclientzone_actions_header").click(function() {
			$(this).find(".wooclientzone_actions_header_icon").toggleClass('open');
			$(this).next().slideToggle();
		});

		/**
		 * Manages the select element for switching view to other Client Zones.
		 * 
		 * @since	1.0.0
		 * @listens    click event
		 */
		$('#selectOtherClientzone').on('change', function() {
			var selectedOption = $('#selectOtherClientzone option:selected');
			var data = selectedOption.data();
			if (typeof data.url != 'undefined') {
				window.location.href = data.url;
			}
		});

		// CLIENTZONE ACTIONS

		/**
		 * Manages the opening/closing of the publicAddCommunicationDiv element.
		 * 
		 * @since	1.0.0
		 * @listens    click event
		 */
		if ($("#publicAddCommunicationHeader").length) {
			$("#publicAddCommunicationHeader").click(function() {
				$(this).find("#publicAddCommunicationHeaderIcon").toggleClass('open');
				$(this).next().slideToggle();
			});
		}

		/**
		 * Manages sending of messages via Ajax.
		 * 
		 * @since	1.0.0
		 * @listens    click event
		 */
		if ($('#publicMessageFormPlaceholder').length) {

			$('#publicMessageSubmitButton').click(function() {
//					console.log('TEXT with content: ' + $('#publicmessagetextarea').val()); // we have disabled the text editor with quicktags => false
				var message = tinyMCE.activeEditor.getContent();
				//console.log('About to send message with content: ' + message);
				if (!message) {
					return;
				}
				$.ajax(
				{
					url:    public_js_options.ajaxurl,
					type:   "POST",
					data:   {
						action: 'public_submit_message',
						security: public_js_options.nonce_submit_message,
						orderid: public_js_options.orderid,
						data: message
					},
					dataType:   "json"
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
		
		// UTILITIES
		
		/**
		 * Generates a delay in milliseconds.
		 * 
		 * Ref: first comment in https://www.sitepoint.com/delay-sleep-pause-wait/
		 * 
		 * @since	1.0.2
		 */
		var doWait = function(milliseconds) {
			
			var start = new Date().getTime();
			var timer = true;

			while (timer) {
				if ((new Date().getTime() - start)> milliseconds) {
					timer = false;
				}
			}
		};

	});

})( jQuery );
