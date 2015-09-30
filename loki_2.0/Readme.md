Loki 2.0
========

### A WYSIWYG browser-based semantic HTML editor. ###

Copyright © 2006 Carleton College.

Synopsis
--------

    <script src="/path/to/loki/loki.js" type="text/javascript"></script>
    <script type="text/javascript">
        // Change all textareas that have the "loki" class to Loki rich-text
        // editors with power-user features:
        Loki.convert_textareas_by_class("loki", {options: "power"});
    </script>

About
-----

Loki is a visual (WYSIWYG) HTML editor. Many such editors exist, but Loki is
different: it encourages authors to produce semantic HTML. Here's how:

* ### HTML is cleaned automatically. ###
  
  Loki enforces many of HTML's rules on where inline content can go. Inline
  content that is inserted directly under the `body` or in a `div` are wrapped
  in paragraphs. List items and table cells that have content separated by
  multiple line breaks have that content split into paragraphs.
  
  Loki also performs many other cleanups: it greatly reduces the cruft of HTML
  produced by Microsoft Office products, it formats and indents the HTML it
  produces, it prevents elements from being nested improperly, and it can
  restrict which tags and inline CSS styles are permitted.

* ### Hitting return produces a new paragraph. ###
  
  When typing normal text in Loki, hitting the `Return` key will create a new
  paragraph instead of a line break. Users must work harder to insert an actual
  `br` tag.
  
* ### Images must have alternate text. ###
  
  Loki ensures that your pages remain accessible to visually-impaired visitors,
  search engines, and mobile users by requiring that images have alternate text.
  
* ### Block quotations are clearly quotations. ###
  
  Other editors encourage abuse of the `blockquote` tag by allowing users to
  create indentation with it, but Loki's block-quotation tool is clearly
  labelled with its intended purpose. Loki's indent/outdent tools only function
  within lists.
  
* ### Semantic, accessible use of tables is encouraged. ###
  
  Table summaries and headers are included whenever a table is created.
  Presentational attributes are not included in the markup, and creation of
  tables with two or more rows and columns is encouraged.
  
* ### No frivolous features. ###
  
  Loki is largely free of presentational features: users cannot arbitrarily
  change fonts or colors, and they certainly can't do far more horrible things
  like insert smilies. If you want such features out of the box, Loki is
  probably not for you.

Compatibility
-------------

Loki is compatible with Internet Explorer ≥ 6 and Mozilla Firefox ≥ 2 (et. al.).

Getting Help
------------

There is a [Loki installers Google Group][group] where you can post any
questions and be answered by either a developer or another user. Release
announcements are also posted to this list.

Installation
------------

_If you have downloaded a source-code release of Loki, please read the
"building" section below first._

Loki's installation documents are available [online at Google Code][install].

Building
--------

While Loki is distributed with all of its scripts pressed into one JavaScript
file, its actual development is spread across many other files in the `js/`
directory.

Loki uses a custom [Python][python]-based build system to produce the single
`loki.js` file. To build a Loki package, run `tools/build -v [version]`, where
`[version]` is whatever you want the Loki version to be. To build just the
script file, add the `--js-only` flag. Run the build script with the `--help`
option for more information.

License
-------

Loki is distributed under the terms of the GNU Lesser General Public License
(GNU LGPL) version 2.1. 

See License.txt for the full text of the license under which Loki is
distributed.

[gc]: http://loki-editor.googlecode.com/
[group]: http://groups.google.com/group/loki-installers
[install]: http://code.google.com/p/loki-editor/wiki/Installation
[python]: http://www.python.org/
