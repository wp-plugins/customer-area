=== Customer Area ===
Contributors: vprat, marvinlabs
Tags: private files,client area,customer area,user files
Requires at least: 3.5
Tested up to: 3.6
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Customer area give your customers the possibility to get a page on your site where they can access private content. 

== Description ==

Customer area give your customers the possibility to get a page on your site where they can access private content. 
As of today, private content means files: you upload files for your customer, and only him will be able to view them
or download them on his page.

**Current features**

* Secure customer area, accessible to logged-in users
* Private files, that can be assigned to a particular user and will get listed in its customer area
* Kick-ass filtering thanks to the integration of the [jQuery shuffle plugin](http://vestride.github.com/Shuffle)
 
**Coming soon**

* Email notifications
* More private content type (messages/...)

If you like our plugins, you might want to [check our website](http://www.marvinlabs.com) for more.

If you want to get updates about our plugins, you can:

* [Follow use on Twitter](http://twitter.com/marvinlabs)
* [Follow use on Google+](https://plus.google.com/u/0/117677945360605555441)
* [Follow use on Facebook](http://www.facebook.com/studio.marvinlabs)
	
== Upgrade Notice ==

Nothing worth mentionning yet. 

== Installation ==

1. Nothing special, just upload the files, activate and you can then visit the settings page if you want. Like any 
other plugin
1. You need then to create a page and insert the [customer-area] shortcode. Your customers will be able to access their
private content on that page.
1. Finally, you will need to create content for the users (!): you can start with a private file for instance. Just 
check out the WordPress menu in your administration panel, you can add new customer files just like any post. Simply 
upload a file (below the content box), set the owner of that file, publish, and your customer should be able to see it.  

== Screenshots ==

Coming soon.

== Frequently Asked Questions ==

= How can I forbid direct download of user files? =

If someone knows the URL of a particular user file, he can download it without restriction. You can however secure this
by copying the file "protect-downloads.htaccess" included in our plugin's extras folder to the plugin's upload folder
(it should be /wp-content/customer-area). Then you will need to rename that file ".htaccess" so that your server takes
it into account.

= That feature is missing, will you implement it? =

Open a new topic on the plugin's support forum, I will consider every feature request and all ideas are welcome.

= I implemented something, could you integrate it in the plugin? =

Contributions are welcome. Additionally, if you wish to participate to development, you can send us an email 
([check-out our website](http://www.marvinlabs.com)) and tell us a little bit about you (specially, send us a link to your wordpress.org profile
with your other developed plugins.

== Changelog ==

= 1.0.0 (2013/04/25) =

* First plugin release
* Upload private files for your customer