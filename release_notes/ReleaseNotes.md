Reason CMS 4.8 Release Notes
============================
### New Feature
- Reason now has a basic focal point/cropping feature.
    - Upgrade script that adds crop_style, focal_point_x, and _y fields
    - New plasmature type for focal point selection with crop samples
    - JS and CSS to make the focal point UI look as intended
    - Crop samples are flexible
    - Samples of any aspect ratio can be specified in the content manager
    - Sized image now pays attention to crop_style and fpx, fpy
    - Any images that are being resized with fill (not fit) will be cropped with regard to the focal point
    - Default behavior if no focal point is set is center crop (which is the original behavior of sized image)
    - New behavior does not take until the upgrade script is run (until the new fields are present)
    - Tested on BrowserStack with Android, iOS (iPhone and iPad), Windows Phone, macOS (Safari, Chrome, Firefox), Windows (Edge, IE 8-current, Chrome, Firefox).
    - NOTE: not responsive in IE 8 because media queries don't work there.
- Timeline - a new page type and module based on <https://timeline.knightlab.com/> and <http://timeline.knightlab.com/docs/json-format.html>
- Mediaworks now support captions and subtitles



### Enhancements
- PHPMailer integrated inside the Email class (used by Tyr)
- Event Slots superseded by a new Form element, configurable in the formbuilder
- Added a video player to Zencoder MediaWork implementations
- Add a Captions/Subtitles type on Media Works
- Add ability to prefill a form element from request parameters
- Page mover script can move all pages of a site at once, descendant pages are moved properly now.
- Accessibility improvements

##### Dependency Updates
- Updated bundled JQuery (to jquery-1.12.4.min.js) and JQuery UI (jquery-ui-1.12.1.min.js)  – [Commit](https://github.com/reasoncms/reasoncms/commit/38b89243879c44a65e768d89aa3fa23036527f37)
- HTMLPurifier updated to 4.8.0 - [Commit](https://github.com/reasoncms/reasoncms/commit/cb9c13ff24e1690ff85a005573c13373001d3039)
- Updated reCAPTCHA to the latest version. This changes the functionality of the reCAPTCHA to use the latest UX for the tool -[Commit](https://github.com/reasoncms/reasoncms/commit/7adcf0c2e905cae03da5fc5921ab6f49dccce39f)
- Vagrant provisioner now installs XDebug into the host - [Commit](https://github.com/reasoncms/reasoncms/commit/74aea56a1fd64e82311d06405be4118c7869e429)
- Updated S3.php to 0.5.1 - [Commit](https://github.com/reasoncms/reasoncms/commit/43d71e63ab36a423bc44e81ca539f7f4987015cd)
- Updated scssphp to 0.6.7 - [Commit](https://github.com/reasoncms/reasoncms/commit/21689ed27ec1b17164374cadf7f722e2c3d0bbb6)
- Updated SimplePie to 1.4.3 - [Commit](https://github.com/reasoncms/reasoncms/commit/1f45b21fb3cee33ea3b37b2c389d4f87de4b97ce)
- Updated Snoopy in MagpieRSS to 2.0.0 - [Commit](https://github.com/reasoncms/reasoncms/commit/9fa0b060d958c4d542cbe41885e1ab8f457b9cff)

### New Settings
 1) REASON_DIVERT_EMAIL_TO (reason_settings)   
    - A setting to divert email messages to a another address
    - Defaults to off
    - Diverted messages contain `[DIVERTED]` in subject line
    - Applies globally for any messages sent with Tyr or Email classes
 
 2) REASON_EVENT_TICKETS_DEFAULT_CLOSE_MODIFIER (reason_settings)   
    - A `strtotime` string that modifies the default closing time of form event ticket registration
    - Defaults to empty string
    - Carleton uses `-60min` to close ticket requests an hour before the event begins
 
 3) XSENDFILE_HEADER (package_settings)   
    - A HTTP header PHP emits to instruct the webserver to deliver a file to the client at the end of the request
    - Defaults to off/none
    - Useful for large files and files behind authentication
    - Used in `thor/getFormFile.php`
    - Server modules required. More Info:
      - Apache: https://tn123.org/mod_xsendfile/
      - Nginx: https://www.nginx.com/resources/wiki/start/topics/examples/x-accel/
      
 4) REASON_DEFAULT_CONTENT_LANGUAGE (reason_settings)
    - Accessibility improvement
    - This is used to set the proper `lang` attribute on the content elements presented on pages Reason emits. 
    - the default is set to `define('REASON_DEFAULT_CONTENT_LANGUAGE', 'en-US');`
   
 5) REASON_DEFAULT_INTERFACE_LANGUAGE (reason_settings)
    - Accessibility improvement
    - This is used to set the proper `lang` attribute on the `html` element on pages Reason emits. 
    - the default is set to `define('REASON_DEFAULT_INTERFACE_LANGUAGE', 'en-US');`
