=== YITH WooCommerce Questions and Answers Premium ===

Contributors: yithemes
Tags: woocommerce, e-commerce, ecommerce, shop, product details, question, questions, answer, answers, product question, product, pre sale question, ask a question, product enquiry, yith, plugin
Requires at least: 4.0.0
Tested up to: 5.2.x
Stable tag: 1.3.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Documentation: https://docs.yithemes.com/yith-woocommerce-questions-and-answers/

It allows users of your shop to ask questions on products and answer to them, a tool like in Amazon pages, but that can be also used as a FAQ section

== Changelog ==


= Version 1.3.2 - Released: Aug 05, 2019 =

* New: support WooCommerce 3.7
* New: new link in the frontend to edit the Q&A in the backend
* New: new metabox with the last Q&A post editor data
* New: now, the Q&A author and the date are displayed in the Q&A overview on frontend
* Update: updated plugin core
* Fix: fixed a problem with the answer editor in the backend
* Dev: added a new condition to avoid a non object warning
* Dev: added an scroll up, if necessary, when access to the question
* Dev: fixing minor issues

= Version 1.3.1 - Released: May 31, 2019 =

* Update: Italian language
* Fix: gcaptcha is loaded although the option is disabled
* Dev: new filter 'ywqa_get_questions_order_by'


= Version 1.3.0 - Released: May 24, 2019 =

* New: support to WordPress 5.2
* New: support to reCaptcha v3
* Tweak: now, the notification bubble display the questions without answers
* Update: updated plugin submodules
* Remove: completely deleted the search bar

= Version 1.2.9 - Released: Apr 09, 2019 =

* New: support to WooCommerce 3.6.0 RC 1
* Update: updated Plugin Framework
* Update: updated Dutch language
* Update: updated Spanish language
* Dev: check if files exist to rename the files

= Version 1.2.8 - Released: Feb 19, 2019 =

* New: added a bubble notification in the backend when a new Q&A is added
* New: checking the Q&A with Akismet Anti-spam if it is installed
* Update: main file language
* Update: updated Dutch language
* Update: updated Plugin Framework
* Dev: new filter 'yith_wcqa_question_success_message'
* Dev: new filter 'yith_wcqa_answer_success_message'


= Version 1.2.7 - Released: Dec 11, 2018 =

* New: support to WordPress 5.0
* Update: plugin core to version 3.1.10
* Dev: new filter 'yith_ywqa_check_count_tab_title'


= Version 1.2.6 - Released: Nov 16, 2018 =

* Update: Plugin framework
* Update: Dutch language
* Update: Language file .pot
* Fix: English correction text
* Fix: Compatibility fix with WPML
* Fix: load questions on "Back to questions" link for users not logged in
* Fix: fixed the answer sorting in the FAQ mode
* Remove: Search bar options


= Version 1.2.5 - Released: Oct 23, 2018 =

* Update: Plugin framework

* New: Support to WooCommerce 3.5.0
* Tweak: new action links and plugin row meta in admin manage plugins page
* Update: Italian language
* Fix: fixing the wp_nonce_field

= Version 1.2.4 - Released: Oct 17, 2018 =

* New: Support to WooCommerce 3.5.0
* Tweak: new action links and plugin row meta in admin manage plugins page
* Update: Italian language
* Fix: fixing the wp_nonce_field

= Version 1.2.3 - Released: Jun 14, 2018 =

* Update: Spanish language
* Update: Dutch language
* Update: updating minified JS
* Updated: updated the official documentation url of the plugin
* Fix: Fixed the issue with the google recaptcha and the JS
* Dev: added a new filter in the login redirect
* Dev: checking YITH_Privacy_Plugin_Abstract for old plugin-fw versions



= Version 1.2.2 - Released: May 25, 2018 =

* GDPR:
   - New: exporting user question and answers data info
   - New: erasing user question and answers data info
   - New: privacy policy content
* Tweak: added trigger yith_ywqa_answer_helpfull
* Tweak: add nofollow to plugin a tags
* Tweak: check product object in email values
* Update: Italian Translation
* Update: Documentation link of the plugin
* Fix: frontend and frontend.min.js get this files from the last version
* Fix: missing product id display on backend
* Fix: Fixed the pagination in answers
* Fix: Fixed an issue with the recaptcha dependencies
* Fix: register and enqueue recaptcha

= Version 1.2.1 - Released: Jan 30, 2018 =

* New: Dutch language
* New: support to WooCommerce 3.3.0-RC2
* Update: plugin framework 3.0.11
* Dev: new filter 'yith_ywqa_tab_title'
* Dev: new js trigger 'yith_ywqa_answer_helpfull'
* Dev: new filter 'yith_ywqa_answer'


= Version 1.2.0 - Released: Dec 11, 2017 =
* Update: Plugin fw to version 3.0


= Version 1.1.30 - Released: Nov 27, 2017 =
* New: support to WooCommerce 3.2.5
* Dev: new filter 'yith_wcqa_allow_user_to_reply'


= Version 1.1.29 - Released: Sep 22, 2017 =
* Dev: new filter 'yith_ywqa_no_answer_text'
* Tweak: email validation process on submit question or answer


= Version 1.1.28 - Released: Aug 29, 2017 =
* New: option to set label for the tab "Questions & Answers"
* New: option to set title for the section "Questions & Answers"
* Update: plugin framework


= Version 1.1.27 - Released: Jul 06, 2017 =

* New: support for WooCommerce 3.1.
* New: tested up to WordPress 4.8.
* Fix: email layout, prevent the product image from overlapping the email content.
* Update: YITH Plugin Framework.

= Version 1.1.26 - Released: Jun 23, 2017 =

* Update: users can change the answers order dynamically.

= Version 1.1.25 - Released: Jun 22, 2017 =

* Update: allow users to receive a notification when an answer to their question is submitted.
* Fix: 'back to questions' link not working due to missing product id.
* Fix: avoid showing duplicated reCaptcha element.
* Fix: do not show product link in Q&A table, if the product is deleted.

= Version 1.1.24 - Released: May 15, 2017 =

* Fix: typo in ywqa-product-questions.php file.

= Version 1.1.23 - Released: Apr 11, 2017 =

* New:  Support to WordPress 4.7.3.
* New:  Support to WooCommerce 3.0
* Fix: filtering questions or answers by product give empty results if the field is left blank.

= Version 1.1.22 - Released: Dec 07, 2016 =

* New: ready for WordPress 4.7

= Version 1.1.21 - Released: Nov 16, 2016 =

* Fix: a notice was shown on outgoing emails generated by third party plugins
* Fix: a notice was shown in admin settings as a conflict with the plugin 'Members'
* Fix: product link not valid in the email sent when a new question is submitted

= Version 1.1.20 - Released: Nov 04, 2016 =

* Fix: removed unwanted escape symbol in question and answer emails

= Version 1.1.19 - Released: Oct 24, 2016 =

* New: email notification with YITH Email Templates compatibility
* Update: frontend templates location
* Fix: enable vote option did not apply correctly
* Fix: questions and answers date format use the WordPress settings

= Version 1.1.18 - Released: Sep 15, 2016 =

* New: email templates overwritable from theme
* New: link to the product page in the notification email
* Fix: non-object reference when there aren't answers to a question
* Fix: user notification on new question not set correctly

= Version 1.1.17 - Released: Aug 19, 2016 =

* New: option for allowing anonymous question or answer submisssion
* Fix: HTML formatting not applied when clicking on "Read more" link

= Version 1.1.16 - Released: Jul 27, 2016 =

* Fix: the badge "answered by admin" shown for mistakenly

= Version 1.1.15 - Released: Jun 20, 2016 =

* Update: WooCommerce 2.6 100% compatible
* Fix: warning for missing 'domain' for custom view in questions and answers table
* Fix: some text in the email were not correctly localizable

= Version 1.1.14 - Released: Jun 13, 2016 =

* Update: WooCommerce 2.6 100% compatible
* Fix: warning for missing 'domain' for custom view in questions and answers table

= Version 1.1.13 - Released: May 26, 2016 =

* Fix: author name shown even if anonymous mode is set
* Update: plugin WooCommerce 2.6 ready

= Version 1.1.12 - Released: May 17, 2016 =

* Update: YITH Plugin FW
* Update: italian localization files
* Fix: missing method call vote_question
* Fix: typos in plugin code

= Version 1.1.11 - Released: Apr 15, 2016 =

* Fix: search questions/answers by product on back-end fails

= Version 1.1.10 - Released: Apr 05, 2016 =

* New: option that let you choose if answers should be moderated before being shown
* New: option that let you choose if guest users can post content
* New: guest users should enter their name and email address when posting content

= Version 1.1.9 - Released: Mar 21, 2016 =

* Fix: error on save on quick edit applied to a product
* Update: removed quick edit on Questions&Answers page

= Version 1.1.8 - Released: Mar 11, 2016 =

* New: choose if the output will be shown on a custom tab of the product tabs or manually via the [ywqa_questions] shortcode

= Version 1.1.7 - Released: Jan 18, 2016 =

* Update: plugin ready for WooCommerce 2.5.x
* Update: improved layout for backend questions page

= Version 1.1.6 - Released: Dec 14, 2015 =

* Fix: YITH Plugin Framework breaks updates on WordPress multisite
* Update: callback fails with PHP version prior to 5.4

= Version 1.1.5 - Released: Dec 10, 2015 =

* Fix: warning on backend Q&A table
* Fix: missing string localization
* Update: localization file

= Version 1.1.4 - Released: Dec 09, 2015 =

* Fix: filters on Questions and Answers table not working
* Fix: questions and answers of guest were assigned to current user if the post is opened on backend

= Version 1.1.3 - Released: Dec 07, 2015 =

* Update: question/answer author name and email shown on Q&A table
* Tweak: Database query for back compatibility fired even if not need

= Version 1.1.2 - Released: Nov 13, 2015 =

* Update: Changed text-domain from ywqa to yith-woocommerce-questions-and-answers
* Update: YITH Plugin Framework
* Update: changed action used for YITH Plugin FW loading

= Version 1.1.1 - Released: Oct 14, 2015 =

* New: CSS class for highlighting unapproved content on Questions & Answers back end table.

= Version 1.1.0 - Released: Sep 04, 2015 =

* New: editor for questions and answers.

= Version 1.0.4 - Released: Aug 12, 2015 =

* Tweak: update YITH Plugin framework.

= Version 1.0.3 - Released: Aug 06, 2015 =

* New: optional "No CAPTCHA reCAPTCHA" system to submit questions and answers.

= Version 1.0.2 - Released: Jul 28, 2015 =

* Fix: questions excluded from site search

= Version 1.0.1 - Released: Jul 14, 2015 =

* Added : user interface improved.

= Version 1.0.0 - Released: Jun 19, 2015 =

* Initial release