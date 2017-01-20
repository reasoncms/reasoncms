#Reason Package Code Layout

Reason's code layout is somewhat idiosyncratic. This document explains the general layout of code in the reason_package.

##Root Level of reason_package

The top level of the package is a rough collection of various libraries and tools – vendor and otherwise - along with the Reason CMS directory itself, the settings directory, and a smattering of automation bootstrap files.

###Key files and directories:

* reason_4.0: The base of Reason CMS codebase proper (as distinct from its dependencies, settings, etc.)
* settings: All global settings for Reason. See [Configuration](../configuration/index.md).
* www: Libraries that need to be web-available (eg. javascript, etc.)
* provisioning: Files used to bootstrap a new development area using Ansible
* release_notes: "What's new/changed" notes on each release of Reason

* README.md
* Vagrantfile: a file that 
* install.sh: A Reason installation shell script

There are a handful of libraries that Reason depends on that are maintained as part of the Reason package (that is, they are not from a separate repository). These are:

* carl_util: Utility functions and classes like db connectivity, error handling, directory integration, etc.
* disco: Library for programmatically creating forms
* thor: User-created forms tool
* tyr: Library for sending email, integrated with directory services

There are also a number of vended external dependency libraries at the root. Examples:

* adodb
* akismet
* date_picker
* google-api-php-client
Etc.