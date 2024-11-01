=== WooClientZone Lite ===
Contributors: Blendscapes
Donate link: http://blendscapes.com/upgrade-wooclientzone/
Tags: WooCommerce, chat, client area, client zone, communication, messaging, message, messages
Requires at least: 4.7
Tested up to: 5.0
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WooClientZone Lite integrates with WooCommerce to create areas where clients and merchants can exchange rich-text messages.

== Description ==

WooClientZone is fully integrated with WooCommerce to provide communication areas (Client Zones) where the client and the merchant can exchange rich-text messages, both before and after an order is placed.

WooClientZone is very flexible and may for example be configured to just allow communications either before or after an order is placed. It can also be configured to disable messaging from the client, with separate settings for Client Zones carrying communications before and after orders.

Please refer to the [online documentation](http://blendscapes.com/wooclientzone_lite_documentation/) for a quick start.

= Who is it for =

WooClientZone is built for businesses providing consultancy work and/or services, and more generally for those activities where strong interaction with the client (before or after an order is placed, or both) is part of the business model. Businesses from providers of legal or medical advice, to those creating custom graphics (from t-shirts to company logos) can greatly benefit from WooClientZone.

= Client Zones =

Client Zones are designed as chat-like communication areas, and mimic the look and feel of applications such as Whatsapp. Client Zones are **fully responsive**, and look right on any device, from desktop to tablets and smartphones. They are also **reactive**, so clients can see a new message pop up as they interact with the merchant (and vice versa).

Messages are written on the WordPress TinyMCE editor, thus allowing text to be easily formatted (to include links, for example).

Client Zones may be associated to the customer before an order is placed, and/or they may be linked to individual orders. The customer is then able to navigate though the Client Zones linked to their various orders (if order-linked Client Zones are enabled from the back end).

You may even specify that only orders containing specific products trigger the creation of a Client Zone. In fact, the plugin can be configured to automatically ensure that any conversation (sequence of messages) occurred before an order is placed **will follow that order**, once that is placed by the client.

= Notifications =

The client may receive notifications of new communications via email, which are sent by the merchant from within the relevant Client Zone in the admin site. Emails are configured in the back end, but can be changed right before sending them from each Client Zone, and are sent in html format.

Within the *My Account* area of WooCommerce, clients can access all their Client Zones, and immediately see if new unseen communications are present.

Similarly, merchants are notified of any new message, unseen by either party, directly from the admin Dashboard, where WooClientZone defines its own widget. This is specifically designed for this purpose, and offers a direct link to any Client Zone containing unseen communications.

= Extensibility =

WooClientZone is built with extensibility in mind. The plugin’s codebase is commented to [WordPress Documentation Standards](https://make.wordpress.org/core/handbook/best-practices/inline-documentation-standards/php/).
Filter and action hooks are available to add functionality through plugins and themes, so any modification or extension is easily achieved.

= Logs =

WooClientZone performs its logging within the WooCommerce logging system, and the administrator can set a minimum level of severity for the messages to be automatically logged. This avoids bloating the log file if the administrator is only interested in warning- to emergency-level messages, for example. WooClientZone uses the new logging class in forthcoming WooCommerce 3.x, but falls back to using the previous one if working alongside an earlier version.

= Fully integrated with WooCommerce =

Client Zones are accessible from a number of WooCommerce standards pages, such as the order list and order edit pages in the front end, as well as from a number of pages in the back end.

Notice that *WooClientZone requires customers to be logged in*, so WooCommerce should ideally be configured to enable registration on the “My Account” page (unless you are only enabling Client Zones for after-purchase communications). The plugin will alert the administrator upon activation if this is required in any particular case. In general, however, **Client Zones are only displayed to registered customers**.

= Upgrade to Premium version =

[Upgrade WooClientZone][upgrade link]. In the premium version:

* Communications include files in addition to rich-text messages
* Files are uploaded by drag & drop, with progress bars customizable in color (multiple file uploads are supported by default)
* Merchants have full control on allowed file type and size, with different settings for merchant and clients
* Client permissions (enable/disable file uploads and/or messaging) can be customized on each individual Client Zone
* Enable extra display options, with the ability to change colors and positions of communication bubbles

[upgrade link]: http://blendscapes.com/upgrade-wooclientzone/
            "Upgrade to WooclientZone - premium version"

== Installation ==

= Installation from the WP back end =

* Go to the 'Plugins' back end page and select 'Add New' from the top part of the page;
* Either upload the WooClientZone plugin zip file (clicking on 'Upload Plugin') or find it in the online repository entering 'WooClientZone' in the 'Search plugins ...' form;
* Once installed, activate the plugin and you are ready to go.

= Manual installation =

* Upload the WooClientZone plugin zip file to the `/wp-content/plugins/` directory of your WP installation;
* Unzip the file;
* Go to the 'Plugins' back end page and activate the WooClientZone plugin, which should be listed among the others.

== Frequently Asked Questions ==

= I have installed and activated the plugin. How do I start? =

Once installed and activated, WooClientZone Lite is already operational with the default settings. Registered customers, when accessing the My Account page, will see information on how to access their communications area (Client Zone). They will see a new Communications link in addition to the other My Account menu items.

So the personal communications areas are immediately accessible. If you intend to use communications areas linked to orders, you can either set this functionality, from the plugin's Settings page, for all the orders, or (the default setting) only for those orders that include specific products.

= How do I enable specific products to trigger communication areas? =

By default, WooClientZone Lite only generates order-linked communication areas for orders containing specific products (this can be changed from the Settings page to enable Client Zones for all orders, or none, as required).

To specify that a product triggers a Client Zone when included in an order, go to that WooCommerce product edit page, and tick 'Use Client Zone', a checkbox next to the 'Virtual' and 'Downloadable' checkboxes.

= Can I disable 'personal' Client Zones, non related to orders? =

Yes, you can do that easily by unchecking the 'Create user-linked zones' from the plugin's settings.

= Can I disable order-linked zones? =

Yes, the Settings entry 'Create order-linked zones' can be set to 'never create order-linked zones', 'only for orders containing specific products' (default), and 'for all orders'.

= I would like communications exchanged before an order to be stored alongside a subsequent order placed by the customer. Can I do that? =

Yes, you just need to tick the Settings checkbox 'Automatically move a user-linked Client Zone to the next eligible order'. All communications exchange before an order (that is, on a user-linked Client Zone) will be moved to the Client Zone associated to the next order. Depending on your settings this could be on any order or (default configuration) on the next order containing an eligible product.

= How do I access the Settings page? =

From the WP Plugins page, find the entry for WooClientZone Lite, and click on the 'Settings' link below the plugin's name. Alternatively, from the admin site, you go to WooCommerce > Settings, then select the 'Client Zone' tab.

= How do I access full documentation? =

Full documentation is [available online](http://blendscapes.com/wooclientzone_lite_documentation/). You can also access it anytime from the WP Plugins page: find the entry for WooClientZone Lite, and click on the 'Docs' link on the second line.

== Screenshots ==

1. A Client Zone linked to a user (independent from orders) seen by the customer
2. A Client Zone linked to a user (independent from orders) seen by the merchant
3. A Client Zone linked to an order seen by the merchant
4. A Client Zone linked to an order seen by the customer (desktop view)
5. A Client Zone linked to an order seen by the customer (mobile view)
6. Dashboard notification widget shown to the merchant

== Changelog ==

= 1.1 =
* Added ability to display only the last communications (defaults to 20), with options to display 20, 40, 60, or all communications; if more communications are present, a 'load more' link is displayed at the top of the communications area.
* Changed description of the settings option 'My Account menu icon', to better describe its use.
* Added new WooCommerce-specific headers (new in WC v3.2).
* Various internal changes to streamline code in Ajax calls.

= 1.0.1 =
* Minor change: fixed links in Plugins page.

= 1.0.0 =
* Initial version.
