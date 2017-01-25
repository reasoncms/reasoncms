# Reason Package Code Layout

The Reason package code layout is somewhat idiosyncratic. This document explains the general layout of code in the package.

The root level of the package is a rough collection of various libraries and tools – vendor and otherwise - along with the Reason CMS directory itself, the settings directory, and a smattering of automation bootstrap files.

## Key Root-Level Files and Directories

* reason_4.0: The base of Reason CMS codebase proper (as distinct from its dependencies, settings, etc.) See [Reason CMS Code Layout](code_layout_reason.md) for documentation of the layout of code inside this directory.
* settings: All global settings for Reason. See [Configuration](../configuration/index.md).
* www: Libraries that need to be web-available (eg. javascript, etc.)
* provisioning: Files used to bootstrap a new development area using Ansible
* release_notes: "What's new/changed" notes on each release of Reason
* README.md
* Vagrantfile: Configuration for Vagrant-based virtual machines
* install.sh: A Reason installation shell script

## Reason project-maintained "external libraries"

There are a handful of libraries that Reason depends on that are maintained as part of the Reason package (that is, they are not maintained in a separate repository) but have been treated as equivalent to external dependencies in terms of the dependency graph. These are:

* carl_util: Utility functions and classes like db connectivity, error handling, directory integration, etc.
* disco: Library for programmatically creating forms
* thor: User-created forms tool
* tyr: Library for sending email, integrated with directory services

## True external dependencies

There are also a number of vended external dependency libraries at the root. Examples:

* adodb
* akismet
* date_picker
* google-api-php-client
* Etc.

Reason is in the process of moving to using [Composer](https://getcomposer.org/) to manage its external dependencies. Reason 4.8+ contains a `vendor` directory for composer-managed libraries. This third set of libraries will largely migrate into `/vendor` and the composer configuration in 4.8 & 4.9.

## Web-available external dependencies

Since Reason is installed outside the web root by default, web-accessible resources need to be placed in a directory that is explicitly web-accessible. For external dependencies this is `www/`.

# Reason CMS Code Layout

See [Reason CMS Code Layout](code_layout_reason.md) for details on how Reason CMS code proper (that is, the contents of `reason_4.0`) is organized.

