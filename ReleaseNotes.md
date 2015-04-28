Reason CMS 4.6 Release Notes
============================
### New Features
- [#121](https://github.com/carleton/reason_package/pull/121) – Fully responsive default theme
- [#109](https://github.com/carleton/reason_package/pull/109) – Deleted site/page redirection UI
- [#109](https://github.com/carleton/reason_package/pull/109) – New administrative module for copying some or all of a user's sites to another user : [Commit](https://github.com/DookTibsCarl/reason_package/commit/2a0d6d790e57e16b2200a5448d54c70a29c3b5d8)
- [#119](https://github.com/carleton/reason_package/pull/119) – Content Reminder script/class - Draws attention to pages that are unchanged for 6 months
- [#121](https://github.com/carleton/reason_package/pull/121) – SASS support


### Enhancements
- [#102](https://github.com/carleton/reason_package/pull/102) – LinkedIn social integration
- [Commit](https://github.com/carleton/reason_package/commit/370b7606676acb6ce8edb311eca1e81c39c30100) – Comprehesive maintenance mode
- [#109](https://github.com/carleton/reason_package/pull/109) – Improved form builder UI (many commits)
- [#84](https://github.com/carleton/reason_package/pull/84) – Image uploader checks for a minimum size
- [#109](https://github.com/carleton/reason_package/pull/109) – Cache locking for improved performance : [Commit](https://github.com/DookTibsCarl/reason_package/commit/f6d833e8b63daaad8d90ff04560a614230d3bc66) : [Commit](https://github.com/DookTibsCarl/reason_package/commit/23738abb3e5971de02bad6817c064622c49e4472) : [Commit](https://github.com/slylth/reason_package/commit/ec7dbe504af57d787c1b19c10d2e971d517a181e)

##### SEO
- [#76](https://github.com/carleton/reason_package/pull/76) – Since meta keywords in <head> are no longer beneficial for SEO, we've added a new keyword, ```REASON_SHOW_META_KEYWORDS```. It defaults to false.
- [#79](https://github.com/carleton/reason_package/pull/79) – rel attributes for blog post prev/next links
- [#81](https://github.com/carleton/reason_package/pull/81) – Better page title logic

##### TinyMCE integration
- [#80](https://github.com/carleton/reason_package/pull/80) & [#107](https://github.com/carleton/reason_package/pull/107) – Asset chooser in TinyMCE editor
- [Commit](https://github.com/carleton/reason_package/commit/081642516721c6f0b262e810bf714cbca51ffca0) – Adds a preview of the TinyMCE editor when creating/modifying a site

### New Settings
in settings/reason_settings.php
- [#76](https://github.com/carleton/reason_package/pull/76) – ```REASON_SHOW_META_KEYWORDS```
- [#81](https://github.com/carleton/reason_package/pull/81) – Page title settings
    - ```REASON_HOME_TITLE_PATTERN```
    - ```REASON_SECONDARY_TITLE_PATTERN```
    - ```REASON_ITEM_TITLE_PATTERN```
- [#109](https://github.com/carleton/reason_package/pull/109) – PDF open action : [Commit](https://github.com/DookTibsCarl/reason_package/commit/4911a5da01fc6576f564336ea7de963fbfbfe06a)
    - ```REASON_PDF_DOWNLOAD_DISPOSITION_DEFAULT```
- [#84](https://github.com/carleton/reason_package/pull/84) – Minimum image size settings
    - ```REASON_STANDARD_MIN_IMAGE_HEIGHT```
    - ```REASON_STANDARD_MIN_IMAGE_WIDTH```
-  [#109](https://github.com/carleton/reason_package/pull/109) – Forms apply akismet spam filtering by default : [Commit](https://github.com/DookTibsCarl/reason_package/commit/c730f2c166450f3d0c0a2e66b050be6464f97937)
    - ```REASON_FORMS_THOR_DEFAULT_AKISMET_FILTER```

### Bugs Squashed (notables)
- [#86](https://github.com/carleton/reason_package/pull/86) – php preg_replace e/ modifer is deprecated now using preg_replace_callback
- [#92](https://github.com/carleton/reason_package/pull/92) – Vagrant and Ansible provisioning fix
- Other bugs
    - [Commit](https://github.com/carleton/reason_package/commit/b3065fc5e55d1438a36048ff15b500b74e39914d)
    - [Commit](https://github.com/DookTibsCarl/reason_package/commit/37e567f4caaab0b841dde35a660b9e1a1caac5ed)
    - [Commit](https://github.com/DookTibsCarl/reason_package/commit/6c4a2f21eff62f0de7781cbe8a8b80c531c2e5b6)

### Other Updates Of Note
- [#94](https://github.com/carleton/reason_package/pull/94) – TinyMCE updated → 4.1.7
- [#110](https://github.com/carleton/reason_package/pull/110) – Magpierss updated → 0.72
- [#113](https://github.com/carleton/reason_package/pull/113) – jQuery updated → 1.10.2
- [#113](https://github.com/carleton/reason_package/pull/113) – jQueryUI updated → 1.10.3
- [#109](https://github.com/carleton/reason_package/pull/109) – Switch from ```addslashes()``` to ```reason_sql_string_escape()``` for SQL statements