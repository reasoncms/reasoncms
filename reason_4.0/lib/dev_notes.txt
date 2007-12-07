------------------------------------------------------------------
Reason: Notes on the lib directory
------------------------------------------------------------------

This directory contains Reason's code libraries.

It is split into two parallel sections: "core" and "local." The reason we have made this distinction is to make it easy as possible to customize and/or add functionality to Reason. The core directory contains all of the "official" reason code in a given release.  The local directory provides a simple method to customize Reason -- both to override the core functionality and to add new functionality -- while simplifying the upgrade process.

The way the core/local split works is pretty simple. Rather than using php's built-in include and require functions, the Reason codebase uses custom functions that first check to see if the file requested exists in the local area. If so, that is the file included.  If not, the custom include function tries to include the file from the core area.

Therefore, if you have a custom modification to a module, you can copy that module into the same location in your local area, make your modifications, and your new file will be used instead of the core file. If you create a new content manager, simply place it in the appropriate location in the local directory, and it will show up in the Reason administrative interface alongside core content managers. If you want an expanded set of page types, simply copy the page_types file into your local area and modify away.

We recommend using this method to make customizations, rather than directly modifying the code in the core area, for several reasons:
1. You keep a clear sense of what you have modified and what you have not
2. Upgrading and patching the system will occur in the core area, so you don't have to worry about your changes being lost
3. You can very easily revert to the original install by removing all files from the local directory
4. If will help you see all the great code you've produced and could contribute back the the Reason project! (hint, hint. :-)

Thanks, and happy developing.
