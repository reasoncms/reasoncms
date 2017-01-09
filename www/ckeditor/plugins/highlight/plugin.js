( function() {
    const highlightDivClassName = 'callOut';

    function noBlockLeft( bqBlock ) {
        for ( var i = 0, length = bqBlock.getChildCount(), child; i < length && ( child = bqBlock.getChild( i ) ); i++ ) {
            if ( child.type == CKEDITOR.NODE_ELEMENT && child.isBlockBoundary() )
                return false;
        }
        return true;
    }

    function isBlockHighlighted( block ) {
        return ( block.getName() == 'div' ) && ( block.hasClass( highlightDivClassName ) );
    }

    var commandObject = {
        exec: function( editor ) {
            var state = editor.getCommand( 'highlight' ).state,
                selection = editor.getSelection(),
                range = selection && selection.getRanges()[ 0 ];

            if ( !range )
                return;

            var bookmarks = selection.createBookmarks();

            var iterator = range.createIterator(),
                block;
            iterator.enlargeBr = editor.config.enterMode != CKEDITOR.ENTER_BR;

            if ( state == CKEDITOR.TRISTATE_OFF ) {
                var paragraphs = [];
                while ( ( block = iterator.getNextParagraph() ) )
                    paragraphs.push( block );

                // If no paragraphs, create one from the current selection position.
                if ( paragraphs.length < 1 ) {
                    var para = editor.document.createElement( editor.config.enterMode == CKEDITOR.ENTER_P ? 'p' : 'div' ),
                        firstBookmark = bookmarks.shift();
                    range.insertNode( para );
                    para.append( new CKEDITOR.dom.text( '\ufeff', editor.document ) );
                    range.moveToBookmark( firstBookmark );
                    range.selectNodeContents( para );
                    range.collapse( true );
                    firstBookmark = range.createBookmark();
                    paragraphs.push( para );
                    bookmarks.unshift( firstBookmark );
                }

                // Make sure all paragraphs have the same parent.
                var commonParent = paragraphs[ 0 ].getParent(),
                    tmp = [];
                for ( var i = 0; i < paragraphs.length; i++ ) {
                    block = paragraphs[ i ];
                    commonParent = commonParent.getCommonAncestor( block.getParent() );
                }

                // The common parent must not be the following tags: table, tbody, tr, ol, ul.
                var denyTags = { table: 1, tbody: 1, tr: 1, ol: 1, ul: 1 };
                while ( denyTags[ commonParent.getName() ] )
                    commonParent = commonParent.getParent();

                // Reconstruct the block list to be processed such that all resulting blocks
                // satisfy parentNode.equals( commonParent ).
                var lastBlock = null;
                while ( paragraphs.length > 0 ) {
                    block = paragraphs.shift();
                    while ( !block.getParent().equals( commonParent ) )
                        block = block.getParent();
                    if ( !block.equals( lastBlock ) )
                        tmp.push( block );
                    lastBlock = block;
                }

                // If any of the selected blocks is a div with the highlight class (highlightDivClassName)
                // remove it it to prevent nested highlights.
                while ( tmp.length > 0 ) {
                    block = tmp.shift();
                    if ( isBlockHighlighted(block) ) {
                        var docFrag = new CKEDITOR.dom.documentFragment( editor.document );
                        while ( block.getFirst() ) {
                            docFrag.append( block.getFirst().remove() );
                            paragraphs.push( docFrag.getLast() );
                        }

                        docFrag.replace( block );
                    } else {
                        paragraphs.push( block );
                    }
                }

                // Now we have all the blocks to be included in a new blockquote node.
                var highlightBlock = editor.document.createElement( 'div' );
                highlightBlock.addClass( highlightDivClassName );
                highlightBlock.insertBefore( paragraphs[ 0 ] );
                while ( paragraphs.length > 0 ) {
                    block = paragraphs.shift();
                    highlightBlock.append( block );
                }
            } else if ( state == CKEDITOR.TRISTATE_ON ) {
                var moveOutNodes = [],
                    database = {};

                while ( ( block = iterator.getNextParagraph() ) ) {
                    var bqParent = null,
                        bqChild = null;
                    var parent;
                    while ( parent = block.getParent() ) {
                        if ( isBlockHighlighted(parent) ) {
                            bqParent = parent;
                            bqChild = block;
                            break;
                        }
                        block = parent;
                    }

                    // Remember the blocks that were recorded down in the moveOutNodes array
                    // to prevent duplicates.
                    if ( bqParent && bqChild && !bqChild.getCustomData( 'blockquote_moveout' ) ) {
                        moveOutNodes.push( bqChild );
                        CKEDITOR.dom.element.setMarker( database, bqChild, 'blockquote_moveout', true );
                    }
                }

                CKEDITOR.dom.element.clearAllMarkers( database );

                var movedNodes = [],
                    processedBlockquoteBlocks = [];

                database = {};
                while ( moveOutNodes.length > 0 ) {
                    var node = moveOutNodes.shift();
                    highlightBlock = node.getParent();

                    // If the node is located at the beginning or the end, just take it out
                    // without splitting. Otherwise, split the blockquote node and move the
                    // paragraph in between the two blockquote nodes.
                    if ( !node.getPrevious() )
                        node.remove().insertBefore( highlightBlock );
                    else if ( !node.getNext() )
                        node.remove().insertAfter( highlightBlock );
                    else {
                        node.breakParent( node.getParent() );
                        processedBlockquoteBlocks.push( node.getNext() );
                    }

                    // Remember the blockquote node so we can clear it later (if it becomes empty).
                    if ( !highlightBlock.getCustomData( 'blockquote_processed' ) ) {
                        processedBlockquoteBlocks.push( highlightBlock );
                        CKEDITOR.dom.element.setMarker( database, highlightBlock, 'blockquote_processed', true );
                    }

                    movedNodes.push( node );
                }

                CKEDITOR.dom.element.clearAllMarkers( database );

                // Clear blockquote nodes that have become empty.
                for ( i = processedBlockquoteBlocks.length - 1; i >= 0; i-- ) {
                    highlightBlock = processedBlockquoteBlocks[ i ];
                    if ( noBlockLeft( highlightBlock ) )
                        highlightBlock.remove();
                }

                if ( editor.config.enterMode == CKEDITOR.ENTER_BR ) {
                    var firstTime = true;
                    while ( movedNodes.length ) {
                        node = movedNodes.shift();

                        if ( node.getName() == 'div' ) {
                            docFrag = new CKEDITOR.dom.documentFragment( editor.document );
                            var needBeginBr = firstTime && node.getPrevious() && !( node.getPrevious().type == CKEDITOR.NODE_ELEMENT && node.getPrevious().isBlockBoundary() );
                            if ( needBeginBr )
                                docFrag.append( editor.document.createElement( 'br' ) );

                            var needEndBr = node.getNext() && !( node.getNext().type == CKEDITOR.NODE_ELEMENT && node.getNext().isBlockBoundary() );
                            while ( node.getFirst() )
                                node.getFirst().remove().appendTo( docFrag );

                            if ( needEndBr )
                                docFrag.append( editor.document.createElement( 'br' ) );

                            docFrag.replace( node );
                            firstTime = false;
                        }
                    }
                }
            }

            selection.selectBookmarks( bookmarks );
            editor.focus();
        },

        refresh: function( editor, path ) {
            // Check if inside of highlight div.
            var firstBlock = path.block || path.blockLimit;
            var elementPath = editor.elementPath( firstBlock );
            console.log("In highlight refresh: " + elementPath);
            var enclosingDiv = elementPath.contains( 'div', 1 );

            if ( enclosingDiv && enclosingDiv.hasClass( highlightDivClassName ) ) {
                console.log("In highlight refresh, we are in a '" + highlightDivClassName +"' DIV");
                this.setState( CKEDITOR.TRISTATE_ON );
            } else {
                this.setState( CKEDITOR.TRISTATE_OFF );
            }
        },
        context: 'div',
        allowedContent: 'div(' + highlightDivClassName + ')',

    };

    CKEDITOR.plugins.add( 'highlight', {
        icons: 'highlight',
        init: function( editor ) {
            if ( editor.blockless )
                return;

            var pluginDirectory = this.path;
            editor.addContentsCss( pluginDirectory + 'styles/highlight.css' );

            editor.addCommand( 'highlight', commandObject );

            editor.ui.addButton && editor.ui.addButton( 'Highlight', {
                label: "Highlight",
                command: 'highlight',
                toolbar: 'insert'
            } );
        }
    } );
} )();
